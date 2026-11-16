/**
 * "MWK 45,000.00" — amounts always shown with two decimals and the system currency.
 */
export const formatMoney = (amount, currency = 'MWK') => {
    const value = Number(amount ?? 0);
    const formatted = Math.abs(value).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });

    return `${value < 0 ? '−' : ''}${currency} ${formatted}`;
};

/**
 * 72.5 → "72.5%", 72 → "72%".
 */
export const formatPercent = (value) =>
    value === null || value === undefined ? '—' : `${Number(value).toLocaleString('en-US', { maximumFractionDigits: 1 })}%`;
