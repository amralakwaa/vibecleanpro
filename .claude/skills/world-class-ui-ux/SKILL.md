---
name: world-class-ui-ux
description: "Senior Product/UI/UX design review for Vibe Clean Pro (Arabic RTL, mobile-first cleaning-services brand). Use when designing or reviewing any page, component, or Design System element — layout, typography, spacing, buttons, cards, forms, CTAs, responsiveness, accessibility. Not for pure backend/logic work."
metadata:
  author: project
---

# World-Class UI/UX (Arabic RTL, Mobile-First)

Audience: homeowners in Riyadh looking for a trustworthy cleaning service, mostly on mobile.

## Before designing any page, answer first
- What does the user want here?
- What must they see first?
- What increases their trust?
- What drives them to contact / WhatsApp?
- What can be deleted?

## Non-negotiables
- **RTL first**: build and review in Arabic RTL, not as an afterthought translation of an LTR layout.
- **Mobile-first**: design for the phone viewport first, then adapt up.
- Contact and WhatsApp must be reachable in 1–2 taps from any page.
- Clear visual hierarchy; one obvious primary CTA per screen/section.
- Excellent Arabic typography (line-height, letter/word spacing, font pairing) and consistent spacing scale.
- One unified Design System reused everywhere: colors, typography, spacing, buttons, cards, forms, icons, radius, shadows, containers, breakpoints, section patterns. Don't invent one-off styles per page.
- Accessible: real contrast ratios, labeled forms, keyboard-usable, no meaning conveyed by color alone.
- Forms are short, clearly labeled, and forgiving (inline validation, no dead ends).
- No decorative-only animation. No heavy frontend libraries pulled in for a small visual effect — build with Blade + Livewire + Alpine.js + Tailwind.
- Avoid both failure modes: a cheap generic template look, and an over-designed/cluttered one. Target: clean, trustworthy, professional — a real service brand, not a demo theme.

## Before approving any page
Check Desktop, Tablet, and Mobile, all in RTL. Confirm the SEO-critical content (H1, main copy) is visible to users, not hidden behind interaction (see seo-local-search skill for the corresponding SEO-side check).
