// assets/js/offline/idb-core.js
// Shared IndexedDB "outbox" layer. Written as a plain global (no ES modules)
// so the SAME file runs both in the page and inside the service worker
// (via importScripts). Do not add DOM/window references here.
(function (scope) {
  'use strict';

  const DB_NAME = 'drfeelgood-offline';
  const DB_VERSION = 1;
  const STORE = 'outbox';

  let _dbPromise = null;

  function openDB() {
    if (_dbPromise) return _dbPromise;
    _dbPromise = new Promise((resolve, reject) => {
      const req = indexedDB.open(DB_NAME, DB_VERSION);
      req.onupgradeneeded = (e) => {
        const db = e.target.result;
        if (!db.objectStoreNames.contains(STORE)) {
          // keyPath = uuid  → natural idempotency, one row per submission.
          const os = db.createObjectStore(STORE, { keyPath: 'uuid' });
          os.createIndex('status', 'status', { unique: false });
          os.createIndex('createdAt', 'createdAt', { unique: false });
        }
      };
      req.onsuccess = () => resolve(req.result);
      req.onerror = () => reject(req.error);
    });
    return _dbPromise;
  }

  function store(mode) {
    return openDB().then((db) => db.transaction(STORE, mode).objectStore(STORE));
  }

  function toPromise(request) {
    return new Promise((resolve, reject) => {
      request.onsuccess = () => resolve(request.result);
      request.onerror = () => reject(request.error);
    });
  }

  function makeUuid() {
    if (scope.crypto && scope.crypto.randomUUID) return scope.crypto.randomUUID();
    return 'xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx'.replace(/[xy]/g, (c) => {
      const r = (Math.random() * 16) | 0;
      return (c === 'x' ? r : (r & 0x3) | 0x8).toString(16);
    });
  }

  // ---- Public API ----

  // Queue a new outbound mutation. entity = 'report' | 'patient' | ...
  function addRecord(entity, endpoint, payload, csrf) {
    const record = {
      uuid: makeUuid(),
      entity: entity,
      endpoint: endpoint,
      method: 'POST',
      payload: payload,        // plain object
      csrf: csrf || '',        // CSRF token, replayed by the service worker
      status: 'pending',       // pending | synced | failed
      attempts: 0,
      lastError: null,
      serverId: null,
      createdAt: Date.now(),
      updatedAt: Date.now(),
    };
    return store('readwrite').then((os) => toPromise(os.add(record))).then(() => record);
  }

  function getRecord(uuid) {
    return store('readonly').then((os) => toPromise(os.get(uuid)));
  }

  function updateRecord(record) {
    record.updatedAt = Date.now();
    return store('readwrite').then((os) => toPromise(os.put(record)));
  }

  function deleteRecord(uuid) {
    return store('readwrite').then((os) => toPromise(os.delete(uuid)));
  }

  function getPending() {
    return store('readonly').then((os) => toPromise(os.index('status').getAll('pending')));
  }

  function getAll() {
    return store('readonly').then((os) => toPromise(os.getAll()));
  }

  scope.OfflineDB = {
    openDB: openDB,
    addRecord: addRecord,
    getRecord: getRecord,
    updateRecord: updateRecord,
    deleteRecord: deleteRecord,
    getPending: getPending,
    getAll: getAll,
    STORE_NAME: STORE,
  };
})(self);
