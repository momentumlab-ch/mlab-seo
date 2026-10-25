<?php
/**
 * All site-wide settings in one place: key, label, type and default value.
 *
 * Settings, the settings screen and the sanitising read only from here.
 *
 * Types: text, email, tel, textarea, code (textarea for JSON or rules), checkbox, image,
 * select (with choices), and structured values handled by Settings::sanitize()
 * (ai_bots, post_types, titles, key).
 *
 * @package MomentumLab\SEO
 */

defined( 'ABSPATH' ) || exit;

/**
 * Definitions of all settings, keyed by setting name.
 *
 * @return array<string,array{type:string,default:mixed,label?:string,help?:string,text?:string,placeholder?:string,rows?:int,choices?:array}>
 */
function mlab_seo_options(): array {
	return array_merge(
		mlab_seo_options_general(),
		mlab_seo_options_content(),
		mlab_seo_options_publisher(),
		mlab_seo_options_crawlers(),
		mlab_seo_options_sitemap()
	);
}

/**
 * General tab: titles, social media, verification, languages.
 *
 * @return array
 */
function mlab_seo_options_general(): array {
	return array(
		'site_name'        => array(
			'type'    => 'text',
			'default' => '',
			'label'   => __( 'Website name', 'mlab-seo' ),
			'help'    => __( 'Used in titles, social previews and schema.org. Empty: the site title from WordPress.', 'mlab-seo' ),
		),
		'separator'        => array(
			'type'    => 'text',
			'default' => '–',
			'label'   => __( 'Title separator', 'mlab-seo' ),
			'help'    => __( 'Between page title and website name, for example – or |.', 'mlab-seo' ),
		),
		'home_title'       => array(
			'type'    => 'text',
			'default' => '',
			'label'   => __( 'Title of the home page', 'mlab-seo' ),
			'help'    => __( 'Can also be set in the SEO box of the home page.', 'mlab-seo' ),
		),
		'home_description' => array(
			'type'    => 'textarea',
			'default' => '',
			'label'   => __( 'Description of the home page', 'mlab-seo' ),
			'help'    => __( '120 to 160 characters. Also used as summary for AI assistants.', 'mlab-seo' ),
		),
		'default_image'    => array(
			'type'    => 'image',
			'default' => 0,
			'label'   => __( 'Default social image', 'mlab-seo' ),
			'help'    => __( 'For pages without their own image. 1200 × 630 px.', 'mlab-seo' ),
		),
		'og_locale'        => array(
			'type'    => 'text',
			'default' => '',
			'label'   => __( 'Language for social media', 'mlab-seo' ),
			'help'    => __( 'For example de_CH. Empty: the WordPress language.', 'mlab-seo' ),
		),
		'twitter_site'     => array(
			'type'        => 'text',
			'default'     => '',
			'label'       => __( 'X (Twitter) account', 'mlab-seo' ),
			'placeholder' => '@account',
		),
		'remove_generator' => array(
			'type'    => 'checkbox',
			'default' => 1,
			'label'   => __( 'Source code', 'mlab-seo' ),
			'text'    => __( 'Hide the WordPress version', 'mlab-seo' ),
		),
		'attachment_pages' => array(
			'type'    => 'checkbox',
			'default' => 0,
			'label'   => __( 'Attachment pages', 'mlab-seo' ),
			'text'    => __( 'Keep the pages WordPress creates for every uploaded file (not recommended: thin content). Off: they redirect to the file.', 'mlab-seo' ),
		),
		'verify_google'    => array(
			'type'    => 'text',
			'default' => '',
			'label'   => __( 'Google Search Console', 'mlab-seo' ),
		),
		'verify_bing'      => array(
			'type'    => 'text',
			'default' => '',
			'label'   => __( 'Bing Webmaster Tools', 'mlab-seo' ),
		),
		'verify_yandex'    => array(
			'type'    => 'text',
			'default' => '',
			'label'   => __( 'Yandex', 'mlab-seo' ),
		),
		'verify_pinterest' => array(
			'type'    => 'text',
			'default' => '',
			'label'   => __( 'Pinterest', 'mlab-seo' ),
		),
		'hreflang_langs'   => array(
			'type'        => 'text',
			'default'     => '',
			'label'       => __( 'Other languages', 'mlab-seo' ),
			'placeholder' => 'en, fr',
		),
		'hreflang_default' => array(
			'type'    => 'text',
			'default' => '',
			'label'   => __( 'Default language (x-default)', 'mlab-seo' ),
			'help'    => __( 'The version for visitors whose language is not offered.', 'mlab-seo' ),
		),
	);
}

