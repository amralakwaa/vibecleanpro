# AREA INTERNAL LINKING MAP V1
Generated: 2026-09-24

## Principles
- Every published area page must be reachable from at least 2 other pages (not an orphan)
- Internal links must use descriptive anchor text — never "اضغط هنا" or "انقر هنا"
- Nearby-areas links are auto-rendered by the template (DB-managed)
- Additional editorial links must be added manually to relevant articles, service pages, and the homepage

---

## Hub Pages → Area Pages

### Homepage
Links to: top 6 priority areas (by tier and search demand)  
Recommended: العليا, الملقا, النخيل, القيروان, التعاون, العارض  
Anchor text format: "تنظيف حي [name]" or "خدماتنا في [name]"

### Services Index (`/services` or `/خدماتنا`)
Links to: area pages where that service is prominent  
Example: تنظيف بعد البناء service page → links to القيروان, العارض, الملقا

### Offers Index (`/offers`)
Links to: area pages where the offer applies  
If an offer is linked to a specific area, the offer page should mention and link that area.

---

## Area ↔ Area Links (Nearby Areas — DB-managed)

The template renders nearby areas automatically. The owner manages which areas appear as "nearby" for each area in the admin panel. Required adjacencies:

| Area | Nearby areas to configure |
|------|--------------------------|
| التعاون | النخيل, الصحافة, الربيع, حطين |
| العارض | القيروان, النرجس, الياسمين |
| القيروان | العارض, النرجس, الياسمين |
| الملقا | الياسمين, النرجس, الصحافة |
| الصحافة | الملقا, التعاون, الربيع |
| النخيل | التعاون, الصحافة, الربيع |
| الربيع | التعاون, النخيل, الصحافة |
| حطين | الملقا, الياسمين, النرجس |
| الياسمين | الملقا, حطين, القيروان |
| النرجس | القيروان, العارض, الياسمين |
| الغدير | العارض, القيروان |
| المونسية | الرمال |
| الرمال | المونسية |
| العليا | (central — link to service pages, not area pages) |

---

## Article → Area Links

When publishing articles, add contextual links to relevant area pages:

| Article topic | Links to area pages |
|---------------|---------------------|
| تنظيف بعد البناء بالرياض | القيروان, العارض, الملقا |
| أسعار تنظيف الفلل بالرياض | النخيل, الملقا, حطين |
| تنظيف المكاتب والمجمعات التجارية | التعاون, العليا |
| تنظيف المسابح في شمال الرياض | الربيع, النخيل, حطين |
| دليل تنظيف الواجهات | الملقا, العليا, الياسمين |

---

## Project → Area Link

Every published project page (34 project pages in DB) must link back to its area page.  
The template handles this automatically through the Project → Area relationship.

---

## Isolated Area Audit

After publishing each new area page, run:
```
php artisan tinker --execute '
$slug = "al-taawun";
$page = App\Models\Page::where("slug", $slug)->first();
// check if any other page links to it
'
```
This is a manual check — scan the homepage, service pages, and nearby-areas config.

---

## Anchor Text Policy

| ✅ Allowed | ❌ Forbidden |
|-----------|-------------|
| تنظيف حي التعاون | اضغط هنا |
| خدمات التنظيف في القيروان | انقر للمزيد |
| تنظيف بعد البناء في العارض | تعرف على المزيد |
| فايب كلين برو في حي النخيل | خدماتنا |
