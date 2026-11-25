import { cartPieces, cartTotal, categoriesOf, findByCode, formatMoney, parseMoney, receiptNumber, searchVariants, summarize, unsettledQuantities } from './core.js';
import { allSales, allVariants, commitSale, getMeta, markSale, salesByStatus, saveBootstrap, setMeta } from './db.js';

const METHODS = { cash: 'نقدًا', card: 'بطاقة', transfer: 'تحويل' };
const NOTES = [5, 10, 20, 50];
const LOW_STOCK = 3;
const SYNC_EVERY = 30_000;
const REFRESH_EVERY = 5 * 60_000;
const KEEP_DAYS = 30;
const BATCH = 25;

const $ = (selector) => document.querySelector(selector);
const el = (tag, props = {}, ...children) => {
    const node = document.createElement(tag);
    for (const [key, value] of Object.entries(props)) {
        if (key === 'onclick' || key === 'onchange') node[key] = value;
        else if (key === 'className') node.className = value;
        else if (value !== false && value !== undefined && value !== null) node.setAttribute(key, value === true ? '' : value);
    }
    node.append(...children.filter((child) => child !== null && child !== undefined && child !== false));
    return node;
};

const state = { device: null, store: {}, variants: [], unsettled: new Map(), cart: [], selected: null, category: null, lastSale: null, sequence: 0, saving: false, connection: 'checking', lastRefresh: 0, pending: [], rejected: [] };

// ---------- feedback ----------
let toastTimer;
const toast = (message, isError = false) => {
    const node = $('#toast');
    node.textContent = message;
    node.classList.toggle('toast--error', isError);
    node.hidden = false;
    clearTimeout(toastTimer);
    toastTimer = setTimeout(() => { node.hidden = true; }, 3000);
};

const renderStatus = () => {
    const labels = { checking: 'جارٍ التحقق…', online: 'متصل', offline: 'دون إنترنت — البيع مستمر', unlinked: 'الجهاز غير مربوط' };
    $('#connection').dataset.state = state.connection;
    $('#connection-text').textContent = labels[state.connection];
    const waiting = state.pending.length;
    $('#pending').hidden = waiting === 0;
    $('#pending').textContent = `${waiting} بانتظار الرفع`;

    const notices = [];
    if (state.connection === 'unlinked' && state.device) notices.push('انتهى ربط هذا الجهاز. البيع مستمر والمبيعات محفوظة هنا، لكنها لن تُرفع حتى يُربط من لوحة الإدارة.');
    const oldest = state.pending.reduce((first, sale) => (sale.sold_at < first ? sale.sold_at : first), new Date().toISOString());
    if (waiting > 0 && Date.now() - Date.parse(oldest) > 24 * 3600_000) notices.push('توجد مبيعات لم تُرفع منذ أكثر من يوم. وصّلي الجهاز بالإنترنت، ولا تمسحي بيانات المتصفح.');
    if (state.rejected.length > 0) notices.push(`رفض الخادم ${state.rejected.length} عملية. افتحي «المبيعات» لمعرفة السبب.`);
    $('#notice').hidden = notices.length === 0;
    $('#notice').textContent = notices.join(' ');
    $('#cashier').textContent = state.device?.cashier ?? '—';
    $('#next-number').textContent = state.device ? receiptNumber(state.device.id, state.sequence + 1) : '—';
};

// ---------- catalog ----------
const available = (variant) => variant.stock - (state.unsettled.get(variant.id) ?? 0);

const stockClass = (stock) => (stock <= 0 ? 'stock stock--out' : stock <= LOW_STOCK ? 'stock stock--low' : 'stock');

const renderCategories = () => {
    const names = categoriesOf(state.variants);
    if (state.category && !names.includes(state.category)) state.category = null;
    $('#categories').hidden = names.length === 0;
    $('#categories').replaceChildren(...[null, ...names].map((name) => el('button', {
        type: 'button',
        'aria-pressed': String(state.category === name),
        onclick: () => { state.category = name; renderCategories(); renderResults(); focusSearch(); },
    }, name ?? 'الكل')));
};

