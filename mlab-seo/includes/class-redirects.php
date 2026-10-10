<?php
/**
 * Redirect manager and 404 log (Pro).
 *
 * @package MomentumLab\SEO
 */

namespace MomentumLab\SEO;

defined( 'ABSPATH' ) || exit;

/**
 * 301/302/410 rules for old URLs, plus a log of pages not found.
 */
final class Redirects {

	/**
	 * Option with the rules.
	 */
	const OPTION = 'mlab_seo_redirects';

	/**
	 * Option with the 404 log.
	 */
	const LOG = 'mlab_seo_404_log';

	/**
	 * Maximum number of logged 404 paths.
	 */
	const LOG_SIZE = 100;

	/**
	 * Registers hooks.
	 */
	public function hooks() {
		add_action( 'template_redirect', array( $this, 'maybe_redirect' ), 1 );
		add_action( 'template_redirect', array( $this, 'log_404' ), 99 );
	}

	/**
	 * Path of the current request relative to the site, with leading slash.
	 *
	 * @return string
	 */
	public static function request_path() {
		$uri  = isset( $_SERVER['REQUEST_URI'] ) ? esc_url_raw( wp_unslash( $_SERVER['REQUEST_URI'] ) ) : '';
		$path = (string) wp_parse_url( $uri, PHP_URL_PATH );
		$base = rtrim( (string) wp_parse_url( home_url( '/' ), PHP_URL_PATH ), '/' );
		if ( '' !== $base && str_starts_with( $path, $base ) ) {
			$path = substr( $path, strlen( $base ) );
		}
		return self::normalize( $path );
	}

	/**
	 * Normalises a path: leading slash, no trailing slash, lowercase, decoded.
	 *
	 * @param string $path Path or full URL.
	 * @return string
	 */
	public static function normalize( $path ) {
		$path = (string) wp_parse_url( trim( (string) $path ), PHP_URL_PATH );
		$path = '/' . trim( rawurldecode( $path ), '/' );
		return mb_strtolower( $path );
	}

	/**
	 * Stored rules.
	 *
	 * @return array<int,array{from:string,to:string,code:int,hits:int}>
	 */
	public static function rules() {
		$rules = get_option( self::OPTION, array() );
		return is_array( $rules ) ? $rules : array();
	}

	/**
	 * Sanitises and saves rules from the settings form.
	 *
	 * @param array $rows Raw rows with from, to, code.
	 */
	public static function save( array $rows ) {
		$old   = array();
		$rules = array();
		foreach ( self::rules() as $rule ) {
			$old[ $rule['from'] ] = (int) $rule['hits'];
		}
		foreach ( $rows as $row ) {
			$from = self::normalize( sanitize_text_field( wp_unslash( $row['from'] ?? '' ) ) );
			$code = (int) ( $row['code'] ?? 301 );
			$code = in_array( $code, array( 301, 302, 307, 410 ), true ) ? $code : 301;
			$to   = trim( sanitize_text_field( wp_unslash( $row['to'] ?? '' ) ) );
			$to   = str_starts_with( $to, '/' ) ? $to : esc_url_raw( $to, array( 'https', 'http' ) );
			if ( '/' === $from || ( '' === $to && 410 !== $code ) ) {
				continue;
			}
			$rules[] = array(
				'from' => $from,
				'to'   => $to,
				'code' => $code,
				'hits' => $old[ $from ] ?? 0,
			);
		}
		update_option( self::OPTION, $rules, true );
	}

	/**
	 * Redirects when a rule matches the request.
	 */
	public function maybe_redirect() {
		if ( ! License::is_pro() || is_admin() ) {
			return;
		}
		$rules = self::rules();
		if ( ! $rules ) {
			return;
		}
		$path = self::request_path();
		foreach ( $rules as $i => $rule ) {
			if ( $rule['from'] !== $path ) {
				continue;
			}
			++$rules[ $i ]['hits'];
			update_option( self::OPTION, $rules, true );
			if ( 410 === (int) $rule['code'] ) {
				status_header( 410 );
				nocache_headers();
				exit;
			}
			$target = str_starts_with( $rule['to'], '/' ) ? home_url( $rule['to'] ) : $rule['to'];
			wp_redirect( $target, (int) $rule['code'], 'MomentumLab SEO' ); // phpcs:ignore WordPress.Security.SafeRedirect.wp_redirect_wp_redirect -- Targets are set by administrators and may be external.
			exit;
		}
	}

	/**
	 * Records requests that end in a 404.
	 */
	public function log_404() {
		if ( ! is_404() || ! License::is_pro() ) {
			return;
		}
		$path = self::request_path();
		if ( preg_match( '#\.(php|env|git|sql|bak|zip)$|^/wp-(admin|includes|content)/#i', $path ) ) {
			return; // Bot probes, not real visitors.
		}
		$log = get_option( self::LOG, array() );
		$log = is_array( $log ) ? $log : array();
		if ( isset( $log[ $path ] ) ) {
			++$log[ $path ]['count'];
			$log[ $path ]['last'] = time();
		} else {
			$log[ $path ] = array(
				'count' => 1,
				'last'  => time(),
			);
		}
		if ( count( $log ) > self::LOG_SIZE ) {
			uasort(
				$log,
				function ( $a, $b ) {
					return $b['last'] <=> $a['last'];
				}
			);
			$log = array_slice( $log, 0, self::LOG_SIZE, true );
		}
		update_option( self::LOG, $log, false );
	}

	/**
	 * 404 log, most frequent first.
	 *
	 * @return array<string,array{count:int,last:int}>
	 */
	public static function log() {
		$log = get_option( self::LOG, array() );
		$log = is_array( $log ) ? $log : array();
		uasort(
			$log,
			function ( $a, $b ) {
				return $b['count'] <=> $a['count'];
			}
		);
		return $log;
	}
}
