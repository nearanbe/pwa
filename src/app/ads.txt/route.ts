import { site } from "@/lib/config";

export const dynamic = "force-dynamic";

/** AdSense için zorunlu ads.txt dosyası (ADSENSE_CLIENT'tan üretilir). */
export function GET() {
  const pub = site.adsenseClient.replace(/^ca-/, "");
  const body = pub ? `google.com, ${pub}, DIRECT, f08c47fec0942fa0\n` : "";
  return new Response(body, { headers: { "content-type": "text/plain; charset=utf-8" } });
}
