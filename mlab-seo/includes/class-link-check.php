<?php
/**
 * Broken links: checks the links of all indexable pages once a week, in the background (Pro).
 *
 * @package MomentumLab\SEO
 */

namespace MomentumLab\SEO;

defined( 'ABSPATH' ) || exit;

/**
 * Collects the links of published pages and menus, checks them in small batches over WP-Cron
 * and keeps a list of the broken ones with the pages they are on.
 */
final class Link_Check {

	/**
	 * Option with queue and results (not autoloaded).
	 */
	const OPTION = 'mlab_seo_link_check';

	/**
	 * Weekly start.
	 */
	const CRON = 'mlab_seo_link_check_week';

	/**
	 * One batch.
	 */
	const BATCH_HOOK = 'mlab_seo_link_check_batch';

	/**
	 * Links per batch: short runs, so WP-Cron never slows down a visitor's request for long.
	 */
	const BATCH = 15;

	/**
	 * At most this many links per run.
	 */
	const MAX_LINKS = 1500;

	/**
	 * Registers hooks.
	 */
	public function hooks() {
		add_action( self::CRON, array( self::class, 'start' ) );
		add_action( self::BATCH_HOOK, array( self::class, 'batch' ) );
		if ( is_admin() || wp_doing_cron() ) {
			add_action( 'init', array( self::class, 'schedule' ) );
		}
	}

	/**
	 * Plans the weekly run while Pro is active.
	 */
	public static function schedule() {
		$next = wp_next_scheduled( self::CRON );
		if ( License::is_pro() && ! $next ) {
			wp_schedule_event( time() + HOUR_IN_SECONDS, 'weekly', self::CRON );
		} elseif ( ! License::is_pro() && $next ) {
			self::unschedule();
		}
	}

	/**
	 * Removes all planned runs.
	 */
	public static function unschedule() {
		wp_clear_scheduled_hook( self::CRON );
		wp_unschedule_hook( self::BATCH_HOOK );
	}

	/**
	 * Stored state.
	 *
	 * @return array{started:int,checked:int,total:int,queue:array<string,list<int>>,broken:array<string,array{code:string,pages:list<int>}>}
	 */
	public static function state() {
		$s = get_option( self::OPTION, array() );
		$s = is_array( $s ) ? $s : array();
		return array(
			'started' => (int) ( $s['started'] ?? 0 ),
			'checked' => (int) ( $s['checked'] ?? 0 ),
			'total'   => (int) ( $s['total'] ?? 0 ),
			'queue'   => (array) ( $s['queue'] ?? array() ),
			'broken'  => (array) ( $s['broken'] ?? array() ),
		);
	}

	/**
	 * Is a run in progress?
	 *
	 * @return bool
	 */
	public static function running() {
		return (bool) self::state()['queue'];
	}

	/**
	 * Broken links, most pages first.
	 *
	 * @return array<string,array{code:string,pages:list<int>}>
	 */
	public static function broken() {
		$broken = self::state()['broken'];
		uasort(
			$broken,
			function ( $a, $b ) {
				return count( $b['pages'] ) <=> count( $a['pages'] );
			}
		);
		return $broken;
	}

	/**
	 * Starts a run: collects all links and plans the first batch. Results of the last run stay visible meanwhile.
	 */
	public static function start() {
		if ( ! License::is_pro() ) {
			return;
		}
		$queue = self::collect();
		$state = self::state();
		update_option(
			self::OPTION,
			array(
				'started' => time(),
				'checked' => $state['checked'],
				'total'   => count( $queue ),
				'queue'   => $queue,
				'broken'  => $state['broken'],
				'next'    => array(),
			),
			false
		);
		wp_unschedule_hook( self::BATCH_HOOK );
		wp_schedule_single_event( time() + 10, self::BATCH_HOOK );
	}

	/**
	 * Checks one batch and plans the next one; the last batch replaces the list of broken links.
	 */
	public static function batch() {
		$s     = get_option( self::OPTION, array() );
		$s     = is_array( $s ) ? $s : array();
		$queue = (array) ( $s['queue'] ?? array() );
		$found = (array) ( $s['next'] ?? array() );
		if ( ! $queue ) {
			return;
		}
		foreach ( array_slice( $queue, 0, self::BATCH, true ) as $url => $pages ) {
			unset( $queue[ $url ] );
			$code = self::check( (string) $url );
			if ( '' !== $code ) {
				$found[ $url ] = array(
					'code'  => $code,
					'pages' => array_values( array_map( 'intval', (array) $pages ) ),
				);
			}
		}
		$s['queue'] = $queue;
		$s['next']  = $found;
		if ( ! $queue ) {
			$s['broken']  = $found;
			$s['next']    = array();
			$s['checked'] = time();
		}
		update_option( self::OPTION, $s, false );
		if ( $queue ) {
			wp_schedule_single_event( time() + MINUTE_IN_SECONDS, self::BATCH_HOOK );
		}
	}

