// assets/js/offline/offline-client.js
// Page-side offline API. Depends on idb-core.js being loaded first.
// Exposes window.Offline with saveOffline(), updatePending(), requestSync(),
// pendingCount(), and a small showToast() helper.
(function (window) {
  'use strict';

  const SYNC_TAG = 'sync-outbox';

  function postJson(endpoint, uuid, entity, payload) {
    return fetch(endpoint, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      credentials: 'same-origin',       // carry the PHP session cookie
      redirect: 'manual',               // a login redirect must not read as success
      body: JSON.stringify({ client_uuid: uuid, entity: entity, data: payload }),
    });
  }

  // Interpret a sync response. Returns one of:
  //   { synced, result }           — server accepted (or already had) the record
  //   { validation, error }        — permanent 4xx, caller should surface + drop
  //   throws                       — transient (offline/5xx), caller should queue
  async function interpret(res) {
    const json = await res.json().catch(() => ({}));
    if (res.ok && json.success !== false) return { synced: true, result: json };
    if (res.status === 409) return { synced: true, result: json };            // dedup hit
    if (res.status >= 400 && res.status < 500) {
      return { validation: true, error: json.message || 'Validation error' };
    }
    throw new Error(json.message || 'HTTP ' + res.status);
  }

  // Save now → try the network → fall back to the outbox queue.
  // Returns { synced, uuid, serverId?, result?, queued?, error? }.
  function csrfToken() {
    const m = document.querySelector('meta[name="csrf-token"]');
    return m ? m.getAttribute('content') : '';
  }

  async function saveOffline(entity, endpoint, payload) {
    const record = await OfflineDB.addRecord(entity, endpoint, payload, csrfToken());
    emitChanged();

    if (!navigator.onLine) {
      await requestSync();
      return { synced: false, uuid: record.uuid, queued: true };
    }

    try {
      const outcome = await interpret(await postJson(endpoint, record.uuid, entity, payload));
      if (outcome.synced) {
        record.status = 'synced';
        record.serverId = serverIdOf(outcome.result);
        await OfflineDB.updateRecord(record);
        await OfflineDB.deleteRecord(record.uuid);
        emitChanged();
        return { synced: true, uuid: record.uuid, serverId: record.serverId, result: outcome.result };
      }
      // Permanent validation failure — don't keep retrying.
      await OfflineDB.deleteRecord(record.uuid);
      emitChanged();
      return { synced: false, uuid: record.uuid, error: outcome.error };
    } catch (err) {
      // Network flaked → leave it queued as pending and schedule a sync.
      await requestSync();
      return { synced: false, uuid: record.uuid, queued: true };
    }
  }

  // Replace the payload of an already-queued (still pending) record instead of
  // creating a new one — used when the user edits a save that hasn't synced yet.
  // Falls back to a fresh saveOffline() if the record is gone or already synced.
  async function updatePending(uuid, entity, endpoint, payload) {
    const record = await OfflineDB.getRecord(uuid);
    if (!record || record.status === 'synced') {
      return saveOffline(entity, endpoint, payload);
    }
    record.payload = payload;
    record.csrf = csrfToken();   // refresh token in case the session rotated
    record.status = 'pending';
    record.attempts = 0;
    await OfflineDB.updateRecord(record);
    emitChanged();

    if (!navigator.onLine) {
      await requestSync();
      return { synced: false, uuid: uuid, queued: true };
    }
    try {
      const outcome = await interpret(await postJson(endpoint, uuid, entity, payload));
      if (outcome.synced) {
        record.status = 'synced';
        record.serverId = serverIdOf(outcome.result);
        await OfflineDB.updateRecord(record);
        await OfflineDB.deleteRecord(uuid);
        emitChanged();
        return { synced: true, uuid: uuid, serverId: record.serverId, result: outcome.result };
      }
      await OfflineDB.deleteRecord(uuid);
      emitChanged();
      return { synced: false, uuid: uuid, error: outcome.error };
    } catch (err) {
      await requestSync();
      return { synced: false, uuid: uuid, queued: true };
    }
  }

  // Notify the UI (badge / review screen) that the queue changed.
  function emitChanged() {
    window.dispatchEvent(new CustomEvent('outbox:changed'));
  }

  // All queued records (any status), newest first — powers the review screen.
  function getQueue() {
    return OfflineDB.getAll().then((list) =>
      list.sort((a, b) => (b.createdAt || 0) - (a.createdAt || 0))
    );
  }

  // Re-send a single record now (used by the "Retry" button).
  async function retry(uuid) {
    const record = await OfflineDB.getRecord(uuid);
    if (!record) return { removed: true };
    record.status = 'pending';
    record.attempts = 0;
    record.lastError = null;
    await OfflineDB.updateRecord(record);
    emitChanged();

    if (!navigator.onLine) { await requestSync(); return { queued: true }; }
    try {
      const outcome = await interpret(await postJson(record.endpoint, uuid, record.entity, record.payload));
      if (outcome.synced) {
        await OfflineDB.deleteRecord(uuid);
        emitChanged();
        return { synced: true, result: outcome.result };
      }
      record.status = 'failed';
      record.lastError = outcome.error;
      await OfflineDB.updateRecord(record);
      emitChanged();
      return { synced: false, error: outcome.error };
    } catch (err) {
      await requestSync();
      return { queued: true };
    }
  }

  // Discard a record the user chooses not to keep (e.g. a permanent failure).
  async function remove(uuid) {
    await OfflineDB.deleteRecord(uuid);
    emitChanged();
  }

  function serverIdOf(result) {
    if (!result) return null;
    return result.server_id || result.report_id || result.patient_id || null;
  }

  // Register a Background Sync, or fall back to messaging the SW directly
  // (Safari / iOS have no SyncManager).
  async function requestSync() {
    if (!('serviceWorker' in navigator)) return;
    const reg = await navigator.serviceWorker.ready;
    if ('sync' in reg) {
      try { await reg.sync.register(SYNC_TAG); return; } catch (_) { /* fall through */ }
    }
    if (reg.active) reg.active.postMessage({ type: 'FLUSH_OUTBOX' });
  }

  function pendingCount() {
    return OfflineDB.getPending().then((list) => list.length);
  }

  // Minimal, dependency-free toast (Bootstrap-styled if available).
  function showToast(message, variant) {
    variant = variant || 'info';
    let host = document.getElementById('offlineToastHost');
    if (!host) {
      host = document.createElement('div');
      host.id = 'offlineToastHost';
      host.style.cssText = 'position:fixed;z-index:1080;bottom:16px;left:50%;transform:translateX(-50%);max-width:92vw;';
      document.body.appendChild(host);
    }
    const el = document.createElement('div');
    const bg = { info: '#0d6efd', success: '#198754', warning: '#fd7e14', danger: '#dc3545' }[variant] || '#0d6efd';
    el.style.cssText = 'background:' + bg + ';color:#fff;padding:12px 16px;border-radius:8px;margin-top:8px;'
      + 'box-shadow:0 4px 14px rgba(0,0,0,.25);font-size:.9rem;line-height:1.3;';
    el.textContent = message;
    host.appendChild(el);
    setTimeout(() => { el.style.transition = 'opacity .4s'; el.style.opacity = '0';
      setTimeout(() => el.remove(), 400); }, 4200);
  }

  // Flush whenever connectivity returns or a page loads with a backlog.
  window.addEventListener('online', () => {
    requestSync();
    showToast('Back online — syncing saved data…', 'success');
  });
  window.addEventListener('load', () => {
    pendingCount().then((n) => { if (n > 0) requestSync(); });
  });

  // Re-broadcast the SW's "sync complete" as a DOM event pages can listen for.
  if ('serviceWorker' in navigator) {
    navigator.serviceWorker.addEventListener('message', (e) => {
      if (e.data && e.data.type === 'SYNC_COMPLETE') {
        window.dispatchEvent(new CustomEvent('outbox:synced', { detail: e.data }));
        emitChanged();
      }
    });
  }

  window.Offline = {
    saveOffline: saveOffline,
    updatePending: updatePending,
    requestSync: requestSync,
    pendingCount: pendingCount,
    getQueue: getQueue,
    retry: retry,
    remove: remove,
    showToast: showToast,
  };
})(window);
