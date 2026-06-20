#!/usr/bin/env bash
# Yerel geliştirme sunucusu. pdo_mysql php.ini'de kapalıysa bile yükler.
# Kullanım:  ./serve.sh   →  http://127.0.0.1:8091
cd "$(dirname "$0")" || exit 1
exec php -d extension=pdo_mysql -S 127.0.0.1:8091 index.php
