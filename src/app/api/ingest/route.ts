import { NextResponse } from "next/server";
import { z } from "zod";
import { checkApiToken } from "@/lib/auth";
import { ingest } from "@/lib/ingest";

export const runtime = "nodejs";
export const maxDuration = 300;

const Body = z.object({
  items: z
    .array(
      z.object({
        title: z.string().min(1),
        link: z.string().url(),
        summary: z.string().default(""),
        source: z.string().default("Bilinmeyen"),
        published: z.string().optional(),
      })
    )
    .max(500),
});

/** n8n buraya RSS'ten topladığı haberleri gönderir. */
export async function POST(req: Request) {
  if (!checkApiToken(req)) return NextResponse.json({ error: "yetkisiz" }, { status: 401 });
  const parsed = Body.safeParse(await req.json().catch(() => null));
  if (!parsed.success) return NextResponse.json({ error: parsed.error.issues }, { status: 400 });
  try {
    return NextResponse.json(await ingest(parsed.data.items));
  } catch (err) {
    console.error("ingest hatası", err);
    return NextResponse.json({ error: (err as Error).message }, { status: 500 });
  }
}
