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
3. In phpMyAdmin, import **`sql/schema.sql`** once (this is the single source of truth for schema).
4. Login as admin and open **`admin_setup_db.php`** once — validates schema and imports `data/subscribers.json` into MySQL.
5. New subscribers save to MySQL via `api/subscribe.php`; **Analytics** reads subscribers from MySQL when the DB connects.

### Team page (`team.php`)

- Tables are defined in `sql/schema.sql` and should be created by importing that file.
- **Public page** `team.php` lists only rows where **`is_active = 1`** (ordered by `sort_order`, then name).
- Columns: `name`, `email`, `photo_url` (full URL to image, or leave `NULL` for initials placeholder), `designation`, `is_active`, `sort_order`.
- Example insert:

```sql
INSERT INTO team_members (name, email, photo_url, designation, is_active, sort_order)
VALUES
  ('Komal Gupta', 'studio@example.com', 'https://example.com/photo.jpg', 'Lead Makeup Artist', 1, 0),
  ('Priya Sharma', 'priya@example.com', NULL, 'Bridal Specialist', 1, 1);
```

## Friend website users via cURL
- Your public users endpoint: `api/users.php` — responds with a **JSON array** of user objects (includes `first_name`, `last_name`, `name`, `email`, `joined`, …). `name` is derived from first/last name.
  - Optional protection: if `config/db_credentials.php` has `friend_access_key` set, friends must call `/api/users.php?key=THE_KEY` (or send `X-Friend-Key: THE_KEY`). On failure: `{"error":"Unauthorized"}`.
- Combined admin page: `secure/network_users.php` (supports 3–4+ friend endpoints)
- Set your friend API URLs in `config/db_credentials.php` key `friend_users_api`
  - Use comma-separated or whitespace-separated URLs (example: `https://friend1.com/api/users.php, https://friend2.com/api/users.php`)
- The page fetches friend users using cURL with timeout + JSON validation, and shows:
  - combined unique users (by email)
  - per-friend tables (inside collapsible sections)
