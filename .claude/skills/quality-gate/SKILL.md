---
name: quality-gate
description: "Final pre-delivery review gate for a completed feature in Vibe Clean Pro. Use before marking a non-trivial feature done — not for small isolated edits, and not as a rewrite pass on code that is already sound."
metadata:
  author: project
---

# Quality Gate

Review the finished work from six angles, but only report real, important findings — this is not a rewrite pass:

1. Architecture (see `software-architecture`)
2. Security (see `security-performance-review`)
3. Database (see `database-engineering`)
4. Performance (see `security-performance-review`)
5. UI/UX (see `world-class-ui-ux`)
6. SEO (see `seo-local-search`) — only if the feature touches public/indexable pages

## Output
Rank findings **Critical / High / Medium / Low**. Fix Critical and High findings that originated in the current task before calling it complete, when the fix is safe to make without further approval. Report Medium/Low without necessarily fixing them now. Don't rewrite code that is already correct just to match a stylistic preference.
