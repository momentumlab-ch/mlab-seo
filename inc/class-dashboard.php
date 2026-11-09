<?php
/**
 * Data of the overview screen: health checks with a direct action, page statistics, AI visibility.
 *
 * @package MomentumLab\SEO
 */

namespace MomentumLab\SEO;

defined( 'ABSPATH' ) || exit;

/**
 * Collects what the overview shows. Page statistics are one aggregated query, cached until content changes.
 */
final class Dashboard {

	/**
	 * Transient with the page statistics.
	 */
	const CACHE = 'mlab_seo_page_stats';

	/**
	 * Score below which a page counts as poor.
	 */
	const POOR = 50;

	/**
	 * Registers hooks.
	 */
	public function hooks() {
		add_action( 'save_post', array( self::class, 'flush_cache' ) );
		add_action( 'deleted_post', array( self::class, 'flush_cache' ) );
		add_action( 'update_option_' . Settings::OPTION, array( self::class, 'flush_cache' ) );
	}

	/**
	 * Clears the cached statistics.
	 */
	public static function flush_cache() {
		delete_transient( self::CACHE );
	}

	/**
	 * Statistics of the published pages with SEO fields.
	 *
	 * @return array{total:int,indexed:int,noindex:int,no_description:int,unchecked:int,poor:int,score:int}
	 */
	public static function stats() {
		$cached = get_transient( self::CACHE );
		if ( is_array( $cached ) ) {
			return $cached;
		}
		$stats = array_fill_keys( array( 'total', 'indexed', 'noindex', 'no_description', 'unchecked', 'poor', 'score' ), 0 );
		$sum   = 0;
		$rated = 0;
		foreach ( self::rows() as $row ) {
			$type            = Titles::post_type( $row->post_type );
			$hidden          = (int) $row->total;
			$stats['total'] += (int) $row->total;
			if ( ! $type['noindex'] ) {
				$hidden                   = (int) $row->noindex;
				$stats['no_description'] += '' === trim( (string) $type['description'] ) ? (int) $row->no_description : 0;
				$stats['unchecked']      += (int) $row->unchecked;
				$stats['poor']           += (int) $row->poor;
				$sum                     += (float) $row->score_sum;
				$rated                   += (int) $row->rated;
			}
			$stats['noindex'] += $hidden;
		}
		$stats['indexed'] = $stats['total'] - $stats['noindex'];
		$stats['score']   = $rated ? (int) round( $sum / $rated ) : 0;
		set_transient( self::CACHE, $stats, DAY_IN_SECONDS );
		return $stats;
	}

	/**
	 * One aggregated row per post type. Description, unchecked and poor count only indexable pages.
	 *
	 * @return object[]
	 */
	private static function rows() {
		global $wpdb;
		$types = Post_Meta::post_types();
		if ( ! $types ) {
			return array();
		}
		$in   = implode( ',', array_fill( 0, count( $types ), '%s' ) );
		$sql  = "SELECT p.post_type,
				COUNT(*) AS total,
				SUM( n.meta_value = '1' ) AS noindex,
				SUM( COALESCE( n.meta_value, '' ) <> '1' AND COALESCE( d.meta_value, '' ) = '' ) AS no_description,
				SUM( COALESCE( n.meta_value, '' ) <> '1' AND s.meta_value IS NULL ) AS unchecked,
				SUM( COALESCE( n.meta_value, '' ) <> '1' AND s.meta_value + 0 < %d ) AS poor,
				SUM( IF( COALESCE( n.meta_value, '' ) <> '1', s.meta_value + 0, 0 ) ) AS score_sum,
				SUM( COALESCE( n.meta_value, '' ) <> '1' AND s.meta_value IS NOT NULL ) AS rated
			FROM {$wpdb->posts} p
			LEFT JOIN {$wpdb->postmeta} n ON n.post_id = p.ID AND n.meta_key = %s
			LEFT JOIN {$wpdb->postmeta} d ON d.post_id = p.ID AND d.meta_key = %s
			LEFT JOIN {$wpdb->postmeta} s ON s.post_id = p.ID AND s.meta_key = %s
			WHERE p.post_status = 'publish' AND p.post_password = '' AND p.post_type IN ( $in )
			GROUP BY p.post_type";
		$args = array_merge( array( self::POOR, Post_Meta::PREFIX . 'noindex', Post_Meta::PREFIX . 'description', Post_Meta::PREFIX . 'score' ), $types );
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.NotPrepared -- One aggregated query, cached in a transient; placeholders only.
		return (array) $wpdb->get_results( $wpdb->prepare( $sql, $args ) );
	}

	/**
	 * Health checks, problems first. Each item: state (good|ok|bad), text, optional action URL and label.
	 *
	 * @return array<int,array{state:string,text:string,url?:string,action?:string}>
	 */
	public static function checks() {
		$items = array_merge( self::site_checks(), self::content_checks(), self::setup_checks() );
		$check = self::access_check();
		if ( $check ) {
			$items[] = $check;
		}
		$order = array(
			'bad'  => 0,
			'ok'   => 1,
			'good' => 2,
		);
		usort(
			$items,
			function ( $a, $b ) use ( $order ) {
				return $order[ $a['state'] ] <=> $order[ $b['state'] ];
			}
		);
		return $items;
	}

