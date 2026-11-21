#!/usr/bin/env bash
# Eklentiyi, temayı ve Chrome eklentisini sürüm numaralı .zip dosyaları olarak dist/ klasörüne paketler.
# Sürüm, her parçanın kendi başlığından okunur:
#   adacasa-core.php "Version:"  →  dist/adacasa-core-1.0.1.zip
#   style.css "Version:"         →  dist/adacasa-theme-1.0.1.zip
#   manifest.json "version"      →  dist/ilan-aktarici-0.4.1.zip
# Zip içindeki klasör adı sürümsüzdür (adacasa-core/, adacasa-theme/); WordPress "mevcut sürümü değiştir" ile günceller.
# Eski adlar (1.0.1 öncesi): emlak-core, emlak-tema (daha önce adaev). Veri tabloları ve ayarlar aynıdır.
# Kullanım: bash scripts/paketle.sh
set -euo pipefail
cd "$(dirname "$0")/.."
ver() { grep -m1 -E "$2" "$1" | sed -E "s/.*$2[[:space:]]*\"?([0-9][0-9.]*)\"?.*/\1/"; }
V_CORE=$(ver wordpress/wp-content/plugins/adacasa-core/adacasa-core.php 'Version:')
V_TEMA=$(ver wordpress/wp-content/themes/adacasa-theme/style.css 'Version:')
V_EXT=$(ver extension/manifest.json '"version":')
mkdir -p dist
rm -f dist/adacasa-core*.zip dist/adacasa-theme*.zip dist/emlak-core*.zip dist/emlak-tema*.zip dist/adaev*.zip dist/ilan-aktarici*.zip
(cd wordpress/wp-content/plugins && zip -qr "../../../dist/adacasa-core-$V_CORE.zip" adacasa-core -x '*.DS_Store')
(cd wordpress/wp-content/themes && zip -qr "../../../dist/adacasa-theme-$V_TEMA.zip" adacasa-theme -x '*.DS_Store')
(cd extension && zip -qr "../dist/ilan-aktarici-$V_EXT.zip" . -x '*.DS_Store')
ls -lh dist/*.zip
