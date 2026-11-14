<?php
/**
 * Internal link suggestions for the SEO box (Pro): pages that fit the keyword and title, orphans first.
 *
 * @package MomentumLab\SEO
 */

namespace MomentumLab\SEO;

defined( 'ABSPATH' ) || exit;

/**
 * REST route and scoring of the suggestions.
 */
final class Link_Suggestions {

	/**
	 * Number of suggestions shown.
	 */
	const LIMIT = 8;

	/**
	 * Registers hooks.
	 */
	public function hooks() {
		add_action( 'rest_api_init', array( $this, 'routes' ) );
	}

	/**
	 * Registers the route.
	 */
	public function routes() {
		register_rest_route(
			Rest::NS,
			'/link-suggestions',
			array(
				'methods'             => \WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get' ),
				'permission_callback' => fn ( $request ) => License::is_pro() && current_user_can( 'edit_post', (int) $request['post_id'] ),
				'args'                => array(
					'post_id' => array(
						'type'     => 'integer',
						'required' => true,
					),
					'focus'   => array( 'type' => 'string' ),
					'title'   => array( 'type' => 'string' ),
				),
			)
		);
	}

	/**
	 * Suggestions for a post.
	 *
	 * @param \WP_REST_Request $request Request.
	 * @return array
	 */
	public function get( $request ) {
		return self::for_post( (int) $request['post_id'], (string) $request['focus'], (string) $request['title'] );
	}

	/**
	 * Pages worth linking to from a post.
	 *
	 * @param int    $post_id Post ID.
	 * @param string $focus   Focus keywords as typed.
	 * @param string $title   Title as typed.
	 * @return array<int,array{id:int,title:string,url:string,orphan:bool,cornerstone:bool}>
	 */
	public static function for_post( $post_id, $focus, $title ) {
		$main   = mb_strtolower( trim( explode( ',', $focus )[0] ) );
		$terms  = self::terms( $main . ' ' . $title );
		$linked = self::linked_ids( $post_id );
		$orphan = array_flip( Content_Audit::orphans() );
		$scored = array();
		if ( ! $terms ) {
			return array();
		}
		foreach ( self::candidates( $post_id ) as $post ) {
			if ( isset( $linked[ $post->ID ] ) || Post_Meta::get( $post->ID, 'noindex' ) || '' !== Post_Meta::get( $post->ID, 'canonical' ) ) {
				continue;
			}
			$score = self::score( $post, $main, $terms );
			if ( ! $score ) {
				continue;
			}
			$is_orphan  = isset( $orphan[ $post->ID ] );
			$cornerston = (bool) Post_Meta::get( $post->ID, 'cornerstone' );
			$score     += ( $is_orphan ? 4 : 0 ) + ( $cornerston ? 2 : 0 );

			$scored[ $post->ID ] = array(
				'score' => $score,
				'row'   => array(
					'id'          => $post->ID,
					'title'       => Context::plain_title( $post ),
					'url'         => (string) get_permalink( $post ),
					'orphan'      => $is_orphan,
					'cornerstone' => $cornerston,
				),
			);
		}
		uasort( $scored, fn ( $a, $b ) => $b['score'] <=> $a['score'] );
		return array_values( array_map( fn ( $item ) => $item['row'], array_slice( $scored, 0, self::LIMIT, true ) ) );
	}

	/**
	 * Words of a text that are long enough to match on, without duplicates.
	 *
	 * @param string $text Text.
	 * @return string[]
	 */
	private static function terms( $text ) {
		$words = preg_split( '/[^\p{L}\p{N}]+/u', mb_strtolower( $text ), -1, PREG_SPLIT_NO_EMPTY );
		return array_slice( array_values( array_unique( array_filter( (array) $words, fn ( $word ) => mb_strlen( $word ) >= 4 ) ) ), 0, 8 );
	}

	/**
	 * Published pages that could be linked to.
	 *
	 * @param int $post_id Post ID of the page being edited.
	 * @return \WP_Post[]
	 */
	private static function candidates( $post_id ) {
		return get_posts(
			array(
				'post_type'      => Sitemap::post_types() ? Sitemap::post_types() : array( 'page' ),
				'post_status'    => 'publish',
				'has_password'   => false,
				'post__not_in'   => array( $post_id ),
				'posts_per_page' => 500, // phpcs:ignore WordPress.WP.PostsPerPage.posts_per_page_posts_per_page -- Suggestions are loaded on request only.
			)
		);
	}

	/**
	 * Match score of a page: keyword in the title counts most, words in title and address add up.
	 *
	 * @param \WP_Post $post  Page.
	 * @param string   $main  Main keyword.
	 * @param string[] $terms Words to match.
	 * @return int
	 */
	private static function score( $post, $main, array $terms ) {
		$title = mb_strtolower( Context::plain_title( $post ) );
		$slug  = mb_strtolower( $post->post_name );
		$score = '' !== $main && str_contains( $title, $main ) ? 5 : 0;
		foreach ( $terms as $term ) {
			$score += ( str_contains( $title, $term ) ? 3 : 0 ) + ( str_contains( $slug, $term ) ? 1 : 0 );
		}
		return $score;
	}

	/**
	 * Pages the post links to already.
	 *
	 * @param int $post_id Post ID.
	 * @return array<int,bool> Post IDs as keys.
	 */
	private static function linked_ids( $post_id ) {
		$map = array();
		foreach ( self::candidates( $post_id ) as $post ) {
			$map[ Content_Audit::path( (string) get_permalink( $post ) ) ] = $post->ID;
		}
		preg_match_all( '/href=["\']([^"\']+)["\']/i', (string) get_post_field( 'post_content', $post_id ), $found );
		$linked = array();
		foreach ( $found[1] as $href ) {
			if ( isset( $map[ Content_Audit::path( $href ) ] ) ) {
				$linked[ $map[ Content_Audit::path( $href ) ] ] = true;
			}
		}
		return $linked;
	}
}
