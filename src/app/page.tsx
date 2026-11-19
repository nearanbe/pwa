import Link from "next/link";
import { listPublished } from "@/lib/db";
import { CATEGORIES } from "@/lib/config";
import { ArticleCard } from "@/components/ArticleCard";
import { Ad } from "@/components/Ads";
import Sidebar from "@/components/Sidebar";

export default function Home() {
  const breaking = listPublished({ breaking: true, limit: 5 });
  const latest = listPublished({ limit: 13 });
  const [hero, ...rest] = latest;

  if (!hero) {
    return <div className="page"><h1>Henüz haber yok</h1><p>İlk haberler yayınlandığında burada görünecek.</p></div>;
  }

  const sections = CATEGORIES.map((c) => ({ c, items: listPublished({ category: c.slug, limit: 3 }) })).filter((s) => s.items.length);

  return (
    <>
      {breaking.length > 0 && (
        <div className="breaking">
          <strong>SON DAKİKA</strong>
          <div className="items">
            {breaking.map((a) => (
              <Link key={a.id} href={`/haber/${a.slug}`}>{a.title}</Link>
            ))}
          </div>
        </div>
      )}
      <div className="home-top">
        <ArticleCard a={hero} variant="hero" />
        <div className="side-list">
          <h2>Öne Çıkanlar</h2>
          {rest.slice(0, 6).map((a) => <ArticleCard key={a.id} a={a} variant="compact" />)}
        </div>
      </div>
      <Ad position="top" />
      <div className="layout">
        <div>
          <div className="grid">
            {rest.slice(6).map((a) => <ArticleCard key={a.id} a={a} />)}
          </div>
          {sections.map(({ c, items }) => (
            <section key={c.slug}>
              <h2 className="section-title"><Link href={`/kategori/${c.slug}`}>{c.name}</Link></h2>
              <div className="grid">
                {items.map((a) => <ArticleCard key={a.id} a={a} />)}
              </div>
            </section>
          ))}
        </div>
        <Sidebar />
      </div>
    </>
  );
}
