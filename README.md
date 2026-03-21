# komalgupta_makeup_studio

## Secure section (admin)

The **Admin** area shows a document listing current website users. Access requires administrator login.

- **Login page:** `login.php` (or use the “Admin” link in the nav, which goes to the secure section and redirects to login if needed).
- **Credentials:** User ID `admin`. Password is stored hashed with salt in `data/admin_users.json`.
- **First-time setup:** Run `php init_admin.php` to create `data/admin_users.json` (default password: `admin123`). Change the password in the script before running if you want a different one.
- **Secure page:** `secure/users.php` — list of users (Mary Smith, John Wang, Alex Bington, etc.). Only visible after login.
- **Logout:** Use “Sign Out” on the secure page, or go to `api/logout.php`.
- **Analytics:** `secure/analytics.php` — subscribers (footer subscribe form) and visit logs (`data/visits.json`). Single studio location: Civil Lines, Badaun.


## MySQL setup (InfinityFree)

1. Copy `config/db_credentials.php.example` to **`config/db_credentials.php`** (exact filename).
2. Add your DB password in `config/db_credentials.php` and upload it to the server.
3. Login as admin and open **`admin_setup_db.php`** once — creates tables, imports `data/site_users.json` if empty, and imports `data/subscribers.json` into MySQL.
4. New subscribers save to MySQL via `api/subscribe.php`; **Analytics** reads subscribers from MySQL when the DB connects.

### If you still see JSON data (not MySQL)

- Wrong config filename (e.g. `db_credentials.php copy.example`) — PHP only loads `db_credentials.php` or `db_credentials.local.php`.
- Empty password in credentials file.
- Connection error — open **`admin_setup_db.php`**; it shows the last connection/SQL error message.
- Earlier bug: subscriber INSERT had wrong SQL — fixed in `includes/user_repository.php`; redeploy that file.

### Error: `getaddrinfo ... nodename nor servname provided, or not known` (errno 2002)

That means **DNS failed**: PHP could not turn the hostname into an IP address.

- **Running the site on your laptop** (e.g. `php -S`, MAMP, XAMPP): your Mac must resolve `sql303.infinityfree.com`. Check Wi‑Fi, try `ping sql303.infinityfree.com` in Terminal. Many free hosts **do not allow** MySQL from the public internet anyway — only from **their** web servers. For local coding, either leave DB password empty (JSON fallback) or use **`config/db_credentials.local.php`** with a **local** MySQL (`host` → `127.0.0.1`, create the same tables with `sql/schema.sql`).
- **Running on InfinityFree** (uploaded site): open the hosting **Control Panel → MySQL** and copy the **exact** hostname shown (e.g. `sql303.infinityfree.com` or `sql###.epizy.com`). Put that in `db_credentials.php`. If DNS still fails on the server, wait/retry or ask InfinityFree support — it’s a resolver/network issue on the side where PHP runs.

## Friend website users via cURL
- Your public users endpoint: `api/users.php` — responds with a **JSON array** of user objects `[{ "name", "email", "joined" }, …]` (no `success` / `site` wrapper).
  - Optional protection: if `config/db_credentials.php` has `friend_access_key` set, friends must call `/api/users.php?key=THE_KEY` (or send `X-Friend-Key: THE_KEY`). On failure: `{"error":"Unauthorized"}`.
- Combined admin page: `secure/network_users.php` (supports 3–4+ friend endpoints)
- Set your friend API URLs in `config/db_credentials.php` key `friend_users_api`
  - Use comma-separated or whitespace-separated URLs (example: `https://friend1.com/api/users.php, https://friend2.com/api/users.php`)
- The page fetches friend users using cURL with timeout + JSON validation, and shows:
  - combined unique users (by email)
  - per-friend tables (inside collapsible sections)
