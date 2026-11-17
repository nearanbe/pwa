#!/usr/bin/env bash
# Tema ve eklenti için WordPress'e yüklenebilir .zip dosyaları üretir.
set -euo pipefail
cd "$(dirname "$0")"
mkdir -p dist
rm -f dist/feriha-celik.zip dist/feriha-randevu.zip
zip -rq dist/feriha-celik.zip feriha-celik -x '*.DS_Store'
zip -rq dist/feriha-randevu.zip feriha-randevu -x '*.DS_Store'
ls -la dist
