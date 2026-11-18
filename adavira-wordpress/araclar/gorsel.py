#!/usr/bin/env python3
"""
AdaVira kapak görseli aracı.

Cloudflare Workers AI (FLUX.2 [dev]) ile görsel üretir, 1600x900 JPEG yapar, adavira.com ortam
kütüphanesine başlık/alt metin/açıklamalarıyla yükler ve istenirse bir yazının kapağı yapar.

Anahtarlar bu dosyada yoktur: istekler ortamın ağ vekili tarafından yetkilendirilir
(api.cloudflare.com ve adavira.com için kayıtlı kimlik bilgileri).

Kullanım:
  python3 gorsel.py ayar.json
ayar.json:
  {
    "prompt": "Photorealistic editorial photo ...",   (İngilizce sahne tarifi)
    "dosya": "girne-emlak-ofisi-....jpg",              (anahtar kelimeli dosya adı)
    "baslik": "...", "alt": "...", "kisa": "...", "aciklama": "...",
    "yazi_turu": "blog" | "haber",                       (isteğe bağlı)
    "yazi_id": 123,                                      (isteğe bağlı: kapak yapılacak yazı)
    "temsili": true,                                     (haberlerde "Temsili görsel" notu)
    "model": "flux-2-dev" | "flux-1-schnell",            (isteğe bağlı, varsayılan flux-2-dev)
    "sadece_uret": false                                 (true: yüklemeden yalnızca dosyayı kaydet)
  }
"""
import base64, io, json, sys, time, urllib.request, uuid

ACCOUNT = 'b30a1eee1d8de12b206e7a49257b419a'
CF = f'https://api.cloudflare.com/client/v4/accounts/{ACCOUNT}/ai/run/@cf/black-forest-labs/'
WP = 'https://adavira.com/wp-json/wp/v2/'
UA = {'User-Agent': 'Mozilla/5.0 (adavira-gorsel)'}
STIL = (' Warm natural light, subtle teal shadows, Mediterranean atmosphere, high-end editorial look,'
        ' shallow depth of field. No text, no letters, no logos, no watermark.')


def _multipart(fields):
    b = uuid.uuid4().hex
    body = b''.join(f'--{b}\r\nContent-Disposition: form-data; name="{k}"\r\n\r\n{v}\r\n'.encode() for k, v in fields.items())
    return body + f'--{b}--\r\n'.encode(), f'multipart/form-data; boundary={b}'


def uret(prompt, model='flux-2-dev', deneme=3):
    prompt = prompt.strip() + STIL
    for i in range(deneme):
        try:
            if model == 'flux-1-schnell':
                data, ct = json.dumps({'prompt': prompt, 'steps': 8}).encode(), 'application/json'
            else:
                data, ct = _multipart({'prompt': prompt, 'width': 1600, 'height': 896, 'steps': 28})
            r = urllib.request.Request(CF + model, data=data, method='POST', headers={**UA, 'Content-Type': ct})
            d = json.loads(urllib.request.urlopen(r, timeout=180).read())
            img = (d.get('result') or {}).get('image')
            if img:
                return base64.b64decode(img)
            raise RuntimeError(d.get('errors'))
        except Exception as e:
            print(f'  üretim denemesi {i + 1} başarısız: {e}')
            time.sleep(4)
    raise SystemExit('Görsel üretilemedi (günlük ücretsiz hak dolmuş olabilir).')


def hazirla(raw):
    from PIL import Image, ImageFilter
    im = Image.open(io.BytesIO(raw)).convert('RGB')
    w, h = im.size
    th = round(w * 9 / 16)
    if th <= h:
        t = (h - th) // 2; im = im.crop((0, t, w, t + th))
    else:
        tw = round(h * 16 / 9); l = (w - tw) // 2; im = im.crop((l, 0, l + tw, h))
    im = im.resize((1600, 900), Image.LANCZOS).filter(ImageFilter.UnsharpMask(radius=1, percent=40, threshold=2))
    out = io.BytesIO(); im.save(out, 'JPEG', quality=88, optimize=True, progressive=True)
    return out.getvalue()


def wp(method, path, data=None, raw=None, headers=None):
    h = dict(UA); h.update(headers or {})
    body = None
    if data is not None:
        body = json.dumps(data).encode(); h['Content-Type'] = 'application/json'
    if raw is not None:
        body = raw
    return json.loads(urllib.request.urlopen(urllib.request.Request(WP + path, data=body, method=method, headers=h), timeout=180).read())


def main(cfg):
    jpg = hazirla(uret(cfg['prompt'], cfg.get('model', 'flux-2-dev')))
    if cfg.get('sadece_uret'):
        open(cfg['dosya'], 'wb').write(jpg); print('kaydedildi:', cfg['dosya']); return
    m = wp('POST', 'media', raw=jpg, headers={'Content-Type': 'image/jpeg', 'Content-Disposition': f'attachment; filename="{cfg["dosya"]}"'})
    kisa = cfg.get('kisa', '')
    if cfg.get('temsili'):
        kisa = (kisa + ' ' if kisa else '') + '(Temsili görsel)'
    meta = {'title': cfg['baslik'], 'alt_text': cfg['alt'], 'caption': kisa, 'description': cfg.get('aciklama', cfg['alt'])}
    if cfg.get('yazi_id'):
        meta['post'] = cfg['yazi_id']
    wp('POST', f'media/{m["id"]}', meta)
    print('yüklendi:', m['id'], m['source_url'])
    if cfg.get('yazi_id'):
        wp('POST', f'{cfg.get("yazi_turu", "blog")}/{cfg["yazi_id"]}', {'featured_media': m['id']})
        print('kapak yapıldı → yazı', cfg['yazi_id'])
    return m['id']


if __name__ == '__main__':
    main(json.load(open(sys.argv[1])))
