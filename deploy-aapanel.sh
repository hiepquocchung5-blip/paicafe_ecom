#!/usr/bin/env bash
set -euo pipefail
ROOT="$(cd "$(dirname "$0")" && pwd)"
cd "$ROOT"

# Use PHP_BIN when supplied. Otherwise prefer the newest aaPanel PHP CLI and
# fall back to the system binary only when no panel-managed version exists.
if [[ -n "${PHP_BIN:-}" ]]; then
  PHP="$PHP_BIN"
else
  PHP=""
  for candidate in $(find /www/server/php -maxdepth 2 -type f -path '*/bin/php' 2>/dev/null | sort -Vr); do
    PHP="$candidate"
    break
  done
  PHP="${PHP:-$(command -v php || true)}"
fi

if [[ -z "$PHP" || ! -x "$PHP" ]]; then
  echo "PHP CLI was not found. Set it explicitly, for example:"
  echo "  PHP_BIN=/www/server/php/83/bin/php ./deploy-aapanel.sh"
  exit 1
fi

echo "Using PHP: $PHP ($("$PHP" -r 'echo PHP_VERSION;'))"
if [[ ! -f .env ]]; then
  cp .env.example .env
  chmod 600 .env
  echo "Created .env. Add production secrets, then run again."
  exit 1
fi
chmod 600 .env

for placeholder in kitchen/index.html table/index.html; do
  if [[ -f "$placeholder" ]] && grep -Eqi 'aaPanel|Congratulations|404 Not Found' "$placeholder"; then
    mv "$placeholder" "/tmp/$(basename "$(dirname "$placeholder")")-index.html.$(date +%s).bak"
    echo "Disabled aaPanel placeholder: $placeholder"
  fi
done
if ! "$PHP" -r 'exit(in_array("mysql", PDO::getAvailableDrivers(), true) ? 0 : 1);'; then
  echo "The selected PHP does not have the PDO MySQL driver."
  echo "In aaPanel: App Store > PHP > Install extensions > pdo_mysql, then restart PHP."
  echo "Or select the website's PHP CLI explicitly:"
  echo "  PHP_BIN=/www/server/php/83/bin/php ./deploy-aapanel.sh"
  exit 1
fi

for extension in mbstring; do
  "$PHP" -m | grep -qi "^${extension}$" || {
    echo "Missing PHP extension for $PHP: ${extension}"
    exit 1
  }
done

if ! "$PHP" -m | grep -qi '^openssl$'; then
  echo "Warning: OpenSSL is not listed by the CLI; enable it for secure outbound connections."
fi

while IFS= read -r file; do "$PHP" -l "$file" >/dev/null; done < <(find . -type f -name '*.php' -not -path './assets/*' | sort)
"$PHP" -r 'exit(simplexml_load_file("sitemap.xml") ? 0 : 1);'
echo "Deployment checks passed for paicafes.com. Reload PHP-FPM if OPcache is enabled."
