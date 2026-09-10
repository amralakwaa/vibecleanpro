---
name: software-architecture
description: "Principal Software Architect judgment for Vibe Clean Pro. Use when designing a new module or domain, changing architecture, planning a large feature, choosing between patterns, deciding whether a Service/Action/Repository is warranted, or reviewing scalability/maintainability of a design. Not for small isolated code edits."
metadata:
  author: project
---

# Software Architecture (Principal-level review)

Vibe Clean Pro is a **Laravel Modular Monolith**. Apply these rules to any non-trivial design decision.

## Priorities, in order
1. Laravel conventions first — don't fight the framework.
2. KISS — the simplest design that satisfies known, real requirements.
3. SOLID applied moderately — a guide, not a ritual. Don't add abstraction layers to satisfy a principle in the abstract.
4. DRY without premature abstraction — three similar lines beat a speculative shared helper built for a future that hasn't arrived.
5. Separation of concerns — thin controllers, Form Requests for validation, Policies for authorization, Services/Actions only when there is real reuse or real orchestration complexity (not by default).

## Before any significant architectural decision
Compare the simplest viable solution against the more complex one, against the actually-known future requirements (not hypothetical ones), and choose the simplest one that satisfies them. State the tradeoff in one line if it's non-obvious; don't narrate a long internal debate.

## Hard rules
- No God classes (a class doing controller + business logic + query building + formatting).
- No circular dependencies between modules/domains.
- Wrap multi-step writes that must succeed or fail together in DB transactions.
- Code must be maintainable by a different team later: obvious names, no clever tricks, no unexplained magic.

## Module boundaries
Keep each domain (e.g. Services, Areas, Projects, Articles, Offers) self-contained: its own models, its own Livewire/Filament resources, its own routes where practical. Cross-domain reads are fine; cross-domain writes should go through the owning domain's own Action/Service, not direct Eloquent manipulation from outside.
