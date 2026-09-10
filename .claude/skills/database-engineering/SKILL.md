---
name: database-engineering
description: "Senior MySQL/Eloquent database engineering for Vibe Clean Pro. Use when creating or changing a migration, schema, relationship, or an important/performance-sensitive query. Not needed for simple read-only queries using existing, already-indexed columns."
metadata:
  author: project
---

# Database Engineering (MySQL + Eloquent)

## Review every schema change for
Cardinality and correct relationship type · normalization (and deliberate, justified denormalization) · foreign keys · unique constraints · index coverage for actual query patterns · nullable vs required fields with sensible defaults · cascade behavior (`onDelete`/`onUpdate`) chosen deliberately, not left default · data integrity · N+1 risk in the queries this schema will serve · pagination for any list endpoint · transactions around multi-step writes · migration rollback (`down()` must actually reverse `up()`) · compatibility with existing data before altering a live column.

## Hard rules
- Never add an index by guessing — justify it by an actual query pattern (`WHERE`, `JOIN`, `ORDER BY` on that column/combination).
- Prefer a relational structure over a JSON column whenever the data is clearly relational (has its own identity, needs to be queried/filtered/joined independently).
- Never run a destructive migration (dropping a column/table with data, a lossy type change) without explicit user approval — propose it and stop.

## Arabic content
Use `utf8mb4` / `utf8mb4_unicode_ci` (already the project default) for any column holding Arabic text.
