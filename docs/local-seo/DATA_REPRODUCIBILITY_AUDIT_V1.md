# Data Reproducibility Audit V1

**Date:** 2026-09-24  
**Question:** Are all changes from this session reproducible from `git clone` + `php artisan migrate:fresh --seed`?  
**Requirement:** `fresh migration + fresh seed = same intended state`

---

## Changes Made in This Session

### Category A: Code changes (in git, reproducible)

| File | Change | In Git? |
|------|--------|---------|
| `app/Http/Controllers/HomeController.php` | SEO title wording | ✅ YES |
| `app/Http/Controllers/OffersIndexController.php` | SEO title wording | ✅ YES |
| `app/Http/Controllers/ServicesIndexController.php` | SEO title wording | ✅ YES |
| `tests/Feature/StaticPagesTest.php` | 2 comment lines | ✅ YES |
| `docs/local-seo/*.md` (11 files) | Planning documents | ✅ YES (untracked, pending commit) |

### Category B: DB-only changes (NOT in git, NOT reproducible)

| Change | Table | Reproducible? | Source of Truth |
|--------|-------|--------------|-----------------|
| 47 project titles | `projects.title` | ❌ DB-only | Must add to seeder |
| التعاون content blocks 1007-1011 | `content_blocks.data` | ❌ DB-only | Must add to seeder |
| التعاون CTA block 1013 | `content_blocks.data` | ❌ DB-only | Must add to seeder |
| article_area links (A1,A2,A9 → التعاون) | `article_area` | ❌ DB-only | Must add to seeder |
| 47 project area_id (REVERTED to NULL) | `projects.area_id` | N/A — reverted | — |
| area_project_support data (PENDING) | new table | ❌ Pending migration | Will need seeder |

---

## Source of Truth Investigation

### How does the project currently seed DB content?

The main seeder for production-equivalent content is:
```
database/seeders/ProductionContentSeeder.php
```

And content-specific seeders in:
```
database/seeders/content/
```

The `ProductionContentSeeder` appears to handle:
- Business profile
- Services + service categories  
- Areas + service-area relationships
- Articles
- Media (references)
- Projects (with `source_ref` identifiers)
- Content blocks for pages

### What needs to be added to achieve reproducibility:

#### 1. Project Titles
Find where projects are seeded (by `source_ref`) and add the new titles there. Or update the project seeder to set titles.

#### 2. التعاون Content Blocks
The content blocks are seeded as part of the area page setup. The seeder should include the new content for blocks 1007-1011 and 1013.

#### 3. article_area relationships
The `ArticleContentSeeder` or a dedicated area-article seeder should include:
- A1 (أسعار) → التعاون
- A2 (كيف تختار) → التعاون  
- A9 (نطاق مكاتب) → التعاون

#### 4. area_project_support data (after migration approved)
A seeder should populate the التعاون supporting projects pivot once the table exists.

---

## Reproducibility Action Plan

**Before any commit, these seeders must be updated:**

1. `database/seeders/content/` — find project title seeder and update 47 titles
2. `database/seeders/content/` — find التعاون area content seeder and update blocks
3. `database/seeders/content/` — add article-area relationships
4. After migration: add `area_project_support` seeder data

**Verification test** (Section 8 of mandate): 
A test that runs `db:seed` in a fresh DB and asserts the intended state matches. See `DATA_REPRODUCIBILITY_TEST` below.

---

## Current Status

| Requirement | Status |
|-------------|--------|
| Fresh seed reproduces project titles | ❌ NOT YET — seeder not updated |
| Fresh seed reproduces التعاون content | ❌ NOT YET — seeder not updated |
| Fresh seed reproduces article relations | ❌ NOT YET — seeder not updated |
| Fresh seed reproduces area_project_support | ❌ PENDING migration approval |
| Code changes in git | ✅ YES (uncommitted, but tracked) |

**Reproducibility = NO** until seeders are updated.

---

## Next Steps (Pending Owner Approval)

1. **Approve** `area_project_support` migration
2. **Find** the relevant seeder files for projects and content blocks
3. **Update** seeders with: project titles, التعاون content blocks, article-area links
4. **Add** area_project_support seeder data
5. **Write** Fresh Seed Equivalence Test
6. **Run** test to verify

---

**لا Commit · لا Push — في انتظار موافقتك.**
