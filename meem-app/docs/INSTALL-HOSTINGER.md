# Install on Hostinger (Business plan, hPanel + SSH)

Hostinger normally uses **hPanel**, not cPanel. The steps are the same idea. Check each menu name against your dashboard.

## 1. Create the database
hPanel → **Databases → MySQL Databases** → create a database, user and password. Write down the full database name and user (Hostinger adds a prefix such as `u123456789_`).

## 2. Upload the files
Upload the folder **outside** the web root:
```
/home/uXXXXXXXX/meem-app/        <- everything in this ZIP except public/
/home/uXXXXXXXX/domains/meemobaidullah.com/public_html/   <- contents of public/
```
Use hPanel File Manager or SFTP. `public_html` must contain only: `index.php`, `.htaccess`, `app.css`.
If you put the whole project inside `public_html` instead, the `.htaccess` files in `app`, `database`, `views`, `storage`, `docs` block web access, but the outside layout is safer.

## 3. Configure
Over SSH (hPanel → Advanced → SSH Access):
```
cd ~/meem-app
cp .env.example .env
nano .env        # set DB_NAME, DB_USER, DB_PASS, APP_KEY (long random text), APP_URL
chmod 600 .env
chmod -R 750 storage
```
Generate an APP_KEY with: `php -r "echo bin2hex(random_bytes(32));"`
Choose PHP 8.1 or newer in hPanel → **Advanced → PHP Configuration**.

## 4. Create tables and the first admin
```
php database/migrate.php
php database/create-admin.php "Meem Obaidullah" meemobaidullah@gmail.com   # asks for a 12+ character password
```
If `public_html` is not next to `meem-app`, edit the two paths at the top of `public/index.php`.

## 5. Turn on HTTPS and test
Enable the free SSL in hPanel → **Security → SSL**. Then open `https://meemobaidullah.com/health` (should say ok), `/contact` and `/admin/login`.

## 6. Settings inside the admin
Admin → Settings: add the GTM ID, Search Console verification code, verified WhatsApp URL. Leave the affiliate program on `coming_soon` until every rule is filled in and the terms are reviewed.

## Backups
hPanel → **Backups** for files and database, plus a monthly export: `mysqldump -u USER -p DBNAME > backup.sql`. Test a restore on a staging copy at least once.

## Updating
Upload new files over the old ones (keep `.env`), then run `php database/migrate.php`.

## Preset public settings
After `php database/migrate.php`, run `php database/seed-settings.php`. It fills GTM (GTM-K7JZXFJ3), Clarity (z6zlgq2maq), the WhatsApp link (+880 1731 558195), contact email and office hours only where they are still empty. GA4 and Search Console are added later in Admin > Settings. The Clarity export API token is a secret: keep it in `.env` or the Clarity dashboard, never in these settings or in page code.