const renderResults = () => {
    const text = $('#search').value;
    // Typing searches everything; the category only narrows browsing.
    const source = text.trim() || !state.category ? state.variants : state.variants.filter((variant) => variant.category === state.category);
    const results = searchVariants(source, text, 120);
    $('#clear-search').hidden = text === '';
    $('#results').replaceChildren(...results.map((variant) => {
        const stock = available(variant);
        let image = null;
        if (variant.image) {
            image = el('img', { src: variant.image, alt: '', loading: 'lazy' });
            image.onerror = () => image.remove();
        }
        return el('button', { type: 'button', className: 'card', onclick: () => { addToCart(variant); focusSearch(); } },
            image,
            el('span', { className: 'card__body' }, el('span', { className: 'card__name' }, variant.name), variant.variant ? el('span', { className: 'card__variant' }, variant.variant) : null),
            el('span', { className: 'card__foot' }, el('strong', {}, variant.price), el('span', { className: stockClass(stock), dir: stock > 999 ? 'ltr' : null, title: `الكمية المسجلة ${stock}` }, stock > 999 ? '999+' : stock > 0 ? String(stock) : 'نفد')));
    }));
    $('#results').hidden = results.length === 0;
    $('#results-empty').hidden = results.length > 0;
};

const focusSearch = () => { if (!document.querySelector('dialog[open]')) $('#search').focus(); };

// ---------- cart ----------
const saveCart = () => setMeta('cart', state.cart).catch(() => toast('تعذر حفظ الفاتورة مؤقتًا على الجهاز.', true));

const addToCart = (variant) => {
    const line = state.cart.find((item) => item.id === variant.id);
    if (line) {
        if (line.quantity >= 999) return;
        line.quantity += 1;
    } else {
        state.cart.push({ id: variant.id, name: variant.name, variant: variant.variant, sku: variant.sku, price: parseMoney(variant.price), quantity: 1 });
    }
    state.selected = variant.id;
    state.lastSale = null;
    saveCart();
    renderCart();
};

const changeQuantity = (id, quantity) => {
    const line = state.cart.find((item) => item.id === id);
    if (!line) return;
    if (quantity < 1) {
        const index = state.cart.indexOf(line);
        state.cart = state.cart.filter((item) => item.id !== id);
        state.selected = (state.cart[index] ?? state.cart[index - 1])?.id ?? null;
    } else {
        line.quantity = Math.min(999, quantity);
        state.selected = id;
    }
    saveCart();
    renderCart();
};

const selectedLine = () => state.cart.find((line) => line.id === state.selected) ?? null;

const moveSelection = (step) => {
    if (state.cart.length === 0) return;
    const index = state.cart.findIndex((line) => line.id === state.selected);
    state.selected = state.cart[Math.min(state.cart.length - 1, Math.max(0, (index < 0 ? state.cart.length : index) + step))].id;
    renderCart();
};

const renderLastSale = () => {
    const sale = state.lastSale;
    $('#last-sale').hidden = !sale;
    if (!sale) return;
    $('#last-sale').replaceChildren(
        el('div', {}, el('span', {}, 'آخر فاتورة'), el('strong', { dir: 'ltr' }, sale.receipt_number)),
        el('div', {}, el('span', {}, 'الإجمالي'), el('strong', {}, `${formatMoney(sale.total)} د.ل`)),
        sale.amount_received !== null ? el('div', { className: 'last-sale__change' }, el('span', {}, 'الباقي للزبونة'), el('strong', {}, `${formatMoney(sale.amount_received - sale.total)} د.ل`)) : el('div', {}, el('span', {}, 'الدفع'), el('strong', {}, METHODS[sale.payment_method])),
        el('button', { type: 'button', className: 'button', onclick: () => void printReceipt(sale) }, 'طباعة'));
};

