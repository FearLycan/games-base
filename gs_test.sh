#!/usr/bin/env bash
# Quick connectivity test for GameSeal scraping from THIS machine/server.
# Mirrors the 2-step flow a real importer would use:
#   1) warm up a session cookie on the homepage
#   2) hit the /pl/suggest AJAX endpoint and parse name/region/price
#
# Why this matters on the server: GameSeal sits behind Cloudflare. A datacenter
# IP may get a JS/Turnstile challenge that plain curl can't solve. If step 2
# prints products -> scraping is viable here. If it prints a challenge page
# ("Just a moment", "challenge-platform", cf-mitigated) -> we'd need a headless
# browser or a proxy.

set -u

UA="Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0 Safari/537.36"
QUERY="${1:-007 First Light}"
JAR="$(mktemp)"
OUT="$(mktemp)"
ENC_QUERY="${QUERY// /+}"

echo "=== GameSeal scrape test ==="
echo "query: $QUERY"
echo

# --- Step 1: warm up session cookie ---
HOME_CODE=$(curl -s -c "$JAR" "https://gameseal.com/" \
  -A "$UA" -L --max-redirs 3 \
  -o /dev/null -w "%{http_code}")
echo "[1] homepage handshake -> HTTP $HOME_CODE  (cookies: $(grep -vc '^#' "$JAR"))"

# --- Step 2: AJAX suggest with the warmed cookie ---
read -r SUG_CODE SUG_SIZE SUG_URL < <(
  curl -s -b "$JAR" -c "$JAR" \
    "https://gameseal.com/pl/suggest?search=$ENC_QUERY" \
    -A "$UA" -H "X-Requested-With: XMLHttpRequest" \
    -L --max-redirs 4 \
    -o "$OUT" -w "%{http_code} %{size_download} %{url_effective}"
)
echo "[2] /pl/suggest      -> HTTP $SUG_CODE  size ${SUG_SIZE}B  url $SUG_URL"
echo

# --- Cloudflare challenge detection ---
if grep -qiE "challenge-platform|just a moment|cf-mitigated|turnstile|cf-please-wait" "$OUT"; then
  echo "!! Cloudflare challenge detected — plain curl is NOT enough on this host."
  echo "   (would need headless browser / proxy)"
  rm -f "$JAR" "$OUT"; exit 2
fi

# --- Parse results ---
echo "=== parsed products ==="
# Split tags onto their own lines, then pull title= / region badge / price.
sed -E 's/></>\n</g' "$OUT" \
  | grep -iE 'class="[^"]*search-suggest-product"|title="[^"]+"|badge-region|product-price-(regular|was)|[0-9]+[.,][0-9]{2}[[:space:]]*(€|zł|EUR|PLN)' \
  | sed -E 's/<[^>]*>//g; s/^[[:space:]]+//; s/[[:space:]]+$//' \
  | grep -vE '^$' | head -40

# One "name-region" block is rendered per product in the suggest dropdown.
PROD_COUNT=$(grep -oc 'search-suggest-product-name-region' "$OUT")
echo
echo "=== summary ==="
echo "product links found: $PROD_COUNT"
[ "$SUG_CODE" = "200" ] && [ "$PROD_COUNT" -gt 0 ] \
  && echo "RESULT: OK — scraping works from this host." \
  || echo "RESULT: FAIL — no products returned (check IP / Cloudflare / locale)."

rm -f "$JAR" "$OUT"
