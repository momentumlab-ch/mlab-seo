<?php
/**
 * XML sitemap (WordPress core) tuning.
 *
 * @package MomentumLab\SEO
 */

namespace MomentumLab\SEO;

defined( 'ABSPATH' ) || exit;

/**
 * Limits the core sitemap to useful content.
 */
final class Sitemap {

	/**
	 * Common sitemap addresses of other plugins and guesses, sent to the core index.
	 */
	const ALIASES = array( '/sitemap.xml', '/sitemaps.xml', '/sitemap_index.xml', '/sitemap-index.xml' );

	/**
	 * Registers hooks.
	 */
	public function hooks() {
		add_action( 'parse_request', array( $this, 'redirect_alias' ), 0 );
		add_filter( 'wp_sitemaps_add_provider', array( $this, 'providers' ), 10, 2 );
		add_filter( 'wp_sitemaps_post_types', array( $this, 'filter_post_types' ) );
		add_filter( 'wp_sitemaps_posts_query_args', array( $this, 'exclude_noindex' ) );
		add_filter( 'wp_sitemaps_posts_entry', array( $this, 'entry' ), 10, 2 );
		add_filter( 'wp_sitemaps_taxonomies_query_args', array( $this, 'exclude_noindex' ) );
		// No XSL stylesheet: Chrome is removing XSLT and warns on pages that use it.
		add_filter( 'wp_sitemaps_stylesheet_url', '__return_false' );
		add_filter( 'wp_sitemaps_stylesheet_index_url', '__return_false' );
	}

	/**
	 * Redirects common sitemap addresses to wp-sitemap.xml before WordPress
	 * runs the main query, so they do not end in a slow 404 page that
	 * crawlers such as Bing give up on.
	 */
	public function redirect_alias() {
		if ( ! in_array( Redirects::request_path(), self::ALIASES, true ) || ! wp_sitemaps_get_server()->sitemaps_enabled() ) {
			return;
		}
		wp_safe_redirect( home_url( '/wp-sitemap.xml' ), 301, 'MomentumLab SEO' );
		exit;
	}

	/**
	 * Post types in the sitemap.
	 *
	 * @return string[]
	 */
	public static function post_types() {
		return (array) Settings::get( 'sitemap_types' );
	}

	/**
	 * Drops the users sitemap (exposes login names) and taxonomies unless enabled.
	 *
	 * @param \WP_Sitemaps_Provider|false $provider Provider.
	 * @param string                      $name     Provider name.
	 * @return \WP_Sitemaps_Provider|false
	 */
	public function providers( $provider, $name ) {
		if ( 'users' === $name && ! Settings::get( 'sitemap_users' ) ) {
			return false;
		}
		if ( 'taxonomies' === $name && ! Settings::get( 'sitemap_taxonomy' ) ) {
			return false;
		}
		return $provider;
	}

	/**
	 * Keeps only the chosen post types.
	 *
	 * @param array $types Post type objects keyed by name.
	 * @return array
	 */
	public function filter_post_types( $types ) {
		return array_intersect_key( $types, array_flip( self::post_types() ) );
	}

	/**
	 * Leaves out posts and terms marked noindex.
	 *
	 * @param array $args WP_Query or WP_Term_Query arguments.
	 * @return array
	 */
	public function exclude_noindex( $args ) {
		$args['meta_query']   = isset( $args['meta_query'] ) ? (array) $args['meta_query'] : array(); // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
		$args['meta_query'][] = array(
			'relation' => 'OR',
			array(
				'key'     => Post_Meta::PREFIX . 'noindex',
				'compare' => 'NOT EXISTS',
			),
			array(
				'key'     => Post_Meta::PREFIX . 'noindex',
				'value'   => '1',
				'compare' => '!=',
			),
		);
		return $args;
	}

	/**
	 * Adds the last modification date.
	 *
	 * @param array    $entry Sitemap entry.
	 * @param \WP_Post $post  Post.
	 * @return array
	 */
	public function entry( $entry, $post ) {
		$entry['lastmod'] = get_post_modified_time( 'c', true, $post );
		return $entry;
	}
}
