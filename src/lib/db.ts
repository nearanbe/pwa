import Database from "better-sqlite3";
import fs from "node:fs";
import path from "node:path";
import { slugify } from "./slug";

export type ArticleStatus = "draft" | "scheduled" | "published" | "rejected";
export type Risk = "low" | "high";

export interface Source {
  name: string;
  url: string;
}

export interface Article {
  id: number;
  slug: string;
  title: string;
  summary: string;
  body: string; // markdown
  category: string;
  tags: string[];
  image_url: string | null;
  sources: Source[];
  status: ArticleStatus;
  risk: Risk;
  risk_reason: string;
  is_breaking: boolean;
  author: string;
  seo_description: string;
  publish_at: number | null;
  published_at: number | null;
  created_at: number;
  updated_at: number;
  social_shared: boolean;
}

type Row = Omit<Article, "tags" | "sources" | "is_breaking" | "social_shared"> & {
  tags: string;
  sources: string;
  is_breaking: number;
  social_shared: number;
};

let _db: Database.Database | null = null;

function db(): Database.Database {
  if (_db) return _db;
  const file = process.env.DATABASE_PATH ?? path.join(process.cwd(), "data", "haber.db");
  fs.mkdirSync(path.dirname(file), { recursive: true });
  const d = new Database(file);
  d.pragma("journal_mode = WAL");
  d.pragma("busy_timeout = 5000");
  d.exec(`
    CREATE TABLE IF NOT EXISTS articles (
      id INTEGER PRIMARY KEY AUTOINCREMENT,
      slug TEXT NOT NULL UNIQUE,
      title TEXT NOT NULL,
      summary TEXT NOT NULL,
      body TEXT NOT NULL,
      category TEXT NOT NULL,
      tags TEXT NOT NULL DEFAULT '[]',
      image_url TEXT,
      sources TEXT NOT NULL DEFAULT '[]',
      status TEXT NOT NULL,
      risk TEXT NOT NULL DEFAULT 'low',
      risk_reason TEXT NOT NULL DEFAULT '',
      is_breaking INTEGER NOT NULL DEFAULT 0,
      author TEXT NOT NULL,
      seo_description TEXT NOT NULL DEFAULT '',
      publish_at INTEGER,
      published_at INTEGER,
      created_at INTEGER NOT NULL,
      updated_at INTEGER NOT NULL,
      social_shared INTEGER NOT NULL DEFAULT 0
    );
    CREATE INDEX IF NOT EXISTS idx_articles_status_pub ON articles(status, published_at DESC);
    CREATE INDEX IF NOT EXISTS idx_articles_cat ON articles(category, status, published_at DESC);
    CREATE TABLE IF NOT EXISTS seen_links (
      url TEXT PRIMARY KEY,
      seen_at INTEGER NOT NULL
    );
  `);
  _db = d;
  return d;
}

function toArticle(r: Row): Article {
  return {
    ...r,
    tags: JSON.parse(r.tags),
    sources: JSON.parse(r.sources),
    is_breaking: !!r.is_breaking,
    social_shared: !!r.social_shared,
  };
}

/** Zamanı gelen planlı haberleri yayına alır. Okuma sorgularından önce çağrılır. */
export function promoteDue(): void {
  const now = Date.now();
  db()
    .prepare(
      `UPDATE articles SET status='published', published_at=publish_at, updated_at=?
       WHERE status='scheduled' AND publish_at <= ?`
    )
    .run(now, now);
}

export function listPublished(opts: { category?: string; limit?: number; offset?: number; breaking?: boolean } = {}): Article[] {
  promoteDue();
  const where = ["status='published'"];
  const params: unknown[] = [];
  if (opts.category) {
    where.push("category=?");
    params.push(opts.category);
  }
  if (opts.breaking) {
    where.push("is_breaking=1 AND published_at > ?");
    params.push(Date.now() - 6 * 3600_000);
  }
  params.push(opts.limit ?? 20, opts.offset ?? 0);
  const rows = db()
    .prepare(`SELECT * FROM articles WHERE ${where.join(" AND ")} ORDER BY published_at DESC LIMIT ? OFFSET ?`)
    .all(...params) as Row[];
  return rows.map(toArticle);
}

export function getPublishedBySlug(slug: string): Article | null {
  promoteDue();
  const r = db().prepare(`SELECT * FROM articles WHERE slug=? AND status='published'`).get(slug) as Row | undefined;
  return r ? toArticle(r) : null;
}

export function getById(id: number): Article | null {
  const r = db().prepare(`SELECT * FROM articles WHERE id=?`).get(id) as Row | undefined;
  return r ? toArticle(r) : null;
}

