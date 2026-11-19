// Sitenin tüm ayarları buradan ve .env dosyasından gelir.

export const CATEGORIES = [
  { slug: "gundem", name: "Gündem" },
  { slug: "politika", name: "Politika" },
  { slug: "ekonomi", name: "Ekonomi" },
  { slug: "dunya", name: "Dünya" },
  { slug: "spor", name: "Spor" },
  { slug: "teknoloji", name: "Teknoloji" },
  { slug: "saglik", name: "Sağlık" },
  { slug: "yasam", name: "Yaşam" },
  { slug: "kultur-sanat", name: "Kültür-Sanat" },
  { slug: "magazin", name: "Magazin" },
] as const;

export type CategorySlug = (typeof CATEGORIES)[number]["slug"];

export const CATEGORY_SLUGS = CATEGORIES.map((c) => c.slug) as [
  CategorySlug,
  ...CategorySlug[],
];

export function categoryName(slug: string): string {
  return CATEGORIES.find((c) => c.slug === slug)?.name ?? "Gündem";
}

function env(name: string, fallback = ""): string {
  return process.env[name] ?? fallback;
}

export const site = {
  get name() {
    return env("SITE_NAME", "Haber Merkezi");
  },
  get url() {
    return env("SITE_URL", "http://localhost:3000").replace(/\/$/, "");
  },
  get description() {
    return env(
      "SITE_DESCRIPTION",
      "Türkiye ve dünyadan son dakika haberleri, güncel gelişmeler."
    );
  },
  get author() {
    return env("SITE_AUTHOR", "Haber Merkezi");
  },
  // Künye bilgileri (5187 sayılı Basın Kanunu gereği zorunlu)
  get owner() {
    return env("KUNYE_SAHIBI", "");
  },
  get editor() {
    return env("KUNYE_SORUMLU_MUDUR", "");
  },
  get address() {
    return env("KUNYE_ADRES", "");
  },
  get email() {
    return env("KUNYE_EPOSTA", "");
  },
  get phone() {
    return env("KUNYE_TELEFON", "");
  },
  get hostingProvider() {
    return env("KUNYE_YER_SAGLAYICI", "");
  },
  get adsenseClient() {
    return env("ADSENSE_CLIENT", ""); // ca-pub-XXXXXXXXXXXXXXXX
  },
  get adsenseSlots() {
    return {
      top: env("ADSENSE_SLOT_TOP"),
      sidebar: env("ADSENSE_SLOT_SIDEBAR"),
      article: env("ADSENSE_SLOT_ARTICLE"),
    };
  },
};

export const automation = {
  get autoPublishDelayMin() {
    return Number(env("AUTO_PUBLISH_DELAY_MIN", "5"));
  },
  get breakingAutoPublish() {
    return env("BREAKING_AUTO_PUBLISH", "true") === "true";
  },
  get maxArticlesPerRun() {
    return Number(env("MAX_ARTICLES_PER_RUN", "3"));
  },
  get model() {
    return env("CLAUDE_MODEL", "claude-opus-5-5");
  },
};
