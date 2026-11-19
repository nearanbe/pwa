import crypto from "node:crypto";
import { cookies } from "next/headers";

function secret(): string {
  const s = process.env.ADMIN_SECRET;
  if (!s || s.length < 16) throw new Error("ADMIN_SECRET en az 16 karakter olmalı (.env)");
  return s;
}

function hmac(data: string): string {
  return crypto.createHmac("sha256", secret()).update(data).digest("base64url");
}

function safeEqual(a: string, b: string): boolean {
  const ab = Buffer.from(a);
  const bb = Buffer.from(b);
  return ab.length === bb.length && crypto.timingSafeEqual(ab, bb);
}

/** n8n → site istekleri: "Authorization: Bearer <API_TOKEN>" */
export function checkApiToken(req: Request): boolean {
  const token = process.env.API_TOKEN;
  if (!token) return false;
  const header = req.headers.get("authorization") ?? "";
  return safeEqual(header, `Bearer ${token}`);
}

/** Telegram'a giden onay linkleri için imzalı, 24 saat geçerli token */
export function signAction(id: number, ttlHours = 24): string {
  const exp = Date.now() + ttlHours * 3600_000;
  return `${exp}.${hmac(`action:${id}:${exp}`)}`;
}

export function verifyAction(id: number, token: string): boolean {
  const [expStr, sig] = token.split(".");
  const exp = Number(expStr);
  if (!exp || !sig || exp < Date.now()) return false;
  return safeEqual(sig, hmac(`action:${id}:${exp}`));
}

const COOKIE = "yonetim";

export function checkPassword(pw: string): boolean {
  const expected = process.env.ADMIN_PASSWORD ?? "";
  return expected.length > 0 && safeEqual(pw, expected);
}

export function sessionValue(days = 30): string {
  const exp = Date.now() + days * 86400_000;
  return `${exp}.${hmac(`session:${exp}`)}`;
}

export async function isAdmin(): Promise<boolean> {
  const v = (await cookies()).get(COOKIE)?.value;
  if (!v) return false;
  const [expStr, sig] = v.split(".");
  const exp = Number(expStr);
  if (!exp || !sig || exp < Date.now()) return false;
  return safeEqual(sig, hmac(`session:${exp}`));
}

export const ADMIN_COOKIE = COOKIE;