/**
 * Titles & meta tab and content settings: templates, archives, links, images, feeds, breadcrumbs.
 *
 * @return array
 */
function mlab_seo_options_content(): array {
	$archive_choices = array(
		'index'   => __( 'Show in search results', 'mlab-seo' ),
		'noindex' => __( 'Hide from search engines (noindex)', 'mlab-seo' ),
		'off'     => __( 'Switch off (redirect to the home page)', 'mlab-seo' ),
	);
	return array(
		'titles'            => array(
			'type'    => 'titles',
			'default' => array(),
		),
		'author_archive'    => array(
			'type'    => 'select',
			'default' => 'noindex',
			'label'   => __( 'Author pages', 'mlab-seo' ),
			'help'    => __( 'On websites with one author, author pages repeat the blog and show user names.', 'mlab-seo' ),
			'choices' => $archive_choices,
		),
		'date_archive'      => array(
			'type'    => 'select',
			'default' => 'noindex',
			'label'   => __( 'Date archives', 'mlab-seo' ),
			'choices' => $archive_choices,
		),
		'noindex_empty'     => array(
			'type'    => 'checkbox',
			'default' => 1,
			'label'   => __( 'Empty categories and tags', 'mlab-seo' ),
			'text'    => __( 'Hide categories and tags without posts from search engines', 'mlab-seo' ),
		),
		'external_nofollow' => array(
			'type'    => 'checkbox',
			'default' => 0,
			'label'   => __( 'External links', 'mlab-seo' ),
			'text'    => __( 'Add rel="nofollow" to links to other websites', 'mlab-seo' ),
		),
		'external_blank'    => array(
			'type'    => 'checkbox',
			'default' => 0,
			'label'   => __( 'Links in a new tab', 'mlab-seo' ),
			'text'    => __( 'Open links to other websites in a new tab', 'mlab-seo' ),
		),
		'image_alt'         => array(
			'type'    => 'checkbox',
			'default' => 0,
			'label'   => __( 'Missing alternative texts', 'mlab-seo' ),
			'text'    => __( 'Fill missing alternative texts of images from the image title', 'mlab-seo' ),
		),
		'rss_source'        => array(
			'type'    => 'checkbox',
			'default' => 0,
			'label'   => __( 'RSS feed', 'mlab-seo' ),
			'text'    => __( 'Add a link to the original post below each post in the feed, so copied content points back to you', 'mlab-seo' ),
		),
		'breadcrumb_home'   => array(
			'type'        => 'text',
			'default'     => '',
			'label'       => __( 'First breadcrumb', 'mlab-seo' ),
			'help'        => __( 'Name of the home page in breadcrumbs. Empty: the title of the home page.', 'mlab-seo' ),
			'placeholder' => __( 'Home', 'mlab-seo' ),
		),
		'breadcrumb_sep'    => array(
			'type'    => 'text',
			'default' => '›',
			'label'   => __( 'Breadcrumb separator', 'mlab-seo' ),
		),
		'auto_redirect'     => array(
			'type'    => 'checkbox',
			'default' => 1,
		),
		'woo_schema'        => array(
			'type'    => 'checkbox',
			'default' => 1,
			'label'   => __( 'WooCommerce products', 'mlab-seo' ),
			'text'    => __( 'Describe products with price, availability and ratings in schema.org (replaces the product markup of WooCommerce)', 'mlab-seo' ),
		),
	);
}

