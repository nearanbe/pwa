import { notFound, redirect } from "next/navigation";
import { isAdmin } from "@/lib/auth";
import { getById } from "@/lib/db";
import { CATEGORIES } from "@/lib/config";

export default async function Edit({ params }: { params: Promise<{ id: string }> }) {
  if (!(await isAdmin())) redirect("/yonetim/giris");
  const a = getById(Number((await params).id));
  if (!a) notFound();
  return (
    <div className="admin">
      <h1>Haberi düzenle</h1>
      <form method="post" action="/api/admin/save">
        <input type="hidden" name="id" value={a.id} />
        <div className="field"><label>Başlık</label><input name="title" defaultValue={a.title} required /></div>
        <div className="field"><label>Spot</label><textarea name="summary" rows={3} defaultValue={a.summary} required /></div>
        <div className="field">
          <label>Kategori</label>
          <select name="category" defaultValue={a.category}>
            {CATEGORIES.map((c) => <option key={c.slug} value={c.slug}>{c.name}</option>)}
          </select>
        </div>
        <div className="field"><label>Metin (Markdown)</label><textarea name="body" rows={22} defaultValue={a.body} required /></div>
        <button className="btn btn-red" type="submit">Kaydet</button>
      </form>
    </div>
  );
}
