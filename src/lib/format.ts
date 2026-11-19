import { marked } from "marked";

const TZ = "Europe/Istanbul";

export function formatDate(ms: number | null): string {
  if (!ms) return "";
  return new Date(ms).toLocaleString("tr-TR", {
    timeZone: TZ, day: "numeric", month: "long", year: "numeric", hour: "2-digit", minute: "2-digit",
  });
}

export function timeAgo(ms: number | null): string {
  if (!ms) return "";
  const diff = Math.max(0, Date.now() - ms) / 60_000;
  if (diff < 1) return "az önce";
  if (diff < 60) return `${Math.floor(diff)} dk önce`;
  if (diff < 1440) return `${Math.floor(diff / 60)} saat önce`;
  return new Date(ms).toLocaleDateString("tr-TR", { timeZone: TZ, day: "numeric", month: "long" });
}

export function todayLong(): string {
  return new Date().toLocaleDateString("tr-TR", { timeZone: TZ, weekday: "long", day: "numeric", month: "long", year: "numeric" });
}

/** Markdown → HTML. Ham HTML kaçırılır, yalnızca http(s) linklere izin verilir. */
export function renderMarkdown(md: string): string {
  const safe = md.replace(/</g, "&lt;").replace(/>/g, "&gt;").replace(/^&gt;/gm, ">");
  const html = marked.parse(safe, { async: false, gfm: true, breaks: false }) as string;
  return html.replace(/href="(?!https?:\/\/)[^"]*"/gi, 'href="#"').replace(/<a /g, '<a rel="nofollow noopener" target="_blank" ');
}

export function readingMinutes(md: string): number {
  return Math.max(1, Math.round(md.split(/\s+/).length / 200));
}
