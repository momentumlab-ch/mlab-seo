<?php
/**
 * Content check: finds indexable pages with too little text (thin content).
 *
 * @package MomentumLab\SEO
 */

namespace MomentumLab\SEO;

defined( 'ABSPATH' ) || exit;

/**
 * Counts the words of every indexable page and lists the short ones.
 */
final class Content_Audit {

	/**
	 * Below this many words a page counts as thin.
	 */
	const THIN = 100;

	/**
	 * Below this many words a page counts as short.
	 */
	const SHORT = 300;

	/**
	 * Transient with the word counts.
	 */
	const CACHE = 'mlab_seo_content_audit';

	/**
	 * Transient with the pages that no other page links to.
	 */
	const ORPHAN_CACHE = 'mlab_seo_orphans';

	/**
	 * Registers hooks.
	 */
	public function hooks() {
		add_action( 'save_post', array( self::class, 'flush_cache' ) );
		add_action( 'deleted_post', array( self::class, 'flush_cache' ) );
	}

	/**
	 * Clears the cached counts.
	 */
	public static function flush_cache() {
		delete_transient( self::CACHE );
		delete_transient( self::ORPHAN_CACHE );
	}

	/**
	 * Words in a post's text, including FAQ answers.
	 *
	 * @param \WP_Post $post Post.
	 * @return int
	 */
	public static function words( $post ) {
		$text = excerpt_remove_blocks( strip_shortcodes( $post->post_content ) );
		foreach ( FAQ::for_post( $post->ID ) as $item ) {
			$text .= ' ' . $item['question'] . ' ' . $item['answer'];
		}
		$text = html_entity_decode( wp_strip_all_tags( $text ), ENT_QUOTES | ENT_HTML5, 'UTF-8' );
		return count( preg_split( '/[\s\p{Z}]+/u', trim( $text ), -1, PREG_SPLIT_NO_EMPTY ) );
	}

	/**
	 * Indexable pages with fewer than SHORT words, shortest first.
	 *
	 * @return array<int,array{id:int,words:int,images:int}>
	 */
	public static function results() {
		$cached = get_transient( self::CACHE );
		if ( is_array( $cached ) ) {
			return $cached;
		}
		$results = self::short_pages();
		usort(
			$results,
			function ( $a, $b ) {
				return $a['words'] <=> $b['words'];
			}
		);
		set_transient( self::CACHE, $results, DAY_IN_SECONDS );
		return $results;
	}

	/**
	 * Scans published, indexable posts and keeps those under SHORT words.
	 *
	 * @return array<int,array{id:int,words:int,images:int}> Unsorted rows.
	 */
	private static function short_pages() {
		$posts   = get_posts(
			array(
				'post_type'      => Sitemap::post_types() ? Sitemap::post_types() : array( 'page' ),
				'post_status'    => 'publish',
				'posts_per_page' => 1000, // phpcs:ignore WordPress.WP.PostsPerPage.posts_per_page_posts_per_page -- Admin report, cached for a day.
				'has_password'   => false,
			)
		);
		$results = array();
		foreach ( $posts as $post ) {
			if ( Post_Meta::get( $post->ID, 'noindex' ) || '' !== Post_Meta::get( $post->ID, 'canonical' ) ) {
				continue;
			}
			$words = self::words( $post );
			if ( $words < self::SHORT ) {
				$results[] = array(
					'id'     => $post->ID,
					'words'  => $words,
					'images' => preg_match_all( '/<img\s/i', $post->post_content ),
				);
			}
		}
		return $results;
	}

	/**
	 * Indexable pages without a link from another page or from a menu, cornerstone content first.
	 *
	 * @return int[] Post IDs.
	 */
	public static function orphans() {
		$cached = get_transient( self::ORPHAN_CACHE );
		if ( is_array( $cached ) ) {
			return $cached;
		}
		$posts = get_posts(
			array(
				'post_type'      => Sitemap::post_types() ? Sitemap::post_types() : array( 'page' ),
				'post_status'    => 'publish',
				'posts_per_page' => 1000, // phpcs:ignore WordPress.WP.PostsPerPage.posts_per_page_posts_per_page -- Admin report, cached for a day.
				'has_password'   => false,
			)
		);
		$map   = array();
		foreach ( $posts as $post ) {
			$map[ self::path( (string) get_permalink( $post ) ) ] = $post->ID;
		}
		$linked = self::menu_targets();
		foreach ( $posts as $post ) {
			preg_match_all( '/href=["\']([^"\']+)["\']/i', $post->post_content, $found );
			foreach ( $found[1] as $href ) {
				$target = isset( $href[0] ) && '#' !== $href[0] ? ( $map[ self::path( $href ) ] ?? 0 ) : 0;
				if ( $target && $target !== $post->ID ) {
					$linked[ $target ] = true;
				}
			}
		}
		$front   = (int) get_option( 'page_on_front' );
		$orphans = array();
		foreach ( $posts as $post ) {
			if ( isset( $linked[ $post->ID ] ) || $post->ID === $front || Post_Meta::get( $post->ID, 'noindex' ) || '' !== Post_Meta::get( $post->ID, 'canonical' ) ) {
				continue;
			}
			$orphans[ $post->ID ] = Post_Meta::get( $post->ID, 'cornerstone' ) ? 0 : 1;
		}
		asort( $orphans );
		$orphans = array_keys( $orphans );
		set_transient( self::ORPHAN_CACHE, $orphans, DAY_IN_SECONDS );
		return $orphans;
	}

	/**
	 * Posts that a menu links to.
	 *
	 * @return array<int,bool> Post IDs as keys.
	 */
	private static function menu_targets() {
		$targets = array();
		foreach ( wp_get_nav_menus() as $menu ) {
			foreach ( (array) wp_get_nav_menu_items( $menu ) as $item ) {
				if ( 'post_type' === $item->type ) {
					$targets[ (int) $item->object_id ] = true;
				}
			}
		}
		return $targets;
	}

	/**
	 * Path of an address on this website, lower case and without trailing slash, or an empty string for other websites.
	 *
	 * @param string $url Address.
	 * @return string
	 */
	public static function path( $url ) {
		$parts = wp_parse_url( trim( $url ) );
		$host  = wp_parse_url( home_url(), PHP_URL_HOST );
		if ( ! is_array( $parts ) || isset( $parts['scheme'] ) && ! in_array( $parts['scheme'], array( 'http', 'https' ), true ) || ( isset( $parts['host'] ) && strtolower( $parts['host'] ) !== strtolower( (string) $host ) ) ) {
			return '';
		}
		$path = strtolower( untrailingslashit( rawurldecode( $parts['path'] ?? '' ) ) );
		return '' !== $path ? $path : '/';
	}

	/**
	 * Number of thin pages.
	 *
	 * @return int
	 */
	public static function thin_count() {
		return count(
			array_filter(
				self::results(),
				function ( $row ) {
					return $row['words'] < self::THIN;
				}
			)
		);
	}
}