const renderCart = () => {
    $('#cart-items').replaceChildren(...state.cart.map((line, index) => {
        const variant = state.variants.find((item) => item.id === line.id);
        const short = variant && line.quantity > available(variant);
        const input = el('input', { type: 'number', min: 1, max: 999, value: line.quantity, 'aria-label': `كمية ${line.name}` });
        input.onchange = () => { changeQuantity(line.id, Number.parseInt(input.value, 10) || 0); focusSearch(); };
        const row = el('tr', { className: line.id === state.selected ? 'is-selected' : '', 'aria-selected': String(line.id === state.selected) },
            el('td', { className: 'c-index' }, String(index + 1)),
            el('td', {}, el('div', { className: 'item__name' }, line.name), el('div', { className: 'item__meta' }, line.variant ? `${line.variant} · ` : '', el('span', { dir: 'ltr' }, line.sku)), short ? el('span', { className: 'item__warn' }, `المسجل في النظام ${Math.max(0, available(variant))}`) : null),
            el('td', { className: 'c-num' }, formatMoney(line.price)),
            el('td', { className: 'c-qty' }, el('div', { className: 'qty' }, el('button', { type: 'button', 'aria-label': `زيادة ${line.name}`, onclick: () => { changeQuantity(line.id, line.quantity + 1); focusSearch(); } }, '+'), input, el('button', { type: 'button', 'aria-label': `إنقاص ${line.name}`, onclick: () => { changeQuantity(line.id, line.quantity - 1); focusSearch(); } }, '−'))),
            el('td', { className: 'c-num c-total' }, formatMoney(line.price * line.quantity)),
            el('td', { className: 'c-remove' }, el('button', { type: 'button', className: 'remove', 'aria-label': `حذف ${line.name}`, onclick: () => { changeQuantity(line.id, 0); focusSearch(); } }, '×')));
        row.onclick = (event) => {
            if (event.target.closest('button, input')) return;
            state.selected = line.id;
            renderCart();
            focusSearch();
        };
        return row;
    }));
    const isEmpty = state.cart.length === 0;
    $('#cart-empty').hidden = !isEmpty;
    renderLastSale();
    $('#cart-items .is-selected')?.scrollIntoView({ block: 'nearest' });
    $('#total').textContent = formatMoney(cartTotal(state.cart));
    $('#pieces').textContent = String(cartPieces(state.cart));
    document.querySelectorAll('[data-pay], [data-action=clear], [data-action=plus], [data-action=minus], [data-action=remove]').forEach((button) => { button.disabled = isEmpty; });
};

// ---------- payment ----------
const method = () => $('#pay-form').elements.method.value;

const renderPayment = () => {
    const total = cartTotal(state.cart);
    const isCash = method() === 'cash';
    $('#cash-fields').hidden = !isCash;
    $('#cash-keys').hidden = !isCash;
    $('#reference-field').hidden = isCash;
    const text = $('#received').value.trim();
    const received = text === '' ? total : parseMoney(text);
    const unreadable = isCash && received === null;
    const missing = isCash && !unreadable && received < total;
    const valid = !unreadable && !missing;
    $('#change-row').classList.toggle('change--missing', missing);
    $('#change-label').textContent = missing ? 'المبلغ ناقص' : 'الباقي للزبونة';
    $('#change').textContent = unreadable ? '—' : `${formatMoney(Math.abs(received - total))} د.ل`;
    $('#pay-error').hidden = !unreadable;
    $('#pay-error').textContent = unreadable ? 'المبلغ غير صحيح. اكتبي أرقامًا فقط، مثل 25 أو 25.500.' : '';
    $('#confirm-sale').disabled = !valid || state.saving;
    return { valid, received: isCash ? received : null };
};

const openPayment = (payWith = 'cash') => {
    if (state.cart.length === 0 || document.querySelector('dialog[open]')) return;
    $('#pay-form').reset();
    $('#pay-form').elements.method.value = payWith;
    $('#print-receipt').checked = state.printByDefault !== false;
    $('#pay-total').textContent = `${formatMoney(cartTotal(state.cart))} د.ل`;
    renderPayment();
    $('#pay-dialog').showModal();
    (payWith === 'cash' ? $('#received') : $('#reference')).focus();
};

