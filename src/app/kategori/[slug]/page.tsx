import type { Metadata } from "next";
import Link from "next/link";
import { Fragment } from "react";
import { notFound } from "next/navigation";
import { listPublished } from "@/lib/db";
import { CATEGORIES } from "@/lib/config";
import { ArticleCard } from "@/components/ArticleCard";
import Sidebar from "@/components/Sidebar";
import { Ad } from "@/components/Ads";

const PER_PAGE = 18;

type Props = { params: Promise<{ slug: string }>; searchParams: Promise<{ s?: string }> };

export async function generateMetadata({ params }: Props): Promise<Metadata> {
  const { slug } = await params;
  const cat = CATEGORIES.find((c) => c.slug === slug);
  if (!cat) return {};
  return { title: `${cat.name} Haberleri`, description: `En son ${cat.name.toLowerCase()} haberleri ve güncel gelişmeler.`, alternates: { canonical: `/kategori/${cat.slug}` } };
}

export default async function CategoryPage({ params, searchParams }: Props) {
  const { slug } = await params;
  const cat = CATEGORIES.find((c) => c.slug === slug);
  if (!cat) notFound();
  const page = Math.max(1, Number((await searchParams).s) || 1);
  const items = listPublished({ category: slug, limit: PER_PAGE + 1, offset: (page - 1) * PER_PAGE });
  const hasNext = items.length > PER_PAGE;

  return (
    <div className="layout">
      <div>
        <h1 className="section-title">{cat.name} Haberleri</h1>
        {items.length === 0 && <p>Bu kategoride henüz haber yok.</p>}
        <div className="grid">
          {items.slice(0, PER_PAGE).map((a, i) => (
            <Fragment key={a.id}>
              <ArticleCard a={a} />
              {i === 5 && <div style={{ gridColumn: "1 / -1" }}><Ad position="top" /></div>}
            </Fragment>
          ))}
        </div>
        <div className="pager">
          {page > 1 && <Link className="btn" href={`/kategori/${slug}?s=${page - 1}`}>← Önceki</Link>}
          {hasNext && <Link className="btn" href={`/kategori/${slug}?s=${page + 1}`}>Sonraki →</Link>}
        </div>
      </div>
      <Sidebar />
    </div>
  );
}
