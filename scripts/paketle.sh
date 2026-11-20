#!/usr/bin/env bash
# Tema ve eklentiyi WordPress'e yüklenecek .zip dosyaları olarak dist/ klasörüne paketler.
# Kullanım: bash scripts/paketle.sh
set -euo pipefail
cd "$(dirname "$0")/.."
mkdir -p dist
rm -f dist/emlak-core.zip dist/adaev.zip
(cd wordpress/wp-content/plugins && zip -qr ../../../dist/emlak-core.zip emlak-core -x '*.DS_Store')
(cd wordpress/wp-content/themes && zip -qr ../../../dist/adaev.zip adaev -x '*.DS_Store')
ls -lh dist/*.zip
