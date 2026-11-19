import type { Metadata } from "next";
import { getById } from "@/lib/db";
import { isAdmin, verifyAction } from "@/lib/auth";
import { formatDate, renderMarkdown } from "@/lib/format";
import { categoryName } from "@/lib/config";
import ReviewButtons, { StatusBadge } from "@/components/ReviewButtons";

export const metadata: Metadata = { robots: { index: false, follow: false } };

type Props = { params: Promise<{ id: string }>; searchParams: Promise<{ t?: string; ok?: string }> };

/** Telegram bildirimindeki linkin açtığı önizleme + onay sayfası. */
export default async function ReviewPage({ params, searchParams }: Props) {
  const id = Number((await params).id);
  const { t = "", ok } = await searchParams;
  if (!verifyAction(id, t) && !(await isAdmin())) {
    return <div className="page"><h1>Link geçersiz</h1><p>Linkin süresi dolmuş olabilir. <a href="/yonetim">Yönetim paneline</a> giriş yapın.</p></div>;
  }
  const a = getById(id);
  if (!a) return <div className="page"><h1>Haber bulunamadı</h1></div>;

  return (
    <div className="page" style={{ maxWidth: 900 }}>
      {ok && <div className="notice">İşlem kaydedildi.</div>}
      <p><StatusBadge a={a} /></p>
      {a.status === "scheduled" && <p className="notice">Bu haber <strong>{formatDate(a.publish_at)}</strong> saatinde otomatik yayınlanacak. Durdurmak için “Durdur”a basın.</p>}
      {a.risk === "high" && <p className="notice"><strong>Neden elle onay gerekiyor:</strong> {a.risk_reason}</p>}
      <ReviewButtons a={a} token={t} />
      <hr />
      <small>{categoryName(a.category)}</small>
      <h1>{a.title}</h1>
      <p><strong>{a.summary}</strong></p>
      <div className="content" dangerouslySetInnerHTML={{ __html: renderMarkdown(a.body) }} />
      <div className="sources">Kaynaklar: {a.sources.map((s) => <div key={s.url}><a href={s.url} target="_blank" rel="noopener">{s.name} – {s.url}</a></div>)}</div>
      <hr />
      <ReviewButtons a={a} token={t} />
    </div>
  );
}
