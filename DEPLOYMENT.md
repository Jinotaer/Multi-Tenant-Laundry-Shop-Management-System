# Deploying Laundry Shop Management to DigitalOcean

A practical guide for deploying this Laravel 12 multi-tenant (stancl/tenancy) system on DigitalOcean using a single **Droplet** with Nginx + PHP-FPM + MySQL + Redis.

We use a Droplet (not App Platform) because multi-tenancy with dynamic subdomains is significantly easier when you control the Nginx config directly.

---

## 1. Create the Droplet

1. Log in to DigitalOcean → **Create → Droplet**
2. Choose **Ubuntu 24.04 LTS**
3. Size: **Basic → Regular → 2 GB RAM / 1 CPU** ($12/mo) minimum — multi-tenant apps need headroom because every tenant gets its own database
4. Authentication: **SSH key** (generate one with `ssh-keygen` on your machine if you don't have one)
5. Hostname: e.g. `laundry-prod`

## 2. Point your domain

In your DNS provider (or DigitalOcean → Networking → Domains), create:

| Type | Host | Value           | Purpose                            |
| ---- | ---- | --------------- | ---------------------------------- |
| A    | `@`  | `<droplet-ip>`  | Central app (`yourdomain.com`)     |
| A    | `*`  | `<droplet-ip>`  | Wildcard for tenant subdomains     |

The wildcard record is required so tenants at `tenant1.yourdomain.com`, `tenant2.yourdomain.com`, etc. all resolve to the same server.

## 3. Install the LEMP stack

SSH in (`ssh root@<droplet-ip>`) and run:

```bash
apt update && apt upgrade -y
apt install -y nginx mysql-server redis-server unzip git curl \
  software-properties-common ca-certificates lsb-release

# PHP 8.3 (compatible with composer.json requirement of ^8.2)
add-apt-repository ppa:ondrej/php -y
apt update
apt install -y php8.3-fpm php8.3-cli php8.3-mysql php8.3-mbstring \
  php8.3-xml php8.3-curl php8.3-zip php8.3-bcmath php8.3-gd \
  php8.3-intl php8.3-redis

# Composer
curl -sS https://getcomposer.org/installer | php -- \
  --install-dir=/usr/local/bin --filename=composer

# Node 20 (for `npm run build`)
curl -fsSL https://deb.nodesource.com/setup_20.x | bash -
apt install -y nodejs

# Lock down MySQL
mysql_secure_installation
```

## 4. Create the central database and app user

```bash
mysql -uroot -p
```

```sql
CREATE DATABASE laundry_central CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'laundry'@'localhost' IDENTIFIED BY 'STRONG_PASSWORD';

-- stancl/tenancy needs CREATE/DROP DB rights to spin up tenant databases
GRANT ALL PRIVILEGES ON *.* TO 'laundry'@'localhost' WITH GRANT OPTION;
FLUSH PRIVILEGES;
EXIT;
```

> **Why `*.*` grant?** The tenancy package creates `tenant<uuid>` databases dynamically. The app user needs permission to create and drop them.

## 5. Deploy the code

```bash
# Create a non-root deploy user
adduser --disabled-password --gecos "" deploy
usermod -aG www-data deploy
su - deploy

cd /var/www
git clone <your-repo-url> laundry
cd laundry

composer install --no-dev --optimize-autoloader
npm ci && npm run build

cp .env.example .env
php artisan key:generate
```

Edit `.env`:

```ini
APP_ENV=production
APP_DEBUG=false
APP_URL=https://yourdomain.com

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_DATABASE=laundry_central
DB_USERNAME=laundry
DB_PASSWORD=STRONG_PASSWORD

CACHE_STORE=redis
QUEUE_CONNECTION=redis
SESSION_DRIVER=redis
REDIS_HOST=127.0.0.1
```

Also update [config/tenancy.php](config/tenancy.php) — add your production central domain:

```php
'central_domains' => ['yourdomain.com'],
```

Finish setup:

```bash
php artisan migrate --force
php artisan storage:link
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

Fix ownership (run as **root**):

```bash
chown -R deploy:www-data /var/www/laundry
find /var/www/laundry -type d -exec chmod 755 {} \;
find /var/www/laundry -type f -exec chmod 644 {} \;
chmod -R 775 /var/www/laundry/storage /var/www/laundry/bootstrap/cache
```

## 6. Nginx config

Create `/etc/nginx/sites-available/laundry`:

```nginx
server {
    listen 80;
    server_name yourdomain.com *.yourdomain.com;
    root /var/www/laundry/public;
    index index.php;

    add_header X-Frame-Options "SAMEORIGIN";
    add_header X-Content-Type-Options "nosniff";

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.3-fpm.sock;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\.(?!well-known).* { deny all; }
    client_max_body_size 50M;
}
```

Enable and reload:

```bash
ln -s /etc/nginx/sites-available/laundry /etc/nginx/sites-enabled/
rm /etc/nginx/sites-enabled/default
nginx -t && systemctl reload nginx
```

## 7. HTTPS with a wildcard certificate

Wildcard certs require a DNS-01 challenge. The easiest path is Certbot with DigitalOcean's DNS plugin:

```bash
apt install -y certbot python3-certbot-dns-digitalocean

# Create an API token at https://cloud.digitalocean.com/account/api/tokens
mkdir -p /root/.secrets
echo "dns_digitalocean_token = YOUR_DO_TOKEN" > /root/.secrets/do.ini
chmod 600 /root/.secrets/do.ini

certbot certonly \
  --dns-digitalocean \
  --dns-digitalocean-credentials /root/.secrets/do.ini \
  -d yourdomain.com -d '*.yourdomain.com'
```

Then update the Nginx server block to `listen 443 ssl;` pointing at `/etc/letsencrypt/live/yourdomain.com/fullchain.pem` and `privkey.pem`, and add a port 80 → 443 redirect server block. Auto-renewal runs via the built-in systemd timer.

## 8. Queue worker and scheduler

The `composer dev` script runs these locally. In production use systemd + cron.

**Cron** (as deploy user — `crontab -e`):

```
* * * * * cd /var/www/laundry && php artisan schedule:run >> /dev/null 2>&1
```

**Queue worker** — `/etc/systemd/system/laundry-queue.service`:

```ini
[Unit]
Description=Laundry queue worker
After=redis-server.service

[Service]
User=deploy
Group=www-data
Restart=always
ExecStart=/usr/bin/php /var/www/laundry/artisan queue:work --sleep=3 --tries=3 --max-time=3600

[Install]
WantedBy=multi-user.target
```

Enable it:

```bash
systemctl enable --now laundry-queue
```

## 9. Firewall

```bash
ufw allow OpenSSH
ufw allow 'Nginx Full'
ufw enable
```

## 10. Deploying updates

```bash
cd /var/www/laundry
git pull
composer install --no-dev --optimize-autoloader
npm ci && npm run build
php artisan migrate --force
php artisan tenants:migrate --force    # stancl/tenancy tenant DBs
php artisan config:cache
php artisan route:cache
php artisan view:cache
sudo systemctl restart laundry-queue php8.3-fpm
```

---

## Project-specific notes

- **Tenant storage** — the `storage/tenant*/` directories show per-tenant filesystem usage. Consider mounting a **DigitalOcean Volume** at `/var/www/laundry/storage` so tenant files survive droplet resizes and make backups easier.
- **Backups** — enable Droplet backups ($2.40/mo for a 2 GB droplet) and also keep DB dumps on **DigitalOcean Spaces** (S3-compatible). The codebase already creates `pre_update` / `pre_rollback` zips in `storage/tenant*/app/backups/` — point those to a Spaces bucket for durable off-droplet storage.
- **Why not App Platform?** Wildcard subdomain routing for tenants is awkward on App Platform and SSH/filesystem access is limited. A Droplet gives you full control over Nginx, systemd, and the per-tenant storage layout this app uses.

---

## Cost summary (minimum)

| Item                            | Monthly |
| ------------------------------- | ------- |
| 2 GB Droplet                    | $12     |
| Droplet backups (20%)           | $2.40   |
| Spaces (250 GB, for DB backups) | $5      |
| **Total**                       | **~$19.40** |

Scale the Droplet up as tenant count grows — each tenant database adds some baseline memory and connection overhead.
