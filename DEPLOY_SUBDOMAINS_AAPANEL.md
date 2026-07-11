# Same-repository aaPanel subdomains

Use the existing checkout at `/www/wwwroot/paicafes.com`. Do not create duplicate
copies of the code or `.env`.

## Website roots

| Domain | Document root | Purpose |
|---|---|---|
| `paicafes.com` | `/www/wwwroot/paicafes.com` | Customer website |
| `poskitchen.paicafes.com` | `/www/wwwroot/paicafes.com/kitchen` | Authenticated kitchen display |
| `postable.paicafes.com` | `/www/wwwroot/paicafes.com/table` | Ready-order display |

Create DNS `A` records for both subdomains pointing to the same server IP. Add
each subdomain as a separate aaPanel website, issue SSL, and force HTTPS.

## Shared assets

Add this block inside each subdomain's Nginx `server` block. It lets `/assets/`
resolve from the shared repository while `/api/` continues to resolve inside
the kitchen or table document root.

```nginx
location ^~ /assets/ {
    alias /www/wwwroot/paicafes.com/assets/;
    access_log off;
    expires 30d;
    add_header Cache-Control "public, immutable";
}

location ~ /\. {
    deny all;
}
```

Do not redirect `/api/` to the main domain: each module has its own API routes.
Both modules safely load the shared `.env` and PDO connection from the parent
repository.

## Verification

```bash
curl -I https://poskitchen.paicafes.com/login.php
curl -I https://poskitchen.paicafes.com/assets/css/tailwind.css
curl -I https://postable.paicafes.com/
curl -I https://postable.paicafes.com/api/get_ready_orders.php
```

Expected results are `200`, or `302` for protected kitchen pages before login.
