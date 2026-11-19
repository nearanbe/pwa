import { listPublished } from "@/lib/db";
import { ArticleCard } from "./ArticleCard";
import { Ad } from "./Ads";

export default function Sidebar() {
  const latest = listPublished({ limit: 10 });
  return (
    <aside>
      <div className="sticky-side">
        <Ad position="sidebar" />
        <div className="side-list">
          <h2>Son Haberler</h2>
          {latest.map((a) => (
            <ArticleCard key={a.id} a={a} variant="compact" />
          ))}
        </div>
      </div>
    </aside>
  );
}
