import type { Metadata } from "next";
import { notFound } from "next/navigation";
import { site } from "@/lib/config";

type Props = { params: Promise<{ page: string }> };

function missing(v: string) {
  return v || <em style={{ color: "#b00" }}>(.env dosyasında doldurulmalı)</em>;
}

const PAGES: Record<string, { title: string; render: () => React.ReactNode }> = {
  kunye: {
    title: "Künye",
    render: () => (
      <table>
        <tbody>
          <tr><td>Yayın Adı</td><td>{site.name}</td></tr>
          <tr><td>Alan Adı</td><td>{site.url}</td></tr>
          <tr><td>İmtiyaz Sahibi</td><td>{missing(site.owner)}</td></tr>
          <tr><td>Sorumlu Yazı İşleri Müdürü</td><td>{missing(site.editor)}</td></tr>
          <tr><td>Yönetim Yeri Adresi</td><td>{missing(site.address)}</td></tr>
          <tr><td>E-posta</td><td>{missing(site.email)}</td></tr>
          <tr><td>Telefon</td><td>{missing(site.phone)}</td></tr>
          <tr><td>Yer Sağlayıcı</td><td>{missing(site.hostingProvider)}</td></tr>
        </tbody>
      </table>
    ),
  },
  hakkimizda: {
    title: "Hakkımızda",
    render: () => (
      <>
        <p>{site.name}, Türkiye ve dünyadan güncel gelişmeleri hızlı, tarafsız ve anlaşılır biçimde okurlarına ulaştırmayı amaçlayan bağımsız bir haber sitesidir.</p>
        <p>Haberlerimiz ulusal haber ajansları, resmi kurum açıklamaları ve güvenilir yayın kuruluşlarının aktardığı bilgilerden derlenir; her haberin altında kaynakları belirtilir. Hatalı olduğunu düşündüğünüz bir içerik için <a href="/sayfa/iletisim">iletişim</a> sayfasından bize ulaşabilirsiniz; düzeltme talepleri en kısa sürede değerlendirilir.</p>
      </>
    ),
  },
  iletisim: {
    title: "İletişim",
    render: () => (
      <>
        <p>Haber ihbarı, düzeltme ve cevap hakkı talepleri, reklam ve iş birliği için bize ulaşın:</p>
        <p><strong>E-posta:</strong> {missing(site.email)}<br /><strong>Telefon:</strong> {missing(site.phone)}<br /><strong>Adres:</strong> {missing(site.address)}</p>
      </>
    ),
  },
  gizlilik: {
    title: "Gizlilik Politikası ve KVKK Aydınlatma Metni",
    render: () => (
      <>
        <p>Bu metin, 6698 sayılı Kişisel Verilerin Korunması Kanunu (KVKK) kapsamında, {site.name} ({site.url}) ziyaretçilerinin kişisel verilerinin işlenmesine ilişkin olarak veri sorumlusu sıfatıyla {missing(site.owner)} tarafından hazırlanmıştır.</p>
        <h2>İşlenen veriler</h2>
        <p>Sitemizi ziyaret ettiğinizde IP adresiniz, tarayıcı ve cihaz bilgileriniz, ziyaret ettiğiniz sayfalar ve çerez verileri otomatik olarak işlenebilir. İletişim formu veya e-posta yoluyla bize ulaşmanız halinde ad, e-posta ve mesaj içeriğiniz işlenir.</p>
        <h2>İşleme amaçları ve hukuki sebep</h2>
        <p>Veriler; sitenin güvenli ve verimli çalışması, ziyaret istatistiklerinin oluşturulması, reklamların gösterilmesi ve yasal yükümlülüklerin (5651 sayılı Kanun kapsamında trafik kayıtlarının saklanması dahil) yerine getirilmesi amaçlarıyla, KVKK m.5/2 kapsamındaki hukuki sebeplere ve gerekli hallerde açık rızanıza dayanılarak işlenir.</p>
        <h2>Reklamlar ve üçüncü taraflar</h2>
        <p>Sitemizde Google AdSense reklamları gösterilmektedir. Google dahil üçüncü taraf sağlayıcılar, kullanıcının bu siteye veya diğer sitelere yaptığı önceki ziyaretlere dayalı reklamlar sunmak için çerezleri kullanır. Google&apos;ın reklam çerezlerini kullanması, Google ve iş ortaklarının sitemize ve/veya internetteki diğer sitelere yapılan ziyaretlere dayalı olarak kullanıcılara reklam sunmasını sağlar. Kişiselleştirilmiş reklamcılığı <a href="https://adssettings.google.com" target="_blank" rel="noopener">Google Reklam Ayarları</a> üzerinden devre dışı bırakabilirsiniz. Ayrıntılı bilgi için: <a href="https://policies.google.com/technologies/partner-sites" target="_blank" rel="noopener">Google&apos;ın iş ortağı sitelerdeki veri kullanımı</a>.</p>
        <h2>Haklarınız</h2>
        <p>KVKK m.11 uyarınca verilerinizin işlenip işlenmediğini öğrenme, düzeltilmesini veya silinmesini isteme ve diğer haklarınızı kullanmak için {missing(site.email)} adresine başvurabilirsiniz.</p>
      </>
    ),
  },
  "cerez-politikasi": {
    title: "Çerez Politikası",
    render: () => (
      <>
        <p>{site.name}, sitenin çalışması, ziyaret istatistikleri ve reklam gösterimi için çerezler kullanır.</p>
        <ul>
          <li><strong>Zorunlu çerezler:</strong> Sitenin temel işlevleri için gereklidir.</li>
          <li><strong>Analitik çerezler:</strong> Ziyaret sayıları ve kullanım istatistikleri için kullanılır.</li>
          <li><strong>Reklam çerezleri:</strong> Google AdSense tarafından ilgi alanlarınıza uygun reklam göstermek için kullanılır.</li>
        </ul>
        <p>Çerez tercihlerinizi sayfa açılışında gösterilen onay penceresinden veya tarayıcı ayarlarınızdan değiştirebilirsiniz.</p>
      </>
    ),
  },
  "kullanim-kosullari": {
    title: "Kullanım Koşulları",
    render: () => (
      <>
        <p>{site.name} üzerinde yayımlanan içeriklerin tüm hakları saklıdır. İçerikler, kaynak gösterilmeden ve link verilmeden kopyalanamaz, çoğaltılamaz.</p>
        <p>Haberler, yayımlandıkları an itibarıyla mevcut bilgilere dayanır; gelişmelere göre güncellenebilir. Sitede yer alan bilgiler yatırım, sağlık veya hukuki tavsiye niteliği taşımaz.</p>
        <p>Hatalı veya hak ihlali içerdiğini düşündüğünüz içerikleri {missing(site.email)} adresine bildirebilirsiniz.</p>
      </>
    ),
  },
};

export async function generateMetadata({ params }: Props): Promise<Metadata> {
  const p = PAGES[(await params).page];
  return p ? { title: p.title } : {};
}

export default async function StaticPage({ params }: Props) {
  const p = PAGES[(await params).page];
  if (!p) notFound();
  return (
    <div className="page">
      <h1>{p.title}</h1>
      {p.render()}
    </div>
  );
}
