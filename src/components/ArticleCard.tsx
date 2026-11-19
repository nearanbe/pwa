import Link from "next/link";
import type { Article } from "@/lib/db";
import { categoryName } from "@/lib/config";
import { timeAgo } from "@/lib/format";

export function imageFor(a: Article): string {
  return a.image_url || `/haber/${a.slug}/opengraph-image`;
}

export function ArticleCard({ a, variant = "default" }: { a: Article; variant?: "default" | "hero" | "compact" }) {
  if (variant === "compact") {
    return (
      <Link href={`/haber/${a.slug}`} className="card-compact">
        <span className="time">{timeAgo(a.published_at)}</span>
        <span className="t">{a.title}</span>
      </Link>
    );
  }
  return (
    <Link href={`/haber/${a.slug}`} className={`card card-${variant}`}>
      <div className="thumb">
        {/* eslint-disable-next-line @next/next/no-img-element */}
        <img src={imageFor(a)} alt={a.title} loading={variant === "hero" ? "eager" : "lazy"} width={1200} height={630} />
        <span className="cat">{categoryName(a.category)}</span>
      </div>
      <div className="body">
        <h3>{a.title}</h3>
        {variant === "hero" && <p>{a.summary}</p>}
        <span className="time">{timeAgo(a.published_at)}</span>
      </div>
    </Link>
  );
}