const setReceived = (text) => {
    $('#received').value = text;
    renderPayment();
    $('#received').focus();
};

const buildCashKeys = () => {
    $('#notes').replaceChildren(
        el('button', { type: 'button', className: 'notes__exact', onclick: () => setReceived('') }, 'المبلغ مضبوط'),
        // Notes add up, the way cash is counted: 50 + 20 + 5.
        ...NOTES.map((note) => el('button', { type: 'button', dir: 'ltr', onclick: () => setReceived(formatMoney((parseMoney($('#received').value) ?? 0) + note * 1000).replace(/\.000$/, '')) }, `+${note}`)));
    $('#pad').replaceChildren(...['7', '8', '9', '4', '5', '6', '1', '2', '3', '.', '0', '⌫'].map((key) => el('button', { type: 'button', 'aria-label': key === '⌫' ? 'مسح رقم' : key, onclick: () => setReceived(key === '⌫' ? $('#received').value.slice(0, -1) : $('#received').value + key) }, key)));
};

const completeSale = async () => {
    const payment = renderPayment();
    if (!payment.valid || state.saving || state.cart.length === 0) return;
    state.saving = true;
    $('#confirm-sale').disabled = true;
    const lines = structuredClone(state.cart);
    try {
        const sale = await commitSale((device, sequence) => ({
            uuid: crypto.randomUUID(),
            sequence,
            receipt_number: receiptNumber(device.id, sequence),
            sold_at: new Date().toISOString(),
            cashier: device.cashier,
            payment_method: method(),
            payment_reference: method() === 'cash' ? null : $('#reference').value.trim() || null,
            amount_received: payment.received,
            total: cartTotal(lines),
            items: lines.map((line) => ({ variant_id: line.id, name: line.name, variant: line.variant ?? null, sku: line.sku, unit_price: line.price, quantity: line.quantity })),
            status: 'pending',
            settled: false,
        }));
        state.printByDefault = $('#print-receipt').checked;
        setMeta('print', state.printByDefault).catch(() => {});
        state.cart = [];
        state.selected = null;
        state.lastSale = sale;
        state.sequence = sale.sequence;
        $('#pay-dialog').close();
        await reloadSales();
        $('#search').value = '';
        renderAll();
        toast(`تم حفظ الفاتورة ${sale.receipt_number}`);
        focusSearch();
        void sync();
        if (state.printByDefault) void printReceipt(sale);
    } catch (error) {
        console.error(error);
        $('#pay-error').hidden = false;
        $('#pay-error').textContent = 'لم يُحفظ البيع على هذا الجهاز. الفاتورة ما زالت مفتوحة، حاولي مرة أخرى.';
    } finally {
        state.saving = false;
        $('#confirm-sale').disabled = false;
    }
};

// ---------- receipt ----------
const printReceipt = async (sale) => {
    const row = (label, value) => el('p', { className: 'receipt__row' }, el('span', {}, label), el('span', {}, value));
    $('#receipt').replaceChildren(
        el('img', { src: '/images/glowy-logo.png', alt: 'Glowy' }),
        ...[state.store.address, state.store.phone].filter(Boolean).map((text) => el('p', { className: 'receipt__center' }, text)),
        row('إيصال', sale.receipt_number),
        row('التاريخ', new Date(sale.sold_at).toLocaleString('en-GB', { dateStyle: 'short', timeStyle: 'short' })),
        row('الكاشير', sale.cashier),
        el('hr'),
        ...sale.items.map((item) => el('div', {}, el('p', {}, [item.name, item.variant].filter(Boolean).join(' — ')), row(`${item.quantity} × ${formatMoney(item.unit_price)}`, formatMoney(item.quantity * item.unit_price)))),
        el('hr'),
        el('p', { className: 'receipt__row receipt__total' }, el('span', {}, 'الإجمالي'), el('span', {}, `${formatMoney(sale.total)} د.ل`)),
        row('الدفع', METHODS[sale.payment_method]),
        ...(sale.amount_received ? [row('المستلم', formatMoney(sale.amount_received)), row('الباقي', formatMoney(sale.amount_received - sale.total))] : []),
        ...(sale.payment_reference ? [row('المرجع', sale.payment_reference)] : []),
        el('p', { className: 'receipt__center' }, 'شكرًا لزيارتك ✦'),
    );
    await $('#receipt img').decode?.().catch(() => {});
    // `size: 80mm auto` is not valid CSS, so the page height is measured for every receipt.
    $('#page-size').textContent = `@page { size: 80mm ${Math.ceil($('#receipt').offsetHeight * 25.4 / 96) + 4}mm; margin: 0; }`;
    window.print();
};

