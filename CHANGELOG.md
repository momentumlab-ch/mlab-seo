# Changelog

All notable changes to MomentumLab SEO are documented here.
The project follows [Semantic Versioning](https://semver.org/): MAJOR for breaking changes, MINOR for new features, PATCH for fixes.

## [1.6.0] - 2026-10-25

### Neu
- Vorlagen für SEO-Titel und Meta-Beschreibung pro Inhaltstyp und Taxonomie (Reiter «Titel & Meta»), mit Variablen wie `%title%`, `%sep%`, `%sitename%`, `%category%` und `%currentyear%`. Die Schreibweise von Rank Math und Yoast wird erkannt.
- noindex und Artikeltyp (schema.org) pro Inhaltstyp. Inhaltstypen mit noindex fehlen in der Sitemap.
- Autoren- und Datumsarchive: indexieren, ausblenden oder abschalten (Weiterleitung auf die Startseite). Leere Kategorien und Schlagwörter sind noindex.
- Schnellbearbeitung (SEO-Titel, Meta-Beschreibung, Fokus-Keyword, noindex) und Mehrfachbearbeitung (noindex) in der Beitragsliste.
- Weitere Robots-Regeln pro Seite: noarchive, nosnippet, noimageindex.
- Hauptkategorie pro Beitrag für Brotkrumen und `%category%`, kürzerer Brotkrumen-Titel pro Seite und Kategorie. Name der Startseite und Trennzeichen der Brotkrumen sind einstellbar.
- Externe Links mit nofollow oder in neuem Tab, fehlende Alternativtexte aus dem Bildtitel, Link zum Original unter jedem Beitrag im RSS-Feed. Alles einzeln einschaltbar.
- Social-Titel, -Beschreibung und -Bild für Kategorien und Schlagwörter.
- WooCommerce-Produkte in schema.org mit Preis, Verfügbarkeit, Marke und Bewertungen. Die eigenen Produktdaten von WooCommerce entfallen, damit es nur einen Eintrag gibt.
- Pro: Weiterleitungen mit regulären Ausdrücken (`^/blog/(.*)$` nach `/news/$1`).
- Pro: Import der Weiterleitungen aus Rank Math und Redirection.

### Geändert
- Der Import aus Rank Math, Yoast und SEOPress übernimmt Hauptkategorie, Brotkrumen-Titel und weitere Robots-Regeln. Titel-Variablen bleiben als Variablen erhalten, statt beim Import ersetzt zu werden.
- Updates laufen über die Update-Funktion von WordPress (Plugin Update Checker). Das Release enthält `mlab-seo.zip`.

### Behoben
- SEO-Felder von Kategorien und Schlagwörtern wurden nicht gespeichert.

## [1.5.0] - 2026-10-25

### Added
- Editor: the Google preview shows rich snippets from the schema.org data of the page: the breadcrumb path in the URL line, the date before the description of blog posts, up to three FAQ questions from FAQ blocks, up to three event dates (Pro) and rating stars from an `aggregateRating` in the additional schema (Pro). Everything updates while typing. A note below the preview names the rich snippets found and says that Google decides itself whether to show them; with FAQ it adds that Google shows FAQ snippets only for a few websites. Pages set to noindex show no rich snippets.

## [1.4.2] - 2026-10-25

### Fixed
- `/sitemap.xml`, `/sitemaps.xml`, `/sitemap_index.xml` and `/sitemap-index.xml` redirect (301) to `wp-sitemap.xml` before WordPress runs its main query. Before, all but `/sitemap.xml` ended in a full 404 page, which on large sites took seconds; Bing Webmaster Tools reported "The request has timed out". Addresses submitted from Yoast SEO or Rank Math (`sitemap_index.xml`) keep working after switching.

### Added
- The Sitemap tab shows the address to submit in Google Search Console and Bing Webmaster Tools.

## [1.4.1] - 2026-10-25

### Fixed
- `wp-sitemap.xml` and its sub-sitemaps are served without the XSL stylesheet of WordPress. Chrome is removing XSLT and showed a warning on these pages. Browsers now show the plain XML; search engines read sitemaps without the stylesheet anyway.

## [1.4.0] - 2026-10-22

### Added
- Pro: 404 watcher. Each logged address gets a suggested target (same address name, a clearly similar address, or the nearest existing parent page) and a one-click "Redirect" button; "Ignore" stops logging an address. The log records where visitors came from: a page of the site (a broken internal link) or another website.
- Pro: broken links. Once a week the links on indexable pages (rendered blocks included) and in visible menus are checked in the background, 15 links per minute, so WP-Cron stays short. Only clear errors count: 404, 410, no answer, and server errors on the own site. Results in the Content tab and as a status message; "Check links now" starts a run.

### Changed
- The 404 log leaves out `/.well-known/` requests and removes addresses that work again (a redirect exists or a page was published there).

## [1.3.0] - 2026-10-10

### Added
- FAQ block (`mlab-seo/faq`): questions and answers as `<details>` accordion, rendered on the server. The questions go into the schema.org graph as FAQPage (on a page of type FAQPage directly into the page node) and into llms-full.txt as headings.
- Image sitemap `/sitemap-images.xml` (social, featured and content images) and video sitemap `/sitemap-videos.xml` (YouTube, Vimeo, uploaded videos), both listed in robots.txt and switchable under Sitemap.
- VideoObject schema for the videos of a page.
- hreflang: list the other languages under General; each page gets a field per language for the address of its translation, plus an optional x-default. Skipped while WPML, Polylang or TranslatePress is active.
- Content tab: indexable pages under 300 words, rated thin below 100 words, with images count and edit links; status warning for thin pages.
- Editor: pages under 100 words count as a problem in the SEO check; FAQ block text counts towards the word checks.

## [1.2.0] - 2026-10-10

### Added
- Import from Yoast SEO, Rank Math, SEOPress and All in One SEO (titles, descriptions, focus keyword, noindex, nofollow, canonical, social fields; categories and tags for Yoast, Rank Math and SEOPress). Title variables are resolved; default patterns are left empty.
- Status warning while another SEO plugin is active.
- SEO title, meta description and noindex for categories, tags and other public taxonomies; noindex terms are left out of the sitemap.
- IndexNow: changed pages are reported to Bing, Yandex, Seznam and Naver right after saving (opt-in, production sites only).
- Breadcrumbs: posts show their category in the schema.org breadcrumbs; visible navigation with `[mlab_seo_breadcrumbs]` or `mlab_seo_breadcrumbs()`.
- Local SEO: more business types, opening hours, map position and price range in the publisher schema.
- Site verification for Google Search Console, Bing, Yandex and Pinterest.
- Social title and description per page.
- Readability checks for long sentences and long paragraphs.
- Blog posts: language, category and word count in the schema.
- Attachment pages redirect to the file (can be switched off).
- Pro: automatic 301 redirect when the address of a published page changes, without chains.
- Filter `mlab_seo_taxonomies`.

### Fixed
- Archive pages 2, 3 … had the title and canonical URL of page 1.
- Locales with a variant (de_CH_informal, de_DE_formal) produced invalid og:locale and inLanguage values.
- Post type archives had no canonical URL.

## [1.1.1] - 2026-10-10

### Added
- Plugin icon (search result with magnifying glass), shown under Dashboard > Updates and in the details dialog.

## [1.1.0] - 2026-10-10

### Added
- Updates from the GitHub releases of this repository: new versions appear under Dashboard > Updates and on the Plugins page, with release notes under "View details", and can be installed with one click or by auto-update.
- Filter `mlab_seo_update_repository` to point the update check at another repository.

## [1.0.0] - 2026-10-10

### Added
- SEO box in the editor: Google preview with pixel check, SEO title, meta description, focus keyword, SEO check with score, social image, noindex, nofollow, canonical URL, page type and llms.txt listing.
- SEO score column in page and post lists.
- Head output: title, meta description, robots directives, canonical for archives, Open Graph and X cards.
- schema.org graph: publisher, website, web page, breadcrumbs, blog posts.
- Settings screen with status checks: general, publisher, AI and crawlers, sitemap, redirects and licence.
- robots.txt with per-crawler AI rules, llms.txt, crawler access check.
- Sitemap tuning: content types, no author pages, no noindex pages, last-modified dates.
- Pro: event schema with sub-events and offers, custom JSON-LD per page, redirect manager, 404 log, llms-full.txt.
- Licence activation with daily check and a 7-day grace period when the licence server cannot be reached.
- German translations (de_DE, de_DE_formal, de_CH, de_CH_informal).
