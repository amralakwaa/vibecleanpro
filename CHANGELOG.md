# Changelog

All notable changes to Vibe Clean Pro. Every entry that touches URLs, indexing or structured data carries an **SEO impact** line; every migration states whether it is reversible.

## [Unreleased] — feature/production-system

Production system build on branch `feature/production-system`. Test suite: **630 tests / 2,686 assertions, all green**.

### Added — system integrations & operations control center (2026-09-20, uncommitted)
- **System & Integrations settings centre** in the admin panel (mail, backups, Google), with every integration reporting a computed state: NOT CONFIGURED / CONFIGURED / READY / ACTIVE / ERROR.
- **SMTP management** — full transport settings, password encrypted at rest and never shown again, environment configuration always wins, and a test-message action.
- **Backup management** — schedule, retention and contents driven from the panel; off-site destination contract and registry with no provider implemented yet (reports NOT CONFIGURED rather than claiming a copy exists).
- **Search Console & GA4 readiness** — verification tag printed only for a valid, enabled, non-demo token; GA4 activates only with a valid ID, an explicit switch and a published privacy policy.
- **Google Business Profile settings** — profile URL, review URL and Place ID, surfaced publicly only when real.
- **Commercial registration settings** — shown in the footer only with a real number and the display switch on.
- **System Readiness dashboard** — 14 checks with a reason and a link to the screen that fixes each one; no secret is ever rendered.
- **Demo data protection** — `DemoIntegrationSeeder` refuses to run outside local/testing, seeds everything switched off, and uses reserved placeholder values that the public site recognises and refuses to display.
- **Admin accounts** — 2FA state, last sign-in, safe 2FA reset, session revoke, and Super Admin only grantable by a Super Admin.

Migration: one reversible migration adding `users.last_login_at` and `business_profiles.google_maps_place_id`. **SEO impact:** none — no page, URL or indexing rule changed.

### Added — pre-launch hardening (2026-09-19/20)
- **Launch command** — `php artisan site:launch --mode=dry-run|validate|apply` applies the owner-approved manifest `database/seeders/content/launch-manifest.json` (47 project confirmations, 10 article→service links, 4 services + 10 articles + 4 projects published). Validates dependencies, runs every page through the Publishing Gate as it *will* be, refuses a page linking to something that stays unpublished, applies in one transaction, and is idempotent. Verified end to end on an empty database (65 changes, re-run 0).
- **Launch content moved into the repo** — project page drafts (factual summary, meta description, noindex, CTA) are now produced by `ProductionContentSeeder`; the 10 launch articles by `ArticleContentSeeder`; the Privacy and About drafts by `TrustPagesDraftSeeder`.
- **Publishing Gate check `owner_input`** — a page carrying a `NEEDS OWNER INPUT` marker can never be published.
- **Business profile fields** — commercial registration number + explicit "show publicly" switch (footer), service area, Google Business Profile URL, Google review URL. Reuses the existing public email, lead notification inbox and working hours. Migration reversible.
- **Two-factor authentication** — Filament's built-in authenticator-app MFA, optional per user, on a new "أمان الحساب" page limited to MFA (no self-service name/email/password changes). Secrets and recovery codes are encrypted at rest and hidden from serialisation. No new dependency. Migration reversible.
- **Security audit trail** — sign-in, sign-out, failed sign-in, rate-limited sign-in and password reset are written to the existing `audit_logs` table (never a password, token or code).
- **Backups** — `php artisan backup:run` writes a gzipped database dump, a zip of media originals and a SHA-256 manifest to private storage, with retention; scheduled daily 02:30. Restore tested locally: 45/45 tables and 516/516 media files identical. No new dependency.
- **Readiness widget** — computed states for lead-notification email, privacy policy, Search Console, GA4, review CTA, two-factor enrolment and backups.
- **Search Console + GA4** — verification meta printed only for a valid token; GA4 reuses the existing `google_analytics_id` setting and stays off until explicitly enabled *and* the privacy policy is published.
- **Google review CTA** — shown on the contact page only when a real review URL is saved; asks for an honest review, with no incentive and no rating steering.
- **Areas coverage directory** — `/areas` now lists all 84 served districts grouped by direction; only areas with a published page are links.

