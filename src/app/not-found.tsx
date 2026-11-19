import Link from "next/link";

export default function NotFound() {
  return (
    <div className="page">
      <h1>Sayfa bulunamadı</h1>
      <p>Aradığınız haber kaldırılmış veya adresi değişmiş olabilir. <Link href="/" style={{ color: "#d0021b" }}>Ana sayfaya dönün.</Link></p>
    </div>
  );
}
