<?php
/**
 * Structure of the SEO screens: pages in the admin menu, their segments and the settings each segment saves.
 *
 * A segment with keys renders its part inside a settings form that saves exactly these keys.
 * A segment without keys renders its part as is (own forms, lists).
 *
 * @package MomentumLab\SEO
 */

defined( 'ABSPATH' ) || exit;

/**
 * Pages of the SEO menu, keyed by menu slug, in menu order.
 *
 * @return array<string,array{title:string,menu:string,intro:string,segments:array<string,array{label:string,part:string,keys?:string[],after?:string}>}>
 */
function mlab_seo_admin_pages(): array {
	return array(
		'mlab-seo'           => array(
			'title'    => __( 'Overview', 'mlab-seo' ),
			'menu'     => __( 'Overview', 'mlab-seo' ),
			'intro'    => '',
			'segments' => array(
				'overview' => array(
					'label' => __( 'Overview', 'mlab-seo' ),
					'part'  => 'overview',
				),
			),
		),
		'mlab-seo-pages'     => array(
			'title'    => __( 'Pages', 'mlab-seo' ),
			'menu'     => __( 'Pages', 'mlab-seo' ),
			'intro'    => __( 'Title, description and previews of all pages in one place. Click a row to edit it.', 'mlab-seo' ),
			'segments' => array(
				'list'    => array(
					'label' => __( 'All pages', 'mlab-seo' ),
					'part'  => 'pages-list',
				),
				'content' => array(
					'label' => __( 'Content check', 'mlab-seo' ),
					'part'  => 'tab-content',
				),
			),
		),
		'mlab-seo-search'    => mlab_seo_admin_page_search(),
		'mlab-seo-ai'        => mlab_seo_admin_page_ai(),
		'mlab-seo-sitemap'   => array(
			'title'    => __( 'Sitemap', 'mlab-seo' ),
			'menu'     => __( 'Sitemap', 'mlab-seo' ),
			'intro'    => __( 'The list of all pages for Google and Bing. Hidden pages are left out automatically.', 'mlab-seo' ),
			'segments' => array(
				'sitemap' => array(
					'label' => __( 'Sitemap', 'mlab-seo' ),
					'part'  => 'tab-sitemap',
					'keys'  => array( 'sitemap_types', 'sitemap_taxonomy', 'sitemap_users', 'sitemap_images', 'sitemap_videos' ),
				),
			),
		),
		'mlab-seo-redirects' => array(
			'title'    => __( 'Redirects', 'mlab-seo' ),
			'menu'     => __( 'Redirects', 'mlab-seo' ),
			'intro'    => __( 'Old addresses lead to the new page, so links and rankings are kept.', 'mlab-seo' ),
			'segments' => array(
				'redirects' => array(
					'label' => __( 'Redirects', 'mlab-seo' ),
					'part'  => 'tab-redirects',
				),
			),
		),
		'mlab-seo-settings'  => mlab_seo_admin_page_settings(),
	);
}

/**
 * Search appearance: home page, templates, publisher.
 *
 * @return array
 */
function mlab_seo_admin_page_search(): array {
	return array(
		'title'    => __( 'Search appearance', 'mlab-seo' ),
		'menu'     => __( 'Search appearance', 'mlab-seo' ),
		'intro'    => __( 'How Google, social media and AI assistants show the website.', 'mlab-seo' ),
		'segments' => array(
			'home'      => array(
				'label' => __( 'Home page', 'mlab-seo' ),
				'part'  => 'search-home',
				'keys'  => array( 'site_name', 'separator', 'home_title', 'home_description', 'default_image', 'og_locale', 'twitter_site' ),
			),
			'templates' => array(
				'label' => __( 'Templates', 'mlab-seo' ),
				'part'  => 'tab-titles',
				'keys'  => array( 'titles', 'author_archive', 'date_archive', 'noindex_empty' ),
			),
			'publisher' => array(
				'label' => __( 'Organisation', 'mlab-seo' ),
				'part'  => 'tab-publisher',
				'keys'  => array( 'org_type', 'org_name', 'org_alt_name', 'org_logo', 'org_email', 'org_phone', 'org_street', 'org_zip', 'org_city', 'org_region', 'org_country', 'org_hours', 'org_lat', 'org_lng', 'org_price_range', 'org_same_as', 'org_extra' ),
			),
		),
	);
}

/**
 * AI and crawlers: crawler rules with access check, robots.txt, llms.txt, IndexNow.
 *
 * @return array
 */
function mlab_seo_admin_page_ai(): array {
	return array(
		'title'    => __( 'AI & crawlers', 'mlab-seo' ),
		'menu'     => __( 'AI & crawlers', 'mlab-seo' ),
		'intro'    => __( 'Who may read the website, and what AI assistants learn about it.', 'mlab-seo' ),
		'segments' => array(
			'crawlers' => array(
				'label' => __( 'Crawlers', 'mlab-seo' ),
				'part'  => 'ai-crawlers',
				'keys'  => array( 'ai_bots' ),
				'after' => 'access-check',
			),
			'robots'   => array(
				'label' => 'robots.txt',
				'part'  => 'ai-robots',
				'keys'  => array( 'robots_extra' ),
			),
			'llms'     => array(
				'label' => 'llms.txt',
				'part'  => 'ai-llms',
				'keys'  => array( 'llms_enabled', 'llms_summary', 'llms_facts', 'llms_full' ),
			),
			'indexnow' => array(
				'label' => 'IndexNow',
				'part'  => 'ai-indexnow',
				'keys'  => array( 'indexnow', 'indexnow_key' ),
			),
		),
	);
}

/**
 * Rarely needed settings: links and images, verification, languages, import, licence.
 *
 * @return array
 */
function mlab_seo_admin_page_settings(): array {
	return array(
		'title'    => __( 'Settings', 'mlab-seo' ),
		'menu'     => __( 'Settings', 'mlab-seo' ),
		'intro'    => __( 'Rarely needed. Everything has a sensible default.', 'mlab-seo' ),
		'segments' => array(
			'general'   => array(
				'label' => __( 'General', 'mlab-seo' ),
				'part'  => 'settings-general',
				'keys'  => array( 'external_nofollow', 'external_blank', 'image_alt', 'rss_source', 'breadcrumb_home', 'breadcrumb_sep', 'remove_generator', 'attachment_pages', 'woo_schema' ),
			),
			'verify'    => array(
				'label' => __( 'Verification', 'mlab-seo' ),
				'part'  => 'settings-verify',
				'keys'  => array( 'verify_google', 'verify_bing', 'verify_yandex', 'verify_pinterest' ),
			),
			'languages' => array(
				'label' => __( 'Languages', 'mlab-seo' ),
				'part'  => 'settings-languages',
				'keys'  => array( 'hreflang_langs', 'hreflang_default' ),
			),
			'import'    => array(
				'label' => __( 'Import', 'mlab-seo' ),
				'part'  => 'tab-import',
			),
			'license'   => array(
				'label' => __( 'Licence', 'mlab-seo' ),
				'part'  => 'tab-license',
			),
		),
	);
}