### Added
- **Conversion tracking** — `conversion_events` table; `POST /e` beacon endpoint (throttled 30/min, bots dropped, 10 s dedupe, HMAC session hash, no raw IP); WhatsApp/phone click and quote-form-start tracking in `resources/js/tracking.js`; server-side recording of quote/contact submissions; page attribution code `V-XXXXXXX` appended to WhatsApp messages and resolvable in the CRM. Migration reversible.
- **Leads CRM pipeline** — statuses New → Contacted → Qualified → Quoted → Won → Completed / Lost / Spam, auto-stamped stage dates, assignee, lost reason, linked project, device type, attribution code, marketing consent; Sales role sees only its own leads; `leads:prune-personal-data` (daily 03:00: IP/UA cleared after 90 days, spam removed after 30). Migration reversible.
- **Media verification** — status (ready / pending / private / replace), privacy status, media type (real / stock / placeholder), verified description, stage, service/area, consent ref, content hash. Model-level rules: held photos are always private; nothing becomes ready without cleared privacy, alt text and a verified description; placeholders are never ready; only `approve_media` holders can approve. `media:import-library` imported the company library (504 photos: 91 ready, 340 pending, 73 private). Migration reversible.
- **Responsive images** — GD-generated WebP sizes (480/960/1600 w) stored in `media.variants`, queued on upload (`GenerateMediaVariants`), backfill `media:generate-variants`, memory-guarded; `srcset`/`sizes` on 40 public `<img>` tags. No new dependency.
- **Publishing Gate checks** — media must be publishable (no pending/private/placeholder), service capability must be *available*, area Tier C blocked and Tier B forced noindex, project requires owner confirmation.
- **Service capability** (available / needs confirmation / not available) and **area tiers** (A / B / C) with promotion date and reason. Migration reversible.
- **Project verification** — `source_ref` (library group id) and `owner_confirmed_at`; only `confirm_project` holders can confirm. Migration reversible.
- **Testimonial approval** — source, source reference, customer consent, approved_at/by; public pages show approved reviews only; editing an approved review's text withdraws approval. Migration reversible.
- **Production content** — `ProductionContentSeeder` (idempotent, never overwrites): 17 services (4 fully written pages with blocks, FAQs and verified images; 7 awaiting capability confirmation), 86 areas in 6 groups (9 A / 75 B noindex / 2 C without page), 47 project candidates (unconfirmed, private photos excluded), About/Privacy/Terms shells, 60 related-service links. **All pages are drafts.**
- **Business profile** — approved NAP (name, phone, WhatsApp, city) via `CompanyProfileSeeder`; remaining fields left for the owner.
- **Roles & permissions** — new *Media Manager* role; new permissions `approve_media`, `confirm_project`, `approve_testimonial`, `view_conversion_reports`.
- **Admin dashboard** — conversion stats (30 days), top services / areas / sources, launch-readiness widget (photos pending, projects unconfirmed, services unconfirmed, reviews unapproved).

### Changed
- `robots.txt` disallows `/e`. Public layout carries `csrf-token` and tracking endpoint meta tags.
- Lead Filament resource rewritten (fixed a latent crash from a wrong `Section` import).
- Test base class fakes only `GenerateMediaVariants`; `phpunit.xml` sets `memory_limit=512M`.

### SEO impact
- No page was published or unpublished; no URL changed. All new pages are drafts, so sitemap output is unchanged until pages are published one by one through the Gate.
- Tier B area pages are noindex by construction; Tier C areas have no page.
- Images now ship responsive WebP candidates (LCP improvement on mobile).

### Security
- Tracking endpoint stores no IP; lead personal data pruned on schedule.
- Known open item: `rich_text` blocks render raw HTML — a sanitiser (package) needs owner approval.

## [VibeCleanPro-Production-Baseline] — 2026-09-19

Release point before the production execution phase. Tag: `VibeCleanPro-Production-Baseline` on `8ce65b2`.

### State
- 27 commits · test suite green: 477 tests / 1928 assertions.
- Database: schema complete (44 tables), content tables empty.

### Backup
- Location: `D:/VibeCleanPro_Backups/2026-09-19_production-baseline/`
  - `code/vibecleanpro-full-history.bundle` — full git history (verified: restore clone reproduces 27 commits)
  - `code/working-tree.tar.gz` · `code/env.backup` · research document
  - `database/vibecleanpro.sql.gz` — mysqldump (44 tables)
  - `seo/VibeCleanPro_SEO/` — all SEO architecture and production documents
  - `media/VibeCleanPro_Media.zip` — 537-file media package (second copy on C:, identical SHA-256)
  - `SHA256SUMS` — checksums of every file

### Open
- No git remote yet — requires a private repository URL from the owner.
