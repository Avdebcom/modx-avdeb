#!/usr/bin/env bash
# Throw-away MODX 3 site in Docker (MariaDB + PHP 8.2 Apache) with this extra built into a transport
# package and installed through the MODX package manager.
#   tests/dev-site.sh [name] [port]   → http://localhost:<port>/manager/  (admin / password123)
# Env: AVDEB_INSTALL=0 skips the package install; AVDEB_REF_CODE=… sets avdeb.ref_code.
# Run PHP in it:  docker exec -u www-data <name> php /var/www/html/avdeb-eval.php '<code>'
# Remove it:      docker rm -f <name> <name>-db && docker network rm <name>
set -euo pipefail
NAME="${1:-avdeb-modx}"
PORT="${2:-8098}"
EXTRA="$(cd "$(dirname "$0")/.." && pwd)"
MODX_VERSION="${MODX_VERSION:-3.2.4}"
CACHE="${XDG_CACHE_HOME:-$HOME/.cache}/modx-avdeb"
ZIP="$CACHE/modx-$MODX_VERSION-pl.zip"
IMAGE=avdeb-modx-php:8.2

mkdir -p "$CACHE"
[ -f "$ZIP" ] || curl -fsSL -o "$ZIP" "https://modx.s3.amazonaws.com/releases/$MODX_VERSION/modx-$MODX_VERSION-pl.zip"
docker image inspect "$IMAGE" >/dev/null 2>&1 || docker build -q -t "$IMAGE" "$EXTRA/tests/docker" >/dev/null

docker rm -f "$NAME" "$NAME-db" >/dev/null 2>&1 || true
docker network rm "$NAME" >/dev/null 2>&1 || true
docker network create "$NAME" >/dev/null
docker run -d --name "$NAME-db" --network "$NAME" \
  -e MARIADB_DATABASE=modx -e MARIADB_USER=modx -e MARIADB_PASSWORD=modx -e MARIADB_ROOT_PASSWORD=root \
  mariadb:11 >/dev/null
# Apache starts only after the install, so the port refuses connections until the site is ready.
docker run -d --name "$NAME" --network "$NAME" -p "$PORT:80" \
  -v "$ZIP:/tmp/modx.zip:ro" -v "$EXTRA:/opt/avdeb:ro" "$IMAGE" sleep infinity >/dev/null
for _ in $(seq 1 60); do
  docker exec "$NAME-db" mariadb -umodx -pmodx -e 'select 1' modx >/dev/null 2>&1 && break
  sleep 1
done

docker exec -e PORT="$PORT" -e DB="$NAME-db" "$NAME" bash -c '
  set -e
  cd /tmp && rm -rf modx-src && unzip -q modx.zip -d modx-src
  cp -a modx-src/modx-*/. /var/www/html/
  cd /var/www/html && mv ht.access .htaccess
  cat > /tmp/modx-config.xml <<XML
<modx>
  <database_type>mysql</database_type>
  <database_server>$DB</database_server>
  <database>modx</database>
  <database_user>modx</database_user>
  <database_password>modx</database_password>
  <database_connection_charset>utf8mb4</database_connection_charset>
  <database_charset>utf8mb4</database_charset>
  <database_collation>utf8mb4_unicode_ci</database_collation>
  <table_prefix>modx_</table_prefix>
  <https_port>443</https_port>
  <http_host>localhost:$PORT</http_host>
  <cache_disabled>0</cache_disabled>
  <inplace>1</inplace>
  <unpacked>0</unpacked>
  <language>en</language>
  <cmsadmin>admin</cmsadmin>
  <cmspassword>password123</cmspassword>
  <cmsadminemail>admin@example.com</cmsadminemail>
  <core_path>/var/www/html/core/</core_path>
  <context_mgr_path>/var/www/html/manager/</context_mgr_path>
  <context_mgr_url>/manager/</context_mgr_url>
  <context_connectors_path>/var/www/html/connectors/</context_connectors_path>
  <context_connectors_url>/connectors/</context_connectors_url>
  <context_web_path>/var/www/html/</context_web_path>
  <context_web_url>/</context_web_url>
  <remove_setup_directory>1</remove_setup_directory>
</modx>
XML
  chown -R www-data:www-data /var/www/html
  su -s /bin/bash www-data -c "php setup/index.php --installmode=new --config=/tmp/modx-config.xml" | tail -3
  cp /opt/avdeb/tests/avdeb-eval.php /var/www/html/avdeb-eval.php
  chown www-data:www-data /var/www/html/avdeb-eval.php
'
if [ "${AVDEB_INSTALL:-1}" != "0" ]; then
  docker exec -u www-data -e AVDEB_REF_CODE="${AVDEB_REF_CODE:-}" "$NAME" php /opt/avdeb/tests/install.php
  # The installable package, for the MODX Extras repository or Package Management → Upload.
  mkdir -p "$EXTRA/_packages"
  docker cp "$NAME:/var/www/html/core/packages/avdeb-1.0.0-pl.transport.zip" "$EXTRA/_packages/" >/dev/null
fi
docker exec -d "$NAME" apache2-foreground
for _ in $(seq 1 60); do
  curl -s -o /dev/null "http://localhost:$PORT/manager/" && break
  sleep 0.5
done
echo "MODX ready: http://localhost:$PORT/manager/ (admin / password123)"
