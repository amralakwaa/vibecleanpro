# Area Project Support — Architecture Decision V1

**Date:** 2026-09-24  
**Mandate:** Use all 47 projects in 15-area SEO network, without polluting `projects.area_id` (location fact) with SEO strategy.

---

## 1. Current Architecture Audit

### Existing mechanisms investigated:

| Mechanism | Tables | Can it map "supporting projects" to area? | Verdict |
|-----------|--------|------------------------------------------|---------|
| `Area::projects()` | `HasMany via projects.area_id` | ❌ Only location-confirmed projects | NOT suitable for support mapping |
| `project_service` pivot | `project_id, service_id, is_primary` | Projects linked to services | PARTIAL — doesn't target specific area |
| `service_area` pivot | `service_id, area_id, is_active` | Services linked to areas | Together with above: too broad, uncontrollable |
| `article_area` pivot | `article_id, area_id` | Articles → areas | Works for articles, not projects |
| `offer_area` pivot | `offer_id, area_id` | Offers → areas | Works for offers, not projects |
| Content blocks | `type: rich_text/cta/faq/steps/image` | No project reference type | NOT suitable |
| Internal links | `from_page_id, to_page_id` | Page-level only | NOT suitable for project-area mapping |

**Conclusion:** No existing mechanism allows: "Project P supports Area A without claiming P was located in A."

The project-service-area chain (`project_service` + `service_area`) technically links a project to an area, but:
- It returns ALL service-linked projects for ALL services in an area (uncontrollable)
- It doesn't let you set `support_type` (primary vs. supporting) per area
- Admin cannot manage which specific projects appear on which area page

---

## 2. Recommended Solution: `area_project_support` Pivot

### Minimal migration (new pivot table only):

```sql
CREATE TABLE area_project_support (
    id            BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    area_id       BIGINT UNSIGNED NOT NULL,
    project_id    BIGINT UNSIGNED NOT NULL,
    support_type  ENUM('primary', 'supporting') NOT NULL DEFAULT 'supporting',
    sort_order    SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    is_active     BOOLEAN NOT NULL DEFAULT TRUE,
    created_at    TIMESTAMP NULL,
    updated_at    TIMESTAMP NULL,
    UNIQUE KEY uq_area_project (area_id, project_id),
    FOREIGN KEY (area_id) REFERENCES areas(id) ON DELETE CASCADE,
    FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE
);
```

### What this means semantically:
- `area_project_support` = "this project's portfolio content supports this area page"
- Does NOT mean "the project was located in this area"
- Separate from `projects.area_id` which remains a location-only field

### Model relationship additions:
```php
// Area model
public function supportingProjects(): BelongsToMany
{
    return $this->belongsToMany(Project::class, 'area_project_support')
        ->withPivot('support_type', 'sort_order', 'is_active')
        ->wherePivot('is_active', true)
        ->orderByPivot('sort_order');
}

// Project model  
public function supportedAreas(): BelongsToMany
{
    return $this->belongsToMany(Area::class, 'area_project_support')
        ->withPivot('support_type', 'sort_order', 'is_active');
}
```

### Controller change (PublicPageController::area()):
Replace the current location-only query:
```php
// CURRENT (location-only — returns 0 projects since no area_id is set):
$projects = $area->projects()
    ->whereHas('page', fn ($query) => $query->published())
    ...

// NEW (location-confirmed first, then supporting):
$locationProjects = $area->projects()  // area_id matches = actual location
    ->whereHas('page', fn ($query) => $query->published())
    ->with(['media', 'page'])
    ->get();

$supportProjects = $area->supportingProjects()
    ->whereHas('page', fn ($query) => $query->published())
    ->with(['media', 'page'])
    ->limit(6)
    ->get();

// Merge: confirmed location projects first, then supporting, no duplicates
$projects = $locationProjects->merge($supportProjects)->unique('id')->take(6);
```

### View changes needed:
- Add a visual separator between "confirmed location" projects and "supporting" projects
- Confirmed location: label `"مشروع نفذناه في [area]"` — only when `hasVerifiedLocation()` is true
- Supporting: label `"من أعمال فايب كلين برو في الرياض"` — city-level, no neighborhood claim

---

## 3. Migration File Structure

```
database/migrations/[timestamp]_create_area_project_support_table.php
```

Seeder data for التعاون supporting projects goes in:
```
database/seeders/content/ (existing content seeder pattern)
```

---

## 4. Impact Assessment

| What changes | Impact |
|--------------|--------|
| New migration | Low — additive only |
| Area model: new `supportingProjects()` | Low — new method, no existing behavior changed |
| Project model: new `supportedAreas()` | Low — new method |
| `PublicPageController::area()` | Medium — replace `$projects` query logic |
| Area page view | Low — add visual distinction for support_type |
| Existing `area->projects()` (location) | Unchanged — still works for future confirmed-location projects |
| `projects.area_id` semantics | Preserved — location-only |

---

## 5. Alternative: Stay with Service-Project Chain (No New Table)

**Option:** Use the existing `project_service` + `service_area` chain to display "related projects" on area pages.

**Why this is weaker:**
- Cannot control which specific projects appear per area
- Returns projects for ALL services in the area (may return 50+ projects)
- No `support_type` distinction (primary vs. supporting evidence)
- Admin cannot curate the selection
- No explicit `sort_order` per area

**Verdict:** Only viable as a temporary fallback. Needs `area_project_support` for long-term control.

---

## 6. Pending Decision

**Awaiting owner approval for:**

1. Create `area_project_support` migration
2. Add model relationships
3. Update `PublicPageController::area()`
4. Update area page view (visual distinction)
5. Populate التعاون supporting projects via seeder/DB

**Once approved:** Implementation is 1-2 hours. Migration is reversible (table drop).

---

**لا Commit · لا Push — في انتظار موافقتك.**
