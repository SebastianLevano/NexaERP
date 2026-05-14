const CURRENCY = 'PEN';
const LOCALE = 'es-PE';

const currencyFormatter = new Intl.NumberFormat(LOCALE, {
    style: 'currency',
    currency: CURRENCY,
    minimumFractionDigits: 2,
});

const numberFormatter = new Intl.NumberFormat(LOCALE, {
    minimumFractionDigits: 0,
    maximumFractionDigits: 2,
});

export function formatCurrency(value: number | string): string {
    const n = typeof value === 'string' ? Number(value) : value;
    return currencyFormatter.format(Number.isFinite(n) ? n : 0);
}

export function formatNumber(value: number | string): string {
    const n = typeof value === 'string' ? Number(value) : value;
    return numberFormatter.format(Number.isFinite(n) ? n : 0);
}

export function formatDate(value: string | Date): string {
    const d = value instanceof Date ? value : new Date(value);
    return new Intl.DateTimeFormat(LOCALE, {
        day: '2-digit',
        month: 'short',
        year: 'numeric',
    }).format(d);
}