// ---------- history ----------
const openHistory = async () => {
    const sales = (await allSales()).sort((first, second) => (first.sold_at < second.sold_at ? 1 : -1));
    const today = new Date().toDateString();
    const totals = summarize(sales, (date) => date.toDateString() === today);
    $('#summary').replaceChildren(...[['مبيعات اليوم', totals.cash + totals.card + totals.transfer], ['نقدًا', totals.cash], ['بطاقة', totals.card], ['تحويل', totals.transfer]].map(([label, amount]) => el('p', {}, el('span', {}, label), el('strong', {}, `${formatMoney(amount)} د.ل`))), el('p', {}, el('span', {}, 'عدد العمليات'), el('strong', {}, String(totals.count))));
    const labels = { pending: 'بانتظار الرفع', synced: 'مرفوع', rejected: 'مرفوض' };
    const row = (sale) => el('tr', {},
        el('td', {}, el('strong', { dir: 'ltr' }, sale.receipt_number)),
        el('td', {}, new Date(sale.sold_at).toLocaleString('ar-LY-u-nu-latn', { dateStyle: 'short', timeStyle: 'short' })),
        el('td', {}, String(cartPieces(sale.items))),
        el('td', {}, METHODS[sale.payment_method]),
        el('td', {}, el('strong', {}, formatMoney(sale.total))),
        el('td', {}, el('span', { className: `tag tag--${sale.status}` }, labels[sale.status]), sale.status === 'rejected' ? el('div', { className: 'error' }, sale.message ?? 'رفضه الخادم') : null),
        el('td', {}, el('button', { type: 'button', className: 'link', onclick: () => void printReceipt(sale) }, 'طباعة'), sale.status === 'rejected' ? el('button', { type: 'button', className: 'link', onclick: () => retry(sale.uuid) }, 'إعادة المحاولة') : null));
    $('#history').replaceChildren(...(sales.length ? sales.slice(0, 200).map(row) : [el('tr', {}, el('td', { colspan: 7, className: 'history__empty' }, 'لا توجد مبيعات على هذا الجهاز بعد.'))]));
    if (!$('#history-dialog').open) $('#history-dialog').showModal();
};

const retry = async (uuid) => {
    await markSale(uuid, { status: 'pending', message: null });
    await reloadSales();
    await sync({ force: true });
    await openHistory();
};

// ---------- sync ----------
const call = async (path, body) => {
    const controller = new AbortController();
    const timer = setTimeout(() => controller.abort(), 20_000);
    try {
        const response = await fetch(`/pos/api/${path}`, {
            method: body ? 'POST' : 'GET',
            headers: { Accept: 'application/json', 'X-Glowy-Pos': '1', ...(body ? { 'Content-Type': 'application/json' } : {}) },
            credentials: 'same-origin',
            cache: 'no-store',
            body: body ? JSON.stringify(body) : undefined,
            signal: controller.signal,
        });
        if (response.status === 401) throw Object.assign(new Error('unlinked'), { unlinked: true });
        if (!response.ok) throw new Error(`HTTP ${response.status}`);
        return await response.json();
    } finally {
        clearTimeout(timer);
    }
};

