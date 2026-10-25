=== MomentumLab SEO ===
Contributors: momentumlab
Tags: seo, schema, llms.txt, ai, sitemap
Requires at least: 6.5
Tested up to: 7.1
Requires PHP: 8.0
Stable tag: 1.5.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Search engine and AI optimisation for WordPress: titles, meta descriptions, Google preview with SEO check, social previews, schema.org, sitemap, robots.txt and llms.txt.

== Description ==

Free

* SEO title, meta description and focus keyword for every page and post.
* Google preview with a pixel check for the title (like Google, about 580 px).
* SEO check with score: title and description length, focus keyword in title, description, first paragraph, URL and subheadings, keyword density, text length, headings, image alt texts, internal and external links, social image. The score shows as a column in the page list.
* Social previews: Open Graph and X cards with a per-page image, featured image or default image.
* schema.org as one JSON-LD graph: publisher (organisation or person, with logo, address and profiles), website, web page with page type, breadcrumbs, blog posts.
* Readability check: long sentences and long paragraphs.
* FAQ block: questions and answers as an accordion, with FAQPage schema for Google, ChatGPT, Perplexity and other AI assistants.
* Content check: lists indexable pages with fewer than 100 (thin) or 300 (short) words, with a warning in the status.
* Image sitemap (/sitemap-images.xml) and video sitemap (/sitemap-videos.xml) for YouTube, Vimeo and uploaded videos, plus VideoObject schema.
* hreflang for websites with one site or domain per language (for example DE, EN, FR): the address of each translation per page. Off while WPML, Polylang or TranslatePress handle it.
* Social title and description per page, separate from the Google snippet.
* noindex, nofollow and canonical URL per page. Search and 404 pages are noindex.
* SEO title, meta description and noindex for categories, tags and other taxonomies. Archive pages 2, 3 … get their own title and canonical URL.
* Breadcrumbs with category for posts, in schema.org and as visible navigation: shortcode `[mlab_seo_breadcrumbs]` or `mlab_seo_breadcrumbs()` in the theme.
* Local SEO for local businesses: opening hours, map position and price range in schema.org.
* Site verification for Google Search Console, Bing, Yandex and Pinterest.
* IndexNow: new and changed pages are reported to Bing (also used by ChatGPT search and Copilot), Yandex, Seznam and Naver right after saving.
* Attachment pages redirect to the file.
* Import from Yoast SEO, Rank Math, SEOPress and All in One SEO, with a warning while another SEO plugin is active.
* XML sitemap tuning: choose content types, hide author pages (they expose user names), leave out noindex pages, add last-modified dates.
* robots.txt with an allow or block switch for each AI crawler (GPTBot, ClaudeBot, PerplexityBot, Google-Extended and more).
* llms.txt: a Markdown summary of the website for AI assistants with key facts and all pages, built from the SEO descriptions.
* Access check: requests the home page as Google, Bing, ChatGPT, Claude and Perplexity and reports server-side blocks.
* Status checks: indexing allowed, permalinks, HTTPS, root domain.

Pro (licence key)

* Events in Google results: event type, dates, venue, status, tickets, price and sub-events.
* Custom schema.org (JSON-LD) per page, for example FAQ, product or recipe.
* Redirect manager (301, 302, 410) with hit counter.
* Automatic redirect when the address of a published page changes.
* 404 log with one-click redirect.
* llms-full.txt: the full text of all pages as Markdown for AI.

Privacy and security

* No tracking, no external requests except the licence check (key, site URL and plugin version, once a day while a key is set), the access check the administrator starts, IndexNow when switched on (the addresses of changed pages, sent to api.indexnow.org) and the update check against the public GitHub releases of the plugin (when WordPress checks for updates, no site data is sent).
* All forms use nonces and capability checks (`manage_options` for settings, `edit_post` for the SEO box). All input is sanitised, all output escaped.

Developer hooks

* `mlab_seo_context` – SEO data of the current request.
* `mlab_seo_meta_tags` – meta tags before output.
* `mlab_seo_schema_graph` – the schema.org graph.
* `mlab_seo_llms_txt` – llms.txt content.
* `mlab_seo_post_types` – post types with the SEO box.
* `mlab_seo_taxonomies` – taxonomies with SEO fields.
* `mlab_seo_settings_capability` – capability for the settings (default `manage_options`).
* `mlab_seo_is_pro` – unlock Pro, for example for agency-hosted sites.
* `mlab_seo_license_endpoint` – licence server URL.
* `mlab_seo_update_repository` – GitHub repository that is checked for updates (default `momentumlab-ch/wp-mlab-seo`).
* Define `MLAB_SEO_KEEP_DATA` as `true` to keep all SEO data when the plugin is deleted.

== Changelog ==

= 1.5.0 =
* New: the Google preview shows rich snippets as Google may display them: breadcrumb path instead of the URL, date for blog posts, FAQ questions, event dates (Pro) and rating stars from the additional schema (Pro). A note below the preview lists the rich snippets the page provides.

= 1.4.1 =
* Fix: the XML sitemap no longer uses an XSL stylesheet, so Chrome no longer warns that XSLT is being removed. Browsers show the plain XML; search engines are not affected.

= 1.4.0 =
* New (Pro): 404 watcher. Each missing address gets a suggested target and a one-click redirect; "Ignore" stops logging an address. Shows where visitors came from (broken link on a page of the site, or another website).
* New (Pro): broken links. Links on indexable pages and in visible menus are checked weekly in the background, in small batches.

= 1.3.0 =
* New: FAQ block with FAQPage schema.
* New: image and video sitemaps, VideoObject schema for embedded videos.
* New: hreflang links to the translations of a page.
* New: content check for thin pages (Settings › SEO › Content) and a stricter word check in the editor.

= 1.2.0 =
* New: import from Yoast SEO, Rank Math, SEOPress and All in One SEO.
* New: SEO title, meta description and noindex for categories, tags and other taxonomies.
* New: IndexNow, breadcrumbs, local SEO, site verification, social title and description, readability check.
* New (Pro): automatic redirect when a page address changes.
* Fix: own title and canonical URL for archive pages 2, 3 …; language codes like de_CH_informal are output as de_CH.

= 1.1.1 =
* New: plugin icon, shown under Dashboard > Updates and in the details dialog.

= 1.1.0 =
* New: WordPress checks the GitHub releases of the plugin for updates. New versions appear under Dashboard > Updates and on the Plugins page and can be installed with one click or by auto-update.

= 1.0.0 =
* First release.
