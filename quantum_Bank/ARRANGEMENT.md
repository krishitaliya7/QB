This file describes the pieces of the QuantumBank website and the recommended order to assemble and verify them.

Top-level structure

- `quantum_Bank/pages/` - user-facing pages (index.php, login.php, signup.php, dashboard.php, etc.)
- `quantum_Bank/includes/` - shared includes: `header.php`, `footer.php`, `db_connect.php`, `session.php`, `email_helpers.php`, `audit.php`, etc.
- `quantum_Bank/config/` - configuration (settings.php, smtp.php)
- `quantum_Bank/admin/` - admin utilities (audit logs, loan management)
- `css/`, `js/`, `assets/` - static assets for the public site
- `quantum_bank.sql` - database schema

Recommended assembly and check order (puzzle pieces)

1. Environment and DB
   - Copy `.env.example` -> `.env` and set DB credentials.
   - Import `quantum_bank.sql` into MySQL.
   - Verify `quantum_Bank/includes/db_connect.php` can connect.

2. Shared includes
   - `includes/session.php`: session/start helpers and auth helpers. Ensure `session_start()` is called before any output.
   - `includes/db_connect.php`: database connection. It now supports a simple .env parser and environment variables.
   - `includes/header.php` / `includes/footer.php`: global layout. They should reference assets using the correct base path for your deployment. We converted them to `/QB/...` to work under XAMPP by default.

3. Authentication flow
   - `pages/login.php` and `pages/signup.php`: verify account creation, email verification (email queue or SMTP).
   - `pages/password_reset_request.php` and `pages/password_reset.php`: verify token generation and password reset flow.

4. Core user pages
   - `pages/dashboard.php`, `pages/transactions.php`, `pages/transfer.php`: require login. Use `requireLogin()` helper from `session.php`.
   - `pages/open_account.php` and `pages/payments.php`: ensure forms include CSRF token `generateCsrfToken()`.

5. Admin tools
   - `admin/audit_logs.php`: view audit logs; add an admin check if needed using `isAdmin()` helper.

6. Email & background tasks
   - `includes/send_mail.php`, `includes/email_helpers.php`: configure SMTP in `.env` or `config/smtp.php` and test sending.
   - `scripts/seed_db.php` and migration scripts in `quantum_Bank/scripts/`.

Verification checklist

- [ ] App loads at http://localhost/QB/quantum_Bank/pages/index.php
- [ ] Login / Signup work
- [ ] Database queries execute without PDO errors
- [ ] Static assets (CSS/JS/images) load correctly
- [ ] Email sending (or mail log) works as expected

If you'd like, I can:
- Adjust includes to use a central `bootstrap.php` that sets up environment and common paths to make includes consistent.
- Create a simple `start.sh` / `start.bat` that helps Windows users copy `.env.example` to `.env` and run composer install.
- Add a simple smoke-test PHP script that checks DB connection and required tables.

Tell me which of the above you'd like me to implement next and I'll make the changes.
