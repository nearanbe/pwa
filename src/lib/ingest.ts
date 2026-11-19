import { selectStories, writeArticle, type FeedItem, type SourceDoc } from "./ai";
import { automation, site } from "./config";
import { filterUnseen, insertArticle, markSeen, recentTitles, type Article } from "./db";
import { signAction } from "./auth";

/** Kaynak sayfayı indirip kaba bir metin çıkarır. Başarısız olursa RSS özetiyle devam edilir. */
async function fetchText(url: string): Promise<string> {
  try {
    const res = await fetch(url, {
      signal: AbortSignal.timeout(10_000),
      headers: { "user-agent": "Mozilla/5.0 (compatible; HaberBot/1.0)" },
    });
    if (!res.ok) return "";
    const html = await res.text();
    const paragraphs = [...html.matchAll(/<p[^>]*>([\s\S]*?)<\/p>/gi)]
      .map((m) => m[1].replace(/<[^>]+>/g, " ").replace(/&nbsp;/g, " ").replace(/&amp;/g, "&").replace(/&quot;/g, '"').replace(/&#39;/g, "'").replace(/\s+/g, " ").trim())
      .filter((p) => p.length > 60);
    return paragraphs.join("\n").slice(0, 10_000);
  } catch {
    return "";
  }
}

export interface IngestResult {
  received: number;
  fresh: number;
  created: Array<{
    id: number;
    title: string;
    status: Article["status"];
    risk: Article["risk"];
    risk_reason: string;
    is_breaking: boolean;
    url: string;
    review_url: string;
    publish_at: number | null;
  }>;
  skipped: string[];
}

export async function ingest(items: FeedItem[]): Promise<IngestResult> {
  const unseen = filterUnseen(items.map((i) => i.link));
  const fresh = items.filter((i) => unseen.has(i.link)).slice(0, 60);
  const result: IngestResult = { received: items.length, fresh: fresh.length, created: [], skipped: [] };
  if (fresh.length === 0) return result;

  const selection = await selectStories(fresh, recentTitles(), automation.maxArticlesPerRun);
  // Değerlendirilen tüm linkleri gördü olarak işaretle; seçilmeyenler bir daha gönderilmesin.
  markSeen(fresh.map((i) => i.link));

  for (const story of selection.stories) {
    const picked = story.item_indexes.map((i) => fresh[i]).filter(Boolean);
    if (picked.length === 0) continue;
    try {
      const docs: SourceDoc[] = await Promise.all(
        picked.map(async (p) => ({ ...p, text: (await fetchText(p.link)) || p.summary }))
      );
      const written = await writeArticle(docs, story.category);
      if (written.insufficient_sources) {
        result.skipped.push(`${picked[0].title} (kaynak yetersiz)`);
        continue;
      }

      // Yayın kuralı:
      //  - yüksek risk           → taslak, elle onay şart
      //  - son dakika + düşük risk → hemen yayın
      //  - düşük risk             → X dk sonra otomatik yayın (bu sürede durdurulabilir)
      const now = Date.now();
      let status: Article["status"];
      let publishAt: number | null = null;
      if (written.risk === "high") {
        status = "draft";
      } else if (story.is_breaking && automation.breakingAutoPublish) {
        status = "published";
      } else {
        status = "scheduled";
        publishAt = now + automation.autoPublishDelayMin * 60_000;
      }

      const sources = [...new Map(picked.map((p) => [p.link, { name: p.source, url: p.link }])).values()];
      const a = insertArticle({
        title: written.title,
        summary: written.summary,
        body: written.body_markdown,
        category: written.category,
        tags: written.tags,
        sources,
        status,
        risk: written.risk,
        risk_reason: written.risk_reason,
        is_breaking: story.is_breaking,
        author: site.author,
        seo_description: written.seo_description,
        publish_at: publishAt,
      });
      result.created.push({
        id: a.id,
        title: a.title,
        status: a.status,
        risk: a.risk,
        risk_reason: a.risk_reason,
        is_breaking: a.is_breaking,
        url: `${site.url}/haber/${a.slug}`,
        review_url: `${site.url}/onay/${a.id}?t=${signAction(a.id)}`,
        publish_at: a.publish_at,
      });
    } catch (err) {
      result.skipped.push(`${picked[0].title} (hata: ${(err as Error).message})`);
    }
  }
  return result;
}
