import type { Article } from "@/lib/db";

/** Yayınla / Durdur / Reddet butonları. Token (Telegram linki) veya yönetici oturumu ile çalışır. */
export default function ReviewButtons({ a, token = "", back = "" }: { a: Article; token?: string; back?: string }) {
  const btn = (action: string, label: string, cls: string) => (
    <form method="post" action={`/api/review/${a.id}`}>
      <input type="hidden" name="action" value={action} />
      <input type="hidden" name="t" value={token} />
      <input type="hidden" name="back" value={back} />
      <button className={`btn ${cls}`} type="submit">{label}</button>
    </form>
  );
  return (
    <div className="row-actions">
      {a.status !== "published" && btn("publish", "Hemen yayınla", "btn-green")}
      {(a.status === "scheduled" || a.status === "published") && btn("hold", "Durdur (taslağa al)", "")}
      {a.status !== "rejected" && btn("reject", a.status === "published" ? "Yayından kaldır" : "Reddet", "btn-red")}
    </div>
  );
}

export function StatusBadge({ a }: { a: Article }) {
  const label = { draft: "Onay bekliyor", scheduled: "Otomatik yayınlanacak", published: "Yayında", rejected: "Reddedildi" }[a.status];
  return (
    <>
      <span className={`badge b-${a.status}`}>{label}</span>{" "}
      {a.risk === "high" && <span className="badge b-high" title={a.risk_reason}>Yüksek risk</span>}{" "}
      {a.is_breaking && <span className="badge b-high">Son dakika</span>}
    </>
  );
}
