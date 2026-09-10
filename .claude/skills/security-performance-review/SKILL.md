---
name: security-performance-review
description: "Application security + web performance review for Vibe Clean Pro. Use when touching authentication, authorization, validation, file uploads, forms handling user input, rate limiting, sessions, secrets — or when diagnosing/improving page speed, query count, image weight, or JS/CSS payload."
metadata:
  author: project
---

# Security & Performance Review

## Security checklist (apply what's relevant to the change)
Authentication and authorization boundaries (Policies/Gates actually enforced, not just present) · input validation via Form Requests · CSRF protection intact · XSS (escape output, avoid raw HTML from user input) · SQL injection (parameter binding, no raw string-built queries) · mass assignment (`$fillable`/`$guarded` correct) · file upload security (type/size/extension validation, storage outside public execution when needed) · rate limiting on public forms (contact, quote requests) · session security · no secrets in code or git history · production error pages must not leak stack traces/config · permissions checked at the actual authorization boundary, not just hidden in the UI.

## Performance: diagnose before treating
Never guess at a performance fix. Identify the actual cause first (query log, `search-docs`/Boost's `database-query` tool, browser network panel), then fix that specific cause.

Check: query count per request / N+1 (`with()`/eager loading) · image sizes and modern formats (WebP/AVIF) · lazy loading below the fold · Core Web Vitals (LCP, CLS, INP) · caching where it actually helps (config/route/view cache, query caching if justified) · Blade rendering cost · Livewire request frequency/payload · total JS and CSS payload — keep it lean given the Blade + Livewire + Alpine + Tailwind stack.
