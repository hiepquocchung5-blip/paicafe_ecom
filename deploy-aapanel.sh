#!/usr/bin/env bash
set -euo pipefail
ROOT="$(cd "$(dirname "$0")" && pwd)"
cd "$ROOT"
if [[ ! -f .env ]]; then
  cp .env.example .env
  chmod 600 .env
  echo "Created .env. Add production secrets, then run again."
  exit 1
fi
chmod 600 .env
for extension in pdo_mysql mbstring openssl; do
  php -m | grep -qi "^${extension}$" || { echo "Missing PHP extension: ${extension}"; exit 1; }
done
while IFS= read -r file; do php -l "$file" >/dev/null; done < <(find . -type f -name '*.php' -not -path './assets/*' | sort)
php -r 'exit(simplexml_load_file("sitemap.xml") ? 0 : 1);'
echo "Deployment checks passed for paicafes.com. Reload PHP-FPM if OPcache is enabled."
