#!/bin/sh
# Kullanım: ./paketle.sh 1.0.2   -> sürümü günceller ve adavira-tema-1.0.2.zip oluşturur
set -e
cd "$(dirname "$0")"
V="${1:?Sürüm numarası verin, ör. ./paketle.sh 1.0.2}"
sed -i "s/^Version: .*/Version: $V/" adavira/style.css
sed -i "s/define('ADV_VER', '[^']*');/define('ADV_VER', '$V');/" adavira/functions.php
rm -f adavira-tema*.zip
zip -qr "adavira-tema-$V.zip" adavira
echo "adavira-tema-$V.zip hazır"
