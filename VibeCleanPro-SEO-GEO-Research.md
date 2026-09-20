# Vibe Clean Pro — Pre-Launch SEO + GEO + Local Search Research (Riyadh)
Research only. No content written, no code changed. Date: 2026-09-18.

---

## 1. Tools / skills / data sources actually used

| Used | What it gave |
|---|---|
| **Google Autocomplete** (`gl=sa`, `hl=ar`, `client=firefox`) | 283 seed queries → ~2,000 real Saudi suggestions (service heads, modifiers, prices, questions, districts, English). The only Google-native data that was reachable. |
| **DuckDuckGo HTML** (`kl=sa-ar`, Bing index) | 42 SERPs, top-10 organic each → competitive set, page types, title patterns. Used as a *proxy* for organic competition, not for Google rank positions. |
| **WebSearch** (US index) | 1 cross-check query (villa cleaning) — confirmed the same domains appear. |
| **Local Python crawler** (from the local machine, residential IP) | 60 competitor URLs requested, 52 parsed: title, description, canonical, robots, H1/H2/H3 counts, word count, price mentions, FAQ, JSON-LD types, images/alt, tel/WhatsApp links, internal links, districts mentioned, claim words, ratings/before-after mentions, "بالرياض" repetition. |
| **In-app browser** (sandbox) | DOM extraction for cleaner4me pages; Google/Bing/Trends probing. |
| **WebFetch — Google Search Central** | "AI features and your website", LocalBusiness structured data, FAQ/HowTo status. |
| Project skill `seo-local-search` | Hard rules applied (no Service×Area matrix, content graph, per-page checklist). |
| Codebase inspection | `app/Seo/*` (StructuredDataGenerator, PublishingGate, Canonical/Sitemap/Robots), routes, block types, PublicPrice. |

**Available but not used:** searchfit-seo plugin agents (competitor-analyzer / seo-auditor / content-strategist — only have WebFetch/WebSearch on a US index; would re-derive the same set), `quality-gate` / `world-class-ui-ux` / `database-engineering` / `security-performance-review` / `software-architecture` skills (not research skills), Claude in Chrome (no Chrome instance connected to this account), PageSpeed Insights / Rich Results Test (site is localhost-only, not publicly testable yet).

## 2. Data sources unavailable (nothing was invented for these)

| Source | Status |
|---|---|
| Google SERP pages (organic positions, Local Pack, PAA, Related searches) | **Blocked**: Google served its anti-bot page to the sandbox browser, to a plain client, and to headless Edge on the local network. I did not attempt to bypass CAPTCHA. |
| Google Trends | HTTP 429 from the sandbox. |
| Keyword Planner / Search Console / GBP Insights / GA4 | No account access. |
| Ahrefs, Semrush, Moz, DataForSEO, SE Ranking, KeywordTool, Similarweb, Screaming Frog | Not available in this environment. |
| Bing SERP | Served decoy results to the sandbox (bot detection). |
| malekclean.com crawl | Cloudflare 403 (titles known from DDG only). |

**Consequence:** Search volume, CPC, keyword difficulty and SERP-feature presence are **DATA NOT AVAILABLE** for every keyword. Demand is graded qualitatively from (a) autocomplete presence and depth, (b) how many competitors invest in dedicated pages, (c) price/best/near-me modifiers appearing. Local Pack / PAA presence per cluster is inferred from autocomplete signals ("مفتوح الآن", "قريب مني", "الأعلى تقييمًا", question forms) and competitor page formats — labelled as inference wherever stated.

## 3. Methodology

1. Seed list from the business scope (27 services) → Arabic colloquial variants (تنظيف/غسيل/جلي/رش/نظافة) → autocomplete wave 1 (64 seeds), wave 2 (134: prices, best/cheapest, questions, districts, English), wave 3 (83: GEO question forms + alphabet expansion of the head term "شركة تنظيف بالرياض").
2. 42 SERPs on the Saudi-region DDG index (one per cluster + pricing + best + district + English) → domain frequency & top-3 counts → competitive set.
3. Crawl of 52 competitor pages (homepages, service pages, pricing guides, district pages, listicles) → on-page pattern matrix.
4. Google Search Central re-read (AI features, LocalBusiness, FAQ/HowTo deprecations) → constraint set.
5. Cannibalization/architecture mapping against the actual Vibe routes and blocks.
6. Independent second-pass review (section 30 / QA note at the end).

## 4. Saudi / Riyadh search-language findings (evidence: Google Autocomplete SA)

