export function money(value, decimals, currency = "SEK") {
    if (!value) {
        return null;
    }

    if (!currency) {
        currency = "SEK";
    }

    const money = new Intl.NumberFormat("se-SV", {
        currencyDisplay: "narrowSymbol",
        style: "currency",
        currency: "SEK",
        minimumFractionDigits: decimals ?? 2,
        maximumFractionDigits: decimals ?? 2,
    });

    return money.format(value);
}