const payload = (sale) => ({
    uuid: sale.uuid,
    sequence: sale.sequence,
    receipt_number: sale.receipt_number,
    sold_at: sale.sold_at,
    payment_method: sale.payment_method,
    payment_reference: sale.payment_reference,
    amount_received: sale.amount_received === null ? null : formatMoney(sale.amount_received),
    items: sale.items.map((item) => ({ ...item, unit_price: formatMoney(item.unit_price) })),
});

const upload = async () => {
    const queue = (await salesByStatus('pending')).sort((first, second) => first.sequence - second.sequence);
    let saved = 0;
    for (let start = 0; start < queue.length; start += BATCH) {
        const { results } = await call('sync', { sales: queue.slice(start, start + BATCH).map(payload) });
        for (const result of results) {
            if (result.status === 'saved') { await markSale(result.uuid, { status: 'synced', message: null }); saved += 1; }
            if (result.status === 'rejected') await markSale(result.uuid, { status: 'rejected', message: result.message });
        }
    }
    return saved;
};

const refresh = async () => {
    const settled = (await salesByStatus('synced')).map((sale) => sale.uuid);
    const data = await call('bootstrap');
    await saveBootstrap(data, settled, new Date(Date.now() - KEEP_DAYS * 86_400_000).toISOString());
    state.lastRefresh = Date.now();
    await loadLocal();
};

const runSync = async (force) => {
    const saved = await upload();
    if (force || saved > 0 || Date.now() - state.lastRefresh > REFRESH_EVERY) await refresh();
    else if (state.connection !== 'online') await refresh();
    state.connection = 'online';
};

let running = null;
const sync = ({ force = false } = {}) => {
    running ??= (async () => {
        $('#sync-button').disabled = true;
        try {
            // Only one tab talks to the server at a time.
            if (navigator.locks) await navigator.locks.request('glowy-pos-sync', () => runSync(force));
            else await runSync(force);
        } catch (error) {
            state.connection = error.unlinked ? 'unlinked' : 'offline';
        } finally {
            running = null;
            $('#sync-button').disabled = false;
            await reloadSales();
            renderAll();
        }
    })();
    return running;
};

// ---------- state ----------
const reloadSales = async () => {
    const sales = await allSales();
    state.unsettled = unsettledQuantities(sales);
    state.pending = sales.filter((sale) => sale.status === 'pending');
    state.rejected = sales.filter((sale) => sale.status === 'rejected');
};

const loadLocal = async () => {
    state.device = (await getMeta('device')) ?? null;
    state.store = (await getMeta('store')) ?? {};
    state.variants = await allVariants();
    state.sequence = (await getMeta('sequence')) ?? 0;
    await reloadSales();
};

const renderAll = () => {
    $('#register').hidden = !state.device;
    $('#keys').hidden = !state.device;
    $('#unlinked').hidden = Boolean(state.device) || state.connection === 'checking';
    renderStatus();
    if (!state.device) return;
    renderCategories();
    renderResults();
    // A background sync must not interrupt someone editing a quantity.
    if (!document.activeElement?.closest('#cart-items input')) renderCart();
};

// ---------- events ----------
$('#search').addEventListener('input', renderResults);
$('#search').addEventListener('keydown', (event) => {
    if (event.key !== 'Enter') return;
    event.preventDefault();
    const text = $('#search').value;
    if (!text.trim()) return;
    const exact = findByCode(state.variants, text);
    const results = exact ? [exact] : searchVariants(state.variants, text, 2);
    if (results.length === 1) {
        addToCart(results[0]);
        $('#search').value = '';
        renderResults();
    } else {
        toast(results.length ? 'أكثر من نتيجة. اختاري المنتج من القائمة.' : `لا يوجد منتج بالكود «${text.trim()}».`, results.length === 0);
        $('#search').select();
    }
});
$('#clear-search').addEventListener('click', () => { $('#search').value = ''; renderResults(); focusSearch(); });

