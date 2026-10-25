<?php
/**
 * Import of redirects from Rank Math and the Redirection plugin.
 *
 * @package MomentumLab\SEO
 */

namespace MomentumLab\SEO;

defined( 'ABSPATH' ) || exit;

/**
 * Reads the redirect tables of the other plugins and adds their active rules.
 * The tables stay untouched, so the import can be repeated.
 */
final class Redirect_Import {

	/**
	 * Sources: label and table without prefix.
	 */
	const SOURCES = array(
		'rankmath'    => array(
			'label' => 'Rank Math',
			'table' => 'rank_math_redirections',
		),
		'redirection' => array(
			'label' => 'Redirection',
			'table' => 'redirection_items',
		),
	);

	/**
	 * Number of active redirects per source.
	 *
	 * @return array<string,int>
	 */
	public static function detect() {
		$counts = array();
		foreach ( array_keys( self::SOURCES ) as $source ) {
			$counts[ $source ] = count( self::rows( $source ) );
		}
		return $counts;
	}

	/**
	 * Adds the redirects of a source. Existing rules for the same address are replaced.
	 *
	 * @param string $source Source key.
	 * @return int Number of imported redirects.
	 */
	public static function run( $source ) {
		$rows = self::rows( $source );
		foreach ( $rows as $row ) {
			Redirects::import( $row );
		}
		return count( $rows );
	}

	/**
	 * Active rules of a source as from, to, code and regex.
	 *
	 * @param string $source Source key.
	 * @return array
	 */
	private static function rows( $source ) {
		$table = self::table( $source );
		if ( '' === $table ) {
			return array();
		}
		return 'rankmath' === $source ? self::rank_math_rows( $table ) : self::redirection_rows( $table );
	}

	/**
	 * Table name with prefix, when the table exists.
	 *
	 * @param string $source Source key.
	 * @return string
	 */
	private static function table( $source ) {
		global $wpdb;
		if ( ! isset( self::SOURCES[ $source ] ) ) {
			return '';
		}
		$table = $wpdb->prefix . self::SOURCES[ $source ]['table'];
		return $table === $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $wpdb->esc_like( $table ) ) ) ? $table : ''; // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- One-off check in the admin.
	}

	/**
	 * Rules from Rank Math: each redirect has one or more sources with a comparison.
	 *
	 * @param string $table Table name.
	 * @return array
	 */
	private static function rank_math_rows( $table ) {
		global $wpdb;
		$results = $wpdb->get_results( "SELECT sources, url_to, header_code FROM {$table} WHERE status = 'active'" ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Table name from $wpdb->prefix.
		$rows    = array();
		foreach ( (array) $results as $result ) {
			foreach ( (array) maybe_unserialize( $result->sources ) as $item ) {
				$from = self::rank_math_source( (array) $item );
				if ( $from ) {
					$rows[] = $from + array(
						'to'   => (string) $result->url_to,
						'code' => (int) $result->header_code,
					);
				}
			}
		}
		return $rows;
	}

	/**
	 * Source pattern of Rank Math as an exact path or a regular expression.
	 *
	 * @param array $item Pattern and comparison (exact, contains, start, end, regex).
	 * @return array|null
	 */
	private static function rank_math_source( array $item ) {
		$pattern = trim( (string) ( $item['pattern'] ?? '' ) );
		if ( '' === $pattern ) {
			return null;
		}
		$quoted = preg_quote( '/' . ltrim( $pattern, '/' ), '#' );
		$regex  = array(
			'regex'    => $pattern,
			'contains' => preg_quote( $pattern, '#' ),
			'start'    => '^' . $quoted,
			'end'      => preg_quote( $pattern, '#' ) . '$',
		);
		$type   = (string) ( $item['comparison'] ?? 'exact' );
		if ( isset( $regex[ $type ] ) ) {
			return array(
				'from'  => $regex[ $type ],
				'regex' => true,
			);
		}
		return array( 'from' => '/' . ltrim( $pattern, '/' ) );
	}

	/**
	 * Rules from the Redirection plugin (URL redirects only).
	 *
	 * @param string $table Table name.
	 * @return array
	 */
	private static function redirection_rows( $table ) {
		global $wpdb;
		$results = $wpdb->get_results( "SELECT url, regex, action_code, action_data FROM {$table} WHERE status = 'enabled' AND action_type = 'url' AND match_type = 'url'" ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Table name from $wpdb->prefix.
		$rows    = array();
		foreach ( (array) $results as $result ) {
			$rows[] = array(
				'from'  => (string) $result->url,
				'to'    => (string) $result->action_data,
				'code'  => (int) $result->action_code,
				'regex' => (bool) $result->regex,
			);
		}
		return $rows;
	}
}