	/**
	 * One check item.
	 *
	 * @param string $state  good, ok or bad.
	 * @param string $text   Message.
	 * @param string $url    Action URL.
	 * @param string $action Action label.
	 * @return array
	 */
	private static function item( $state, $text, $url = '', $action = '' ) {
		return array_filter(
			array(
				'state'  => $state,
				'text'   => $text,
				'url'    => $url,
				'action' => $action,
			)
		);
	}

	/**
	 * Checks of the WordPress setup.
	 *
	 * @return array
	 */
	private static function site_checks() {
		$items = array(
			get_option( 'blog_public' )
				? self::item( 'good', __( 'Search engines may index the website.', 'mlab-seo' ) )
				: self::item( 'bad', __( 'Search engines are discouraged from indexing the website.', 'mlab-seo' ), admin_url( 'options-reading.php' ), __( 'Change', 'mlab-seo' ) ),
			get_option( 'permalink_structure' )
				? self::item( 'good', __( 'Readable URLs (permalinks) are active.', 'mlab-seo' ) )
				: self::item( 'bad', __( 'Plain URLs like ?p=123 are in use. Choose a permalink structure.', 'mlab-seo' ), admin_url( 'options-permalink.php' ), __( 'Change', 'mlab-seo' ) ),
			'' === trim( (string) wp_parse_url( home_url( '/' ), PHP_URL_PATH ), '/' )
				? self::item( 'good', __( 'The website runs at the root of its domain, so robots.txt is served.', 'mlab-seo' ) )
				: self::item( 'ok', __( 'The website runs in a subfolder. Search engines only read robots.txt at the root of a domain, so the robots.txt rules take effect once the website moves to its own domain.', 'mlab-seo' ) ),
			str_starts_with( home_url( '/' ), 'https://' )
				? self::item( 'good', __( 'The website uses HTTPS.', 'mlab-seo' ) )
				: self::item( 'bad', __( 'The website does not use HTTPS. Ask your hosting provider for a certificate.', 'mlab-seo' ) ),
		);
		foreach ( Admin::other_seo_plugins() as $name ) {
			/* translators: %s: plugin name. */
			$items[] = self::item( 'bad', sprintf( __( '%s is active as well. Two SEO plugins print duplicate titles and tags. Take over its data, then deactivate it.', 'mlab-seo' ), $name ), Admin::url( 'mlab-seo-settings', 'import' ), __( 'Take over data', 'mlab-seo' ) );
		}
		return $items;
	}

	/**
	 * Checks of the pages: descriptions, scores, thin content, broken links, 404s.
	 *
	 * @return array
	 */
	private static function content_checks() {
		$stats = self::stats();
		$items = array();
		if ( $stats['no_description'] ) {
			/* translators: %d: number of pages. */
			$items[] = self::item( 'bad', sprintf( _n( '%d page without meta description. Google then picks any text snippet.', '%d pages without meta description. Google then picks any text snippet.', $stats['no_description'], 'mlab-seo' ), $stats['no_description'] ), Admin::pages_url( 'no_description' ), __( 'Add', 'mlab-seo' ) );
		}
		if ( $stats['poor'] ) {
			/* translators: %d: number of pages. */
			$items[] = self::item( 'ok', sprintf( _n( '%d page has a low SEO score.', '%d pages have a low SEO score.', $stats['poor'], 'mlab-seo' ), $stats['poor'] ), Admin::pages_url( 'poor' ), __( 'Improve', 'mlab-seo' ) );
		}
		if ( $stats['unchecked'] ) {
			/* translators: %d: number of pages. */
			$items[] = self::item( 'ok', sprintf( _n( '%d page has not been checked yet.', '%d pages have not been checked yet.', $stats['unchecked'], 'mlab-seo' ), $stats['unchecked'] ), Admin::pages_url( 'unchecked' ), __( 'Check', 'mlab-seo' ) );
		}
		$thin = Content_Audit::thin_count();
		if ( $thin ) {
			/* translators: 1: number of pages, 2: minimum number of words. */
			$items[] = self::item( 'ok', sprintf( _n( '%1$d page has fewer than %2$d words. Search engines and AI assistants find little to show for it.', '%1$d pages have fewer than %2$d words. Search engines and AI assistants find little to show for them.', $thin, 'mlab-seo' ), $thin, Content_Audit::THIN ), Admin::url( 'mlab-seo-pages', 'content' ), __( 'Show', 'mlab-seo' ) );
		}
		return array_merge( $items, self::pro_checks() );
	}

