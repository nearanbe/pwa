import { NextResponse } from "next/server";
import { isAdmin, verifyAction } from "@/lib/auth";
import { applyAction, type ReviewAction } from "@/lib/db";

export const runtime = "nodejs";

/** Onay sayfasındaki ve yönetim panelindeki butonlar buraya POST eder. */
export async function POST(req: Request, ctx: { params: Promise<{ id: string }> }) {
  const id = Number((await ctx.params).id);
  const form = await req.formData();
  const action = String(form.get("action")) as ReviewAction;
  const token = String(form.get("t") ?? "");
  const back = String(form.get("back") ?? "");
  if (!["publish", "reject", "hold"].includes(action)) {
    return NextResponse.json({ error: "geçersiz işlem" }, { status: 400 });
  }
  if (!(await isAdmin()) && !verifyAction(id, token)) {
    return NextResponse.json({ error: "yetkisiz veya linkin süresi dolmuş" }, { status: 401 });
  }
  applyAction(id, action);
  const target = back.startsWith("/") ? back : `/onay/${id}?t=${encodeURIComponent(token)}&ok=1`;
  return NextResponse.redirect(new URL(target, req.url), 303);
}
