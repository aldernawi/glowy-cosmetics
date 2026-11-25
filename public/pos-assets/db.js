// Thin IndexedDB layer. Everything the register needs to sell lives here.

const NAME = 'glowy-pos';
const VERSION = 1;
let connection;

const asPromise = (request) => new Promise((resolve, reject) => {
    request.onsuccess = () => resolve(request.result);
    request.onerror = () => reject(request.error);
});

const open = () => {
    connection ??= new Promise((resolve, reject) => {
        const request = indexedDB.open(NAME, VERSION);
        request.onupgradeneeded = () => {
            const database = request.result;
            database.createObjectStore('meta');
            database.createObjectStore('variants', { keyPath: 'id' });
            const sales = database.createObjectStore('sales', { keyPath: 'uuid' });
            sales.createIndex('status', 'status');
        };
        request.onsuccess = () => {
            request.result.onversionchange = () => request.result.close();
            resolve(request.result);
        };
        request.onerror = () => reject(request.error);
    });
    return connection;
};

// Resolves only after the browser confirms the transaction is committed.
const transact = async (stores, mode, work) => {
    const database = await open();
    const transaction = database.transaction(stores, mode, mode === 'readwrite' ? { durability: 'strict' } : undefined);
    const done = new Promise((resolve, reject) => {
        transaction.oncomplete = () => resolve();
        transaction.onerror = () => reject(transaction.error);
        transaction.onabort = () => reject(transaction.error ?? new Error('تعذر الحفظ على هذا الجهاز.'));
    });
    let result;
    try {
        result = await work(...stores.map((name) => transaction.objectStore(name)));
    } catch (error) {
        try { transaction.abort(); } catch { /* already finished */ }
        await done.catch(() => {});
        throw error;
    }
    await done;
    return result;
};

export const getMeta = (key) => transact(['meta'], 'readonly', (meta) => asPromise(meta.get(key)));
export const setMeta = (key, value) => transact(['meta'], 'readwrite', (meta) => asPromise(meta.put(value, key)));
export const allVariants = () => transact(['variants'], 'readonly', (variants) => asPromise(variants.getAll()));
export const allSales = () => transact(['sales'], 'readonly', (sales) => asPromise(sales.getAll()));
export const salesByStatus = (status) => transact(['sales'], 'readonly', (sales) => asPromise(sales.index('status').getAll(status)));

// The receipt number, the sale and the emptied cart are committed together or not at all.
export const commitSale = (buildSale) => transact(['meta', 'sales'], 'readwrite', async (meta, sales) => {
    const device = await asPromise(meta.get('device'));
    const sequence = ((await asPromise(meta.get('sequence'))) ?? 0) + 1;
    const sale = buildSale(device, sequence);
    await asPromise(sales.add(sale));
    await asPromise(meta.put(sequence, 'sequence'));
    await asPromise(meta.delete('cart'));
    return sale;
});

export const markSale = (uuid, changes) => transact(['sales'], 'readwrite', async (sales) => {
    const sale = await asPromise(sales.get(uuid));
    if (sale) await asPromise(sales.put({ ...sale, ...changes }));
});

// The new catalog already includes every sale in `settledUuids`.
export const saveBootstrap = (data, settledUuids, keepSince) => transact(['meta', 'variants', 'sales'], 'readwrite', async (meta, variants, sales) => {
    await asPromise(variants.clear());
    for (const variant of data.variants) variants.put(variant);
    meta.put(data.device, 'device');
    meta.put(data.store, 'store');
    meta.put(new Date().toISOString(), 'catalog_at');
    const sequence = (await asPromise(meta.get('sequence'))) ?? 0;
    meta.put(Math.max(sequence, data.device.last_sequence), 'sequence');
    for (const uuid of settledUuids) {
        const sale = await asPromise(sales.get(uuid));
        if (!sale) continue;
        if (sale.sold_at < keepSince) sales.delete(uuid);
        else sales.put({ ...sale, settled: true });
    }
});
