import { NextResponse } from "next/server";
import { z } from "zod";
import { checkApiToken } from "@/lib/auth";
import { markSocial } from "@/lib/db";

export const runtime = "nodejs";

export async function POST(req: Request) {
  if (!checkApiToken(req)) return NextResponse.json({ error: "yetkisiz" }, { status: 401 });
  const parsed = z.object({ ids: z.array(z.number().int()) }).safeParse(await req.json().catch(() => null));
  if (!parsed.success) return NextResponse.json({ error: "ids gerekli" }, { status: 400 });
  markSocial(parsed.data.ids);
  return NextResponse.json({ ok: true });
}
