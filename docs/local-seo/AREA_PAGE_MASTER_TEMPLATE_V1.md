# AREA PAGE MASTER TEMPLATE V1
Generated: 2026-09-24

This template defines the required structure, content rules, and SEO requirements for every area page published on Vibe Clean Pro. The Laravel template is `resources/views/pages/area.blade.php`. The admin panel and DB content blocks populate the dynamic sections. This document is the content specification — not the blade template.

---

## URL Structure
`/[area-slug]` — e.g., `/al-taawun`  
Canonical: self-referencing  
Robots: index, follow  
Sitemap: yes  
Schema: LocalBusiness + Service + BreadcrumbList

---

## Meta Title
**Pattern**: `[خدمة/وصف مركّز] في حي [اسم الحي] بالرياض | فايب كلين برو`  
**Rules**:
- Must contain the area name in Arabic
- Must contain "الرياض"
- Must describe a specific service angle (not generic "تنظيف")
- 50–65 characters
- No fake claims (no "أفضل", "رقم 1", "الأرخص", "مضمون 100%")
- Must differ from all other area page titles (zero near-duplicates)

**Examples**:
- ✅ `تنظيف مكاتب ومجمعات تجارية في حي التعاون بالرياض | فايب كلين برو`
- ✅ `تنظيف بعد البناء في حي القيروان بالرياض — فلل ومنازل | فايب كلين برو`
- ❌ `شركة تنظيف حي التعاون الرياض خصم 30% مضمون`
- ❌ `تنظيف شامل في حي التعاون والأحياء المجاورة بالرياض` (generic second half)

---

## Meta Description
- 120–160 characters
- Contains area name + primary service angle
- Ends with a soft call-to-action (e.g., "تواصل للحجز")
- No fake numbers (no customer counts, year claims, guarantees)

---

## H1
- Single H1 per page
- Must use the area name: `شركة تنظيف في حي [اسم الحي] بالرياض`
- OR a more specific variant that reflects the page's primary angle
- Must NOT repeat the meta title verbatim
- Must NOT contain "أفضل", "أرخص", "رقم 1"

---

## Page Sections (in order)

### 1. HERO
- Typographic (no fake photos)
- Area name prominent
- Sub-heading: primary service angle for this area
- CTA: "اتصل الآن" → tel:+966534999194 or WhatsApp link

### 2. EVIDENCE BAND (Projects / شواهد العمل)
**GATE**: Only visible if the area has ≥ 1 project record in DB  
- Shows before/after photos from real documented projects
- Each project card: service type, scope, neighborhood sub-zone if known
- No placeholder images
- No stock photos
- If empty → section is hidden automatically (Laravel template handles this)

### 3. CMS CONTENT BLOCKS (5 Rich Text Blocks)
Written in Arabic. Each block covers one of:
- Block 1: Introduction to the area + why cleaning matters here (area-specific context — not generic)
- Block 2: Primary service spotlight (the area's #1 service based on SERP intent)
- Block 3: Secondary service detail
- Block 4: Scope & methodology (what we actually do, no invented technology claims)
- Block 5: Why Vibe Clean Pro (factual: trained team, safe materials, real completed work) — no fake awards or certifications

**Rules for content blocks**:
- Never mention a service not in the area's service list (DB-checked)
- Never invent prices ("starting from X SAR")
- Never invent timelines ("done in 2 hours")
- Never invent guarantees ("100% satisfaction guaranteed")
- Never use area name more than 3× in a single block
- Each block must have ≥ 1 sentence that is genuinely area-specific (not copy-paste from another area)

### 4. SERVICES (Grouped by Category)
- Auto-rendered from DB: `$area->services` grouped by `category->name`
- Service names are DB-managed — do not hard-code
- Tier A areas: short focused list
- Tier B areas: full list grouped into categories (e.g., داخلي, خارجي, تخصصي)

### 5. OFFER (if linked)
**GATE**: Only visible if area has an active linked offer  
- No fake prices
- No expiry dates unless real

### 6. TESTIMONIALS
**GATE**: Only visible if area has ≥ 1 testimonial  
- Real client quotes only
- No invented reviews
- No fake stars
- If empty → hidden automatically

### 7. NEARBY AREAS
- Auto-rendered from `$nearbyAreas` (DB-managed)
- Anchor text: area name only (no "best cleaning in X")
- Internal linking — no rel="nofollow" needed

### 8. ARTICLES
**GATE**: Only visible if area has linked articles  
- Links to relevant blog posts (e.g., pricing guide, seasonal tips)
- Article must be genuinely relevant to this area's services

### 9. FAQ
**GATE**: Only visible if area has FAQ items  
- Answers real questions (pricing range is OK if honest, timing, what's included)
- FAQPage JSON-LD schema attached
- No keyword stuffing in Q or A

### 10. DECISION CTA
- Final call-to-action
- Phone + WhatsApp
- Area name used once: "تحتاج خدمة تنظيف في [اسم الحي]؟"

---

## Schema.org Requirements

```json
{
  "@type": "LocalBusiness",
  "name": "فايب كلين برو",
  "telephone": "+966534999194",
  "areaServed": {
    "@type": "Place",
    "name": "[Area Arabic Name]",
    "containedInPlace": {
      "@type": "City",
      "name": "الرياض"
    }
  },
  "hasOfferCatalog": {
    "@type": "OfferCatalog",
    "itemListElement": [/* services from DB */]
  }
}
```

BreadcrumbList:
- Home → خدماتنا (or root) → [Area Name]

---

## Doorway Page Test
Before publishing, confirm ALL of these are TRUE:
- [ ] The page contains content not found on any other area page
- [ ] The SERP intent is served (user question answered without leaving the page)
- [ ] A human reader would find this page genuinely useful
- [ ] The area name appears in natural context, not forced
- [ ] The page does not redirect to another page after load
- [ ] At least 1 section of evidence (project, testimonial, or detailed scope) is present

---

## What This Page Is NOT
- Not a city-wide service page with the area name swapped in
- Not a page whose only purpose is to rank for "[area] + تنظيف"
- Not a page with invented local facts (fake client stories, invented landmarks)
- Not a page generated from a formula with no genuine human editorial input
