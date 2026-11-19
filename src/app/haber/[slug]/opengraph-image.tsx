import { ImageResponse } from "next/og";
import { getPublishedBySlug } from "@/lib/db";
import { categoryName, site } from "@/lib/config";

export const runtime = "nodejs";
export const size = { width: 1200, height: 630 };
export const contentType = "image/png";

const COLORS: Record<string, [string, string]> = {
  gundem: ["#b00016", "#5a000b"], politika: ["#1e3a8a", "#0f172a"], ekonomi: ["#065f46", "#022c22"],
  dunya: ["#0e7490", "#083344"], spor: ["#15803d", "#052e16"], teknoloji: ["#6d28d9", "#2e1065"],
  saglik: ["#0891b2", "#164e63"], yasam: ["#c2410c", "#431407"], "kultur-sanat": ["#a21caf", "#4a044e"],
  magazin: ["#db2777", "#500724"],
};

/** Her haber için otomatik kapak görseli (telif sorunu olmadan). */
export default async function Image({ params }: { params: Promise<{ slug: string }> }) {
  const a = getPublishedBySlug((await params).slug);
  const title = a?.title ?? site.name;
  const [c1, c2] = COLORS[a?.category ?? "gundem"] ?? COLORS.gundem;
  return new ImageResponse(
    (
      <div style={{ width: "100%", height: "100%", display: "flex", flexDirection: "column", justifyContent: "space-between", padding: 64, background: `linear-gradient(135deg, ${c1}, ${c2})`, color: "#fff" }}>
        <div style={{ display: "flex", alignItems: "center", gap: 16 }}>
          <div style={{ background: "#fff", color: c1, fontSize: 28, fontWeight: 800, padding: "6px 16px", textTransform: "uppercase" }}>
            {a ? categoryName(a.category) : "Haber"}
          </div>
          {a?.is_breaking && <div style={{ background: "#ffd400", color: "#000", fontSize: 28, fontWeight: 800, padding: "6px 16px" }}>SON DAKİKA</div>}
        </div>
        <div style={{ fontSize: title.length > 80 ? 54 : 64, fontWeight: 800, lineHeight: 1.15, display: "flex" }}>{title}</div>
        <div style={{ fontSize: 30, fontWeight: 700, opacity: 0.85, textTransform: "uppercase", display: "flex" }}>{site.name}</div>
      </div>
    ),
    size
  );
}