	/**
	 * Links of indexable pages and of the menus, each with the pages it is on (0 = menu).
	 *
	 * @return array<string,list<int>>
	 */
	public static function collect() {
		global $post;
		$links = array();
		$posts = get_posts(
			array(
				'post_type'      => Sitemap::post_types() ? Sitemap::post_types() : array( 'page' ),
				'post_status'    => 'publish',
				'posts_per_page' => 500, // phpcs:ignore WordPress.WP.PostsPerPage.posts_per_page_posts_per_page -- Weekly background job.
				'has_password'   => false,
			)
		);
		$saved = $post;
		foreach ( $posts as $item ) {
			if ( Post_Meta::get( $item->ID, 'noindex' ) ) {
				continue;
			}
			$post = $item; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited -- Blocks that read the current post render correctly.
			setup_postdata( $item );
			// Rendered blocks: links stored in block attributes (buttons, teasers) only appear in the HTML.
			foreach ( self::links_in( do_blocks( $item->post_content ) ) as $url ) {
				$links[ $url ][] = $item->ID;
			}
		}
		$post = $saved; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited -- Restored.
		wp_reset_postdata();
		// Only menus that are shown somewhere: old, unassigned menus are not visible to visitors.
		foreach ( array_unique( array_filter( get_nav_menu_locations() ) ) as $menu ) {
			foreach ( (array) wp_get_nav_menu_items( $menu ) as $item ) {
				$url = self::clean( (string) ( $item->url ?? '' ) );
				if ( '' !== $url ) {
					$links[ $url ][] = 0;
				}
			}
		}
		$links = array_map(
			function ( $ids ) {
				return array_values( array_unique( $ids ) );
			},
			$links
		);
		return array_slice( $links, 0, self::MAX_LINKS, true );
	}

	/**
	 * Checkable links in a piece of HTML.
	 *
	 * @param string $html HTML.
	 * @return list<string>
	 */
	public static function links_in( $html ) {
		preg_match_all( '/<a\s[^>]*href=("|\')([^"\']+)\1/i', (string) $html, $match );
		$urls = array();
		foreach ( $match[2] as $href ) {
			$url = self::clean( html_entity_decode( $href, ENT_QUOTES, 'UTF-8' ) );
			if ( '' !== $url ) {
				$urls[] = $url;
			}
		}
		return array_values( array_unique( $urls ) );
	}

	/**
	 * Absolute http(s) address without fragment, or '' for links that cannot be checked (mail, phone, anchors, admin).
	 *
	 * @param string $href Link target.
	 * @return string
	 */
	public static function clean( $href ) {
		$href = trim( (string) strtok( trim( $href ), '#' ) );
		if ( '' === $href || preg_match( '#^(mailto|tel|sms|javascript|data|whatsapp):#i', $href ) ) {
			return '';
		}
		if ( str_starts_with( $href, '//' ) ) {
			$href = 'https:' . $href;
		} elseif ( str_starts_with( $href, '/' ) ) {
			$href = home_url( $href );
		}
		if ( ! preg_match( '#^https?://#i', $href ) || preg_match( '#/wp-(admin|login\.php)|[?&](replytocom|share)=#i', $href ) ) {
			return '';
		}
		return esc_url_raw( $href );
	}

	/**
	 * Checks one address. Returns '' when fine, else the HTTP status or "offline".
	 *
	 * Only clear errors count: 404, 410, and for the own website also server errors. Other sites often block
	 * automatic checks (403, 429), that is not counted as broken.
	 *
	 * @param string $url Address.
	 * @return string
	 */
	public static function check( $url ) {
		$own  = wp_parse_url( $url, PHP_URL_HOST ) === wp_parse_url( home_url(), PHP_URL_HOST );
		$args = array(
			'timeout'     => 8,
			'redirection' => 3,
			'user-agent'  => 'MomentumLab SEO link check; ' . home_url( '/' ),
		);
		$res  = wp_safe_remote_head( $url, $args );
		$code = is_wp_error( $res ) ? 0 : (int) wp_remote_retrieve_response_code( $res );
		if ( in_array( $code, array( 0, 403, 405, 501 ), true ) ) {
			// Some servers do not answer HEAD: try a short GET.
			$res  = wp_safe_remote_get( $url, $args + array( 'limit_response_size' => 4096 ) );
			$code = is_wp_error( $res ) ? 0 : (int) wp_remote_retrieve_response_code( $res );
		}
		if ( 0 === $code ) {
			return 'offline';
		}
		if ( in_array( $code, array( 404, 410 ), true ) || ( $own && $code >= 500 ) ) {
			return (string) $code;
		}
		return '';
	}
}
