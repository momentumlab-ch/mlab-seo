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
 * The key is shared with all MomentumLab plugins on the site. The licence server is asked only
 * in the admin or in cron; is_pro() never makes a request.
 */
final class License {

	/**
	 * Unlock slug of the Pro features on the licence server.
	 */
	const PRODUCT = 'mlab-seo-pro';

	/**
	 * Cron hook of the former own licence check (removed in 1.9.0, cleaned up on upgrade).
	 */
	const CRON = 'mlab_seo_license_check';

	/**
	 * Option of the former own licence data (removed in 1.9.0, cleaned up on upgrade).
	 */
	const LEGACY_OPTION = 'mlab_seo_license';

	/**
	 * Registers hooks.
	 */
	public function hooks() {
		// The library adopts the old key on admin_init (priority 10); clean up afterwards.
		add_action( 'admin_init', array( self::class, 'cleanup_legacy' ), 20 );
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
		return (bool) apply_filters( 'mlab_seo_is_pro', function_exists( 'mlab_license' ) && mlab_license()->unlocked( self::PRODUCT ) );
	}

	/**
	 * Removes the old cron and option once the shared library holds the key.
	 */
	public static function cleanup_legacy() {
		if ( ! function_exists( 'mlab_license' ) || '' === mlab_license()->key() ) {
			return;
		}
		wp_clear_scheduled_hook( self::CRON );
		if ( false !== get_option( self::LEGACY_OPTION, false ) ) {
			delete_option( self::LEGACY_OPTION );
		}
	}
}
