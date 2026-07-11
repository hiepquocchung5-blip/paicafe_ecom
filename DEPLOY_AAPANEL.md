# aaPanel deployment for paicafes.com

1. Point the website document root to this repository root.
2. Copy `.env.example` to `.env`, enter production secrets, and run `chmod 600 .env`.
3. Create the database/user using `database-access.example.sql`.
4. Enable PHP extensions `pdo_mysql`, `mbstring`, `openssl`, and optionally `redis`.
5. Issue SSL for `paicafes.com` and `www.paicafes.com`, force HTTPS, and redirect `www` to the canonical domain.
6. Keep Redis bound to `127.0.0.1`; never expose port 6379 publicly.
7. For the kitchen and table subdomains, follow `DEPLOY_SUBDOMAINS_AAPANEL.md`.

After updates:

```bash
git pull --ff-only
./deploy-aapanel.sh
```

If the shell PHP differs from the website PHP, select aaPanel's binary:

```bash
PHP_BIN=/www/server/php/83/bin/php ./deploy-aapanel.sh
```

Replace `83` with the PHP version assigned to the website in aaPanel. The script
also attempts to find the newest installed aaPanel PHP automatically.
