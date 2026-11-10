<?php
/**
 * Pro licence: activation, periodic check and the feature gate.
 *
 * @package MomentumLab\SEO
 */

namespace MomentumLab\SEO;

defined( 'ABSPATH' ) || exit;

/**
 * Validates the licence key against the MomentumLab licence server (mlab-license-server).
 *
 * GET {endpoint} with the key in the X-MLAB-License header and the website in X-MLAB-Site.
 * The server answers 200 with { "products": [ { "slug": "mlab-seo", "unlocked": true|false }, … ] },
 * 401 for an unknown key, 403 for an inactive licence or a full site limit,
 * each error as { "code": "…", "message": "…" }.
 */
final class License {

	/**
	 * Option holding key and status.
	 */
	const OPTION = 'mlab_seo_license';

	/**
	 * Daily re-check hook.
	 */
	const CRON = 'mlab_seo_license_check';

	/**
	 * A failed check (server down) keeps Pro active this long.
	 */
	const GRACE = 7 * DAY_IN_SECONDS;

	/**
	 * Product slug on the licence server.
	 */
	const PRODUCT = 'mlab-seo';

	/**
	 * Registers hooks.
	 */
	public function hooks() {
		add_action( self::CRON, array( self::class, 'check' ) );
	}

	/**
	 * Stored licence data.
	 *
	 * @return array{key:string,status:string,checked:int,last_ok:int,message:string}
	 */
	public static function data() {
		$data = get_option( self::OPTION, array() );
		return array_merge(
			array(
				'key'     => '',
				'status'  => 'none',
				'checked' => 0,
				'last_ok' => 0,
				'message' => '',
			),
			is_array( $data ) ? $data : array()
		);
	}

	/**
	 * Whether Pro features are unlocked.
	 *
	 * @return bool
	 */
	public static function is_pro() {
		$data   = self::data();
		$active = 'valid' === $data['status']
			|| ( 'unreachable' === $data['status'] && $data['last_ok'] > time() - self::GRACE );

		/**
		 * Filters whether Pro features are unlocked.
		 *
		 * MomentumLab uses this for sites it hosts under an agency agreement.
		 *
		 * @param bool $active Licence state.
		 */
		return (bool) apply_filters( 'mlab_seo_is_pro', $active );
	}

	/**
	 * Licence server endpoint.
	 *
	 * @return string
	 */
	private static function endpoint() {
		/**
		 * Filters the licence server endpoint.
		 *
		 * @param string $url Endpoint URL.
		 */
		return (string) apply_filters( 'mlab_seo_license_endpoint', 'https://momentumlab.ch/wp-json/mlab-ls/v1/catalog' );
	}

	/**
	 * Masked key for display.
	 *
	 * @return string
	 */
	public static function masked_key() {
		$key = self::data()['key'];
		return strlen( $key ) > 8 ? str_repeat( '•', strlen( $key ) - 4 ) . substr( $key, -4 ) : $key;
	}

	/**
	 * Stores a new key and validates it right away.
	 *
	 * @param string $key Licence key.
	 * @return array Licence data after the check.
	 */
	public static function activate( $key ) {
		$key = preg_replace( '/[^A-Za-z0-9\-]/', '', (string) $key );
		update_option( self::OPTION, array( 'key' => $key ), false );
		if ( '' === $key ) {
			return self::data();
		}
		if ( ! wp_next_scheduled( self::CRON ) ) {
			wp_schedule_event( time() + DAY_IN_SECONDS, 'daily', self::CRON );
		}
		return self::check();
	}

	/**
	 * Removes the key.
	 */
	public static function deactivate() {
		delete_option( self::OPTION );
		wp_clear_scheduled_hook( self::CRON );
	}

	/**
	 * Asks the licence server about the stored key.
	 *
	 * @return array Licence data after the check.
	 */
	public static function check() {
		$data = self::data();
		if ( '' === $data['key'] ) {
			return $data;
		}

		$response = wp_remote_get(
			self::endpoint(),
			array(
				'timeout' => 10,
				'headers' => array(
					'X-MLAB-License' => $data['key'],
					'X-MLAB-Site'    => home_url( '/' ),
				),
			)
		);

		$data['checked'] = time();
		$code            = (int) wp_remote_retrieve_response_code( $response );
		$body            = is_wp_error( $response ) ? null : json_decode( wp_remote_retrieve_body( $response ), true );

		$data = self::apply_response( $data, $code, $body );

		update_option( self::OPTION, $data, false );
		return $data;
	}

	/**
	 * Merges a licence server answer into the stored licence data.
	 *
	 * @param array $data Licence data before the answer.
	 * @param int   $code HTTP status code of the answer.
	 * @param mixed $body Decoded JSON body, or null when the request failed.
	 * @return array Updated licence data.
	 */
	private static function apply_response( $data, $code, $body ) {
		$message = is_array( $body ) && isset( $body['message'] ) ? sanitize_text_field( (string) $body['message'] ) : '';
		$message = '' !== $message ? $message : null;

		if ( 401 === $code || 403 === $code ) {
			return self::with_status( $data, 'invalid', $message ?? __( 'This licence key is not valid.', 'mlab-seo' ) );
		}
		if ( 200 !== $code || ! is_array( $body ) || ! isset( $body['products'] ) || ! is_array( $body['products'] ) ) {
			// A known-good key survives a server outage as "unreachable" instead of turning into an error.
			$status = 'valid' === $data['status'] || 'unreachable' === $data['status'] ? 'unreachable' : 'error';
			return self::with_status( $data, $status, $message ?? __( 'The licence server could not be reached.', 'mlab-seo' ) );
		}
		if ( ! self::unlocks_product( $body['products'] ) ) {
			return self::with_status( $data, 'invalid', __( 'This licence does not include MomentumLab SEO Pro.', 'mlab-seo' ) );
		}
		$data['status']  = 'valid';
		$data['message'] = '';
		$data['last_ok'] = time();
		return $data;
	}

	/**
	 * Whether the catalog marks this plugin as unlocked.
	 *
	 * @param array $products Catalog entries.
	 * @return bool
	 */
	private static function unlocks_product( array $products ) {
		foreach ( $products as $product ) {
			if ( is_array( $product ) && self::PRODUCT === ( $product['slug'] ?? '' ) ) {
				return ! empty( $product['unlocked'] );
			}
		}
		return false;
	}

	/**
	 * Sets status and message on the licence data.
	 *
	 * @param array  $data    Licence data.
	 * @param string $status  New status.
	 * @param string $message Message for the admin.
	 * @return array
	 */
	private static function with_status( $data, $status, $message ) {
		$data['status']  = $status;
		$data['message'] = $message;
		return $data;
	}
}
