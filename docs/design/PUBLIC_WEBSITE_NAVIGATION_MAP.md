# PCDR — Public Website Navigation Map

**Status:** Implemented and covered by public regression tests. Every link below resolves to a real locale-prefixed destination; homepage preview anchors are translated to routes, not left as dead-end anchors.

---

## 1. Public route inventory

Locale group: `/{locale}` where `locale` is `ar` or `en`. Root `/` redirects to the configured default (`ar`).

| Route name | Path | Controller / response | Page component |
|---|---|---|---|
| `root` | `/` | Redirect to `/ar` | — |
| `sitemap` | `/sitemap.xml` | `SitemapController` | XML |
| `robots` | `/robots.txt` | `RobotsController` | Text |
| `public.home` | `/{locale}` | `Public\HomeController` | `Public/Home` |
| `public.about` | `/{locale}/about` | `Public\AboutController::__invoke` | `Public/About` |
| `public.about.focus` | `/{locale}/about/{focus}` | `Public\AboutController::__invoke` | `Public/About` |
| `public.pillars.index` | `/{locale}/strategic-areas` | `Public\PillarController@index` | `Public/Pillars/Index` |
| `public.pillars.show` | `/{locale}/strategic-areas/{pillar}` | `Public\PillarController@show` | `Public/Pillars/Show` |
| `public.programs.index` | `/{locale}/programs` | `Public\ProgramController@index` | `Public/Programs/Index` |
| `public.programs.show` | `/{locale}/programs/{program}` | `Public\ProgramController@show` | `Public/Programs/Show` |
| `public.projects.show` | `/{locale}/projects/{project}` | `Public\ProgramController@project` | `Public/Programs/Project` |
| `public.news.index` | `/{locale}/news` | `Public\NewsController@index` | `Public/News/Index` |
| `public.news.show` | `/{locale}/news/{article}` | `Public\NewsController@show` | `Public/News/Show` |
| `public.publications.index` | `/{locale}/publications` | `Public\PublicationController@index` | `Public/Publications/Index` |
| `public.publications.show` | `/{locale}/publications/{publication}` | `Public\PublicationController@show` | `Public/Publications/Show` |
| `public.publications.download` | `/{locale}/publications/{publication}/download` | `Public\PublicationController@download` | Signed stream |
| `public.transparency` | `/{locale}/transparency` | `Public\StaticPageController@transparency` | `Public/Transparency` |
| `public.partnerships` | `/{locale}/partnerships` | `Public\StaticPageController@partnerships` | `Public/Partnerships` |
| `public.involve` | `/{locale}/get-involved` | `Public\StaticPageController@involve` | `Public/Involve` |
| `public.contact` | `/{locale}/contact` | `Public\StaticPageController@contact` | `Public/Contact` |
| `public.inquiry.contact` | POST `/{locale}/contact/inquiry` | `Public\InquiryController@contact` | Redirect + flash |
| `public.inquiry.volunteer` | POST `/{locale}/volunteer/inquiry` | `Public\InquiryController@volunteer` | Redirect + flash |
| `public.inquiry.partnership` | POST `/{locale}/partnership/inquiry` | `Public\InquiryController@partnership` | Redirect + flash |
| `public.page` | `/{locale}/pages/{page}` | `Public\PageController@show` | `Public/Page` |
| `public.media.download` | `/{locale}/media/{uuid}/download` | `Public\MediaController@download` | Signed stream |

A duplicate same-URI `welcome` homepage route was removed in the isolated base because it overwrote the earlier `public.home` name in Laravel's route collection. Current main-repo behavior already has a single registration.

---

## 2. CMS navigation keys

`WebsiteSeeder` seeds the same ten enabled internal keys into both `primary` and `footer` menus. `NavigationItem::resolvedUrl()` maps them as follows:

