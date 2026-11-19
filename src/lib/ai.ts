import Anthropic from "@anthropic-ai/sdk";
import { z } from "zod";
import { CATEGORY_SLUGS, automation } from "./config";

const client = new Anthropic(); // ANTHROPIC_API_KEY ortam değişkeninden okunur

export interface FeedItem {
  title: string;
  link: string;
  summary: string;
  source: string;
  published?: string;
}

export interface SourceDoc extends FeedItem {
  text: string; // kaynak sayfadan çıkarılan metin (yoksa özet)
}

// ---------- Ortak çağrı ----------

async function callJson<T>(opts: {
  system: string;
  user: string;
  schema: Record<string, unknown>;
  validator: z.ZodType<T>;
  effort: "low" | "medium" | "high";
  maxTokens: number;
}): Promise<T> {
  const response = await client.beta.messages.create({
    model: automation.model,
    max_tokens: opts.maxTokens,
    betas: ["server-side-fallback-2026-07-01"],
    fallbacks: "default", // güvenlik reddinde otomatik yedek model
    system: opts.system,
    output_config: {
      effort: opts.effort,
      format: { type: "json_schema", schema: opts.schema },
    },
    messages: [{ role: "user", content: opts.user }],
  });

  if (response.stop_reason === "refusal") {
    throw new Error(`Model isteği reddetti: ${response.stop_details?.category ?? "bilinmiyor"}`);
  }
  if (response.stop_reason === "max_tokens") {
    throw new Error("Yanıt max_tokens sınırında kesildi");
  }
  const text = response.content
    .filter((b): b is Anthropic.Beta.BetaTextBlock => b.type === "text")
    .map((b) => b.text)
    .join("");
  return opts.validator.parse(JSON.parse(text));
}

// ---------- 1) Editör: hangi haberler yazılacak? ----------

const SelectionSchema = z.object({
  stories: z.array(
    z.object({
      item_indexes: z.array(z.number().int()),
      category: z.enum(CATEGORY_SLUGS),
      is_breaking: z.boolean(),
      reason: z.string(),
    })
  ),
});
export type Selection = z.infer<typeof SelectionSchema>;

const selectionJsonSchema = {
  type: "object",
  additionalProperties: false,
  required: ["stories"],
  properties: {
    stories: {
      type: "array",
      items: {
        type: "object",
        additionalProperties: false,
        required: ["item_indexes", "category", "is_breaking", "reason"],
        properties: {
          item_indexes: { type: "array", items: { type: "integer" } },
          category: { type: "string", enum: CATEGORY_SLUGS },
          is_breaking: { type: "boolean" },
          reason: { type: "string" },
        },
      },
    },
  },
};

export async function selectStories(items: FeedItem[], recentTitles: string[], max: number): Promise<Selection> {
  const list = items
    .map((it, i) => `[${i}] (${it.source}) ${it.title}\n    ${it.summary.slice(0, 300)}`)
    .join("\n");

  return callJson({
    system: `Bir Türk haber sitesinin yazı işleri müdürüsün. Gelen ajans/RSS başlıklarından okuyucu için haber değeri en yüksek olanları seçersin.
Kurallar:
- En fazla ${max} haber seç. Haber değeri düşükse daha az seç, hiç seçmemek de geçerli.
- Aynı olayı anlatan farklı kaynakları tek haberde birleştir (item_indexes içine hepsini koy).
- Sitede son 48 saatte zaten yayınlanmış bir olayı, ciddi yeni gelişme yoksa tekrar seçme.
- Reklam, sponsorlu içerik, burç, tık tuzağı, kaynağı belirsiz söylentileri seçme.
- is_breaking: yalnızca gerçekten acil ve geniş kitleyi ilgilendiren gelişmeler (deprem, büyük kaza, önemli resmi karar, büyük ekonomik açıklama) için true.`,
    user: `Sitede son 48 saatte yayınlanan başlıklar:\n${recentTitles.map((t) => `- ${t}`).join("\n") || "(yok)"}\n\nYeni gelen haberler:\n${list}`,
    schema: selectionJsonSchema,
    validator: SelectionSchema,
    effort: "low",
    maxTokens: 4000,
  });
}

// ---------- 2) Muhabir: haberi yaz ----------

const ArticleSchema = z.object({
  title: z.string().min(10).max(140),
  summary: z.string().min(20).max(400),
  body_markdown: z.string().min(200),
  seo_description: z.string().max(170),
  tags: z.array(z.string()).max(8),
  category: z.enum(CATEGORY_SLUGS),
  risk: z.enum(["low", "high"]),
  risk_reason: z.string(),
  insufficient_sources: z.boolean(),
});
export type WrittenArticle = z.infer<typeof ArticleSchema>;