	/**
	 * Pro checks: broken links and missing pages.
	 *
	 * @return array
	 */
	private static function pro_checks() {
		if ( ! License::is_pro() ) {
			return array();
		}
		$items  = array();
		$broken = count( Link_Check::broken() );
		if ( $broken ) {
			/* translators: %d: number of links. */
			$items[] = self::item( 'ok', sprintf( _n( '%d link leads nowhere.', '%d links lead nowhere.', $broken, 'mlab-seo' ), $broken ), Admin::url( 'mlab-seo-pages', 'content' ), __( 'Show', 'mlab-seo' ) );
		}
		$missing = count( Redirects::log() );
		if ( $missing ) {
			/* translators: %d: number of addresses. */
			$items[] = self::item( 'ok', sprintf( _n( '%d address was not found (404).', '%d addresses were not found (404).', $missing, 'mlab-seo' ), $missing ), Admin::url( 'mlab-seo-redirects', 'redirects' ), __( 'Redirect', 'mlab-seo' ) );
		}
		return $items;
	}

	/**
	 * Checks of the own setup: organisation, home page description, Search Console.
	 *
	 * @return array
	 */
	private static function setup_checks() {
		return array(
			'' !== Settings::get( 'org_name' )
				? self::item( 'good', __( 'The organisation behind the website is set.', 'mlab-seo' ) )
				: self::item( 'ok', __( 'Name the organisation or person behind the website. Google and AI assistants use it.', 'mlab-seo' ), Admin::url( 'mlab-seo-search', 'publisher' ), __( 'Add', 'mlab-seo' ) ),
			'' !== Settings::get( 'home_description' ) || '' !== Post_Meta::get( (int) get_option( 'page_on_front' ), 'description' )
				? self::item( 'good', __( 'The home page has a description.', 'mlab-seo' ) )
				: self::item( 'ok', __( 'The home page has no description yet.', 'mlab-seo' ), Admin::url( 'mlab-seo-search', 'home' ), __( 'Add', 'mlab-seo' ) ),
			'' !== Settings::get( 'verify_google' )
				? self::item( 'good', __( 'Google Search Console is verified.', 'mlab-seo' ) )
				: self::item( 'ok', __( 'Google Search Console is not verified here. Not needed if you verified it another way.', 'mlab-seo' ), Admin::url( 'mlab-seo-settings', 'verify' ), __( 'Verify', 'mlab-seo' ) ),
		);
	}

	/**
	 * Result of the last crawler access check.
	 *
	 * @return array|null
	 */
	private static function access_check() {
		$check = get_transient( Admin::CHECK_TRANSIENT );
		$url   = Admin::url( 'mlab-seo-ai', 'crawlers' );
		if ( ! is_array( $check ) ) {
			return self::item( 'ok', __( 'Access for search engines and AI crawlers has not been checked yet.', 'mlab-seo' ), $url, __( 'Check', 'mlab-seo' ) );
		}
		$blocked = array_keys(
			array_filter(
				(array) $check[1],
				function ( $code ) {
					return 200 !== $code;
				}
			)
		);
		if ( ! $blocked ) {
			return self::item( 'good', __( 'Search engines and AI crawlers can reach the website.', 'mlab-seo' ) );
		}
		/* translators: %s: list of crawler names. */
		return self::item( 'bad', sprintf( __( 'The server blocks these crawlers: %s. This is usually a firewall or bot protection setting at the hosting provider (for example in Plesk), not WordPress.', 'mlab-seo' ), implode( ', ', $blocked ) ), $url, __( 'Check again', 'mlab-seo' ) );
	}

	/**
	 * AI visibility: allowed crawlers, llms.txt, last IndexNow submission.
	 *
	 * @return array{allowed:int,bots:int,llms:bool,indexnow:array|null}
	 */
	public static function ai() {
		$bots = (array) Settings::get( 'ai_bots' );
		$last = get_option( IndexNow::LAST );
		return array(
			'allowed'  => count( array_filter( $bots ) ),
			'bots'     => count( Settings::AI_BOTS ),
			'llms'     => (bool) Settings::get( 'llms_enabled' ),
			'indexnow' => Settings::get( 'indexnow' ) && is_array( $last ) ? $last : null,
		);
	}

	/**
	 * State of a score: good from 80, ok from the poor limit, else bad.
	 *
	 * @param int $score Score 0 to 100.
	 * @return string
	 */
	public static function state( $score ) {
		if ( $score >= 80 ) {
			return 'good';
		}
		return $score >= self::POOR ? 'ok' : 'bad';
	}

	/**
	 * Score badge, or "not checked" for pages without a score.
	 *
	 * @param int $score Score 0 to 100.
	 * @return string HTML.
	 */
	public static function score_badge( $score ) {
		if ( ! $score ) {
			return '<span class="mlab-seo-tag" data-score="">' . esc_html__( 'not checked', 'mlab-seo' ) . '</span>';
		}
		return '<span class="mlab-seo-tag is-' . self::state( $score ) . '" data-score="' . (int) $score . '">' . (int) $score . '</span>';
	}

	/**
	 * Recently changed pages with their score.
	 *
	 * @param int $count Number of pages.
	 * @return \WP_Post[]
	 */
	public static function recent( $count = 5 ) {
		return get_posts(
			array(
				'post_type'              => Post_Meta::post_types(),
				'post_status'            => array( 'publish', 'draft', 'future', 'pending', 'private' ),
				'orderby'                => 'modified',
				'posts_per_page'         => $count,
				'no_found_rows'          => true,
				'update_post_term_cache' => false,
			)
		);
	}
}
