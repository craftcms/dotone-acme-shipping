import {computed, type Ref} from 'vue';

export interface Carrier {
  handle: string;
  name: string;
  service: string;
  base: number;
  perKg: number;
  etaDays: number;
}

export interface Quote extends Carrier {
  total: number;
  recommended: boolean;
}

/** The plugin's own pricing logic, cheapest first. */
export function useRateQuotes(carriers: Carrier[], weight: Ref<number>) {
  return computed<Quote[]>(() => {
    const priced = carriers
      .map((carrier) => ({
        ...carrier,
        total: carrier.base + carrier.perKg * weight.value,
        recommended: false,
      }))
      .sort((a, b) => a.total - b.total);

    // Cheapest that still arrives inside three days, else just the cheapest.
    const pick = priced.find((quote) => quote.etaDays <= 3) ?? priced[0];

    if (pick) {
      pick.recommended = true;
    }

    return priced;
  });
}
