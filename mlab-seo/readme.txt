=== MomentumLab SEO ===
Contributors: momentumlab
Tags: seo, schema, llms.txt, ai, sitemap
Requires at least: 6.5
Tested up to: 7.1
Requires PHP: 8.0
Stable tag: 1.1.0
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
* noindex, nofollow and canonical URL per page. Search and 404 pages are noindex.
* XML sitemap tuning: choose content types, hide author pages (they expose user names), leave out noindex pages, add last-modified dates.
* robots.txt with an allow or block switch for each AI crawler (GPTBot, ClaudeBot, PerplexityBot, Google-Extended and more).
* llms.txt: a Markdown summary of the website for AI assistants with key facts and all pages, built from the SEO descriptions.
* Access check: requests the home page as Google, Bing, ChatGPT, Claude and Perplexity and reports server-side blocks.
* Status checks: indexing allowed, permalinks, HTTPS, root domain.

Pro (licence key)

* Events in Google results: event type, dates, venue, status, tickets, price and sub-events.
* Custom schema.org (JSON-LD) per page, for example FAQ, product or recipe.
* Redirect manager (301, 302, 410) with hit counter.
* 404 log with one-click redirect.
* llms-full.txt: the full text of all pages as Markdown for AI.

Privacy and security

* No tracking, no external requests except the licence check (key, site URL and plugin version, once a day while a key is set), the access check the administrator starts and the update check against the public GitHub releases of the plugin (when WordPress checks for updates, no site data is sent).
* All forms use nonces and capability checks (`manage_options` for settings, `edit_post` for the SEO box). All input is sanitised, all output escaped.

Developer hooks

* `mlab_seo_context` – SEO data of the current request.
* `mlab_seo_meta_tags` – meta tags before output.
* `mlab_seo_schema_graph` – the schema.org graph.
* `mlab_seo_llms_txt` – llms.txt content.
* `mlab_seo_post_types` – post types with the SEO box.
* `mlab_seo_settings_capability` – capability for the settings (default `manage_options`).
* `mlab_seo_is_pro` – unlock Pro, for example for agency-hosted sites.
* `mlab_seo_license_endpoint` – licence server URL.
* `mlab_seo_update_repository` – GitHub repository that is checked for updates (default `momentumlab-ch/wp-mlab-seo`).
* Define `MLAB_SEO_KEEP_DATA` as `true` to keep all SEO data when the plugin is deleted.

== Changelog ==

= 1.1.0 =
* New: WordPress checks the GitHub releases of the plugin for updates. New versions appear under Dashboard > Updates and on the Plugins page and can be installed with one click or by auto-update.

= 1.0.0 =
* First release.
