import type { Metadata } from "next";

export const metadata: Metadata = { title: "Yönetim Girişi", robots: { index: false } };

export default async function Login({ searchParams }: { searchParams: Promise<{ hata?: string }> }) {
  const { hata } = await searchParams;
  return (
    <div className="page" style={{ maxWidth: 420 }}>
      <h1>Yönetim Girişi</h1>
      {hata && <div className="notice">Şifre hatalı.</div>}
      <form method="post" action="/api/admin/login">
        <div className="field"><label>Şifre</label><input type="password" name="password" required autoFocus /></div>
        <button className="btn btn-red" type="submit">Giriş</button>
      </form>
    </div>
  );
}
