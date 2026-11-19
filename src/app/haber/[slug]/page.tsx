import type { Metadata } from "next";
import Link from "next/link";
import { notFound } from "next/navigation";
import { getPublishedBySlug, listPublished } from "@/lib/db";
import { categoryName, site } from "@/lib/config";
import { formatDate, readingMinutes, renderMarkdown } from "@/lib/format";
import { ArticleCard, imageFor } from "@/components/ArticleCard";
import Sidebar from "@/components/Sidebar";
import { Ad } from "@/components/Ads";

type Props = { params: Promise<{ slug: string }> };

export async function generateMetadata({ params }: Props): Promise<Metadata> {
  const a = getPublishedBySlug((await params).slug);
  if (!a) return {};
  return {
    title: a.title,
    description: a.seo_description || a.summary,
    keywords: a.tags,
    alternates: { canonical: `/haber/${a.slug}` },
    openGraph: {
      type: "article",
      title: a.title,
      description: a.summary,
      url: `/haber/${a.slug}`,
      publishedTime: a.published_at ? new Date(a.published_at).toISOString() : undefined,
      modifiedTime: new Date(a.updated_at).toISOString(),
      section: categoryName(a.category),
      tags: a.tags,
    },
    twitter: { card: "summary_large_image", title: a.title, description: a.summary },
  };
}

export default async function ArticlePage({ params }: Props) {
  const a = getPublishedBySlug((await params).slug);
  if (!a) notFound();

  const html = renderMarkdown(a.body);
  // İçeriğin ortasına bir reklam yerleştirmek için paragraflardan böl
  const parts = html.split("</p>");
  const mid = Math.min(3, Math.floor(parts.length / 2));
  const first = parts.slice(0, mid).join("</p>") + (mid > 0 ? "</p>" : "");
  const second = parts.slice(mid).join("</p>");

  const related = listPublished({ category: a.category, limit: 4 }).filter((r) => r.id !== a.id).slice(0, 3);
  const url = `${site.url}/haber/${a.slug}`;
  const image = a.image_url || `${url}/opengraph-image`;

  const jsonLd = {
    "@context": "https://schema.org",
    "@type": "NewsArticle",
    headline: a.title,
    description: a.summary,
    image: [image],
    datePublished: a.published_at ? new Date(a.published_at).toISOString() : undefined,
    dateModified: new Date(a.updated_at).toISOString(),
    articleSection: categoryName(a.category),
    keywords: a.tags.join(", "),
    mainEntityOfPage: url,
    author: { "@type": "Person", name: a.author },
    publisher: { "@type": "NewsMediaOrganization", name: site.name, url: site.url },
  };

  return (
    <div className="layout">
      <article className="article">
        <script type="application/ld+json" dangerouslySetInnerHTML={{ __html: JSON.stringify(jsonLd).replace(/</g, "\\u003c") }} />
        <div className="crumbs">
          <Link href="/">Ana Sayfa</Link> › <Link href={`/kategori/${a.category}`}>{categoryName(a.category)}</Link>
        </div>
        <h1>{a.title}</h1>
        <p className="spot">{a.summary}</p>
        <div className="meta">
          <span>{a.author}</span>
          <time dateTime={a.published_at ? new Date(a.published_at).toISOString() : undefined}>{formatDate(a.published_at)}</time>
          {a.updated_at - (a.published_at ?? 0) > 10 * 60_000 && <span>Güncelleme: {formatDate(a.updated_at)}</span>}
          <span>{readingMinutes(a.body)} dk okuma</span>
        </div>
        {/* eslint-disable-next-line @next/next/no-img-element */}
        <img className="hero-img" src={imageFor(a)} alt={a.title} width={1200} height={630} />
        <div className="content" dangerouslySetInnerHTML={{ __html: first }} />
        <Ad position="article" />
        <div className="content" dangerouslySetInnerHTML={{ __html: second }} />
        {a.sources.length > 0 && (
          <div className="sources">
            Kaynak:{" "}
            {a.sources.map((s, i) => (
              <span key={s.url}>
                {i > 0 && ", "}
                <a href={s.url} target="_blank" rel="nofollow noopener">{s.name}</a>
              </span>
            ))}
          </div>
        )}
        {a.tags.length > 0 && <div className="tags">{a.tags.map((t) => <span key={t}>#{t}</span>)}</div>}
        <div className="share">
          <a style={{ background: "#000" }} href={`https://x.com/intent/post?text=${encodeURIComponent(a.title)}&url=${encodeURIComponent(url)}`} target="_blank" rel="noopener">X'te paylaş</a>
          <a style={{ background: "#1877f2" }} href={`https://www.facebook.com/sharer/sharer.php?u=${encodeURIComponent(url)}`} target="_blank" rel="noopener">Facebook</a>
          <a style={{ background: "#25d366" }} href={`https://wa.me/?text=${encodeURIComponent(`${a.title} ${url}`)}`} target="_blank" rel="noopener">WhatsApp</a>
        </div>
        {related.length > 0 && (
          <>
            <h2 className="section-title">İlgili Haberler</h2>
            <div className="grid">{related.map((r) => <ArticleCard key={r.id} a={r} />)}</div>
          </>
        )}
      </article>
      <Sidebar />
    </div>
  );
}
