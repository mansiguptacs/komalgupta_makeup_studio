# komalgupta_makeup_studio

## Secure section (admin)

The **Admin** area shows a document listing current website users. Access requires administrator login.

- **Login page:** `login.php` (or use the “Admin” link in the nav, which goes to the secure section and redirects to login if needed).
- **Credentials:** User ID `admin`. Password is stored hashed with salt in `data/admin_users.json`.
- **First-time setup:** Run `php init_admin.php` to create `data/admin_users.json` (default password: `admin123`). Change the password in the script before running if you want a different one.
- **Secure page:** `secure/users.php` — list of users (Mary Smith, John Wang, Alex Bington, etc.). Only visible after login.
- **Logout:** Use “Sign Out” on the secure page, or go to `api/logout.php`.

