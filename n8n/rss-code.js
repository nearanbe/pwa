// n8n "Code" düğümünde çalışır (Mode: Run Once for All Items).
// Kaynak listesini buradan düzenleyebilirsin.
const FEEDS = [
  { name: "AA", url: "https://www.aa.com.tr/tr/rss/default?cat=guncel" },
  { name: "TRT Haber", url: "https://www.trthaber.com/sondakika_articles.rss" },
  { name: "NTV", url: "https://www.ntv.com.tr/son-dakika.rss" },
  { name: "CNN Türk", url: "https://www.cnnturk.com/feed/rss/all/news" },
  { name: "Hürriyet", url: "https://www.hurriyet.com.tr/rss/anasayfa" },
  { name: "BBC Türkçe", url: "https://feeds.bbci.co.uk/turkce/rss.xml" },
  { name: "DW Türkçe", url: "https://rss.dw.com/rdf/rss-tur-all" },
];
const MAX_AGE_HOURS = 6;

function decode(s) {
  return (s || "")
    .replace(/<!\[CDATA\[([\s\S]*?)\]\]>/g, "$1")
    .replace(/<[^>]+>/g, " ")
    .replace(/&lt;/g, "<").replace(/&gt;/g, ">").replace(/&quot;/g, '"').replace(/&#39;|&apos;/g, "'")
    .replace(/&#(\d+);/g, (_, n) => String.fromCharCode(Number(n)))
    .replace(/&nbsp;/g, " ").replace(/&amp;/g, "&")
    .replace(/\s+/g, " ").trim();
}
function tag(block, name) {
  const m = block.match(new RegExp(`<${name}[^>]*>([\\s\\S]*?)</${name}>`, "i"));
  return m ? m[1] : "";
}
function parse(xml, source) {
  const blocks = xml.match(/<item[\s>][\s\S]*?<\/item>|<entry[\s>][\s\S]*?<\/entry>/gi) || [];
  return blocks.map((b) => {
    let link = decode(tag(b, "link"));
    if (!link) { const m = b.match(/<link[^>]*href="([^"]+)"/i); link = m ? m[1] : ""; }
    if (!link) link = decode(tag(b, "guid"));
    return {
      title: decode(tag(b, "title")),
      link: link.trim(),
      summary: decode(tag(b, "description") || tag(b, "summary") || tag(b, "content")).slice(0, 1000),
      source,
      published: decode(tag(b, "pubDate") || tag(b, "published") || tag(b, "dc:date") || tag(b, "updated")),
    };
  }).filter((i) => i.title && /^https?:\/\//.test(i.link));
}

const items = [];
const errors = [];
const cutoff = Date.now() - MAX_AGE_HOURS * 3600 * 1000;
for (const f of FEEDS) {
  try {
    const xml = await this.helpers.httpRequest({ url: f.url, timeout: 15000, headers: { "User-Agent": "Mozilla/5.0" } });
    for (const it of parse(String(xml), f.name)) {
      const t = Date.parse(it.published);
      if (!isNaN(t) && t < cutoff) continue; // çok eski haberleri atla
      items.push(it);
    }
  } catch (e) {
    errors.push(`${f.name}: ${e.message}`);
  }
}
return [{ json: { items: items.slice(0, 300), errors } }];
