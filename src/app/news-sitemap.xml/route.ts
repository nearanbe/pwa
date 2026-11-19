import { listPublished } from "@/lib/db";
import { site } from "@/lib/config";
import { xmlEscape } from "@/lib/xml";

export const dynamic = "force-dynamic";

/** Google Haberler site haritası: son 48 saatin haberleri. */
export function GET() {
  const cutoff = Date.now() - 48 * 3600_000;
  const items = listPublished({ limit: 1000 }).filter((a) => (a.published_at ?? 0) > cutoff);
  const body = `<?xml version="1.0" encoding="UTF-8"?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" xmlns:news="http://www.google.com/schemas/sitemap-news/0.9">
${items
  .map(
    (a) => `  <url>
    <loc>${site.url}/haber/${a.slug}</loc>
    <news:news>
      <news:publication><news:name>${xmlEscape(site.name)}</news:name><news:language>tr</news:language></news:publication>
      <news:publication_date>${new Date(a.published_at!).toISOString()}</news:publication_date>
      <news:title>${xmlEscape(a.title)}</news:title>
    </news:news>
  </url>`
  )
  .join("\n")}
</urlset>`;
  return new Response(body, { headers: { "content-type": "application/xml; charset=utf-8", "cache-control": "public, max-age=300" } });
}
