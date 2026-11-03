# Testing report

Status key: Passed = automated test passed locally. Pending = not tested yet. N/A = does not exist yet.
Automated details: see AUTOMATED-TEST-RESULTS.md (63 checks, local PHP server + throwaway SQLite database).

| # | Area | Status | Note |
|---|---|---|---|
| 1 | All pages | Pending | Only backend/admin pages exist; designed pages not converted yet |
| 2 | Navigation links | Pending | |
| 3 | Mobile responsiveness | Pending | Admin/forms use a responsive stylesheet but were not viewed on devices |
| 4 | Desktop responsiveness | Pending | |
| 5 | Dark / light mode | Pending | CSS follows system setting; manual theme switcher not built in the app yet |
| 6 | Theme switcher | N/A | In the design canvas only |
| 7 | Contact forms | Passed | Validation, CSRF, honeypot, rate limit, consent, storage |
| 8 | Email delivery | N/A | SMTP notifications not built |
| 9 | WhatsApp links | Pending | URL setting validated (https://wa.me/NUMBER); real link not tested |
| 10 | Affiliate registration | Passed | Blocked while coming soon; works when live |
| 11 | Referral tracking | Passed | Clicks, cookie, attribution to lead |
| 12 | Commission calculations | Passed | Percent, fixed, invalid, missing-rule cases |
| 13 | Admin permissions | Passed | admin / editor / author checks |
| 14 | AI assistant responses | N/A | Rover not built |
| 15 | Human handoff | N/A | |
| 16 | SEO metadata | Pending | Design pages have it; app pages minimal |
| 17 | Sitemap and robots.txt | N/A | Not built |
| 18 | Analytics events | Pending | GTM tag output tested; events inside GTM not tested |
| 19 | Security controls | Passed (partial) | See SECURITY.md; no external penetration test |
| 20 | Backup and restore | Pending | Instructions only |
| - | MySQL on Hostinger | Pending | Must be tested on the server |
