import type { Metadata } from "next";
import Link from "next/link";
import Script from "next/script";
import { CATEGORIES, site } from "@/lib/config";
import { todayLong } from "@/lib/format";
import "./globals.css";

export const dynamic = "force-dynamic";

export async function generateMetadata(): Promise<Metadata> {
  return {
    metadataBase: new URL(site.url),
    title: { default: `${site.name} - Son Dakika Haberler`, template: `%s - ${site.name}` },
    description: site.description,
    alternates: { types: { "application/rss+xml": "/rss.xml" } },
    openGraph: { siteName: site.name, locale: "tr_TR", type: "website" },
    robots: { index: true, follow: true, "max-image-preview": "large" },
  };
}

export default function RootLayout({ children }: { children: React.ReactNode }) {
  const adClient = site.adsenseClient;
  return (
    <html lang="tr">
      <body>
        {adClient ? (
          <Script
            id="adsense"
            async
            strategy="afterInteractive"
            crossOrigin="anonymous"
            src={`https://pagead2.googlesyndication.com/pagead/js/adsbygoogle.js?client=${adClient}`}
          />
        ) : null}
        <header className="site-header">
          <div className="topbar">
            <div className="wrap">
              <span>{todayLong()}</span>
              <Link href="/rss.xml">RSS</Link>
            </div>
          </div>
          <div className="wrap brand-row">
            <Link href="/" className="logo">
              {site.name}
            </Link>
          </div>
          <nav className="catnav" aria-label="Kategoriler">
            <div className="wrap">
              <Link href="/">Ana Sayfa</Link>
              {CATEGORIES.map((c) => (
                <Link key={c.slug} href={`/kategori/${c.slug}`}>
                  {c.name}
                </Link>
              ))}
            </div>
          </nav>
        </header>
        <main className="wrap main">{children}</main>
        <footer className="site-footer">
          <div className="wrap">
            <div className="footer-links">
              <Link href="/sayfa/kunye">Künye</Link>
              <Link href="/sayfa/hakkimizda">Hakkımızda</Link>
              <Link href="/sayfa/iletisim">İletişim</Link>
              <Link href="/sayfa/gizlilik">Gizlilik ve KVKK</Link>
              <Link href="/sayfa/cerez-politikasi">Çerez Politikası</Link>
              <Link href="/sayfa/kullanim-kosullari">Kullanım Koşulları</Link>
            </div>
            <p>
              © {new Date().getFullYear()} {site.name}. Haberlerin tüm hakları saklıdır; kaynak gösterilmeden kopyalanamaz.
            </p>
          </div>
        </footer>
      </body>
    </html>
  );
}
