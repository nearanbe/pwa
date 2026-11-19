import type { MetadataRoute } from "next";
import { listPublished } from "@/lib/db";
import { CATEGORIES, site } from "@/lib/config";

export const dynamic = "force-dynamic";

export default function sitemap(): MetadataRoute.Sitemap {
  const articles = listPublished({ limit: 5000 });
  return [
    { url: site.url, changeFrequency: "always", priority: 1 },
    ...CATEGORIES.map((c) => ({ url: `${site.url}/kategori/${c.slug}`, changeFrequency: "hourly" as const, priority: 0.8 })),
    ...articles.map((a) => ({ url: `${site.url}/haber/${a.slug}`, lastModified: new Date(a.updated_at), priority: 0.6 })),
  ];
}
