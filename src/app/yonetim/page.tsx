import type { Metadata } from "next";
import Link from "next/link";
import { redirect } from "next/navigation";
import { isAdmin } from "@/lib/auth";
import { listForReview } from "@/lib/db";
import { categoryName } from "@/lib/config";
import { formatDate } from "@/lib/format";
import ReviewButtons, { StatusBadge } from "@/components/ReviewButtons";

export const metadata: Metadata = { title: "Yönetim", robots: { index: false } };

export default async function Admin({ searchParams }: { searchParams: Promise<{ kaydedildi?: string }> }) {
  if (!(await isAdmin())) redirect("/yonetim/giris");
  const items = listForReview();
  const { kaydedildi } = await searchParams;
  const pending = items.filter((a) => a.status === "draft").length;

  return (
    <div className="admin">
      <div style={{ display: "flex", justifyContent: "space-between", alignItems: "center" }}>
        <h1 style={{ margin: 0 }}>Yönetim</h1>
        <form method="post" action="/api/admin/logout"><button className="btn">Çıkış</button></form>
      </div>
      {kaydedildi && <div className="notice">Değişiklikler kaydedildi.</div>}
      <p>{pending} haber onay bekliyor. Taslaklar, planlananlar ve son 24 saatte yayınlananlar listeleniyor.</p>
      <table>
        <thead><tr><th>Haber</th><th>Durum</th><th>Zaman</th><th>İşlem</th></tr></thead>
        <tbody>
          {items.map((a) => (
            <tr key={a.id}>
              <td>
                <strong>{a.title}</strong><br />
                <small>{categoryName(a.category)} · {a.sources.map((s) => s.name).join(", ")}</small>
                {a.risk === "high" && <><br /><small style={{ color: "#991b1b" }}>{a.risk_reason}</small></>}
              </td>
              <td><StatusBadge a={a} /></td>
              <td><small>{a.status === "scheduled" ? `Yayın: ${formatDate(a.publish_at)}` : formatDate(a.published_at ?? a.created_at)}</small></td>
              <td>
                <ReviewButtons a={a} back="/yonetim" />
                <div className="row-actions" style={{ marginTop: 6 }}>
                  <Link className="btn" href={`/onay/${a.id}`}>Önizle</Link>
                  <Link className="btn" href={`/yonetim/duzenle/${a.id}`}>Düzenle</Link>
                </div>
              </td>
            </tr>
          ))}
        </tbody>
      </table>
    </div>
  );
}
