export function useFormat() {
  function money(amount, currencyCode = '') {
    const n = Number(amount);
    if (!Number.isFinite(n)) return String(amount ?? '');
    if (currencyCode) {
      try {
        return new Intl.NumberFormat(undefined, {
          style: 'currency',
          currency: currencyCode,
        }).format(n);
      } catch {
        // ignore
      }
    }
    return new Intl.NumberFormat(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 }).format(n);
  }

  function dateTime(v) {
    if (!v) return '';
    const d = new Date(v);
    if (Number.isNaN(d.getTime())) return String(v);
    return d.toLocaleString();
  }

  return { money, dateTime };
}

