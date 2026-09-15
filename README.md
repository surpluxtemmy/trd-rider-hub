# TRD Rider Hub

Mobile-first PHP + MySQL operations app for **Talabat Rider Division · Oman**. Riders and drivers apply, track status, receive a fleet ID, then use support, appointments and SOS. Admins and city supervisors run the pipeline.

Designed to drop into **Namecheap shared hosting** (`public_html`) with no Composer.

Start with **[UPLOAD-TO-NAMECHEAP.txt](UPLOAD-TO-NAMECHEAP.txt)** for the short cPanel checklist.

## Upload to Namecheap

1. In cPanel open **MySQL Database Wizard**.
   - Create a database and a user, and grant **ALL PRIVILEGES**.
   - Note host (usually `localhost`), database name, username and password. Prefixes like `user_trdhub` are normal.
2. Upload **the contents** of this folder (not the wrapper folder) into `public_html`.
   - You should see `index.php`, `install.php`, `assets/`, `includes/`, `uploads/`, `data/` at the web root (or inside a subfolder).
3. Set permissions:
   - `uploads/` and `data/` must be writable (0755 or 0775).
   - `install.php` must be readable.
4. Visit `https://your-domain.com/install.php`.
   - Enter MySQL details.
   - Create the first **administrator** (name, email, phone, password).
   - Enter the company WhatsApp number in international digits, e.g. `9689XXXXXXX`.
5. The wizard creates tables, seeds Oman cities, writes `config.php`, and locks itself with `data/install.lock`.
6. Optional but recommended: delete `install.php` from the server after a successful install. Keep `data/install.lock`.
7. Sign in at `/login` with the admin account. Open **Settings** to add WhatsApp Cloud API tokens and SMTP if you have them.

PHP 8.0+ with PDO MySQL is required. `fileinfo` and `gd` are recommended. Apache `mod_rewrite` should be on (Namecheap default).

### Subdirectory install

If the app lives in `public_html/trd/` instead of the domain root, edit `.htaccess` and set:

```
RewriteBase /trd/
```

### Uploads

Application documents (JPG, PNG, WebP, PDF, max 2 MB each) are stored under `uploads/YYYY/MM/` with random names. Direct HTTP access is denied; only a signed-in owner, city supervisor, or admin can view them through `/file`. PHP execution is disabled in that folder. `.user.ini` raises `upload_max_filesize` to 8M and `post_max_size` to 16M so a full driver packet can be submitted.

## What the app does

**Applicants**

- Sign up with name, email, phone, password.
- Choose rider (bike) or driver (car).
- Rider uploads: passport data page, resident card front/back, selfie.
- Driver uploads those plus licence, mulkiya, ROP clearance.
- Submit notifies operations by email, WhatsApp (`wa.me` + optional Cloud API) and in-app inbox.

**Status pipeline**

`pending → accepted → processing → awaiting_sponsor → sponsor_approved → completed`

Each change emails and WhatsApps the applicant and writes an in-app notice. Reject is available until completed.

On **completed**, a fleet ID is issued: `TRD-R-YYYY-0001` or `TRD-D-YYYY-0001`. The home screen becomes an ID card plus support, appointments and SOS.

**SOS**

Sends the rider’s city supervisor and all admins an in-app alert, email and WhatsApp, with map coordinates when the browser allows location.

**Staff**

- Admin: all cities, cities list, supervisor accounts, settings.
- Supervisor: one city — applications, tickets, appointments, emergencies only.

## Settings

| Key | Purpose |
| --- | --- |
| Operations WhatsApp | `wa.me` alerts and SOS |
| Cloud API token + phone number ID | Meta WhatsApp Cloud API (optional) |
| Admin email / From header | Outbound mail |
| SMTP host/user/pass | Optional; otherwise PHP `mail()` |

## Security notes

- Passwords use `password_hash` / `password_verify`.
- All SQL uses PDO prepared statements.
- CSRF tokens on POST forms.
- HTML output is escaped.
- `config.php`, `data/`, `includes/` and `uploads/` are denied over HTTP.
- Session cookies are HttpOnly + SameSite=Lax (Secure when HTTPS).
