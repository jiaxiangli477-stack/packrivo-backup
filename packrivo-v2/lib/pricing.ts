export type StyleKey =
  | "mailer"
  | "product"
  | "shipping"
  | "rigid"
  | "carton"
  | "pouch"
  | "bag"
  | "sticker"
  | "poly";

export type MaterialKey =
  | "kraft"
  | "white"
  | "recycled"
  | "corrugated"
  | "rigidboard"
  | "kraftpoly"
  | "compostable";

export type PrintKey = "none" | "one-color" | "full-color" | "full-color-spot";

export type FinishKey = "none" | "matte" | "gloss" | "soft-touch" | "spot-uv" | "foil" | "emboss";

export interface QuoteInput {
  style: StyleKey;
  material: MaterialKey;
  print: PrintKey;
  finish: FinishKey;
  quantity: number;
  length: number;
  width: number;
  height: number;
  rush: boolean;
}

export const STYLE_BASE: Record<StyleKey, { label: string; base: number; unit: "mm" | "cm" }> = {
  mailer: { label: "Mailer box", base: 1.42, unit: "cm" },
  product: { label: "Product box", base: 1.18, unit: "cm" },
  shipping: { label: "Shipping box", base: 1.02, unit: "cm" },
  rigid: { label: "Rigid box", base: 3.4, unit: "cm" },
  carton: { label: "Folding carton", base: 0.74, unit: "cm" },
  pouch: { label: "Stand-up pouch", base: 0.46, unit: "cm" },
  bag: { label: "Paper bag", base: 0.92, unit: "cm" },
  sticker: { label: "Sticker sheet", base: 0.16, unit: "cm" },
  poly: { label: "Poly mailer", base: 0.62, unit: "cm" },
};

export const MATERIALS: Record<MaterialKey, { label: string; factor: number; note: string }> = {
  kraft: { label: "Kraft corrugated", factor: 1.0, note: "Recycled, natural finish, best value" },
  white: { label: "White corrugated", factor: 1.06, note: "Bright print surface for full-colour art" },
  recycled: { label: "100% recycled board", factor: 1.1, note: "Unbleached core; certification subject to supplier verification" },
  corrugated: { label: "Double-wall corrugated", factor: 1.22, note: "Heavy-duty transit protection" },
  rigidboard: { label: "Rigid greyboard", factor: 1.75, note: "Wrap-around luxury construction" },
  kraftpoly: { label: "Kraft-look poly mailer", factor: 0.92, note: "Water resistant, lightweight parcel" },
  compostable: { label: "Compostable mailer", factor: 1.38, note: "Compostability subject to supplier verification" },
};

export const PRINTS: Record<PrintKey, { label: string; add: number }> = {
  none: { label: "Unprinted", add: 0 },
  "one-color": { label: "1-colour flexo", add: 0.09 },
  "full-color": { label: "Full-colour CMYK", add: 0.18 },
  "full-color-spot": { label: "CMYK + spot colour", add: 0.29 },
};

export const FINISHES: Record<FinishKey, { label: string; add: number }> = {
  none: { label: "None", add: 0 },
  matte: { label: "Matte varnish", add: 0.06 },
  gloss: { label: "Gloss varnish", add: 0.05 },
  "soft-touch": { label: "Soft-touch lamination", add: 0.13 },
  "spot-uv": { label: "Spot UV", add: 0.16 },
  foil: { label: "Foil stamp", add: 0.35 },
  emboss: { label: "Blind emboss", add: 0.24 },
};

export const QUANTITY_TIERS = [100, 250, 500, 1000, 2500, 5000, 10000, 25000] as const;
export const FEE_PERCENT = 15;
export const MIN_FEE = 150;

const TIER_FACTOR: Array<[number, number]> = [
  [100, 2.864],
  [250, 1.468],
  [500, 1.0],
  [1000, 0.93],
  [2500, 0.85],
  [5000, 0.78],
  [10000, 0.72],
  [25000, 0.66],
];