- **Head root** is "شركة تنظيف بالرياض" (company + city). "تنظيف X بالرياض" and "شركة تنظيف X بالرياض" co-exist for every service; both must be covered by one page each, never two.
- **Colloquial verbs matter:** غسيل كنب / غسيل مجالس بالبخار / غسيل سجاد / غسيل مكيفات سبليت / غسيل خزانات; جلي بلاط + تلميع رخام (never "تنظيف رخام" as the commercial form); رش مبيدات ≈ مكافحة حشرات; نظافة (شركة نظافة، عقود نظافة، نظافة منازل).
- **Property vocabulary:** منازل (umbrella), بيوت (secondary), فلل, شقق, قصور, عمائر, استراحات, شاليهات, احواش.
- **Modifiers with intent:** عمالة فلبينية / فلبينيين (labour nationality — one of the most frequent Saudi modifiers), بالساعة (+نساء/رجال), مفتوح الآن / قريب مني / الأعلى تقييمًا (Maps/Local-Pack intent), رخيصة / أرخص, أفضل, معتمدة, بلدي (municipal licence), حراج / انستقرام / تيك توك / تويتر (social discovery), بالبخار, سبليت / شباك / مركزي / دكت, أرضي / علوي (tanks), جديدة / قبل السكن / بعد التشطيب (move-in), شمال / شرق / غرب / جنوب الرياض.
- **Pricing language:** "اسعار شركات التنظيف في الرياض", "اسعار تنظيف المنازل بالرياض", "أسعار تنظيف الفلل الجديدة", "سعر تنظيف الشقة", "أسعار تنظيف الكنب بالرياض", "سعر غسيل السجاد في الرياض / بالمتر", "سعر تنظيف الخزان الارضي/العلوي", "اسعار تنظيف خزانات بالرياض", "اسعار تنظيف المكيفات السبلت", "كم سعر تنظيف مكيف سبليت", "اسعار جلي الرخام في الرياض", "سعر متر تلميع الرخام", "اسعار مكافحة الحشرات بالرياض", "اسعار عقود النظافة". English: "… riyadh price list" is the dominant English pattern.
- **No/low demand for the service name as written internally:** "تنظيف دوري" (football), "إدارة مرافق" (jobs/FM industry), "تنظيف زجاج" (cars/DIY), "تنظيف مطابخ/حمامات/أرضيات" (DIY/dream-interpretation), "تعقيم" alone (medical/pets). Their commercial forms are narrower: "تنظيف واجهات زجاج بالرياض", "تعقيم منازل بالرياض", "تنظيف مطابخ بالرياض" (minor).
- **"عقود نظافة بالرياض" is a trap:** the SERP is dominated by the cheap "عقد نظافة معتمد بلدي" product (a paper contract for municipal licence renewal, 50–150 ريال). That is not Vibe's B2B contract intent.
- **Carpets:** suggestions skew to laundries and pickup ("مغسلة", "قريب مني", "بالمتر"; wetndrylaundry ranks #1) — in-place carpet cleaning must be positioned explicitly.
- **Districts:** autocomplete confirms demand only for: العليا (strongest: شقق/فلل/منازل/خزانات + "شركة تنظيف بالرياض العليا"), الملقا, النرجس (+منازل), الياسمين (+خزانات), العقيق (+منازل), اليرموك, الرمال (+بيارات), القيروان, الدرعية (+فلل/منازل), لبن (+شقق/منازل/كنب/خزانات). **No suggestions** for حطين، الصحافة، قرطبة، المونسية، الروضة. "العارض" is confounded with "العارضية". Directional demand is real: شمال الرياض (+منازل/فلل/شقق/خزانات/مكيفات/موكيت), شرق (+كنب), غرب, جنوب.
- **English intent exists but is secondary:** expat, price-list and app/marketplace oriented (Urban Company, justclean, Raha, HomeRun). Arabic first; English pages only for the 4–5 highest-intent services, later wave.

## 5. Competitors discovered (DDG sa-ar, 42 SERPs; n = appearances, t3 = top-3 appearances)

| Tier | Domain | n / t3 | Character |
|---|---|---|---|
| A (broad) | cleaner4me.com | 19 / 8 | Multi-city hub; 4,600-word Riyadh page with prices, steps, every district, FAQPage+Service+LocalBusiness schema, before/after mentions |
| A | malekclean.com | 12 / 8 | "أرخص شركة" positioning, east/west Riyadh, all clusters (crawl blocked) |
| A | cleanspot.sa | 12 / 5 | Bilingual, transparent price tables, blog price guides, Offer/OfferCatalog schema; strongest in pricing & English SERPs |
| A (spam) | cleaning-company-riyadh.com | 11 / 5 | 7,935 words, "بالرياض" ×312, discount titles, 423 internal links — spam pattern, still ranks on the Bing index |
| A (listicle) | alsauditoday.com | 10 / 5 | "أفضل 30 شركة تنظيف بالرياض" — directory-style article |
| B | cleanservice.com.sa | 10 / 3 | Brand site; weak on-page (32/55 images without alt, no schema) |
| B (directory) | cleaningdirectory-riyadh.com | 10 / 2 | Directory/spam, 191 city repeats |
| B (specialist) | aljawadcleaning.com | 10 / 1 | Facades specialist + district pages + before/after projects |
| B | cleanhomesa.com | 9 / 2 | "عمالة فلبينية" positioning, price guide (3,190 words) |
| B | almaheron.com | 8 / 1 | Guides ("الخدمات والأسعار وكيفية الحجز"), title bug |
| B (programmatic) | saudi-cleaning.com | 8 / 1 | `/riyadh/al-malqa` + `/services/x/riyadh/area` = Service×Area matrix (doorway pattern) |
| B | clean-hoouse.com, evercleansa.com (AR/EN), homerun.com.sa (marketplace), alshmasicleaning.com (AR/EN) | 7 | — |
| C | zadksa.com (price-in-title "180 ريال"), baytclean, elmasa.sa, ultraclean.sa (EN premium), nasayem-clean (guides), prokr.co (directory), greecleaning (comparison guide), fayruz-sa, nile.sa, raquen, alafdall.com.sa ("مسجلة منذ 2013، 47 خدمة"), basmetelriyadh, elmagdclean, naba-clean (district guides), middleeastservices (EN), roknnagd, albaarq, shehab-control, awalclean, becleansa, cleanoxco, noorcleanriyad, cleandari ("أسعار معلنة قبل الحجز", Saudi team) | 2–6 | — |
| Specialists | Marble: jaliriyadh, montmarble, marble-ksa, jalibalatriyadh, maestro-clean, bariqcleaning · AC: mukayefat (35 ريال), alarab-takyf (40 ريال), experttechniciansriyadh, cleaningksa · Tanks: cleantank-ksa, etqan-elkaleg, shehab-control · Pools: mehwaralmadar, almasacompany, alzakia-alalamia · Pest: saudi.wiki (listicle #1), lotus-ksa, insectsriad, pestriyadh, mokafeha-riyadh · Contracts (municipal): etf-lab, abeerelriyad, contracts-sa, awalclean · Hourly maids: rahahome, nouralebda, anwar-riyadh, mansoor-care, shaghalat-riyadh | — | Cluster-specific winners |
| Sahm Clean | sahmclean.sa + sahmclean.shop | 0 / 0 | Not in any of the 42 top-10 lists. Two domains splitting the brand; `.sa` has no H1, no schema, 25/25 images without alt, 567 words; `.shop` home is 135 words. Services: villas, apartments, furniture, gardens, sofas, restaurants/shops, pests, tanks, AC. Weak organic threat; social-driven. |

## 6. Competitor × Service matrix (who actually shows up per cluster; ● = top-3, ○ = top-10)

| Cluster | cleaner4me | malekclean | cleanspot | cleaning-company-riyadh | alsauditoday | cleanhomesa | saudi-cleaning | aljawad | zadksa | evercleansa | specialists / others |
|---|---|---|---|---|---|---|---|---|---|---|---|
| Generic "شركة تنظيف" | ● | ● | | ○ | ○ | ○ | | | | | cleanservice ●, alafdall, exaclean, cleanforce |
| Homes | ○ | ● | | | ● | ○ | | | | | homerun ●, nile, d2all, elmasa |
| Villas | ● | ● | | | ○ | ○ | | | | ● (EN) | becleansa ●, cleanservice, greecleaning, operon |
| Apartments | ○ | ● | | | ● | | ○ | | | | basmetelriyadh ●, roknnagd, elmasa, baity, eltfwaq |
| Sofa | | ● | ○ (prices) | ○ | | ● | | | ● | ○ | baytclean, clean-hoouse, prokr, dammam-clean, baity |
| Majlis | ○ | | | ● | | | | | ● | | elnakhil ●, nagdclean, raquen, cleaningdirectory |
| Carpets | ○ | ● | | | | | ○ | | | | wetndrylaundry ●, clean-hoouse ●, albaarq, qasrclean |
| Offices | ○ | | ● (EN) | ● | | | | | ○ | ○ | nileriyadh ●, cfmsaudi ●, nasayem, homerun, alafdall |
| Companies/shops | ● | ○ | | ● | ● | | | ○ | | ○ | evercleansa ●, basmetelriyadh, baytclean, altnfez |
| Post-construction | | | ○ | ● | | | ○ (تشطيب) | ○ | | | cleantouch ●, nasayem ●, albaarq ● (تشطيب), prokr, alshmasi |
| Facades | ○ | | | | | | ○ | ○ | | | alwustacleaning ●, saudigates ●, elkhaig ●, cleanservicee |
| Marble | | | | | | | | | | | jaliriyadh ●, montmarble ●, marble-ksa ●, jalibalatriyadh |
| AC | ● | ○ | | ● | | | | ○ | | | cleaningksa ●, mukayefat, experttechnicians, alarab-takyf, almaheron |
| Tanks | ○ | ● | | | | ○ | | | ● | | shehab-control ●, etqan, dammam-clean, cleantank, alafdall |
| Pools | ○ | | | | | | | ○ | | | roknnagd ●, saudigates ●, mehwaralmadar ●, almasacompany |
| Pest | | | | | | | | | ● | | saudi.wiki ●, lotus ●, insectsriad, elmasa, pestriyadh |
| Disinfection | | | | | | | | | | | alaml-group ●, naqaaclean ●, nouralebda ●, homerun, raqein |
| Contracts (municipal) | ○ | | | | | | | | | | elmagdclean ●, etf-lab ●, abeerelriyad ●, contracts-sa, awalclean |
| Hourly / maids | ● | | | | | | | | | | rahahome ●, cleanservice ●, nouralebda, cleanoxco |
| Pricing guides | | | ● | ○ | ○ | ○ | | | ● (sofa) | ○ (sofa) | greecleaning ●, horyaclean ●, nasayem ●, awalclean ●, cleandari ●, naqaa-cleaning ● |
| "أفضل شركة" | ○ | | | ○ | ● | ○ | | | | | greecleaning ●, elmasa, wataneya, cleanriyadh |
| District (الملقا/النرجس) | | | | | | | ● | ○ | | | naba-clean ●, clean-hoouse ●, sidraclean, green-clean, alamiyaclean |
| English clusters | | | ● | | | | | | | ● | ultraclean ●, middleeastservices ●, noorcleanriyad ●, alshmasi, cleanfactory, cleanoxco |

No single "best competitor": cleaner4me/malekclean own breadth, cleanspot owns pricing + English, specialists own marble/AC/tanks/pools/pest, listicles own "أفضل", programmatic sites own districts.

## 7. SERP-type analysis per cluster (page type the index rewards; Local Pack/PAA = inference)

| Cluster | Rewarded page type | Local Pack likely? | PAA / question layer likely? | Notes |
|---|---|---|---|---|
| Generic head | Homepage or hub service page + listicles + directory | Yes ("مفتوح الآن") | Yes ("كيف تختار") | Mixed intent: brand/discovery + hire |
| Homes / Villas / Apartments | Dedicated service page (2–4.6k words) with prices, steps, FAQ | Yes | Yes | Listicles & marketplace (HomeRun) interleave |
| Sofa / Majlis / Carpet | Service page with **price in title** (180 ريال, 95 ريال, من 100) | Yes | Yes (how-to questions) | Carpet also laundry/pickup pages |
| Post-construction / post-finishing | Guide-style service pages ("دليلك الشامل", "ما يشمله وكيف يتم") | Weak | Yes | Blog/guide format wins |
| Offices / companies | Service page + FM company pages + guides | Weak | Some | B2B; contracts sub-intent |
| Facades / Marble / AC / Tanks / Pools / Pest | **Specialist** domain pages, price-led titles | Yes (AC, tanks, pest) | Yes (كم مرة, هل ضروري) | Generalists rank lower here |
| Pricing queries | Blog/guide with price **tables** + FAQ, updated yearly ("2026") | No | Yes | Never a bare service page |
| "أفضل شركة" | Listicles / comparison guides + brand pages claiming it | Yes | Yes | Cannot be won honestly with a self-claim |
| District queries | District guide pages ("كيف تختار… في حي الملقا", "دليل الخدمة والأسعار") and programmatic pages | Yes (Maps) | Some | Thin competitor pages (300–1,800 words, 2 images) |
| English | Bilingual brand sites, EN service pages, apps | Yes ("near me") | Yes | Price-list intent |

## 8. Keyword universe (summary)

~235 curated keywords across 26 clusters (full table in section 9). Volumes/CPC/KD: **DATA NOT AVAILABLE** (see section 2). Demand grade legend: **H** = multiple autocomplete branches + many dedicated competitor pages; **M** = present in autocomplete or dedicated competitor pages; **L** = single/partial signal.

## 9. Master keyword table

Columns: Keyword · Intent (T = transactional, C = commercial investigation, I = informational, L = local, N = navigational) · Funnel (Hi/Mid/Top) · Cluster · Role (P = primary, S = secondary, V = variant, Q = question, $ = pricing) · Suggested page (E = exists in architecture, N = new) · Priority · Demand · Competitors / notes.

| # | Keyword | Intent | Funnel | Cluster | Role | Suggested page | Pri | Demand | Notes |
|---|---|---|---|---|---|---|---|---|---|
| 1 | شركة تنظيف بالرياض | T/L | Hi | C01 Generic | P | Homepage (E) | P1 | H | cleaner4me, malekclean, cleanservice, alsauditoday |
| 2 | شركة نظافة بالرياض | T/L | Hi | C01 | S | Homepage | P1 | H | cleanservice, cleaningdirectory, najmaltagheer |
| 3 | تنظيف بالرياض | T/L | Hi | C01 | V | Homepage | P1 | M | broad |
| 4 | شركات تنظيف بالرياض / شركات النظافة في الرياض | C/L | Mid | C01 | S | Homepage (+choosing guide) | P1 | H | listicles dominate |
| 5 | شركة تنظيف في الرياض | T/L | Hi | C01 | V | Homepage | P1 | M | |
| 6 | خدمات تنظيف الرياض / خدمات تنظيف المنازل | C | Mid | C01 | S | Services Index (E) | P1 | M | discovery intent |
| 7 | شركة تنظيف شامل بالرياض | T | Hi | C01 | V | Homepage / Homes | P2 | L | |
| 8 | شركة تنظيف سعودية | C | Mid | C01 | S | About (E) + Homepage | P2 | L | identity angle (Saudi-owned) |
| 9 | شركة تنظيف معتمدة / موثوقة | C | Mid | C01 | S | About / trust page | P2 | L | needs real CR/licence facts |
| 10 | رقم شركة تنظيف بالرياض | T/N | Hi | C01 | V | Contact (E) | P2 | M | GBP-driven |
| 11 | شركة تنظيف بالرياض مفتوح الآن | T/L | Hi | C01 | V | GBP (future) | P2 | M | Local Pack intent |
| 12 | شركة تنظيف بالرياض رجال / نسائية | T | Hi | C22 | V | Homes page section | P3 | L | only if true |
| 13 | cleaning company riyadh | T/L | Hi | C26 EN | P | EN homepage variant (N, wave 3) | P3 | M | ultraclean, cleanspot, noorclean |
| 14 | cleaning services riyadh (+ price list / near me / app) | C/T | Mid | C26 | S | EN services index (N, wave 3) | P3 | M | marketplace apps |
| 15 | best / professional cleaning company riyadh | C | Mid | C26 | V | EN choosing guide (N) | P3 | L | |
| 16 | تنظيف منازل بالرياض | T/L | Hi | C02 Homes | P | Service: تنظيف المنازل (E) | P1 | H | malekclean, homerun, cleaner4me, nile |
| 17 | شركة تنظيف منازل بالرياض | T/L | Hi | C02 | V | Homes | P1 | H | |
| 18 | نظافة منازل بالرياض | T | Hi | C02 | V | Homes | P1 | M | |
| 19 | شركة تنظيف بيوت الرياض / تنظيف بيوت الرياض | T | Hi | C02 | V | Homes | P1 | M | |
| 20 | خدمة تنظيف منازل الرياض | T | Hi | C02 | V | Homes | P1 | M | |
| 21 | تنظيف عميق للمنزل الرياض / شركة تنظيف عميق بالرياض | T | Hi | C02 | S | Homes | P1 | M | "deep cleaning" concept |
| 22 | شركة تنظيف منازل بالرياض عمالة فلبينية | T | Hi | C02 | V | Homes (only if factual) | — | H | labour-nationality claim; owner input |
| 23 | أفضل شركة تنظيف منازل بالرياض | C | Mid | C21 Best | S | Choosing guide article (N) | P1 | H | never self-claimed |
| 24 | home cleaning riyadh / house cleaning services riyadh | T | Hi | C26 | P | EN Homes (N, wave 3) | P3 | M | |
| 25 | deep cleaning riyadh / home deep cleaning riyadh | T | Hi | C26 | S | EN Homes | P3 | M | cleanspot, ultraclean |
| 26 | تنظيف فلل بالرياض | T/L | Hi | C03 Villas | P | Service: تنظيف الفلل (E) | P1 | H | becleansa, malekclean, cleaner4me |
| 27 | شركة تنظيف فلل بالرياض | T/L | Hi | C03 | V | Villas | P1 | H | |
| 28 | أفضل شركة تنظيف فلل بالرياض | C | Mid | C21 | S | Villas + choosing guide | P1 | M | |
| 29 | تنظيف فلل جديدة بالرياض / شركة تنظيف فلل جديده | T | Hi | C05 Move-in | S | Post-construction & handover page (E, consolidated) | P1 | M | eltfwaq, evercleansa |
| 30 | تنظيف الفيلا قبل السكن | T | Hi | C05 | V | Post-construction & handover | P1 | M | |
| 31 | تنظيف قصور بالرياض | T | Hi | C03 | S | Villas (section) | P2 | L | cleaner4me has page |
| 32 | شركة تنظيف فلل بالرياض عمالة فلبينية | T | Hi | C03 | V | Villas (only if factual) | — | M | |
| 33 | villa cleaning riyadh | T | Hi | C26 | P | EN Villas (N, wave 3) | P3 | M | evercleansa, alshmasi |
| 34 | تنظيف شقق بالرياض | T/L | Hi | C04 Apartments | P | Service: تنظيف الشقق (E) | P1 | H | basmetelriyadh, roknnagd, elmasa |
| 35 | شركة تنظيف شقق بالرياض | T/L | Hi | C04 | V | Apartments | P1 | H | |
| 36 | تنظيف شقق جديدة بالرياض / تنظيف شقة جديدة الرياض | T | Hi | C05 | S | Post-construction & handover (+ link from Apartments) | P1 | M | |
| 37 | شركة تنظيف شقق مفروشة بالرياض | T | Hi | C04 | V | Apartments (section) | P2 | L | |
| 38 | تنظيف شقق عزاب بالرياض | T | Hi | C04 | V | Apartments (section) | P2 | L | segment-specific |
| 39 | افضل شركة تنظيف شقق بالرياض | C | Mid | C21 | S | Choosing guide | P2 | M | |
| 40 | apartment cleaning riyadh | T | Hi | C26 | S | EN Apartments (N) | P3 | L | |
| 41 | تنظيف بعد التشطيب / شركة تنظيف بعد التشطيب الرياض | T | Hi | C05/C06 | P | Service: تنظيف ما بعد البناء والتشطيب (E, one canonical) | P1 | H | albaarq, services-sa, elferis, nile |
| 42 | تنظيف فلل بعد التشطيب / تنظيف شقق بعد التشطيب | T | Hi | C05 | V | same page (sections) | P1 | M | |
| 43 | تنظيف بعد البناء / شركة تنظيف بعد البناء بالرياض | T | Hi | C06 | P | same page | P1 | H | cleantouch, nasayem, aljawad |
| 44 | تنظيف ما بعد البناء / شركة تنظيف ما بعد البناء | T | Hi | C06 | V | same page | P1 | M | |
| 45 | تنظيف المنازل بعد البناء | T | Hi | C06 | V | same page | P1 | M | |
| 46 | تنظيف البورسلان بعد البناء / السيراميك بعد البناء / البلاط بعد البناء | I→T | Mid | C24/C06 | Q | same page FAQ + article | P1 | M | strong GEO question |
| 47 | تنظيف بعد الدهان / السيراميك بعد التشطيب | I→T | Mid | C24 | Q | article + FAQ | P2 | M | |
| 48 | تنظيف بعد الترميم / شركة تنظيف بعد الترميم | T | Hi | C06 | V | same page (section) | P2 | L | aljawad |
| 49 | تنظيف عمارة جديدة / تنظيف عمائر بالرياض | T | Hi | C06/C25 | V | same page (section) or gap | P2 | M | building-scale |
| 50 | تنظيف قبل السكن / شركة تنظيف منازل قبل السكن | T | Hi | C05 | V | same page | P1 | M | |
| 51 | post construction cleaning riyadh / move in cleaning riyadh | T | Hi | C26 | S | EN page (N, wave 3) | P3 | L | alshmasi |
| 52 | تنظيف كنب بالرياض | T/L | Hi | C07 Sofa | P | Service: تنظيف الكنب (E) | P1 | H | zadksa (180 ريال), cleanhomesa, malekclean |
| 53 | شركة تنظيف كنب بالرياض | T/L | Hi | C07 | V | Sofa | P1 | H | |
| 54 | غسيل كنب الرياض / غسيل كنبات | T | Hi | C07 | V | Sofa | P1 | H | colloquial |
| 55 | تنظيف كنب بالبخار بالرياض / شركة تنظيف كنب بالبخار بالرياض | T | Hi | C07 | S | Sofa (method section) | P1 | H | |
| 56 | تنظيف كنب وسجاد بالرياض / شركة تنظيف كنب سجاد بالرياض | T | Hi | C07/C09 | V | Sofa ↔ Carpets cross-link | P1 | M | bundle intent |
| 57 | ارخص شركة تنظيف كنب بالرياض / افضل شركة تنظيف كنب بالرياض | C | Mid | C21 | S | Sofa pricing section + guide | P1 | M | |
| 58 | تنظيف كنب في المنزل | T | Hi | C07 | V | Sofa ("في مكانه") | P2 | M | |
| 59 | تنظيف مفروشات بالرياض / شركة تنظيف مفروشات بالرياض | T | Hi | C07 | S | Sofa (scope: مفروشات/أثاث) | P2 | M | |
| 60 | تنظيف اثاث بالرياض / شركة تنظيف اثاث بالرياض | T | Hi | C07 | V | Sofa | P2 | L | |
| 61 | شركة تنظيف كنب بالرياض عمالة فلبينية / انستقرام | T | Hi | C07 | V | — | — | M | social/labour modifiers |
| 62 | أسعار تنظيف الكنب بالرياض | C/$ | Mid | C20 Pricing | $ | Sofa price section + pricing guide | P1 | H | cleanspot, evercleansa, althurayaa |
| 63 | sofa cleaning riyadh (+ price list) / upholstery cleaning riyadh | T | Hi | C26 | P | EN Sofa (N, wave 3) | P3 | M | ultraclean, cleanspot |
| 64 | تنظيف مجالس بالرياض | T/L | Hi | C08 Majlis | P | Service: تنظيف المجالس (E) | P1 | H | elnakhil, zadksa, nagdclean |
| 65 | شركة تنظيف مجالس بالرياض | T/L | Hi | C08 | V | Majlis | P1 | H | |
| 66 | غسيل مجالس بالرياض / غسيل مجالس بالبخار | T | Hi | C08 | V | Majlis | P1 | H | |
| 67 | تنظيف مجالس بالبخار بالرياض | T | Hi | C08 | S | Majlis | P1 | M | |
| 68 | غسيل مجالس وكنب / غسيل مجالس وسجاد الرياض | T | Hi | C08 | V | Majlis ↔ Sofa/Carpets | P1 | M | |
| 69 | ارخص / افضل شركة تنظيف مجالس بالرياض | C | Mid | C21 | S | Majlis price section | P2 | M | |
| 70 | تنظيف مجالس عربية | T | Hi | C08 | V | Majlis | P3 | L | no autocomplete |
| 71 | majlis cleaning riyadh | T | Hi | C26 | V | EN Sofa & majlis (N) | P3 | L | |
| 72 | تنظيف سجاد بالرياض / شركة تنظيف سجاد بالرياض | T/L | Hi | C09 Carpets | P | Service: تنظيف السجاد والموكيت (E) | P2 | H | wetndrylaundry, clean-hoouse, malekclean |
| 73 | غسيل سجاد الرياض | T | Hi | C09 | V | Carpets | P2 | H | laundry-model competition |
| 74 | تنظيف سجاد بالبخار بالرياض / شركة تنظيف سجاد بالبخار بالرياض | T | Hi | C09 | S | Carpets (in-place method) | P2 | M | |
| 75 | تنظيف موكيت بالرياض / غسيل موكيت / تنظيف موكيت بالبخار | T | Hi | C09 | S | Carpets (moquette section) | P2 | M | nagdclean, lamsa-clean |
| 76 | سعر غسيل السجاد في الرياض / بالمتر | C/$ | Mid | C20 | $ | Carpets price section | P2 | M | per-metre pricing expected |
| 77 | مغسلة سجاد بالرياض / غسيل سجاد قريب مني | T/L | Hi | C09 | V | — (different model) | — | M | pickup laundries |
| 78 | carpet cleaning riyadh (+ price) | T | Hi | C26 | S | EN Carpets (N) | P3 | M | |
| 79 | تنظيف مكاتب بالرياض / شركة تنظيف مكاتب بالرياض | T/L | Hi | C10 B2B | P | Service: تنظيف المكاتب والشركات (E, one page) | P1 | H | nileriyadh, cfmsaudi, zadksa, nasayem |
| 80 | تنظيف مكاتب وشركات بالرياض / تنظيف شركات الرياض / شركة تنظيف شركات بالرياض | T | Hi | C10 | V | same page | P1 | M | |
| 81 | تنظيف مكاتب ومنشآت | T | Hi | C10 | V | same page | P2 | L | |
| 82 | تنظيف مكاتب بالساعة | T | Hi | C10/C22 | V | same page (if offered) | P3 | L | |
| 83 | تنظيف محلات بالرياض / شركة تنظيف محلات بالرياض / تنظيف محلات تجارية | T/L | Hi | C10b Commercial premises | P | Service: تنظيف المحلات والمعارض (E) | P2 | M | evercleansa, basmetelriyadh, aljawad |
| 84 | تنظيف مطاعم بالرياض / شركة تنظيف مطاعم بالرياض | T | Hi | C25 Gap | P | Commercial premises (section) or gap | P2 | M | hood/duct sub-intent |
| 85 | office cleaning riyadh / office cleaning services riyadh | T | Hi | C26 | P | EN B2B (N, wave 3) | P2 | M | cleanspot, ultraclean, middleeast |
| 86 | commercial cleaning riyadh | T | Hi | C26 | S | EN B2B | P3 | L | no autocomplete |
| 87 | عقود تنظيف شهرية / سنوية / عقود تنظيف مباني / عقود شركات تنظيف | T | Hi | C11 Contracts | P | Service: عقود النظافة الدورية للمنشآت (E) | P2 | M | fayruz, alafdall |
| 88 | شركة عقود نظافة بالرياض / عقود نظافة الرياض | T | Hi | C11 | S | same page — **do not chase the municipal-licence intent** | P2 | H | SERP = عقد بلدي product |
| 89 | عقد نظافة بالرياض معتمد بلدي | T | Hi | C11 (other product) | — | — | — | H | not Vibe's product unless owner decides |
| 90 | اسعار عقود النظافة | C/$ | Mid | C20 | $ | Contracts page pricing section | P2 | L | |
| 91 | تنظيف دوري | — | — | C11 | — | Contracts page (section only) | — | none | football term |
| 92 | ادارة مرافق الرياض / شركة إدارة مرافق بالرياض | C | Mid | C11 | — | B2B landing (E), no keyword target | P3 | M (FM industry) | different industry (dartaltatweer, elara, cfm) |
| 93 | cleaning contract riyadh | T | Hi | C26 | V | EN B2B | P3 | none | |
| 94 | تنظيف واجهات بالرياض / شركة تنظيف واجهات بالرياض | T/L | Hi | C12 Facades | P | Service: تنظيف الواجهات والزجاج (E, one page) | P2 | H | alwusta, elkhaig, aljawad, saudigates |
| 95 | تنظيف واجهات زجاج بالرياض | T | Hi | C12 | S | same page | P2 | H | |
| 96 | تنظيف واجهات حجر بالرياض / شركة تنظيف واجهات حجر بالرياض | T | Hi | C12 | S | same page (stone section) | P2 | M | |
| 97 | تنظيف واجهات كلادينج | T | Hi | C12 | S | same page | P2 | L | |
| 98 | تنظيف زجاج المباني / شركة تنظيف زجاج المباني / تنظيف زجاج المحلات | T | Hi | C12 | V | same page | P2 | M | "تنظيف زجاج" alone = DIY/cars |
| 99 | تنظيف واجهات المباني / تنظيف واجهات عمائر | T | Hi | C12 | V | same page | P2 | M | |
| 100 | facade / high-level window cleaning riyadh | T | Hi | C26 | V | EN (N) | P3 | L | ultraclean |
| 101 | جلي رخام بالرياض / شركة جلي رخام بالرياض | T/L | Hi | C13 Marble | P | Service: جلي وتلميع الرخام والبلاط (E, consolidated) | P2 | H | jaliriyadh, montmarble, marble-ksa |
| 102 | جلي بلاط الرياض / جلي بلاط تلميع رخام الرياض | T | Hi | C13 | S | same page | P2 | H | search language = جلي بلاط |
| 103 | تلميع رخام / تلميع الرخام بالرياض / جلي وتلميع رخام في الرياض | T | Hi | C13 | V | same page | P2 | H | |
| 104 | معلم جلي رخام بالرياض | T | Hi | C13 | V | same page | P3 | L | craftsman term |
| 105 | اسعار جلي الرخام في الرياض / سعر متر تلميع الرخام / سعر تلميع الرخام | C/$ | Mid | C20 | $ | same page price section | P2 | M | per-metre |
| 106 | تنظيف ارضيات الرخام / تنظيف سيراميك بالرياض | T | Hi | C13 | V | same page | P3 | L | "تنظيف ارضيات" alone = DIY |
| 107 | تنظيف الرخام من البقع السوداء / الصفراء / البيضاء | I | Top | C24 | Q | article + FAQ | P2 | M | GEO |
| 108 | marble polishing riyadh | T | Hi | C26 | V | EN (N) | P3 | L | |
| 109 | تنظيف مكيفات بالرياض / شركة تنظيف مكيفات بالرياض | T/L | Hi | C14 AC | P | Service: تنظيف المكيفات (E) | P1* | H | cleaningksa, mukayefat, alarab-takyf (*if executed in-house) |
| 110 | غسيل مكيفات بالرياض / غسيل مكيفات سبليت | T | Hi | C14 | V | AC | P1* | H | |
| 111 | تنظيف مكيفات سبليت الرياض / تنظيف مكيفات شباك بالرياض / مركزي | T | Hi | C14 | S | AC (type sections) | P1* | H | |
| 112 | شركة تنظيف دكت المكيفات بالرياض | T | Hi | C14 | V | AC (section) | P2 | L | |
| 113 | اسعار تنظيف المكيفات السبلت / كم سعر تنظيف مكيف سبليت | C/$ | Mid | C20 | $ | AC price section | P1* | H | 35–40 ريال anchors |
| 114 | كم مرة يجب تنظيف المكيف / هل تنظيف المكيف ضروري / هل يجب تنظيف فلتر المكيف | I | Top | C24 | Q | AC FAQ + article | P1* | M | GEO |
| 115 | شركة تنظيف مكيفات بالرياض الأعلى تقييمًا / عمالة فلبينية / انستقرام | T | Hi | C14 | V | — | — | M | reviews/social |
| 116 | صيانة مكيفات بالرياض | T | Hi | C25 Gap | — | gap (not cleaning) | — | H | adjacent trade |
| 117 | ac cleaning riyadh / split ac cleaning riyadh | T | Hi | C26 | S | EN AC (N) | P3 | M | |
| 118 | مكافحة حشرات بالرياض / شركة مكافحة حشرات بالرياض | T/L | Hi | C15 Pest | P | Service: مكافحة الحشرات (E) | P2* | H | saudi.wiki, zadksa, lotus, insectsriad (*licence-dependent) |
| 119 | رش مبيدات بالرياض / رش حشرات بالرياض / شركة رش مبيدات حشرية | T | Hi | C15 | V | Pest | P2* | H | |
| 120 | مكافحة الصراصير / بق الفراش / النمل الابيض بالرياض | T | Hi | C15 | S | Pest (pest-type sections) | P2* | M | |
| 121 | عقد مكافحة حشرات بالرياض / عقود مكافحة حشرات | T | Hi | C15/C11 | S | Pest (contract section) | P2* | M | |
| 122 | اسعار مكافحة الحشرات بالرياض / ارخص شركة مكافحة حشرات | C/$ | Mid | C20 | $ | Pest price section | P2* | M | 150 ريال anchors |
| 123 | pest control riyadh (+ price list) | T | Hi | C26 | S | EN (N) | P3 | M | |
| 124 | تعقيم منازل بالرياض / شركة تعقيم منازل بالرياض | T/L | Hi | C16 Disinfection | P | Service: التعقيم والتطهير (E) | P3 | M | alaml, naqaaclean, nouralebda |
| 125 | تعقيم فلل / تعقيم شقق / شركة تنظيف وتعقيم بالرياض | T | Hi | C16 | V | same + sections on Homes/Villas/Offices | P3 | L | |
| 126 | الفرق بين تعقيم وتطهير | I | Top | C24 | Q | article/FAQ | P3 | L | GEO |
| 127 | تنظيف مسابح بالرياض / شركة تنظيف مسابح بالرياض | T/L | Hi | C17 Pools | P | Service: تنظيف المسابح (E) | P2* | M | roknnagd, mehwaralmadar, almasacompany |
| 128 | صيانة مسابح بالرياض / تنظيف مسابح صيانه مسابح بالرياض | T | Hi | C17 | S | Pools (maintenance section) | P2* | M | maintenance-led SERP |
| 129 | تنظيف مسابح شمال الرياض | T/L | Hi | C17/C23 | V | Pools + areas | P3 | L | |
| 130 | سعر تنظيف المسبح | C/$ | Mid | C20 | $ | Pools price section | P3 | L | |
| 131 | pool cleaning riyadh | T | Hi | C26 | V | EN (N) | P3 | L | |
| 132 | تنظيف خزانات بالرياض / شركة تنظيف خزانات بالرياض | T/L | Hi | C18 Tanks | P | Service: تنظيف الخزانات (E) | P1 | H | malekclean, zadksa, shehab, cleantank |
| 133 | غسيل خزانات بالرياض / غسيل خزانات المياه بالرياض | T | Hi | C18 | V | Tanks | P1 | H | |
| 134 | تنظيف خزانات المياه بالرياض | T | Hi | C18 | V | Tanks | P1 | H | |
| 135 | تنظيف وعزل خزانات بالرياض / شركه تنظيف خزانات وعزل بالرياض | T | Hi | C18 | S | Tanks (عزل add-on if offered) | P1 | H | |
| 136 | عزل خزانات بالرياض / عزل خزانات المياه بالرياض | T | Hi | C25 Gap | — | gap unless offered | — | H | |
| 137 | شركة تنظيف خزانات بالرياض رخيصة / عمالة فلبينية / الصفرات | T | Hi | C18 | V | — | — | M | |
| 138 | شركة تنظيف خزانات معتمدة / تعقيم معتمد | C | Mid | C18 | S | Tanks (certification facts) | P1 | M | trust signal; owner input |
| 139 | اسعار تنظيف خزانات بالرياض / سعر تنظيف الخزان الارضي / العلوي / سعر تنظيف الخزانات | C/$ | Mid | C20 | $ | Tanks price section | P1 | H | 120–800 ريال anchors |
| 140 | متى يجب تنظيف الخزان / كم مرة تنظيف الخزان | I | Top | C24 | Q | Tanks FAQ | P1 | L | no autocomplete; competitor FAQs |
| 141 | شركة تنظيف خزانات شمال / شرق / غرب / جنوب الرياض / بحي الياسمين / حي لبن | T/L | Hi | C23 | V | Tanks + area pages | P2 | M | |
| 142 | water tank cleaning riyadh | T | Hi | C26 | S | EN (N) | P3 | L | |
| 143 | تنظيف مطابخ بالرياض | T | Hi | C19 | S | Homes (section) | P3 | L | mostly DIY |
| 144 | تنظيف حمامات بالرياض | T | Hi | C19 | S | Homes (section) | P3 | L | |
| 145 | تنظيف شفاطات المطابخ / تنظيف مداخن المطاعم بالرياض / تنظيف هود مطاعم | T | Hi | C25 | — | gap (restaurant hoods) | — | M | |
| 146 | تنظيف عميق للمطبخ قبل رمضان / جدول تنظيف البيت قبل رمضان | I | Top | C24 | Q | seasonal article | P2 | M | seasonal GEO |
| 147 | اسعار شركات التنظيف بالرياض / في الرياض | C/$ | Mid | C20 | P | Pricing guide article (N) | P1 | H | greecleaning, horyaclean, nasayem, cleanspot |
| 148 | اسعار تنظيف المنازل بالرياض / اسعار شركات تنظيف المنازل بالرياض | C/$ | Mid | C20 | S | Pricing guide + Homes price section | P1 | H | awalclean, cleandari, cleanhomesa |
| 149 | أسعار تنظيف الفلل الجديدة / اسعار تنظيف الفلل / كم سعر تنظيف الفيلا / كم تكلفة تنظيف فيلا بالكامل | C/$ | Mid | C20 | $ | Villas price section + guide | P1 | M | naqaa, cleanspot, elmagd, super-ksa |
| 150 | سعر تنظيف الشقة / أسعار تنظيف الشقق | C/$ | Mid | C20 | $ | Apartments price section | P1 | M | |
| 151 | تكلفة تنظيف بعد البناء | C/$ | Mid | C20 | $ | Post-construction price section | P1 | L | no autocomplete, competitor pages exist |
| 152 | اسعار تنظيف المكاتب | C/$ | Mid | C20 | $ | Offices price section | P2 | L | |
| 153 | home cleaning services riyadh price list / cleaning services riyadh price list / deep cleaning services riyadh price list | C/$ | Mid | C26 | $ | EN pricing guide (N, wave 3) | P3 | M | |
| 154 | افضل شركة تنظيف بالرياض | C | Mid | C21 Best | P | Choosing guide article (N): "كيف تختار شركة تنظيف في الرياض" | P1 | H | alsauditoday, greecleaning |
| 155 | ماهي افضل شركة تنظيف بالرياض / افضل شركة تنظيف بالرياض دليل | C | Mid | C21 | V | same article | P1 | M | |
| 156 | افضل شركة تنظيف خزانات / مكيفات / مجالس / مسابح / بيارات / اثاث بالرياض | C | Mid | C21 | V | each service's evidence section + guide | P2 | M | |
| 157 | ارخص شركة تنظيف بالرياض / شركة تنظيف رخيصة بالرياض | C | Mid | C21 | S | Pricing guide (honest: what cheap includes/excludes) | P2 | H | malekclean owns |
| 158 | شركة تنظيف بالرياض الأعلى تقييمًا | C/L | Mid | C21 | V | GBP reviews (future) | P2 | L | |
| 159 | كيف اختار شركة تنظيف | I | Top | C21 | Q | choosing guide | P1 | L | |
| 160 | تنظيف منازل بالرياض بالساعة / شركة تنظيف بالساعة الرياض / خدمة تنظيف بالساعة | T | Hi | C22 Hourly | P | **Potential service gap** | — | H | cleaner4me, rahahome, cleanservice |
| 161 | عاملات تنظيف بالساعة الرياض / عاملات تنظيف منازل بالرياض / عمال تنظيف منازل بالرياض | T | Hi | C22 | V | gap (labour model) | — | H | maids agencies |
| 162 | شركة تنظيف منازل بالرياض بالساعة نساء / رجال | T | Hi | C22 | V | gap | — | M | |
| 163 | شركة تنظيف بالرياض عمالة فلبينية / فلبينيين | T | Hi | C22 | V | only if factual | — | H | |
| 164 | شركة تنظيف شمال الرياض | T/L | Hi | C23 Local | P | Areas Index group anchor (E) → AreaGroup page later (N, conditional) | P2 | H | basmetelriyadh, eletqan, qclean, samaaljanub |
| 165 | شركة تنظيف منازل / فلل / شقق / خزانات / مكيفات / موكيت شمال الرياض | T/L | Hi | C23 | V | same | P2 | M | |
| 166 | شركة تنظيف شرق الرياض (+منازل/فلل/شقق/خزانات/مكيفات/كنب/موكيت) | T/L | Hi | C23 | P | Areas Index group anchor | P2 | H | malekclean |
| 167 | شركة تنظيف غرب الرياض (+منازل/خزانات/مكيفات) | T/L | Hi | C23 | P | Areas Index group anchor | P3 | M | |
| 168 | شركة تنظيف جنوب الرياض (+خزانات/منازل/مكيفات/موكيت) | T/L | Hi | C23 | P | Areas Index group anchor | P3 | M | |
| 169 | شركة تنظيف الملقا / شركة تنظيف منازل في حي الملقا | T/L | Hi | C23 | P | Area page: الملقا (E, conditional on evidence) | P1 | M | naba-clean, clean-hoouse, sidraclean, saudi-cleaning |
| 170 | شركة تنظيف النرجس / تنظيف منازل النرجس / تنظيف فلل النرجس | T/L | Hi | C23 | P | Area page: النرجس | P1 | M | saudi-cleaning, aljawad, alamiya |
| 171 | شركة تنظيف الياسمين / شركة تنظيف خزانات بحي الياسمين | T/L | Hi | C23 | P | Area page: الياسمين | P1 | M | |
| 172 | شركة تنظيف العليا الرياض (+شقق/فلل/منازل/خزانات) | T/L | Hi | C23 | P | Area page: العليا | P1 | H | strongest district signal |
| 173 | شركة تنظيف العقيق / شركة تنظيف منازل العقيق | T/L | Hi | C23 | P | Area page: العقيق (P2) | P2 | L | |
| 174 | شركة تنظيف بالدرعية / تنظيف فلل الدرعية / تنظيف منازل الدرعية | T/L | Hi | C23 | P | Area page: الدرعية (P2) | P2 | M | |
| 175 | شركة تنظيف اليرموك / الرمال / القيروان | T/L | Hi | C23 | P | Area pages (P2, evidence-gated) | P2 | L | |
| 176 | شركة تنظيف لبن الرياض (+شقق/منازل/كنب/خزانات) | T/L | Hi | C23 | P | Area page: لبن (P2; not in owner list, has demand) | P2 | M | |
| 177 | شركة تنظيف حطين / الصحافة / قرطبة / المونسية / الروضة | T/L | Hi | C23 | P | Area pages P3 (no autocomplete demand; publish only with projects) | P3 | none | |
| 178 | شركة تنظيف العارض | T/L | Hi | C23 | P | Area page P3 (term ambiguous) | P3 | L | |
| 179 | كيف انظف الكنب من البقع / كيف انظف الكنب في البيت / تنظيف بقع الكنب الصعبة | I | Top | C24 Info | Q | Article: بقع الكنب (N) → Sofa | P1 | H | strong how-to demand |
| 180 | كيف انظف الكنب من بول القطط / من البول | I | Top | C24 | Q | same article (section) | P2 | M | |
| 181 | كيف تنظيف الكنب المخمل / الكنب البيج / الابيض | I | Top | C24 | Q | same article (fabric types) | P2 | M | |
| 182 | كيف انظف السجاد وهو في مكانه / بدون غسيل / بدون ماء / على الناشف | I | Top | C24 | Q | Article: السجاد في مكانه (N) → Carpets | P2 | M | maps to in-place service |
| 183 | كيف انظف السجاد من الزيت / القهوة / البقع | I | Top | C24 | Q | same article | P2 | M | |
| 184 | تنظيف بقع القهوة من الكنب / بقع الحبر من الكنب | I | Top | C24 | Q | sofa stains article | P2 | M | |
| 185 | تنظيف مجالس بالبخار (method) / هل التنظيف بالبخار يضر الكنب | I | Top | C24 | Q | Article: البخار vs الجاف (N) | P2 | L | |
| 186 | افضل وقت لتنظيف المنزل / جدول تنظيف عميق للمنزل | I | Top | C24 | Q | Article: deep-cleaning checklist (N) | P2 | L | |
| 187 | تنظيف قبل رمضان / تنظيف البيت قبل رمضان / روتين تنظيف البيت قبل رمضان | I | Top | C24 | Q | seasonal article (N) | P2 | M | seasonal spike |
| 188 | تنظيف المنزل بعد الدهان / تنظيف الارضيات بعد الدهان | I | Top | C24 | Q | post-finishing article/FAQ | P2 | M | |
| 189 | تنظيف عميق للمنزل (concept) / تنظيف شامل للمنازل | C | Mid | C02 | S | Homes page defines "التنظيف العميق" | P1 | M | |
| 190 | ماذا يشمل تنظيف الفيلا / خطوات تنظيف الفيلا | I | Top | C24 | Q | Villas includes/steps blocks | P1 | L | GEO answer block |
| 191 | كم يستغرق تنظيف الفيلا / الشقة | I | Top | C24 | Q | Villas/Apartments FAQ | P1 | L | no autocomplete; competitor FAQs |
| 192 | تنظيف بيارات بالرياض / شركات تنظيف بيارات بالرياض / شركة تنظيف مجاري بالرياض | T | Hi | C25 Gap | — | **gap** (sewage pits) | — | H | frequent modifier; different trade |
| 193 | تنظيف ستائر بالرياض / شركة تنظيف ستائر بالبخار بالرياض | T | Hi | C25 | — | gap (or Sofa add-on) | — | M | |
| 194 | تنظيف مساجد بالرياض / شركة تنظيف مساجد | T | Hi | C25 | — | gap (institutional) | — | M | |
| 195 | تنظيف عمائر بالرياض / تنظيف درج عمارة | T | Hi | C25 | — | gap → contracts section | — | M | |
| 196 | تنظيف استراحات الرياض / تنظيف شاليهات بالرياض | T | Hi | C25 | — | gap (rest-house segment) | — | M | |
| 197 | تنظيف احواش الرياض / شركة تنظيف احواش بالرياض | T | Hi | C25 | — | gap or Villas section | — | M | |
| 198 | تنظيف فنادق / مستشفيات / مدارس بالرياض | T | Hi | C25 | — | gap (institutional B2B) | — | L–M | |
| 199 | تنظيف كرفانات / خيام / مظلات / نجف / جلسات بالرياض | T | Hi | C25 | — | gap | — | L | from alphabet expansion |
| 200 | تنظيف سيارات بالرياض / تنظيف سيارات من الداخل | T | Hi | C25 | — | out of scope | — | H | different industry |
| 201 | نقل عفش / تخزين اثاث (bundled by competitors) | T | Hi | C25 | — | out of scope | — | H | zadksa, roknnagd bundle |
| 202 | تنظيف شقق بينبع / بجدة / بالدمام … (other cities) | T | Hi | — | — | out of scope (Riyadh only) | — | H | never target |
| 203 | شركة تنظيف بالرياض حراج / انستقرام / تيك توك / تويتر | N | Mid | C01 | V | social profiles (sameAs), not pages | — | M | Saudi discovery channels |
| 204 | شركة تنظيف بالرياض كلين لايف / السبعي / الصفرات / كلين هوم / ركين هاوس / اليسر | N | Hi | brand | — | competitor brand navigational | — | M | brand awareness benchmarks |
| 205 | cleaning services riyadh near me / app | T/L | Hi | C26 | V | GBP + EN pages | P3 | M | |
| 206 | best pest control riyadh / trap pest control / masa pest control | N/C | Mid | C26 | — | — | — | L | |
| 207 | تنظيف فلل بعد التشطيب في حي النرجس (district × service long-tail) | T/L | Hi | C23 | V | Area page mentions service; never its own URL | — | L | sidraclean does this (doorway) |
| 208 | شركة تنظيف واجهات قصور وفلل فاخرة في حي الملقا | T/L | Hi | C23/C12 | V | Facades page ↔ الملقا area page | — | L | aljawad pattern |
| 209 | تنظيف شقق بعد التشطيب / تنظيف البيت بعد التشطيب | T | Hi | C05 | V | Post-construction page | P1 | M | |
| 210 | شركة تنظيف فلل جديده بالرياض | T | Hi | C05 | V | Post-construction page (handover) | P1 | M | |
| 211 | تنظيف مكيفات سبليت بالريموت (DIY) | I | Top | C24 | Q | AC article (what you can/can't DIY) | P3 | L | |
| 212 | تنظيف مكيفات عمالة فلبينية / فلبينيين | T | Hi | C14 | V | — | — | M | |
| 213 | تنظيف مسابح صيانه فلتر ومضخة | T | Hi | C17 | V | Pools scope | P3 | L | roknnagd |
| 214 | تنظيف سجاد المساجد / تنظيف موكيت المساجد | T | Hi | C25 | — | gap (institutional) | — | M | |
| 215 | تنظيف خزانات بالرياض حراج / مفتوح الآن / instagram | T/L | Hi | C18 | V | GBP/social | — | M | |
| 216 | شركة تنظيف وتعقيم بالرياض / شركة تنظيف ومكافحة حشرات بالرياض / شركة تنظيف وصيانة مكيفات بالرياض | T | Hi | C01 | V | Homepage services strip (bundles) | P2 | M | bundle intent |
| 217 | شركة تنظيف بلاط بالرياض / شركة تنظيف وجلي بلاط بالرياض | T | Hi | C13 | V | Marble & tile page | P2 | M | |
| 218 | شركة تنظيف سيراميك بالرياض | T | Hi | C13 | V | Marble & tile page | P3 | L | |
| 219 | شركة تنظيف شبابيك بالرياض | T | Hi | C12 | V | Facades & glass page (windows section) | P3 | L | |
| 220 | شركة تنظيف حمامات بالرياض / افران غاز | T | Hi | C19 | V | Homes sections | P3 | L | |
| 221 | شركة تنظيف قصور بالرياض | T | Hi | C03 | V | Villas | P2 | L | |
| 222 | شركة تنظيف مجاري / صرف صحي بالرياض | T | Hi | C25 | — | gap | — | M | |
| 223 | تنظيف عميق للمنزل جدة (other city) | — | — | — | — | out of scope | — | — | |
| 224 | urban company / justclean / raha / homerun (apps) | N | Mid | C26 | — | marketplace competitors | — | M | |
| 225 | تنظيف بالبخار بالرياض / شركة تنظيف بالبخار بالرياض | T | Hi | C07/C08/C09 | S | Method hub: Sofa page section + article "التنظيف بالبخار" | P2 | H | prokr, fayruz, shehab |
| 226 | تنظيف مفروشات بالبخار / تنظيف ستائر بالبخار | T | Hi | C07 | V | Sofa page (scope) | P3 | L | |
| 227 | شركة تنظيف نجف بالرياض / تنظيف مظلات | T | Hi | C25 | — | gap | — | L | |
| 228 | شركة تنظيف فرشات بالرياض | T | Hi | C07 | V | Sofa page (mattresses section) | P3 | L | |
| 229 | شركة تنظيف بالرياض منازل / شركة تنظيف بالرياض كنب | T | Hi | C02/C07 | V | respective pages | — | M | word-order variants |
| 230 | شركة تنظيف بالرياض 24 ساعة / خدمة 24 ساعة | T | Hi | C01 | V | only if factual (hours) | — | M | claim word in competitor titles |
| 231 | تنظيف عميق بالانجليزي / deep cleaning (translation intent) | I | Top | — | — | ignore | — | — | |
| 232 | تنظيف شقق عزاب بالرياض / شركة تنظيف شقق عزاب | T | Hi | C04 | V | Apartments section | P2 | M | |
| 233 | تنظيف فلل محروقة | T | Hi | C25 | — | gap (fire damage) | — | L | |
| 234 | تعاون نجد شركة تنظيف بالرياض (brand) | N | — | — | — | — | — | L | |
| 235 | شركة تنظيف بالرياض العليا (district phrasing) | T/L | Hi | C23 | V | Area page: العليا | P1 | M | |

## 10. Keyword clusters (primary → page → priority)

| Cluster | Primary | Key secondaries | Questions / pricing | Page | Pri |
|---|---|---|---|---|---|
| C01 Generic/brand | شركة تنظيف بالرياض | شركة نظافة بالرياض، شركات تنظيف بالرياض، خدمات تنظيف | كيف تختار؛ اسعار شركات التنظيف | Homepage (+Services Index for discovery) | P1 |
| C02 Homes | تنظيف منازل بالرياض | نظافة منازل، تنظيف بيوت، تنظيف عميق للمنزل | اسعار تنظيف المنازل؛ ماذا يشمل | Service: تنظيف المنازل | P1 |
| C03 Villas | تنظيف فلل بالرياض | شركة تنظيف فلل، قصور، أفضل شركة تنظيف فلل | كم سعر تنظيف الفيلا؛ أسعار الفلل الجديدة؛ كم يستغرق | Service: تنظيف الفلل | P1 |
| C04 Apartments | تنظيف شقق بالرياض | شقق مفروشة، عزاب، شقق جديدة | سعر تنظيف الشقة | Service: تنظيف الشقق | P1 |
| C05+C06 Post-construction / finishing / handover | تنظيف بعد التشطيب بالرياض + تنظيف بعد البناء بالرياض | ما بعد البناء، قبل السكن، فلل/شقق جديدة، بعد الترميم/الدهان | تنظيف البورسلان بعد البناء؛ تكلفة | Service: تنظيف ما بعد البناء والتشطيب (one canonical) | P1 |
| C07 Sofa/upholstery | تنظيف كنب بالرياض | غسيل كنب، بالبخار، مفروشات، أثاث، فرشات | أسعار تنظيف الكنب؛ كيف انظف الكنب من البقع | Service: تنظيف الكنب | P1 |
| C08 Majlis | تنظيف مجالس بالرياض | غسيل مجالس بالبخار، مجالس وكنب | ارخص/افضل | Service: تنظيف المجالس | P1 |
| C09 Carpets | تنظيف سجاد بالرياض | غسيل سجاد، موكيت، بالبخار، في مكانه | سعر غسيل السجاد بالمتر؛ كيف انظف السجاد في مكانه | Service: تنظيف السجاد والموكيت | P2 |
| C10 Offices/companies | تنظيف مكاتب بالرياض | تنظيف شركات، مكاتب ومنشآت، office cleaning riyadh | اسعار تنظيف المكاتب | Service: تنظيف المكاتب والشركات | P1 |
| C10b Commercial premises | تنظيف محلات بالرياض | محلات تجارية، معارض، مطاعم | — | Service: تنظيف المحلات والمعارض | P2 |
| C11 Contracts | عقود تنظيف شهرية/سنوية للشركات | عقود تنظيف مباني، شركة عقود نظافة (careful) | اسعار عقود النظافة | Service: عقود النظافة الدورية | P2 |
| C12 Facades/glass | تنظيف واجهات بالرياض | واجهات زجاج، حجر، كلادينج، زجاج المباني | — | Service: تنظيف الواجهات والزجاج | P2 |
| C13 Marble/tile | جلي رخام بالرياض | جلي بلاط، تلميع رخام، جلي وتلميع | اسعار جلي الرخام؛ سعر المتر؛ بقع الرخام | Service: جلي وتلميع الرخام والبلاط | P2 |
| C14 AC | تنظيف مكيفات بالرياض | غسيل مكيفات سبليت، شباك، مركزي، دكت | كم سعر تنظيف مكيف سبليت؛ كم مرة يجب | Service: تنظيف المكيفات | P1* |
| C15 Pest | مكافحة حشرات بالرياض | رش مبيدات، صراصير، بق، نمل أبيض، عقد | اسعار مكافحة الحشرات | Service: مكافحة الحشرات | P2* |
| C16 Disinfection | تعقيم منازل بالرياض | تعقيم فلل/شقق، تنظيف وتعقيم | الفرق بين تعقيم وتطهير | Service: التعقيم (+ sections elsewhere) | P3 |
| C17 Pools | تنظيف مسابح بالرياض | صيانة مسابح، فلتر ومضخة | سعر تنظيف المسبح | Service: تنظيف المسابح | P2* |
| C18 Tanks | تنظيف خزانات بالرياض | غسيل خزانات، خزانات المياه، تنظيف وعزل، معتمدة | اسعار؛ أرضي/علوي؛ متى | Service: تنظيف الخزانات | P1 |
| C19 Kitchens/bathrooms | — | تنظيف مطابخ/حمامات بالرياض | قبل رمضان | Sections inside Homes (no page) | P3 |
| C20 Pricing | اسعار شركات التنظيف بالرياض | per-service price queries | all "كم سعر" | Article: pricing guide + price sections on each service | P1 |
| C21 Best/choose | افضل شركة تنظيف بالرياض | ارخص، الأعلى تقييمًا، دليل | كيف اختار | Article: choosing guide (criteria) | P1 |
| C22 Hourly/maids | تنظيف بالساعة الرياض | عاملات بالساعة، نساء/رجال، عمالة فلبينية | — | Potential service gap (owner decision) | — |
| C23 Local | شركة تنظيف [حي/اتجاه] | directional + 10 districts with signal | — | /areas index anchors + evidence-gated area pages | P1/P2 |
| C24 Informational | how-to / stains / frequency / seasonal | — | — | Articles → services | P1–P2 |
| C25 Adjacent gaps | بيارات، عزل خزانات، ستائر، مساجد، مطاعم/مداخن، عمائر، استراحات، احواش، صيانة مكيفات، نقل عفش | — | — | POTENTIAL SERVICE GAP list | — |
| C26 English | cleaning company / services riyadh | villa/office/sofa/deep/ac/pest + price list | — | EN variants wave 3 | P3 |

## 11. Cannibalization map (current architecture)

| Route | Target intent | Must NOT target | Risk & resolution |
|---|---|---|---|
| `/` Homepage | Brand + "شركة تنظيف بالرياض" (generic hire) + entity | Any single service head ("تنظيف منازل بالرياض", "تنظيف فلل بالرياض") | Homepage currently shows services + evidence + offers — fine, but its Title/H1 must stay generic-company; no service keyword in H1. |
| `/services` | Discovery / category ("خدمات تنظيف بالرياض", "شركات تنظيف - خدماتنا") | The generic hire head | Index Title/H1 = catalogue framing, not "شركة تنظيف بالرياض". Both `/` and `/services` ranking for the head would split signals; keep the index descriptive. |
| Service: تنظيف المنازل vs Villas vs Apartments | منازل = general home deep clean (umbrella); فلل; شقق | Homes page must not restate villa/apartment specifics at length | Three distinct SERPs → three pages, with explicit scope statements and cross-links ("للفلل انظر…"). |
| ما بعد البناء vs ما بعد التشطيب vs ما قبل السكن | One intent (remove construction/finishing residue before occupancy) | Three URLs | **Consolidate to one canonical page** (CMS may keep three service records only if two are unpublished/redirected; else PublishingGate duplicate check will/should flag). |
| الكنب vs المجالس vs (مفروشات/أثاث) | كنب page; مجالس page; مفروشات folded into كنب | A third "مفروشات" page | Two pages, cross-linked, scope-differentiated (units/pricing differ). |
| الأرضيات vs الرخام وتلميعه | One page: جلي وتلميع الرخام والبلاط | "تنظيف الأرضيات" page (DIY intent) | Fold floors into Marble & tile + Homes sections. |
| الزجاج vs الواجهات | One page: الواجهات والزجاج | Separate "تنظيف الزجاج" page | Consolidate. |
| المكاتب vs الشركات vs المحلات vs عقود النظافة vs إدارة المرافق vs الخدمات التجارية vs التنظيف الدوري | Offices+companies (1), Commercial premises (1), Contracts (1), B2B landing (no keyword target) | 7 B2B URLs | Reduce to 3 keyword-targeted pages + 1 B2B landing; "التنظيف الدوري" = section of Contracts; "إدارة المرافق" = B2B landing/trust content, not a keyword page. |
| المطابخ / الحمامات | Sections of Homes | Standalone pages | DIY-dominated SERPs; standalone pages would be thin. |
| التعقيم vs التنظيف العميق | Disinfection page P3; deep cleaning = Homes | Both claiming "تعقيم منازل" | Keep one. |
| `/offers` + Offer pages | Real time-bound offers (OfferPrice) | Pricing queries ("اسعار تنظيف…") | Offers never carry "اسعار" H1s; pricing guide article + service price sections own that intent. |
| Blog pricing guide vs service price sections | Guide = "اسعار شركات التنظيف بالرياض" (umbrella, comparison) | Service page = "سعر تنظيف X" | Guide links down to each service price section; no duplicate tables. |
| `/areas/{slug}` vs directional intent | District pages (evidence-gated) | Service×District URLs | Directional ("شمال الرياض") served by `/areas` group anchors; an AreaGroup page only when ≥3 area pages with projects exist. |
| `/projects/{slug}` | Evidence (specific job) | Service keywords | Project titles stay descriptive (service + area + date), not "شركة تنظيف فلل بالرياض". |
| `/about` | Brand/entity ("من نحن", founder, "شركة تنظيف سعودية") | Service keywords | OK as is. |

## 12. Homepage target intent

- **Primary topic:** professional cleaning company in Riyadh (entity + category).
- **Primary commercial intent:** "شركة تنظيف بالرياض" / "شركة نظافة بالرياض" (hire a company, unspecified service).
- **Brand intent:** "فايب كلين برو" / "Vibe Clean Pro" (+ Instagram/Snapchat/TikTok discovery → sameAs).
- **Secondary support:** the service catalogue strip (each tile links to its page — passes relevance, does not rank), real evidence (projects), coverage (areas), offers, identity.
- **Must NOT target:** any specific service head, districts, prices, "أفضل شركة".
- Title intent: `شركة تنظيف بالرياض | [Brand]` style with a factual differentiator (not "أفضل"/"أرخص"); H1 intent: company + Riyadh + what you get (one sentence), no keyword stuffing; sections: services (linked), how it works, evidence (real projects/before-after), coverage, pricing honesty (PublicPrice + "لا يتم الدفع عبر الموقع"), identity (Saudi company, founder), FAQ (3–5 generic), CTA.
- GEO opportunities: one-paragraph factual definition of the company (who/where/what/since when), a "what we do / don't do" list, coverage list linking to `/areas`.

## 13. Services Index intent

Discovery/category intent: "خدمات تنظيف الرياض", "خدمات شركة تنظيف", "شركات تنظيف - الخدمات". Title/H1 = catalogue ("خدمات التنظيف في الرياض — الفلل والشقق والمكاتب والكنب…"), grouped by category (already implemented), each row with scope + PublicPrice if real; no generic company H1; short category lead paragraphs to make the page more than a link list.

## 14. Service priorities

| P1 (launch) | P2 (wave 2) | P3 (wave 3 / conditional) |
|---|---|---|
| تنظيف المنازل (deep) · تنظيف الفلل · تنظيف الشقق · تنظيف ما بعد البناء والتشطيب (incl. قبل السكن) · تنظيف الكنب · تنظيف المجالس · تنظيف الخزانات · تنظيف المكيفات* · تنظيف المكاتب والشركات | تنظيف السجاد والموكيت · تنظيف الواجهات والزجاج · جلي وتلميع الرخام والبلاط · مكافحة الحشرات* · تنظيف المحلات والمعارض · عقود النظافة الدورية · تنظيف المسابح* | التعقيم والتطهير · المطابخ/الحمامات/الأرضيات as sections · B2B landing (إدارة المرافق/الخدمات التجارية) · English variants |

\* = only if the service is genuinely executed by Vibe (AC needs technicians; pest needs licensing; pools need maintenance capability). If not, demote to P3 or drop.

Rationale: P1 = highest local demand + pricing intent + fits a residential cleaning operator + SERP won by generalist service pages; P2 = real demand but specialist-dominated SERPs or B2B; P3 = weak/declining or DIY-dominated demand.

## 15. Area priorities (evidence-gated; never a Service×Area matrix)

| Pri | Areas | Evidence |
|---|---|---|
| P1 | العليا, الملقا, النرجس, الياسمين | Autocomplete demand for the district itself (+ services for العليا/الياسمين); competitors already publish district guides (thin). |
| P2 | العقيق, الدرعية, لبن (not in owner list; has demand), القيروان, اليرموك, الرمال, حطين (affluent north; no query evidence — only with projects) | partial signals |
| P3 | الصحافة, قرطبة, المونسية, الروضة, العارض | no autocomplete signal; publish only after real projects exist |
| Directional | شمال/شرق/غرب/جنوب الرياض | `/areas` group anchors now; AreaGroup page only when ≥3 published area pages with projects per group |

Publishing gate stays: unique copy, real coverage, real projects (photos/dates), nearby areas, useful FAQ.

## 16. Pricing-search strategy (Vibe already has PublicPrice)

| Query type | Serve with |
|---|---|
| "اسعار شركات التنظيف بالرياض", "اسعار تنظيف المنازل بالرياض", "ارخص شركة تنظيف" | **One pricing-guide article** ("دليل أسعار خدمات التنظيف في الرياض 2026": ranges by service, what drives price, what "cheap" usually excludes, how quotes work) — updated yearly, links to each service's price section. |
| "سعر تنظيف الكنب/الخزان/الشقة/الفيلا", "كم سعر تنظيف مكيف سبليت", "سعر غسيل السجاد بالمتر", "سعر متر تلميع الرخام" | **Price section on the service page** (PublicPrice: starting_from / per_unit / range; price_factors block; inclusions block). No separate URL. |
| "تكلفة تنظيف بعد البناء", "كم تكلفة تنظيف فيلا بالكامل" | Service page price section + FAQ answer ("يعتمد على…"). |
| "اسعار عقود النظافة" | Contracts page: pricing model explanation (per visit/month, scope), quote CTA. |
| Offers | Only real time-bound OfferPrice; never as pricing landing pages. |
| Competitor anchors observed (market context, not Vibe prices): sofa 95–250 ريال; majlis 200; carpets من 100 / بالمتر; tanks 120–800; AC split 35–40 per unit; deep clean apartment ~350–575; villa 1,200–1,350+; contracts (municipal) 50–150; pest 150–500. Owner must set real prices; unpriced services stay quote_only with no placeholder. |

## 17. Article opportunities (content moat, not volume)

| # | Topic (working title) | Primary query | Cluster | Intent | Funnel | Service | Internal links | SERP format | Pri |
|---|---|---|---|---|---|---|---|---|---|
| A1 | دليل أسعار خدمات التنظيف في الرياض (2026) | اسعار شركات التنظيف بالرياض | C20 | C | Mid | all P1 | → each service price section, quote | guide + tables + FAQ | P1 |
| A2 | كيف تختار شركة تنظيف في الرياض: معايير المقارنة | افضل شركة تنظيف بالرياض | C21 | C | Mid | all | → about, projects, services | criteria guide (honest alternative to listicles) | P1 |
| A3 | تنظيف ما بعد البناء والتشطيب: ما يشمله، خطواته، ومتى تطلبه | تنظيف بعد التشطيب / البورسلان بعد البناء | C05/06 | I→T | Mid | post-construction | → service, project (before/after), quote | guide | P1 |
| A4 | بقع الكنب: ما يمكنك إزالته بنفسك ومتى تحتاج تنظيفًا بالبخار | كيف انظف الكنب من البقع | C24 | I | Top | sofa | → sofa, majlis | how-to | P1 |
| A5 | تنظيف المكيف: كم مرة، وماذا يحدث إن أهملته | كم مرة يجب تنظيف المكيف | C24 | I | Top | AC* | → AC service | Q&A | P1* |
| A6 | تنظيف خزان المياه: متى، كيف، وما معنى "معتمد" | متى يجب تنظيف الخزان | C24 | I | Top | tanks | → tanks | Q&A | P1 |
| A7 | تسليم الفيلا الجديدة: قائمة فحص التنظيف قبل السكن | تنظيف الفيلا قبل السكن | C05 | I→T | Mid | villas/post-construction | → both | checklist | P2 |
| A8 | تنظيف السجاد في مكانه vs المغسلة | كيف انظف السجاد وهو في مكانه | C24 | I | Top | carpets | → carpets | comparison | P2 |
| A9 | التنظيف بالبخار: ماذا يناسب (كنب، مجالس، سجاد) وماذا لا | تنظيف بالبخار بالرياض | C07–09 | I/C | Mid | sofa/majlis/carpets | → three services | explainer | P2 |
| A10 | جدول التنظيف العميق قبل رمضان / العيد | تنظيف البيت قبل رمضان | C24 | I | Top | homes | → homes, offers (if real) | seasonal checklist | P2 |
| A11 | بقع الرخام السوداء والصفراء: التنظيف vs الجلي | تنظيف الرخام من البقع | C24 | I | Top | marble | → marble | Q&A | P2 |
| A12 | عقود النظافة للشركات: ما الذي يحدد السعر ونطاق العمل | عقود تنظيف شهرية | C11 | C | Mid | contracts/offices | → both | explainer | P2 |
| A13 | الفرق بين التنظيف العادي والعميق (وماذا يشمل كل منهما) | تنظيف عميق للمنزل | C02 | I/C | Mid | homes | → homes, apartments | comparison | P2 |
| A14 | (project-led) "ماذا تعلمنا من تنظيف فيلا بعد التشطيب في [حي]" | long-tail | evidence | I | Mid | post-construction | → project, area, service | case narrative | P3 (needs real projects) |

## 18. GEO opportunities (answer-first, factual, extractable)

Content shapes that make pages quotable without any special markup (Google: no special schema/optimization exists for AI features):
- **Definition sentence** at the top of every service page (what the service is, for whom, in Riyadh).
- **Price block**: PublicPrice + "ما يحدد السعر" list (price_factors block) + "ما يشمله / ما لا يشمله" (inclusions block).
- **Process steps** (steps block) with durations if real.
- **FAQ** with one-paragraph answers (faq block) — visible text, no dependency on FAQPage rich results.
- **Coverage list** with links to area pages (real only).
- **Evidence**: project cards with service, area, date, scope — first-party facts an answer engine can cite.
- **Comparison tables** in A1/A2/A8/A9/A13.
- Queries that can surface as answers: كم سعر…, كم مرة…, هل … ضروري, ماذا يشمل…, الفرق بين…, كيف اختار…, متى…, ما يحدد السعر.
- Guardrails: no "AI keywords", no invented statistics, no self-proclaimed "أفضل", no hidden text; keep CTAs (quote/WhatsApp) beside the answer so GEO does not dilute conversion.

## 19. Local SEO strategy (GBP not created now)

- **GBP (future):** primary category "House cleaning service" (+ "Cleaning service", "Commercial cleaning service"); service-area business covering the published `/areas` districts; services list mirroring service pages with the same names and PublicPrice where real; photos only from real projects; description = About facts; Q&A seeded from the site's FAQs; hours = stored working_hours; UTM-tagged website link; posts for real offers only.
- **NAP consistency:** phone/WhatsApp/address identical across site footer, contact page, LocalBusiness schema, GBP, social bios.
- **"مفتوح الآن / قريب مني / الأعلى تقييمًا" intents** are GBP-driven: hours accuracy and genuine reviews (ask real customers; never incentivised/fake).
- **Citations:** Saudi directories/marketplaces are also competitors (HomeRun, qaymny, prokr, cleaningdirectory) — list only where NAP is controllable; avoid paid "featured" listings that stuff keywords.
- **Local evidence on-site:** area pages with projects; contact page map when a real address exists; service areas in `areaServed` matching `/areas`.
- **Social discovery** (autocomplete shows انستقرام/تيك توك/تويتر/حراج): consistent brand name + sameAs links; reels of real jobs feed both GBP photos and E-E-A-T.

## 20. Entity / E-E-A-T strategy

- One unambiguous entity: "Vibe Clean Pro — فايب كلين برو", Riyadh, Saudi-owned, founder المهندس عمر بلال الأكوع (already in LocalBusiness.founder), team (real), CR/VAT/licence facts (owner input) on About and footer.
- sameAs: Instagram, Snapchat, TikTok, X, GBP (when created), Maps.
- Author entity for articles: a real person (founder or operations lead) with role and short bio (Article.author Person already emitted).
- Experience signals: project pages with dates/areas/scope, before/after, "what we found / what we did".
- Trust pages: guarantee terms (real), privacy/terms (owner-provided, production blocker), payment method statement ("لا يتم الدفع عبر الموقع").
- No schema spam: no fake aggregateRating, no FAQPage padding, no Review markup for self-collected testimonials.

## 21. Structured-data review (existing generator)

| Item | Status | Recommendation |
|---|---|---|
| WebSite, WebPage, BreadcrumbList | OK | keep |
| LocalBusiness (+telephone, address, geo, sameAs, founder Person) | OK | use most specific subtype: `HomeAndConstructionBusiness` (Google: prefer specific subtype; `CleaningService` is not a schema.org type — a competitor uses it invalidly); add `openingHoursSpecification` from stored working_hours when present; `priceRange` only if owner defines; add `areaServed` at business level mirroring published areas (optional, low value) |
| Service (+provider, areaServed Places, Offer via PublicPrice) | OK | verify `serviceType`/`name` match visible H1; keep Offer only when PublicPrice exists (already) |
| Article (author Person) | OK | verify `image`, `datePublished`, `dateModified` are emitted (check in code) |
| Offer pages (Offer, PriceSpecification) | OK | ensure `validThrough` when the offer has an end date |
| FAQPage | absent | keep absent (ineligible for rich results since Aug 2023 except gov/health); FAQs remain visible content |
| HowTo | absent | keep absent (deprecated) |
| aggregateRating / Review | absent | keep absent unless third-party reviews are captured on-site |
| Project pages | WebPage only | optional `ImageObject` for before/after with `contentUrl`+`caption`; not a priority |

No gaps justify new schema beyond the subtype/opening-hours/article-date checks.

## 22. Internal-link architecture (paths already mostly supported by templates)

- Article → Service (contextual anchor = service name) → Project (evidence) → Quote (prefilled `?service=`).
- Area → Projects in area → Services offered there → Quote (`?area=`).
- Service → Price section/FAQ (same page anchors) → Projects (filtered) → Areas (areaServed list) → Quote (`?service=`); + related services (كنب ↔ مجالس ↔ سجاد; فلل ↔ ما بعد البناء ↔ خزانات).
- Project → Service + Area (+ next project).
- Pricing guide (A1) → every service price section; Choosing guide (A2) → About, Projects, Services Index.
- Homepage → Services (all P1), Areas index, Projects index, About, A1/A2.
- Anchor-text rule: natural service/area names; never "شركة تنظيف بالرياض" repeated as anchor; no footer district dump.

## 23. Competitor content gaps

| Competitor | Does well | Ranks for | Lacks | UX / SEO weaknesses | Vibe can do better |
|---|---|---|---|---|---|
| cleaner4me | Depth, prices, steps, FAQ, schema, all districts | generic + most clusters | Real per-job evidence; district stuffing (17 districts on one page) | Discount/24h/Filipino claims; 68× "بالرياض" on one page | Real projects per area, honest pricing, no stuffing |
| malekclean | "أرخص" positioning, east/west coverage | homes, apartments, tanks, villas | Transparency of what cheap includes; (crawl blocked) | Price-war framing | Includes/excludes + price factors |
| cleanspot.sa | Transparent price tables, bilingual, blog guides, clean Offer schema | pricing, English | Local district evidence; project proof | Generic hero H1 | Evidence + area depth; match its pricing clarity |
| cleaning-company-riyadh / cleaningdirectory | Volume | spammy long-tail | Everything credible | 300+ city repeats, discount titles, 400+ internal links | Do not copy; it is the pattern Google's spam policies target |
| alsauditoday / saudi.wiki / greecleaning | Listicles/comparisons | "أفضل شركة", pest | First-party service | Affiliate-style, dated yearly | Criteria guide with real proof |
| saudi-cleaning.com | Programmatic district×service pages | districts | Uniqueness (generic text, 2 images) | Doorway pattern | Evidence-gated area pages |
| naba-clean | District "how to choose / prices" guides | الملقا, النرجس | Photos, projects, no H1 on home | Thin site (442 words home) | Real local proof |
| aljawadcleaning | Facades specialist, before/after, facade types | facades, post-construction, districts | Residential depth | Many claims | Adopt "choose your facade type" clarity for our facades page |
| zadksa / alarab-takyf / mukayefat | Price in title, per-unit pricing | sofa, tanks, pest / AC | Evidence, identity | Bundled unrelated services (moving) | Per-unit PublicPrice with honest scope |
| jaliriyadh | "المعاينة تسبق السعر", service selector, 14 districts | marble | — | District list | Same honesty; only if Vibe truly polishes |
| cleandari | "أسعار معلنة قبل الحجز", Saudi team, HowTo/FAQ | pricing | Villas/B2B depth | Small catalogue | Match transparency + broader catalogue |
| ultraclean / evercleansa / alshmasi | English pages, premium framing | English clusters | Arabic depth (evercleansa 25/60 images no alt) | Thin pricing pages (263 words) | Bilingual done properly, later wave |
| Sahm Clean | Social presence | nothing organic | H1s, schema, alt text, depth; split across .sa/.shop | 135–567-word pages | Everything technical + evidence |

## 24. Vibe competitive advantages (planned, no fake claims)

1. **PublicPrice architecture**: honest starting_from / per_unit / range with factors and includes/excludes — the pricing SERP rewards exactly this, and most rivals hide or inflate.
2. **First-party evidence**: projects with real service, area, date, before/after, scope — rivals mostly claim, rarely show.
3. **Evidence-gated area pages** vs rivals' programmatic doorway pages.
4. **Entity clarity**: Saudi-owned identity, named founder, real team, policies — rivals are anonymous phone-number sites.
5. **Technical hygiene**: canonical/sitemap/robots/publishing gate/duplicate detection, single H1, alt text, 0 lazy-loading, Core Web Vitals-oriented templates — most rivals fail basics (missing H1s, 30–60% images without alt, title bugs).
6. **Content graph**: Service ↔ Area ↔ Project ↔ Article ↔ Offer ↔ Trust already modelled.
7. **Conversion honesty**: "لا يتم الدفع عبر الموقع", no fake urgency, quote request clarity.

## 25. Launch content map (Minimum Strong Launch)

- **Homepage** (generic company intent, evidence, coverage).
- **About** (identity, founder, team, facts) + Contact + Quote (done) + Trust page (guarantee terms — owner input) + Privacy/Terms (owner input; production blocker).
- **P1 Services (9):** المنازل, الفلل, الشقق, ما بعد البناء والتشطيب, الكنب, المجالس, الخزانات, المكيفات*, المكاتب والشركات — each with PublicPrice (or quote_only), inclusions, price_factors, steps, FAQ, areaServed, related services, project links.
- **P1 Areas (4):** العليا, الملقا, النرجس, الياسمين — only those with real projects at launch; others stay draft.
- **P1 Articles (5):** A1 pricing guide, A2 choosing guide, A3 post-construction guide, A4 sofa stains, A6 tanks (A5 AC if AC is P1).
- **Projects needed:** ≥ 6–10 real projects covering the P1 services and P1 areas (each with before/after, date, scope, area).
- **Offers inputs:** only real, time-bound offers (OfferPrice); none fabricated.
- **FAQ inputs:** 5–8 per P1 service (from A/C questions in §9) + 5 sitewide.
- **Pricing inputs:** PublicPrice mode + numbers per P1 service, unit definitions (per sofa seat / per m² / per tank size / per AC unit), VAT status, minimum charge, inspection policy.

## 26. P1 service blueprints (structure only — no copy)

**Common skeleton (all P1):** search intent → primary keyword in Title + H1 (natural, once) → definition paragraph → who it is for → what's included / excluded (inclusions) → price (PublicPrice) + factors → process steps → evidence (projects) → areas served (links) → FAQ (5–8) → related services → CTA (quote prefilled + WhatsApp) → GEO answer blocks: definition, price sentence, duration, frequency.

| Service | Primary / secondary cluster | Title & H1 intent | User questions | Pricing section | Includes / excludes | Price factors | Process | FAQ themes | Evidence | Areas | Links | CTA | GEO answers |
|---|---|---|---|---|---|---|---|---|---|---|---|---|---|
| تنظيف المنازل | تنظيف منازل بالرياض / نظافة منازل، تنظيف بيوت، تنظيف عميق للمنزل | home deep cleaning by a Riyadh company | ماذا يشمل، كم يستغرق، كم السعر، هل يشمل المطبخ والحمامات، هل توفرون المواد | starting_from or range per home size | rooms, kitchen, bathrooms, windows inside; excludes facades/AC/tanks (link) | size, condition, extras | inspection → team → checklist → handover | frequency, materials, pets, keys/privacy | 2+ projects | P1 areas | villas, apartments, sofa, contracts | quote | "التنظيف العميق يشمل…" |
| تنظيف الفلل | تنظيف فلل بالرياض / فلل جديدة، قصور، أفضل شركة تنظيف فلل | villa cleaning (occupied + new handover pointer) | كم سعر تنظيف الفيلا، كم يستغرق، كم عامل، هل يشمل الحوش والواجهة | range by floors/area | interior floors, stairs, kitchens, bathrooms, glass inside; excludes facades (link), tanks (link) | area m², floors, furnished/empty, post-finishing state | inspection → crew size → phases → QC | new-villa vs occupied, duration, materials | villa projects (before/after) | العليا/الملقا/النرجس/الياسمين | post-construction, facades, tanks, AC | quote | "سعر تنظيف الفيلا يعتمد على…" |
| تنظيف الشقق | تنظيف شقق بالرياض / شقق جديدة، مفروشة، عزاب | apartment cleaning (sizes, furnished, new) | سعر تنظيف الشقة، كم يستغرق، شقة جديدة، مفروشة | fixed/range by rooms | rooms/kitchen/bathrooms/balcony; excludes carpets wash (link) | rooms, furnished, condition | booking → visit → clean → handover | tenants, keys, time slots | apartment projects | العليا (apartments strong) | homes, post-construction, sofa | quote | "تنظيف شقة غرفتين يستغرق…" (if real) |
| ما بعد البناء والتشطيب | تنظيف بعد التشطيب بالرياض + بعد البناء / قبل السكن، فلل/شقق جديدة، بعد الدهان/الترميم | post-construction & finishing cleaning before occupancy | ماذا يشمل، البورسلان والسيراميك، بقايا الدهان، كم يستغرق، السعر | range by m² or quote_only | debris removal, dust, floors (porcelain/ceramic/marble wash), glass, bathrooms, kitchens; excludes marble crystallization (link), facades (link) | m², finishing stage, debris volume, floors | site check → rough clean → fine clean → final | when to schedule vs contractors, materials safe for porcelain | before/after projects | north Riyadh districts | villas, apartments, marble, facades | quote | "تنظيف ما بعد التشطيب يشمل…" |
| تنظيف الكنب | تنظيف كنب بالرياض / غسيل كنب، بالبخار، مفروشات، فرشات | in-home sofa & upholstery cleaning (steam) | السعر بالقطعة/المقعد، بالبخار أم جاف، البقع، الرائحة، مدة الجفاف | per_unit (seat/piece) | fabric types covered, stain treatment, drying; excludes leather? (define) | seats, fabric, stains, pet odour | inspection → pre-treatment → steam/extraction → drying | drying time, kids/pets safety, stains | sofa projects | all | majlis, carpets, steam article A9 | quote | "تنظيف الكنب بالبخار يستغرق…" |
| تنظيف المجالس | تنظيف مجالس بالرياض / غسيل مجالس بالبخار، مجالس وكنب | Arabic majlis (floor seating) cleaning in place | السعر بالمتر/بالمجلس، البخار، الوقت، الجفاف | per_unit (metre/set) | cushions, backs, carpets inside majlis? (define) | size, fabric, condition | same as sofa | steam safety, season peaks (Ramadan/Eid) | majlis projects | all | sofa, carpets, seasonal A10 | quote | definition + price sentence |
| تنظيف الخزانات | تنظيف خزانات بالرياض / غسيل خزانات، خزانات المياه، وعزل، معتمدة | water tank cleaning & disinfection (ground/roof) | السعر أرضي/علوي، كم مرة، التعقيم المعتمد، هل يشمل العزل | per_unit by tank type/size | drain, scrub, disinfect, rinse; excludes insulation unless offered (state) | size, type, access, sediment | drain → clean → disinfect → refill advice | frequency, materials, water safety | tank projects | all + directional demand | villas, homes, contracts | quote | "يُنصح بتنظيف الخزان كل…" only if factual |
| تنظيف المكيفات* | تنظيف مكيفات بالرياض / غسيل سبليت، شباك، مركزي، دكت | AC unit cleaning (split/window/central) | كم سعر تنظيف مكيف سبليت، كم مرة، هل ضروري، هل يشمل الفريون | per_unit (per AC) | filters, indoor/outdoor coils, drain; excludes gas refill/repair (state) | type, count, height/access | check → clean → test | frequency, seasonality, DIY limits | AC projects | all | homes, villas, contracts, A5 | quote | "كم مرة يجب تنظيف المكيف" |
| تنظيف المكاتب والشركات | تنظيف مكاتب بالرياض / تنظيف شركات، office cleaning riyadh | office/company cleaning (one-off + periodic) | السعر، بعد الدوام، عقود، المواد، السرية | quote_only or starting_from per m²/visit | workstations, floors, glass inside, kitchens, bathrooms; excludes facades (link) | m², frequency, after-hours | walkthrough → plan → schedule → reports | insurance/IDs, after-hours, contracts | office projects | business districts (العليا) | contracts, commercial premises, facades, B2B landing | quote (business context) | "تنظيف المكاتب يشمل…" |

## 27. P1 area blueprints (structure only)

| Area | Target intent | Relevant services (real only) | Local proof required | Project requirement | Nearby areas | FAQ opportunities | Publishing requirement |
|---|---|---|---|---|---|---|---|
| العليا | "شركة تنظيف العليا الرياض" (+شقق/فلل/منازل/خزانات) | apartments (towers), offices/companies, homes, tanks | building types (apartments/offices), access/parking notes, real jobs | ≥2 projects in العليا (1 apartment/office) | السليمانية, الورود, الملك فهد | تنظيف شقق في أبراج العليا، مواعيد بعد الدوام للمكاتب | unique copy, projects, nearby links, FAQ |
| الملقا | "شركة تنظيف الملقا" / منازل في حي الملقا | villas, post-construction (new villas), tanks, marble* | villa scale, new builds, real jobs | ≥2 villa/post-finishing projects | حطين, الياسمين, القيروان | تنظيف فيلا جديدة في الملقا، هل تخدمون شمال الرياض | same |
| النرجس | "شركة تنظيف النرجس" / تنظيف منازل/فلل النرجس | villas, post-finishing, homes | new-development character, real jobs | ≥2 projects | الياسمين, العارض, الصحافة (unpublished) | same pattern | same |
| الياسمين | "شركة تنظيف الياسمين" (+خزانات) | homes, villas, tanks, AC* | real jobs, tank work evidence | ≥2 projects (1 tank if P1) | النرجس, الملقا, الصحافة | خزانات في الياسمين | same |

## 28. Content production order

**WAVE 1 (launch)** — dependencies: owner inputs (§29), ≥6 real projects, PublicPrice numbers.
1. Homepage + About + Trust/Privacy (owner content) → 2. P1 services (9) → 3. P1 areas (4) with projects → 4. A1 pricing guide, A2 choosing guide → 5. A3/A4/A6 (+A5) → internal links pass → GBP creation (after launch).

**WAVE 2** — dependencies: wave-1 indexed, more projects.
P2 services (carpets, facades, marble, pest*, commercial premises, contracts, pools*) → P2 areas (العقيق, الدرعية, لبن, القيروان, اليرموك, الرمال, حطين) as projects allow → A7–A13 → offers (real) → reviews program.

**WAVE 3** — dependencies: demand data from Search Console (real volumes replace the "DATA NOT AVAILABLE" cells).
English variants (homepage, villas, homes/deep, offices, sofa, pricing guide) → P3 services/sections → directional AreaGroup pages (if ≥3 area pages per group) → project-led case narratives (A14) → yearly refresh of A1.

## 29. Business inputs required (cannot be invented)

- Actual prices per P1/P2 service, pricing unit definitions, minimum charge, VAT status (included/excluded), inspection/quote policy.
- Exact service scope per service (includes/excludes), which services are executed in-house vs not offered (AC, pest licence, pools, marble crystallization, tank insulation, hourly model).
- Real guarantees/re-clean policy wording; response-time facts (if any) — otherwise none stated.
- Opening hours; phone, WhatsApp, email, address (or service-area only), CR number, VAT number, licences (pest control).
- Founder/team facts and photos; company founding year.
- Real project photos (before/after), dates, areas, scope; permission to publish.
- Actual areas served today (north-first?) and travel limits.
- Contract offering for businesses (frequency, minimums) — and whether the "عقد نظافة بلدي" product is offered at all (recommendation: no).
- Payment methods; privacy/terms content (production blocker); labour facts (nationalities, gender of teams) if any such claim will be made.

## 30. Key uncertainties

1. **No Google SERP/volume data** — priorities rest on autocomplete depth + competitor investment (Bing-index proxy). First 60–90 days of Search Console will recalibrate P1/P2.
2. **AC / pest / pools / marble** depend on real capability; specialist SERPs punish generalists with thin pages.
3. **District demand is thin** for most of the owner's list; only العليا/الملقا/النرجس/الياسمين show clear signals — area pages must be justified by projects, not by the list.
4. **"عقود نظافة" head term** is dominated by the municipal-licence contract product; ranking there is not the B2B goal.
5. **Hourly/maid model** is a large demand pocket Vibe does not offer — a business decision, not an SEO one.
6. **Carpets** compete with pickup laundries; positioning ("في مكانه") may cap reach.
7. **Local Pack** likely decides a large share of "مفتوح الآن/قريب مني" traffic — GBP timing matters more than blog volume.
8. **English demand** is real but marketplace-app heavy; treat as wave 3.
9. Bing-index SERPs may over-represent spam-pattern sites relative to Google; the competitive set (not the rank order) is what was used.

---

### Second-pass QA (independent SEO review)
- All 27 services researched? Yes — each mapped to a cluster or explicitly demoted (دوري، مرافق، زجاج، مطابخ، حمامات، أرضيات، قبل السكن).
- SERP studied per cluster? 42 SERPs on the SA proxy index; Google SERPs unavailable (declared).
- Real competitor per cluster? Matrix §6.
- Evidence vs guess? Every keyword traces to autocomplete or a competitor page; volumes marked unavailable.
- Cannibalization? §11 (7 B2B URLs → 3; three post-construction URLs → 1; floors/glass merged).
- Doorway risk? Area rule kept; directional pages gated; no district footers.
- Content gaps? §23–24. Pricing strategy? §16. Local? §19. GEO realistic? §18 (no special markup claimed). Launch executable? §25/§28 with dependencies.