const articleJsonSchema = {
  type: "object",
  additionalProperties: false,
  required: ["title", "summary", "body_markdown", "seo_description", "tags", "category", "risk", "risk_reason", "insufficient_sources"],
  properties: {
    title: { type: "string", description: "Haber başlığı, 60-90 karakter ideal" },
    summary: { type: "string", description: "Spot: 1-2 cümlelik özet" },
    body_markdown: { type: "string", description: "Haber metni, Markdown. Ara başlıklar ## ile." },
    seo_description: { type: "string", description: "Arama motoru açıklaması, en fazla 160 karakter" },
    tags: { type: "array", items: { type: "string" } },
    category: { type: "string", enum: CATEGORY_SLUGS },
    risk: { type: "string", enum: ["low", "high"] },
    risk_reason: { type: "string" },
    insufficient_sources: { type: "boolean" },
  },
};

const WRITER_SYSTEM = `Deneyimli bir Türk haber muhabirisin. Sana verilen kaynak metinlerden özgün, doğru ve akıcı bir haber yazarsın.

DOĞRULUK (en önemli kural):
- Yalnızca kaynaklarda geçen bilgileri kullan. Rakam, isim, unvan, tarih, yer ve alıntı uydurma.
- Kaynakta olmayan bir alıntıyı tırnak içinde verme. Doğrudan alıntıyı yalnızca kaynakta aynen geçiyorsa kullan.
- İddia, suçlama ve doğrulanmamış bilgileri "iddia edildi", "öne sürüldü", "... açıkladı" gibi atıflarla ver; kesin bilgi gibi sunma.
- Kaynaklar çelişiyorsa bunu belirt. Bilgi yetersizse kısa yaz; boşluğu yorumla doldurma.
- Kaynaklar bir haber çıkarmaya yetmiyorsa insufficient_sources=true yap.

ÜSLUP:
- Ters piramit: en önemli bilgi ilk paragrafta (kim, ne, nerede, ne zaman).
- Sade, tarafsız haber dili. Kişisel yorum, tahmin, öğüt yok.
- Kalıp ifadelerden kaçın ("önemli bir adım", "dikkat çekti", "gözler ... çevrildi", "sonuç olarak", "unutulmamalıdır ki").
- Kaynak metni cümle cümle yeniden yazma; bilgileri derleyip kendi kurgunla anlat. Uzun bölümleri kopyalama.
- 250-600 kelime. Gerekirse 1-3 ara başlık (##). Madde işaretlerini yalnızca liste niteliğindeki bilgilerde kullan.
- Metnin sonuna "Kaynak:" satırı ekleme; kaynaklar sitede ayrıca gösteriliyor.
- Başlık doğru ve bilgilendirici olsun; tık tuzağı, abartı, büyük harf yok.

RİSK DEĞERLENDİRMESİ (risk alanı):
"high" seç ve nedenini yaz, eğer haber şunlardan birini içeriyorsa:
- Adı verilen bir kişiye yönelik suç, yolsuzluk, taciz vb. iddia veya soruşturma
- Çocuklar, mağdurlar, intihar, cinsel suçlar
- Siyasi açıdan hassas açıklamalar, seçim, terör, güvenlik operasyonları
- Sağlık/ilaç/tedavi iddiaları, finansal tavsiye niteliği taşıyan içerik
- Ölüm/yaralanma sayıları gibi sürekli değişen ve henüz netleşmemiş bilgiler
- Tek kaynağa dayanan, doğrulanmamış bilgiler
Diğer durumlarda "low".`;

export async function writeArticle(docs: SourceDoc[], category: string): Promise<WrittenArticle> {
  const sources = docs
    .map(
      (d, i) =>
        `<kaynak no="${i + 1}" ad="${d.source}" tarih="${d.published ?? ""}">\nBaşlık: ${d.title}\nÖzet: ${d.summary}\nMetin:\n${d.text.slice(0, 8000)}\n</kaynak>`
    )
    .join("\n\n");

  return callJson({
    system: WRITER_SYSTEM,
    user: `Önerilen kategori: ${category}\nBugünün tarihi: ${new Date().toLocaleDateString("tr-TR", { timeZone: "Europe/Istanbul" })}\n\n${sources}\n\nBu kaynaklardan haberi yaz.`,
    schema: articleJsonSchema,
    validator: ArticleSchema,
    effort: "medium",
    maxTokens: 16000,
  });
}
