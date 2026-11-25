// Pure helpers. Money is always an integer number of dirhams (1 د.ل = 1000).

const ARABIC_DIGITS = '٠١٢٣٤٥٦٧٨٩';
const PERSIAN_DIGITS = '۰۱۲۳۴۵۶۷۸۹';

// A scanner types through the active keyboard layout, so a code scanned while
// the layout is Arabic arrives as Arabic letters.
const ARABIC_LAYOUT = {
    ض: 'q', ص: 'w', ث: 'e', ق: 'r', ف: 't', غ: 'y', ع: 'u', ه: 'i', خ: 'o', ح: 'p', ش: 'a', س: 's', ي: 'd', ب: 'f', ل: 'g',
    ا: 'h', ت: 'j', ن: 'k', م: 'l', ئ: 'z', ء: 'x', ؤ: 'c', ر: 'v', ى: 'n', ة: 'm',
};

export const toLatinDigits = (text) => String(text).replace(/[٠-٩۰-۹]/g, (digit) => {
    const index = ARABIC_DIGITS.indexOf(digit);
    return String(index >= 0 ? index : PERSIAN_DIGITS.indexOf(digit));
});

// At most six digits before the point: a barcode scanned into an amount field is never a valid amount.
export const parseMoney = (value) => {
    const text = toLatinDigits(value ?? '').trim().replace(/[٫,]/g, '.');
    const match = /^(\d{1,6})(?:\.(\d{0,3}))?$/.exec(text);
    return match ? Number(match[1]) * 1000 + Number((match[2] ?? '').padEnd(3, '0')) : null;
};

export const formatMoney = (amount) => `${Math.floor(amount / 1000)}.${String(amount % 1000).padStart(3, '0')}`;

export const normalizeText = (text) => toLatinDigits(text ?? '')
    .toLowerCase()
    .replace(/[ً-ْـ]/g, '')
    .replace(/[أإآ]/g, 'ا')
    .replace(/ة/g, 'ه')
    .replace(/ى/g, 'ي')
    .trim();

export const normalizeCode = (text) => toLatinDigits(text ?? '').trim().toLowerCase();

export const fromArabicLayout = (text) => normalizeCode(text).replace(/لا/g, 'b').replace(/./gu, (char) => ARABIC_LAYOUT[char] ?? char);

export const findByCode = (variants, text) => {
    for (const code of new Set([normalizeCode(text), fromArabicLayout(text)])) {
        if (!code) continue;
        const match = variants.find((variant) => normalizeCode(variant.barcode) === code)
            ?? variants.find((variant) => normalizeCode(variant.sku) === code);
        if (match) return match;
    }
    return null;
};

export const searchVariants = (variants, text, limit = 60) => {
    const words = normalizeText(text).split(/\s+/).filter(Boolean);
    const results = [];
    for (const variant of variants) {
        const haystack = normalizeText([variant.name, variant.name_en, variant.variant, variant.brand, variant.sku, variant.barcode].filter(Boolean).join(' '));
        if (words.every((word) => haystack.includes(word))) results.push(variant);
        if (results.length >= limit) break;
    }
    return results;
};

export const cartTotal = (lines) => lines.reduce((sum, line) => sum + line.price * line.quantity, 0);

export const cartPieces = (lines) => lines.reduce((sum, line) => sum + line.quantity, 0);

export const categoriesOf = (variants) => [...new Set(variants.map((variant) => variant.category).filter(Boolean))].sort((first, second) => first.localeCompare(second, 'ar'));

// Sales the server has not yet reflected in the downloaded stock.
export const unsettledQuantities = (sales) => {
    const quantities = new Map();
    for (const sale of sales) {
        if (sale.settled) continue;
        for (const item of sale.items) quantities.set(item.variant_id, (quantities.get(item.variant_id) ?? 0) + item.quantity);
    }
    return quantities;
};

export const receiptNumber = (deviceId, sequence) => `P${deviceId}-${String(sequence).padStart(6, '0')}`;

export const summarize = (sales, isInRange) => {
    const totals = { cash: 0, card: 0, transfer: 0, count: 0 };
    for (const sale of sales) {
        if (!isInRange(new Date(sale.sold_at))) continue;
        totals[sale.payment_method] += sale.total;
        totals.count += 1;
    }
    return totals;
};
