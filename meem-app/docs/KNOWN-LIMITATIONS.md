# Known limitations (honest status)

Built and tested locally (see AUTOMATED-TEST-RESULTS.md): database schema, contact and lead capture, lead statuses and notes, admin login and roles, knowledge base editor, settings (GTM, Search Console tag, WhatsApp URL), full affiliate backend (coming-soon lock, rules, registration, approval, referral link, click and conversion tracking, commission calculation, approval period, payout request, refunds/rejection), audit log, rate limiting.

**Not built yet**
- Public pages: the designed pages (home, services, blog, tools, marketplaces, categories) are still design-canvas pages. They must be converted into PHP views (next milestone). Until then `/` is a placeholder.
- Anima AI Rover (chat, voice, booking, handoff): needs an AI provider account and API key. Only the knowledge base editor exists.
- Appointment booking, email notifications (SMTP), Media Library, CMS page editor, navigation manager, SEO manager, sitemap.xml and robots.txt generator, backup tool inside admin.
- Social media automation (Draft → Review → Admin approval → Schedule → Publish). Needs each platform's official API approval; none is connected. Nothing will publish without admin approval when built.
- AI content/video studios, content calendar, analytics dashboard inside admin (use GA4/GTM directly for now).
- Legal pages: need review by a qualified adviser for your jurisdiction.

**Not tested**: MySQL on Hostinger (tests ran on SQLite with the same SQL), real email delivery, browsers/devices, load. Mark these Pending until you test them on the server.

**Affiliate program**: no reward values exist. It stays Coming Soon until an admin fills every rule and sets the status to live. Commission is created only when an admin marks a referred lead Converted and enters the deal amount.

## SEO phase 1 (staging) - what is and is not done
- Done in this app: title/description length limits, canonical, robots meta, JSON-LD (Organization, Person, WebSite, BreadcrumbList), 301 map for /case-stady -> /case-study and /sucess -> /success, sitemap.xml, robots.txt.
- Not done: this app serves only home, contact and affiliate. The current live site is a separate site, so its titles, schema and redirects must be applied there (or this app must front those URLs). Nothing was changed on the live site.
- hreflang: intentionally not added until a translated page exists.
- GA4 tag, conversion events and consent banner still need GTM setup and testing.
