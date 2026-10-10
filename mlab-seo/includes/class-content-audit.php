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
