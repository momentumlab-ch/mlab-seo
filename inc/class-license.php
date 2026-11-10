<?php
/**
 * Pro licence: thin wrapper around the shared MomentumLab License Client (lib/mlab-license-client).
 *
 * @package MomentumLab\SEO
 */

namespace MomentumLab\SEO;

defined( 'ABSPATH' ) || exit;

/**
 * Pro features are unlocked by the "mlab-seo-pro" unlock on the MomentumLab licence server.
 *
 * The key is shared with all MomentumLab plugins on the site. With MomentumLab Core it is managed
 * on the central MomentumLab page; without Core it is entered here. The licence server is asked
 * only in the admin or in cron; is_pro() never makes a request.
 */
final class License {

	/**
	 * Option of the former, plugin-own licence (taken over once by the client).
	 */
	const OPTION = 'mlab_seo_license';

	/**
	 * Former daily re-check hook; the shared client checks twice a day instead.
	 */
	const CRON = 'mlab_seo_license_check';

	/**
	 * Unlock slug of the Pro features on the licence server.
	 */
	const PRODUCT = 'mlab-seo-pro';

	/**
	 * Slug used before the separate Pro unlock existed.
	 */
	const LEGACY_PRODUCT = 'mlab-seo';

	/**
	 * Removes the former check, now done by the shared client.
	 */
	public function hooks() {
		add_action( 'admin_init', array( self::class, 'drop_legacy_cron' ) );
	}

	/**
	 * Unschedules the former daily check.
	 */
	public static function drop_legacy_cron() {
		if ( wp_next_scheduled( self::CRON ) ) {
			wp_clear_scheduled_hook( self::CRON );
		}
	}

	/**
	 * Licence data for the admin screens.
	 *
	 * @return array{key:string,status:string,checked:int,last_ok:int,message:string}
	 */
	public static function data() {
		$client = mlab_license();
		$state  = $client->state();
		$status = $state['status'];
		if ( 'valid' === $status && ! self::unlocked() ) {
			$status = 'invalid';
		}
		return array(
			'key'     => $client->key(),
			'status'  => '' === $client->key() ? 'none' : $status,
			'checked' => max( $state['fetched_at'], $state['failed_at'] ),
			'last_ok' => $state['last_ok'],
			'message' => 'valid' === $status ? '' : self::message( $state ),
		);
	}

	/**
	 * Whether Pro features are unlocked. No request; safe in the frontend.
	 *
	 * @return bool
	 */
	public static function is_pro() {
		/**
		 * Filters whether Pro features are unlocked.
		 *
		 * MomentumLab uses this for sites it hosts under an agency agreement.
		 *
		 * @param bool $active Licence state.
		 */
		return (bool) apply_filters( 'mlab_seo_is_pro', self::unlocked() );
	}

	/**
	 * Masked key for display.
	 *
	 * @return string
	 */
	public static function masked_key() {
		return mlab_license()->masked_key();
	}

	/**
	 * Stores a new key and validates it right away.
	 *
	 * @param string $key Licence key.
	 * @return array Licence data after the check.
	 */
	public static function activate( $key ) {
		mlab_license()->save_key( (string) $key );
		return self::data();
	}

	/**
	 * Removes the key (for all MomentumLab plugins on this site).
	 */
	public static function deactivate() {
		mlab_license()->save_key( '' );
	}

	/**
	 * Asks the licence server now.
	 *
	 * @return array Licence data after the check.
	 */
	public static function check() {
		mlab_license()->refresh( true );
		return self::data();
	}

	/**
	 * Pro unlock. Until the server offers "mlab-seo-pro", a paid "mlab-seo" unlock still counts.
	 *
	 * @return bool
	 */
	private static function unlocked() {
		$client = mlab_license();
		if ( in_array( self::PRODUCT, array_column( $client->products(), 'slug' ), true ) ) {
			return $client->unlocked( self::PRODUCT );
		}
		return $client->unlocked( self::LEGACY_PRODUCT );
	}

	/**
	 * Message for a licence that is not active.
	 *
	 * @param array $state Client state.
	 * @return string
	 */
	private static function message( array $state ) {
		if ( '' !== $state['message'] ) {
			return $state['message'];
		}
		return __( 'This licence does not include MomentumLab SEO Pro.', 'mlab-seo' );
	}
}
