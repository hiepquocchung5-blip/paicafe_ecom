# aaPanel deployment for paicafes.com

1. Point the website document root to this repository root.
2. Copy `.env.example` to `.env`, enter production secrets, and run `chmod 600 .env`.
3. Create the database/user using `database-access.example.sql`.
4. Enable PHP extensions `pdo_mysql`, `mbstring`, `openssl`, and optionally `redis`.
5. Issue SSL for `paicafes.com` and `www.paicafes.com`, force HTTPS, and redirect `www` to the canonical domain.
6. Keep Redis bound to `127.0.0.1`; never expose port 6379 publicly.

After updates:

```bash
git pull --ff-only
./deploy-aapanel.sh
```
