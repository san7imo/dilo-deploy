export const formatAmount = (value) => {
    const n = Number(value ?? 0);
    if (!Number.isFinite(n)) return "0";
    try {
        return new Intl.NumberFormat("es-ES", {
            minimumFractionDigits: 0,
            maximumFractionDigits: 0,
        }).format(n);
    } catch (e) {
        const rounded = Math.round(n);
        return rounded.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ".");
    }
};

export const formatMoney = (value, currency = "USD") => {
    const code = String(currency || "USD").trim() || "USD";
    return `${code} ${formatAmount(value)}`;
};

export const formatMoneyWithSymbol = (value, symbol = "$") => {
    const prefix = String(symbol || "$").trim() || "$";
    return `${prefix} ${formatAmount(value)}`;
};
