// Dr. Feelgood service worker — network-first GET caching + offline sync outbox.
importScripts('/assets/js/offline/idb-core.js');

const CACHE_NAME = 'drfeelgood-v3';
const SYNC_TAG = 'sync-outbox';
const MAX_ATTEMPTS = 8;

self.addEventListener('install', () => {
    self.skipWaiting();
});

self.addEventListener('activate', (event) => {
    // Drop old caches when the version changes
    event.waitUntil(
        caches.keys().then(keys =>
            Promise.all(keys.filter(k => k !== CACHE_NAME).map(k => caches.delete(k)))
        ).then(() => self.clients.claim())
    );
});

self.addEventListener('fetch', (event) => {
    const req = event.request;

    // Only handle same-origin GET requests. POSTs (API calls) go to the network
    // untouched — offline mutations are queued client-side, not intercepted here.
    if (req.method !== 'GET' || new URL(req.url).origin !== self.location.origin) {
        return;
    }

    event.respondWith(
        fetch(req)
            // Network first so pages/data stay fresh; cache is only a fallback.
            .catch(() => caches.match(req).then(cached => {
                if (cached) return cached;
                // Nothing cached and the network failed (e.g. offline) — return a
                // clean error response instead of letting the promise reject.
                return new Response('', { status: 504, statusText: 'Offline' });
            }))
    );
});

// --- Background Sync (Chrome / Android PWA / desktop Chromium) ---
self.addEventListener('sync', (event) => {
    if (event.tag === SYNC_TAG) {
        event.waitUntil(flushOutbox());
    }
});

// --- Fallback trigger from the page (Safari / iOS have no Background Sync) ---
self.addEventListener('message', (event) => {
    if (event.data && event.data.type === 'FLUSH_OUTBOX') {
        event.waitUntil(flushOutbox());
    }
});

async function flushOutbox() {
    const pending = await OfflineDB.getPending();
    let syncedCount = 0;

    for (const record of pending) {
        try {
            const res = await fetch(record.endpoint, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                credentials: 'include',            // send the session cookie
                redirect: 'manual',                // don't follow a login redirect as "success"
                body: JSON.stringify({
                    client_uuid: record.uuid,      // idempotency key
                    entity: record.entity,
                    data: record.payload,
                }),
            });

            // Permanent client error (except 409) → stop retrying, flag for review.
            if (res.status >= 400 && res.status !== 409 && res.status < 500) {
                record.status = 'failed';
                record.lastError = 'HTTP ' + res.status;
                await OfflineDB.updateRecord(record);
                continue;
            }

            const json = await res.json().catch(() => ({}));

            // 409 = server already stored this UUID → treat as success (dedup).
            if (res.ok || res.status === 409 || json.success) {
                record.status = 'synced';
                record.serverId = json.server_id || json.report_id || json.patient_id || null;
                await OfflineDB.updateRecord(record);
                await OfflineDB.deleteRecord(record.uuid);
                syncedCount++;
            } else {
                throw new Error(json.message || 'Sync rejected');
            }
        } catch (err) {
            // Network / 5xx → transient. Keep pending and retry later.
            record.attempts += 1;
            record.lastError = String(err && err.message ? err.message : err);
            record.status = record.attempts >= MAX_ATTEMPTS ? 'failed' : 'pending';
            await OfflineDB.updateRecord(record);
            // Re-throw so Background Sync reschedules with exponential backoff.
            if (record.status === 'pending') throw err;
        }
    }

    // Tell open pages so they can refresh badges / flip "pending" markers.
    const clients = await self.clients.matchAll();
    clients.forEach(c => c.postMessage({ type: 'SYNC_COMPLETE', synced: syncedCount }));
    return syncedCount;
}
