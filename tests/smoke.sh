#!/usr/bin/env bash
# End-to-end check on a fresh MODX site: install the package, render the demo pages against the live
# AVDEB API, clear the cache, then uninstall and make sure the elements and files are gone.
#   tests/smoke.sh [name] [port]
set -euo pipefail
NAME="${1:-avdeb-modx-ci}"
PORT="${2:-8099}"
HERE="$(cd "$(dirname "$0")" && pwd)"
trap 'docker rm -f "$NAME" "$NAME-db" >/dev/null 2>&1; docker network rm "$NAME" >/dev/null 2>&1' EXIT

AVDEB_REF_CODE=CITEST "$HERE/dev-site.sh" "$NAME" "$PORT"
fail() { echo "FAIL: $*" >&2; exit 1; }
home="$(curl -fsS "http://localhost:$PORT/")"
post="$(curl -fsS "http://localhost:$PORT/desk-reset.html")"
grep -q 'avdeb--spotlight' <<<"$home" || fail "snippet did not render on the home page"
grep -q '?ref=CITEST' <<<"$home" || fail "referral code missing"
grep -q '/assets/components/avdeb/avdeb.css' <<<"$home" || fail "stylesheet not added"
grep -q 'avdeb--list' <<<"$post" || fail "{avdeb-products} tag not replaced"
grep -q '{avdeb-' <<<"$post" && fail "tag left in the page"
curl -fsS -o /dev/null "http://localhost:$PORT/assets/components/avdeb/avdeb.js" || fail "assets not installed"

eval_php() { docker exec -u www-data "$NAME" php /var/www/html/avdeb-eval.php "$1" 2>/dev/null | grep -v -i deprecated; }
out="$(eval_php '$modx->invokeEvent("OnSiteRefresh"); echo "refreshed";')"
[ "$out" = "refreshed" ] || fail "OnSiteRefresh: $out"

out="$(eval_php '
  $p = $modx->getObject(MODX\Revolution\Transport\modTransportPackage::class, ["signature" => "avdeb-1.0.0-pl"]);
  echo $p->uninstall() ? "ok" : "failed", " ",
    $modx->getCount(MODX\Revolution\modSnippet::class, ["name:LIKE" => "Avdeb%"]), " ",
    $modx->getCount(MODX\Revolution\modPlugin::class, ["name" => "AVDEB Products"]), " ",
    is_dir(MODX_CORE_PATH . "components/avdeb") ? "core" : "-", " ",
    is_dir(MODX_ASSETS_PATH . "components/avdeb") ? "assets" : "-";')"
[ "$out" = "ok 0 0 - -" ] || fail "uninstall left something behind: $out"
echo "MODX smoke test passed"
