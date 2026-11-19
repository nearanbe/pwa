import { NextResponse } from "next/server";
import { isAdmin } from "@/lib/auth";
import { updateArticle } from "@/lib/db";
import { CATEGORY_SLUGS } from "@/lib/config";

export const runtime = "nodejs";

export async function POST(req: Request) {
  if (!(await isAdmin())) return NextResponse.json({ error: "yetkisiz" }, { status: 401 });
  const f = await req.formData();
  const id = Number(f.get("id"));
  const category = String(f.get("category"));
  updateArticle(id, {
    title: String(f.get("title") ?? "").trim(),
    summary: String(f.get("summary") ?? "").trim(),
    body: String(f.get("body") ?? ""),
    category: (CATEGORY_SLUGS as readonly string[]).includes(category) ? category : "gundem",
  });
  return NextResponse.redirect(new URL("/yonetim?kaydedildi=1", req.url), 303);
}