const actions = {
    search: () => { focusSearch(); $('#search').select(); },
    history: () => void openHistory(),
    clear: () => { if (state.cart.length) $('#confirm-dialog').showModal(); },
    plus: () => { const line = selectedLine(); if (line) changeQuantity(line.id, line.quantity + 1); },
    minus: () => { const line = selectedLine(); if (line) changeQuantity(line.id, line.quantity - 1); },
    remove: () => { const line = selectedLine(); if (line) changeQuantity(line.id, 0); },
    sync: async () => { await sync({ force: true }); toast(state.connection === 'online' ? 'تمت المزامنة.' : 'تعذر الاتصال. البيع مستمر دون إنترنت.', state.connection !== 'online'); },
};
document.querySelectorAll('[data-action]').forEach((button) => button.addEventListener('click', () => { actions[button.dataset.action](); focusSearch(); }));
document.querySelectorAll('[data-pay]').forEach((button) => button.addEventListener('click', () => openPayment(button.dataset.pay)));
$('#confirm-clear').addEventListener('click', () => {
    state.cart = [];
    state.selected = null;
    saveCart();
    $('#confirm-dialog').close();
    renderCart();
});
$('#pay-form').addEventListener('input', renderPayment);
$('#pay-form').addEventListener('change', () => { renderPayment(); (method() === 'cash' ? $('#received') : $('#reference')).focus(); });
$('#pay-form').addEventListener('submit', (event) => { event.preventDefault(); void completeSale(); });
document.querySelectorAll('[data-close]').forEach((button) => button.addEventListener('click', () => button.closest('dialog').close()));
document.querySelectorAll('dialog').forEach((dialog) => dialog.addEventListener('close', focusSearch));
const SHORTCUTS = { F2: actions.search, F3: actions.history, F4: actions.clear, F7: actions.sync, F8: () => openPayment('transfer'), F9: () => openPayment('cash'), F10: () => openPayment('card') };
// These act on the selected row only while nothing is being typed.
const ROW_KEYS = { ArrowDown: () => moveSelection(1), ArrowUp: () => moveSelection(-1), '+': actions.plus, '-': actions.minus, Delete: actions.remove };
document.addEventListener('keydown', (event) => {
    if (event.ctrlKey || event.metaKey || event.altKey) return;
    if (SHORTCUTS[event.key]) {
        event.preventDefault();
        if (!event.repeat && !document.querySelector('dialog[open]') && state.device) SHORTCUTS[event.key]();
        return;
    }
    if (document.querySelector('dialog[open]')) return;
    const typing = event.target.closest('input');
    if (ROW_KEYS[event.key] && (!typing || (typing.id === 'search' && typing.value === ''))) {
        event.preventDefault();
        ROW_KEYS[event.key]();
        return;
    }
    // A scan must land in the search box even when focus is elsewhere on the page.
    if (event.key.length === 1 && !typing) $('#search').focus();
});
window.addEventListener('online', () => void sync());
window.addEventListener('offline', () => { state.connection = 'offline'; renderStatus(); });
document.addEventListener('visibilitychange', () => { if (!document.hidden) void sync(); });
setInterval(() => void sync(), SYNC_EVERY);

// ---------- start ----------
const tick = () => { $('#clock').textContent = new Date().toLocaleString('ar-LY-u-nu-latn', { weekday: 'long', day: 'numeric', month: 'numeric', hour: '2-digit', minute: '2-digit' }); };

const start = async () => {
    tick();
    setInterval(tick, 10_000);
    buildCashKeys();
    if ('serviceWorker' in navigator) navigator.serviceWorker.register('/pos-sw.js', { scope: '/pos' }).catch((error) => console.error(error));
    navigator.storage?.persist?.().catch(() => {});
    await loadLocal();
    state.cart = (await getMeta('cart')) ?? [];
    state.selected = state.cart.at(-1)?.id ?? null;
    state.printByDefault = (await getMeta('print')) ?? true;
    renderAll();
    focusSearch();
    await sync({ force: true });
    focusSearch();
};

start().catch((error) => {
    console.error(error);
    $('#notice').hidden = false;
    $('#notice').textContent = 'تعذر فتح التخزين المحلي في هذا المتصفح. لا تستخدمي وضع التصفح الخفي.';
});
