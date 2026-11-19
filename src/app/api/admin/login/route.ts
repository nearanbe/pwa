import { NextResponse } from "next/server";
import { ADMIN_COOKIE, checkPassword, sessionValue } from "@/lib/auth";

export const runtime = "nodejs";

export async function POST(req: Request) {
  const form = await req.formData();
  if (!checkPassword(String(form.get("password") ?? ""))) {
    return NextResponse.redirect(new URL("/yonetim/giris?hata=1", req.url), 303);
  }
  const res = NextResponse.redirect(new URL("/yonetim", req.url), 303);
  res.cookies.set(ADMIN_COOKIE, sessionValue(), {
    httpOnly: true,
    secure: process.env.NODE_ENV === "production",
    sameSite: "lax",
    path: "/",
    maxAge: 30 * 86400,
  });
  return res;
}