/**
 * Publisher tab: organisation or person, address, local business.
 *
 * @return array
 */
function mlab_seo_options_publisher(): array {
	return array(
		'org_type'        => array(
			'type'    => 'select',
			'default' => 'Organization',
			'label'   => __( 'Type', 'mlab-seo' ),
			'choices' => array_combine( MomentumLab\SEO\Settings::ORG_TYPES, MomentumLab\SEO\Settings::ORG_TYPES ),
		),
		'org_name'        => array(
			'type'    => 'text',
			'default' => '',
			'label'   => __( 'Name', 'mlab-seo' ),
		),
		'org_alt_name'    => array(
			'type'    => 'text',
			'default' => '',
			'label'   => __( 'Other name', 'mlab-seo' ),
			'help'    => __( 'For example a brand or short name.', 'mlab-seo' ),
		),
		'org_logo'        => array(
			'type'    => 'image',
			'default' => 0,
			'label'   => __( 'Logo', 'mlab-seo' ),
			'help'    => __( 'Empty: the site logo from the theme.', 'mlab-seo' ),
		),
		'org_email'       => array(
			'type'    => 'email',
			'default' => '',
			'label'   => __( 'Email', 'mlab-seo' ),
		),
		'org_phone'       => array(
			'type'        => 'tel',
			'default'     => '',
			'label'       => __( 'Phone', 'mlab-seo' ),
			'placeholder' => '+41 …',
		),
		'org_street'      => array(
			'type'    => 'text',
			'default' => '',
			'label'   => __( 'Street', 'mlab-seo' ),
		),
		'org_zip'         => array(
			'type'    => 'text',
			'default' => '',
			'label'   => __( 'Postcode', 'mlab-seo' ),
		),
		'org_city'        => array(
			'type'    => 'text',
			'default' => '',
			'label'   => __( 'City', 'mlab-seo' ),
		),
		'org_region'      => array(
			'type'    => 'text',
			'default' => '',
			'label'   => __( 'Region', 'mlab-seo' ),
			'help'    => __( 'For example the canton: BE.', 'mlab-seo' ),
		),
		'org_country'     => array(
			'type'        => 'text',
			'default'     => '',
			'label'       => __( 'Country code', 'mlab-seo' ),
			'placeholder' => 'CH',
		),
		'org_hours'       => array(
			'type'    => 'textarea',
			'default' => '',
			'label'   => __( 'Opening hours', 'mlab-seo' ),
			'help'    => __( 'For local businesses. One rule per line, for example: Mo-Fr 08:00-12:00, 13:30-17:30 or Sa 09:00-16:00.', 'mlab-seo' ),
		),
		'org_lat'         => array(
			'type'        => 'text',
			'default'     => '',
			'label'       => __( 'Latitude', 'mlab-seo' ),
			'placeholder' => '46.9480',
		),
		'org_lng'         => array(
			'type'        => 'text',
			'default'     => '',
			'label'       => __( 'Longitude', 'mlab-seo' ),
			'help'        => __( 'For local businesses: the position on the map, for example from Google Maps (right-click on the place).', 'mlab-seo' ),
			'placeholder' => '7.4474',
		),
		'org_price_range' => array(
			'type'    => 'text',
			'default' => '',
			'label'   => __( 'Price range', 'mlab-seo' ),
			'help'    => __( 'For local businesses, for example $$ or CHF 20–50.', 'mlab-seo' ),
		),
		'org_same_as'     => array(
			'type'    => 'textarea',
			'default' => '',
			'label'   => __( 'Profiles', 'mlab-seo' ),
			'help'    => __( 'One URL per line: Instagram, Facebook, LinkedIn, Wikipedia …', 'mlab-seo' ),
		),
		'org_extra'       => array(
			'type'    => 'code',
			'default' => '',
			'label'   => __( 'Additional properties (JSON)', 'mlab-seo' ),
			'help'    => __( 'Optional, for experts: properties merged into the publisher, for example {"sport": "Skimo"}.', 'mlab-seo' ),
		),
	);
}

