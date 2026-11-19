import { listPublished } from "@/lib/db";
import { categoryName, site } from "@/lib/config";
import { xmlEscape } from "@/lib/xml";

export const dynamic = "force-dynamic";

export function GET() {
  const items = listPublished({ limit: 50 });
  const body = `<?xml version="1.0" encoding="UTF-8"?>
<rss version="2.0" xmlns:atom="http://www.w3.org/2005/Atom">
<channel>
  <title>${xmlEscape(site.name)}</title>
  <link>${site.url}</link>
  <description>${xmlEscape(site.description)}</description>
  <language>tr</language>
  <atom:link href="${site.url}/rss.xml" rel="self" type="application/rss+xml"/>
${items
  .map(
    (a) => `  <item>
    <title>${xmlEscape(a.title)}</title>
    <link>${site.url}/haber/${a.slug}</link>
    <guid isPermaLink="true">${site.url}/haber/${a.slug}</guid>
    <description>${xmlEscape(a.summary)}</description>
    <category>${xmlEscape(categoryName(a.category))}</category>
    <pubDate>${new Date(a.published_at!).toUTCString()}</pubDate>
  </item>`
  )
  .join("\n")}
</channel>
</rss>`;
  return new Response(body, { headers: { "content-type": "application/rss+xml; charset=utf-8", "cache-control": "public, max-age=300" } });
}