export function quantityFactor(qty: number): number {
  if (qty <= 100) return 2.864;
  for (let i = 1; i < TIER_FACTOR.length; i++) {
    const [cap, factor] = TIER_FACTOR[i];
    const [prevCap, prevFactor] = TIER_FACTOR[i - 1];
    if (qty <= cap) {
      const t = (qty - prevCap) / (cap - prevCap);
      return prevFactor + (factor - prevFactor) * t;
    }
  }
  return 0.64;
}

function sizeFactor(length: number, width: number, height: number): number {
  const l = Math.max(1, length);
  const w = Math.max(1, width);
  const h = Math.max(1, height);
  const area = 2 * (l * w + l * h + w * h);
  const reference = 2 * (30 * 20 + 30 * 10 + 20 * 10);
  return Math.pow(area / reference, 0.6);
}

export interface PriceBreakdown {
  unit: number;
  total: number;
  goods_unit: number;
  goods_total: number;
  fee: number;
  fee_is_min: boolean;
  setup: number;
  base: number;
  material: number;
  size: number;
  print: number;
  finish: number;
  tier: number;
  rush: number;
  quantity: number;
}

export function priceQuote(input: QuoteInput): PriceBreakdown {
  const style = STYLE_BASE[input.style] ?? STYLE_BASE.mailer;
  const material = MATERIALS[input.material] ?? MATERIALS.kraft;
  const print = PRINTS[input.print] ?? PRINTS.none;
  const finish = FINISHES[input.finish] ?? FINISHES.none;

  const qty = Math.min(250000, Math.max(250, Math.round(input.quantity || 500)));
  const sFactor = sizeFactor(input.length || 20, input.width || 15, input.height || 10);
  const tier = quantityFactor(qty);

  const printed = input.print !== "none" || input.finish !== "none";
  const setup = printed ? (qty >= 2500 ? 0 : 220) + (qty >= 5000 ? 0 : 120) : 0;

  const baseUnit = style.base * material.factor;
  const variable = (baseUnit * sFactor + print.add + finish.add) * tier;
  const rushFactor = input.rush ? 1.22 : 1;
  const goodsUnit = variable * rushFactor + setup / qty;
  const goodsTotal = goodsUnit * qty;
  const calculatedFee = goodsTotal * FEE_PERCENT / 100;
  const fee = Math.max(MIN_FEE, calculatedFee);
  const total = goodsTotal + fee;

  return {
    unit: Math.round(total / qty * 100) / 100,
    total: Math.round(total * 100) / 100,
    goods_unit: Math.round(goodsUnit * 100) / 100,
    goods_total: Math.round(goodsTotal * 100) / 100,
    fee: Math.round(fee * 100) / 100,
    fee_is_min: calculatedFee < MIN_FEE,
    setup,
    base: round(style.base),
    material: round(baseUnit),
    size: round(sFactor),
    print: print.add,
    finish: finish.add,
    tier: round(tier),
    rush: input.rush ? 0.22 : 0,
    quantity: qty,
  };
}

export function tierTable(input: QuoteInput): Array<{ qty: number; unit: number; total: number }> {
  return QUANTITY_TIERS.filter((qty) => qty >= 250).map((qty) => {
    const p = priceQuote({ ...input, quantity: qty });
    return { qty, unit: p.unit, total: p.total };
  });
}

function round(n: number): number {
  return Math.round(n * 10000) / 10000;
}

export function formatUSD(n: number, opts: { decimals?: number } = {}): string {
  const decimals = opts.decimals ?? (n < 10 ? 2 : 0);
  return new Intl.NumberFormat("en-US", {
    style: "currency",
    currency: "USD",
    minimumFractionDigits: decimals,
    maximumFractionDigits: decimals,
  }).format(n);
}

export function defaultInput(style: StyleKey = "mailer"): QuoteInput {
  return {
    style,
    material:
      style === "rigid" ? "rigidboard" : style === "pouch" || style === "poly" ? "kraftpoly" : "kraft",
    print: "full-color",
    finish: "matte",
    quantity: 500,
    length: 30,
    width: 20,
    height: 10,
    rush: false,
  };
}
