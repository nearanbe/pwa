import { NextResponse } from "next/server";
import { checkApiToken } from "@/lib/auth";
import { pendingSocial } from "@/lib/db";
import { site } from "@/lib/config";

export const runtime = "nodejs";

/** Yayına girmiş ama henüz sosyal medyada paylaşılmamış haberler. */
export async function GET(req: Request) {
  if (!checkApiToken(req)) return NextResponse.json({ error: "yetkisiz" }, { status: 401 });
  const items = pendingSocial().map((a) => ({
    id: a.id,
    title: a.title,
    summary: a.summary,
    url: `${site.url}/haber/${a.slug}`,
    image: `${site.url}/haber/${a.slug}/opengraph-image`,
    tags: a.tags,
    is_breaking: a.is_breaking,
  }));
  return NextResponse.json({ items });
}
