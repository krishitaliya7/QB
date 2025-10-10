# QuantumBank (Demo)

This repo is a demo banking webapp built with plain PHP and MySQL for educational/testing purposes.

Quick setup (XAMPP / Windows)

1. Ensure XAMPP is installed and Apache + MySQL are running.
2. Place the repository under your XAMPP `htdocs` directory. Example path used here: `C:\xampp\htdocs\QB`.
3. Copy `quantum_Bank/.env.example` to `quantum_Bank/.env` and update database credentials (or set environment variables).
4. Import DB schema:
   ```bash
   mysql -u root -p < quantum_bank.sql
   ```
   Or run the seed script after configuring DB creds:
   ```bash
   php quantum_Bank/scripts/seed_db.php
   ```
5. Install PHP dependencies (PHPMailer) if needed:
   ```bash
   composer install
   ```
6. Access the app in your browser. With the layout above visit:
   - http://localhost/QB/quantum_Bank/pages/index.php (landing page)
   - Or create an Apache virtual host pointing to `quantum_Bank` for a cleaner URL (optional).

Notes about includes & asset paths
- The codebase uses includes in `quantum_Bank/includes/` from the `pages/` directory (e.g. `include '../includes/header.php'`). This is expected when visiting files under `quantum_Bank/pages/`.
- Asset links (CSS/JS) were converted to absolute paths under `/QB/` so they resolve correctly when pages are included from different directories. If your setup uses a different base path, update `includes/header.php` and `includes/footer.php` accordingly.

Signup & CAPTCHA
- The app includes `pages/signup.php` which can create a user and initial bank account.
- To enable Google reCAPTCHA, set `recaptcha_site_key` and `recaptcha_secret` in `quantum_Bank/config/settings.php`. Leave blank to disable.

Database migrations
- Apply SQL migrations in `quantum_Bank/sql_migrations/` as needed. Example:
  ```bash
  mysql -u root -p < quantum_Bank/sql_migrations/2025-10-08_alter_transfer_otps_and_loans.sql
  ```
- After applying the SQL, run the PHP migration script to hash plaintext OTPs:
  ```bash
  php quantum_Bank/scripts/migrate_transfer_otps.php
  ```

Troubleshooting (local/XAMPP)
- If you see database connection errors, open `quantum_Bank/.env` and ensure `QB_DB_HOST`, `QB_DB_NAME`, `QB_DB_USER`, `QB_DB_PASS` are correct. The project also tries to load `.env` automatically.
- If assets do not load, confirm the site is accessed under `/QB/` or adjust asset paths in `includes/header.php` and `includes/footer.php`.

Security notes
- This project is a demo. Do NOT store real card data or CVV in this repo.
- Use tokenization/vault services in production.

Admin
- Audit logs viewer: `/QB/quantum_Bank/admin/audit_logs.php` (no role-based guard by default). Add your own admin guard.