export function listForReview(): Article[] {
  promoteDue();
  const rows = db()
    .prepare(
      `SELECT * FROM articles WHERE status IN ('draft','scheduled')
       OR (status='published' AND published_at > ?)
       ORDER BY created_at DESC LIMIT 100`
    )
    .all(Date.now() - 24 * 3600_000) as Row[];
  return rows.map(toArticle);
}

export function recentTitles(hours = 48): string[] {
  const rows = db()
    .prepare(`SELECT title FROM articles WHERE created_at > ? AND status != 'rejected' ORDER BY created_at DESC LIMIT 200`)
    .all(Date.now() - hours * 3600_000) as { title: string }[];
  return rows.map((r) => r.title);
}

export interface NewArticle {
  title: string;
  summary: string;
  body: string;
  category: string;
  tags: string[];
  image_url?: string | null;
  sources: Source[];
  status: ArticleStatus;
  risk: Risk;
  risk_reason: string;
  is_breaking: boolean;
  author: string;
  seo_description: string;
  publish_at: number | null;
}

export function insertArticle(a: NewArticle): Article {
  const now = Date.now();
  let slug = slugify(a.title) || `haber-${now}`;
  const exists = db().prepare(`SELECT 1 FROM articles WHERE slug=?`);
  if (exists.get(slug)) slug = `${slug}-${now.toString(36)}`;
  const info = db()
    .prepare(
      `INSERT INTO articles (slug,title,summary,body,category,tags,image_url,sources,status,risk,risk_reason,
        is_breaking,author,seo_description,publish_at,published_at,created_at,updated_at)
       VALUES (@slug,@title,@summary,@body,@category,@tags,@image_url,@sources,@status,@risk,@risk_reason,
        @is_breaking,@author,@seo_description,@publish_at,@published_at,@now,@now)`
    )
    .run({
      ...a,
      slug,
      tags: JSON.stringify(a.tags),
      sources: JSON.stringify(a.sources),
      image_url: a.image_url ?? null,
      is_breaking: a.is_breaking ? 1 : 0,
      published_at: a.status === "published" ? now : null,
      now,
    });
  return getById(Number(info.lastInsertRowid))!;
}

export type ReviewAction = "publish" | "reject" | "hold";

export function applyAction(id: number, action: ReviewAction): Article | null {
  const now = Date.now();
  if (action === "publish") {
    db()
      .prepare(`UPDATE articles SET status='published', published_at=COALESCE(published_at, ?), publish_at=NULL, updated_at=? WHERE id=?`)
      .run(now, now, id);
  } else if (action === "reject") {
    // Yayından kaldırır / reddeder
    db().prepare(`UPDATE articles SET status='rejected', publish_at=NULL, updated_at=? WHERE id=?`).run(now, id);
  } else {
    // Otomatik yayını durdurur, taslağa çeker
    db()
      .prepare(`UPDATE articles SET status='draft', publish_at=NULL, published_at=NULL, updated_at=? WHERE id=? AND status IN ('scheduled','published')`)
      .run(now, id);
  }
  return getById(id);
}

export function updateArticle(id: number, fields: { title: string; summary: string; body: string; category: string }): void {
  db()
    .prepare(`UPDATE articles SET title=@title, summary=@summary, body=@body, category=@category, updated_at=@now WHERE id=@id`)
    .run({ ...fields, id, now: Date.now() });
}

export function pendingSocial(): Article[] {
  promoteDue();
  const rows = db()
    .prepare(`SELECT * FROM articles WHERE status='published' AND social_shared=0 AND published_at > ? ORDER BY published_at ASC LIMIT 20`)
    .all(Date.now() - 24 * 3600_000) as Row[];
  return rows.map(toArticle);
}

export function markSocial(ids: number[]): void {
  const stmt = db().prepare(`UPDATE articles SET social_shared=1 WHERE id=?`);
  db().transaction(() => ids.forEach((id) => stmt.run(id)))();
}

export function filterUnseen(urls: string[]): Set<string> {
  const stmt = db().prepare(`SELECT 1 FROM seen_links WHERE url=?`);
  return new Set(urls.filter((u) => !stmt.get(u)));
}

export function markSeen(urls: string[]): void {
  const now = Date.now();
  const stmt = db().prepare(`INSERT OR IGNORE INTO seen_links (url, seen_at) VALUES (?, ?)`);
  db().transaction(() => urls.forEach((u) => stmt.run(u, now)))();
  db().prepare(`DELETE FROM seen_links WHERE seen_at < ?`).run(now - 14 * 86400_000);
}
