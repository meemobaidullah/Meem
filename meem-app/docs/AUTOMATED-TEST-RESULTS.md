# Automated test results (local, throwaway SQLite)

Run: `php tests/run.php`

| Result | Test |
|---|---|
| Passed | health endpoint returns ok |
| Passed | security headers present (CSP, nosniff, frame) |
| Passed | unknown page returns 404 |
| Passed | session cookie is HttpOnly and SameSite |
| Passed | contact form renders with CSRF + spam token |
| Passed | contact POST without CSRF is rejected (419) |
| Passed | honeypot filled is blocked |
| Passed | invalid email gives 422 with message |
| Passed | missing consent gives 422 |
| Passed | invalid phone gives 422 |
| Passed | forged spam token is blocked |
| Passed | valid contact stored as lead New with consent + hashed IP |
| Passed | contact form is rate limited (429) |
| Passed | admin area redirects to login when logged out |
| Passed | wrong password rejected (401) |
| Passed | SQL injection login attempt fails |
| Passed | admin logs in |
| Passed | logout without CSRF rejected |
| Passed | admin sees leads list |
| Passed | lead detail escapes HTML (no raw <script>) |
| Passed | SQL injection in status filter is harmless |
| Passed | lead status update + note saved |
| Passed | unknown lead status rejected |
| Passed | audit log records actions |
| Passed | editor can open leads |
| Passed | editor cannot open settings (403) |
| Passed | editor cannot open users (403) |
| Passed | author can open knowledge base |
| Passed | author cannot open leads (403) |
| Passed | author cannot open affiliates (403) |
| Passed | editor cannot approve affiliates (403) |
| Passed | author creates KB entry |
| Passed | KB validation rejects too-short entry |
| Passed | invalid GTM ID is refused |
| Passed | GTM + Search Console tags appear on public pages once configured |
| Passed | Clarity tag appears with nonce once configured |
| Passed | invalid Clarity ID is refused |
| Passed | non-wa.me WhatsApp URL refused |
| Passed | affiliate page shows Coming soon by default |
| Passed | registration blocked while coming soon (403) |
| Passed | referral link does nothing while coming soon |
| Passed | program cannot go live with missing rules |
| Passed | percent above 100 refused |
| Passed | program goes live once all rules are set |
| Passed | terms page shows the configured rules |
| Passed | affiliate weak password rejected |
| Passed | affiliate registers as pending |
| Passed | pending affiliate sees dashboard without a referral link |
| Passed | admin approves affiliate and a code is issued |
| Passed | referral click recorded and cookie set |
| Passed | referred lead is attributed to the affiliate |
| Passed | converting a referred lead creates a pending commission of exactly 10% (100.00) |
| Passed | no duplicate commission on repeat save |
| Passed | commission cannot be approved before the approval period ends |
| Passed | commission approved after the approval period |
| Passed | payout below the minimum is refused |
| Passed | payout request created at/above minimum |
| Passed | admin marks payout paid and commission becomes paid |
| Passed | refunded referral: admin rejects commission with a reason |
| Passed | calc: 12.5% of 800.00 = 100.00 |
| Passed | calc: fixed 75 = 7500 cents |
| Passed | calc: percent above 100 returns null |
| Passed | calc: zero or negative base returns null |
| Passed | calc: missing rule returns null (no invented reward) |
| Passed | admin login is rate limited after repeated failures |
| Passed | home title is 60 characters or fewer |
| Passed | home meta description exists and is 155 characters or fewer |
| Passed | home has a canonical link and index,follow |
| Passed | home has valid JSON-LD (Organization, Person, WebSite) and no rating/award/review claims |
| Passed | contact page has BreadcrumbList JSON-LD |
| Passed | /case-stady/... 301s to /case-study/... in one hop, keeping the query |
| Passed | /sucess/... 301s to /success/... |
| Passed | sitemap.xml is valid XML and lists only indexable URLs (no admin, affiliate, misspelled slugs) |
| Passed | robots.txt blocks admin and points to the sitemap |
| Passed | admin login page is noindex |

75 passed, 0 failed.