/**
 * AI & crawlers tab: AI crawlers, robots.txt, IndexNow, llms.txt.
 *
 * @return array
 */
function mlab_seo_options_crawlers(): array {
	return array(
		'ai_bots'      => array(
			'type'    => 'ai_bots',
			'default' => array_fill_keys( array_keys( MomentumLab\SEO\Settings::AI_BOTS ), 1 ),
		),
		'robots_extra' => array(
			'type'    => 'code',
			'default' => '',
			'label'   => __( 'Additional robots.txt rules', 'mlab-seo' ),
		),
		'indexnow'     => array(
			'type'    => 'checkbox',
			'default' => 0,
			'label'   => __( 'IndexNow', 'mlab-seo' ),
			'text'    => __( 'Submit changed pages automatically', 'mlab-seo' ),
		),
		'indexnow_key' => array(
			'type'    => 'key',
			'default' => '',
		),
		'llms_enabled' => array(
			'type'    => 'checkbox',
			'default' => 1,
			'label'   => __( 'llms.txt', 'mlab-seo' ),
			'text'    => __( 'Provide /llms.txt', 'mlab-seo' ),
		),
		'llms_summary' => array(
			'type'    => 'textarea',
			'default' => '',
			'label'   => __( 'Summary', 'mlab-seo' ),
			'help'    => __( 'Two or three sentences: who you are, what you offer, where. Empty: the description of the home page.', 'mlab-seo' ),
		),
		'llms_facts'   => array(
			'type'    => 'textarea',
			'default' => '',
			'rows'    => 7,
			'label'   => __( 'Key facts', 'mlab-seo' ),
			'help'    => __( 'One fact per line: dates, places, prices, contact. AI assistants quote these directly.', 'mlab-seo' ),
		),
		'llms_full'    => array(
			'type'    => 'checkbox',
			'default' => 0,
			'label'   => __( 'llms-full.txt', 'mlab-seo' ),
			'text'    => __( 'Also provide the full text of all pages as /llms-full.txt', 'mlab-seo' ),
		),
	);
}

/**
 * Sitemap tab.
 *
 * @return array
 */
function mlab_seo_options_sitemap(): array {
	return array(
		'sitemap_types'    => array(
			'type'    => 'post_types',
			'default' => array( 'page' ),
			'label'   => __( 'Content types', 'mlab-seo' ),
		),
		'sitemap_taxonomy' => array(
			'type'    => 'checkbox',
			'default' => 0,
			'label'   => __( 'Categories and tags', 'mlab-seo' ),
			'text'    => __( 'Include archive pages of categories and tags', 'mlab-seo' ),
		),
		'sitemap_users'    => array(
			'type'    => 'checkbox',
			'default' => 0,
			'label'   => __( 'Authors', 'mlab-seo' ),
			'text'    => __( 'Include author pages (shows user names, not recommended)', 'mlab-seo' ),
		),
		'sitemap_images'   => array(
			'type'    => 'checkbox',
			'default' => 1,
			'label'   => __( 'Image sitemap', 'mlab-seo' ),
			'text'    => __( 'List the images of every page in /sitemap-images.xml (for Google Images)', 'mlab-seo' ),
		),
		'sitemap_videos'   => array(
			'type'    => 'checkbox',
			'default' => 1,
			'label'   => __( 'Video sitemap', 'mlab-seo' ),
			'text'    => __( 'List YouTube, Vimeo and uploaded videos in /sitemap-videos.xml (for Google video results)', 'mlab-seo' ),
		),
	);
}
