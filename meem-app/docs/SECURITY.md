# Security notes (what is implemented)

| Area | Implementation |
|---|---|
| Passwords | `password_hash` (bcrypt/argon default), minimum 12 characters |
| Sessions | HttpOnly, SameSite=Lax, Secure on HTTPS, ID regenerated at login |
| CSRF | Token on every POST form, verified server-side (419 on failure) |
| SQL injection | PDO prepared statements only, no string-built SQL with user input |
| XSS | Every output passes `htmlspecialchars`; CSP header with per-request nonce |
| Rate limiting | Contact 5/hour per IP, admin login 8/10 min per IP and per email, affiliate login/registration, referral clicks |
| Spam | Hidden honeypot field, signed time token (form must be open 3 s to 2 h) |
| Roles | admin (all), editor (leads, KB, view affiliates), author (KB only), enforced on every route |
| Audit | Logins, failed logins, lead changes, settings, user changes, affiliate actions |
| Privacy | IP addresses are stored only as HMAC hashes; consent text and time are stored with each lead |
| Secrets | `.env` kept outside the web root, `.env.example` has no real values, no keys in code |
| Headers | CSP, X-Content-Type-Options, X-Frame-Options, Referrer-Policy, Permissions-Policy, HSTS on HTTPS |

Not yet covered: two-factor login, email verification, automated malware scanning, file uploads (none exist yet; the Media Library must add safe upload handling), WAF/DDoS protection (use Hostinger/Cloudflare).