| Key | Arabic label | English label | Destination |
|---|---|---|---|
| `home` | الرئيسية | Home | `/{locale}` |
| `about` | عن الجمعية | About PCDR | `/{locale}/about` |
| `pillars` | المحاور الاستراتيجية | Strategic areas | `/{locale}/strategic-areas` |
| `programs` | البرامج والمشاريع | Programs & projects | `/{locale}/programs` |
| `news` | الأخبار والأنشطة | News & activities | `/{locale}/news` |
| `publications` | التقارير والإصدارات | Reports & publications | `/{locale}/publications` |
| `transparency` | الشفافية والمساءلة | Transparency & accountability | `/{locale}/transparency` |
| `partnerships` | الشراكات | Partnerships | `/{locale}/partnerships` |
| `involve` | شارك معنا | Get involved | `/{locale}/get-involved` |
| `contact` | اتصل بنا | Contact | `/{locale}/contact` |

Rules:

- Only these ten internal keys are valid. Any other internal key falls back to `public.home`; do not invent one.
- External links must begin with `https://`; otherwise the resolver returns `#`. `javascript:` and `data:` URLs never reach the client.
- The CMS editor exposes the same key allowlist (`routeKeys`).
- `SiteHeader.vue` and `SiteFooter.vue` read `navigation.primary`; `SiteFooter.vue` additionally reads `navigation.footer`. A legacy `navigation.header` fallback is retained for older CMS data.
- Active state compares the normalized `page.url` pathname with each resolved URL, using exact match for the homepage and prefix match for other routes.

---

## 3. About focus navigation

`AboutController` whitelists seven focus values. Unknown values fall back to `overview`.

| Focus | Path | Title source |
|---|---|---|
| `overview` | `/{locale}/about` | `website.navigation.about` |
| `vision` | `/{locale}/about/vision` | `website.ui.vision` |
| `mission` | `/{locale}/about/mission` | `website.ui.mission` |
| `values` | `/{locale}/about/values` | `website.ui.values` |
| `direction` | `/{locale}/about/direction` | `website.ui.strategic_direction` |
| `groups` | `/{locale}/about/groups` | `website.ui.target_groups` |
| `approach` | `/{locale}/about/approach` | `website.ui.approach` |

Each focus receives unique `focus`, `focusTitle`, `focusDescription`, and canonical metadata. `AboutSubnav.vue` renders all seven links with `aria-current="page"` on the active focus.

---

## 4. Homepage preview link translation

`Home.vue` remains the approved static composition, but its preview anchors now resolve through `routeFor()`:

| Original anchor | Real destination |
|---|---|
| `#top` | `localPath()` — homepage |
| `#about` | `localPath('about')` |
| `#areas` | `localPath('strategic-areas')` |
| `#programs` | `localPath('programs')` |
| `#targets` | `localPath('about/direction')` |
| `#knowledge` | `localPath('publications')` |
| `#contact` | `localPath('contact')` |
| `#partners` | `localPath('partnerships')` |
| `#footer` | `localPath('contact')` |
| `#community` | `localPath('get-involved')` |
| `#main` | In-page skip link — the only remaining `href="#..."` in the file |

The hero "discover" action now goes to `/{locale}/about`; program tiles go to `/{locale}/programs`; CTA actions go to contact, get-involved, and strategic areas; footer preview links go to their dedicated pages.

---

## 5. Locale and SEO behavior

- `SetWebsiteLocale` makes the URL segment authoritative; `/ar/ar` is normalized.
- `app.blade.php` emits `<html lang dir data-locale>` and server-rendered SEO fallback metadata.
- Inertia `PublicLayout` emits client-side `title`, description, canonical, `hreflang`, Open Graph, Twitter, and robots metadata from controller `meta` props.
- `LanguageSwitcher.vue` uses `alternateUrl()` to swap only the first path segment, preserving the current destination in the other language. It visits with `preserveScroll: true` and `preserveState: false`.
- `app.js` repeats `lang`/`dir`/`data-locale` synchronization on every Inertia navigate event.

---

## 6. Verification

| Check | Result |
|---|---|
| 15 destination paths × `ar`/`en` render `200` with correct `lang`/`dir` and expected component | **Passed** |
| Ten `nav` translation keys and ten seeded `navigation.primary` items | **Passed** |
| Resolved `/ar/about` and `/ar/strategic-areas` URLs | **Passed** |
| Header and footer read `navigation.primary` | **Passed** |
| Seven distinct About focus pages with unique titles and canonicals | **Passed** |
| Homepage has exactly one `href="#"` and uses `routeFor(link.href)` | **Passed** |
| `php artisan route:list \| grep public.home` | **Resolves** |
