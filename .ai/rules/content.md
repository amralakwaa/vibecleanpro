---
paths:
  - 'database/seeders/content/**'
  - 'database/seeders/content/wave1-services*.php'
---

# Content

## Media availability never gates building a service page
Owner rule (2026-09-20): a service gets a page whenever the company can genuinely perform it. Missing photos are never a reason to skip or defer building the page — pick the real media that exists, and leave the page and its media editable from the panel so they can be replaced later.

Execution order is decided by, in order: search demand, commercial intent, competitive opportunity, business value, then available proof. Media strengthens trust and conversion; it is not an entry condition.

Still binding: the service's capability_status must be confirmed (a page for a service the company cannot perform is a lie), and the Publishing Gate still refuses any page that references pending/private/placeholder media. Missing operational detail is asked of the owner, never invented.

## Owner-supplied trust points may be used; their specifics must be asked, never invented
Owner stated on 2026-09-20 that these are facts and may appear on the homepage, About, service pages, trust sections and CTAs: a Saudi team, accreditation certificates, and a warranty of up to 10 years.

Allowed: state them in confident marketing language ("فريق سعودي مدرب", "معايير جودة واضحة", "ضمان يصل إلى 10 سنوات حسب نطاق الخدمة وشروطها").

Never: invent a certificate name, an issuing body, a registration number, a warranty period per service, or any warranty term. Those specifics come from the owner only.

A warranty is a commitment a customer will claim. Publish it only alongside the service it covers and its conditions; a bare "10 years" on a cleaning page reads as implausible and is a consumer-protection exposure. The Terms page still lists warranty scope as NEEDS OWNER INPUT — keep them consistent.

## Wave 1 service content: several files, one slug, no duplicates
ServiceContentSeeder loads every `wave1-services*.php` file and groups definitions BY SLUG, so one page may get a `page` definition in one file and extra `append` sections in another. Rules that matter:
- `append` sections must carry a unique `section` key; it is stamped into the block data and is the only thing preventing duplicates on re-run.
- `page` mode is skipped entirely once the page has any content block, so an editor's work is never overwritten.
- An `image` block whose `media_file` is missing or not `status = ready` is dropped, never written with an empty media_id. A missing photo must never block or delay a page.
- After each page, the seeder scans the written body for `href="/services/{slug}"` and records those as `internal_links` rows with context `body_link`. Never create link rows for anchors that are not actually in the text - the orphan/inbound checks are supposed to describe the rendered HTML.
- The seeder publishes nothing. Publishing is `site:launch` + the manifest + the Publishing Gate.

## Credentials, licences and service scope: the standing answers
Settled on 2026-09-20 so nobody re-opens them:
- CERTIFICATES: the site may say the company holds accreditation (owner-authorised), but must NEVER name a certificate, issuing body, number or date until a document exists in hand. Generic wording only.
- PEST CONTROL (Wave 3): the page ships with no licence number and no licensing claim of any kind. It describes scope only.
- WATER TANKS: scope is cleaning + disinfection with materials fit for drinking-water tanks. Insulation, repairs and plumbing are explicitly out of scope; the page says we report what we see and nothing more.
- AC: scope is cleaning filters, coil, fan, drain pan and drain line for split, window, central/duct and concealed units. Refrigerant, compressor and electrical repair are explicitly excluded on the page.
- TERMS: the six policy clauses (scheduling/cancellation, payment, client duties, warranty, liability, complaints + governing law) are written and published; they carry no fee amount, no period, and no claim of legal review. A lawyer has NOT reviewed them - if one ever does, update the page, not the code.
