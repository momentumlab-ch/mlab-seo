<?php
/**
 * Form handlers of the SEO screens (admin-post.php) and the notices after them.
 *
 * Every handler checks the capability and a nonce before it changes anything.
 *
 * @package MomentumLab\SEO
 */

namespace MomentumLab\SEO;

defined( 'ABSPATH' ) || exit;
// phpcs:disable WordPress.Security.NonceVerification.Missing -- Every admin-post handler calls verify() (capability + nonce) before reading the form.

/**
 * Handles settings, redirects, licence, access check, link check and imports.
 */
final class Admin_Actions {

	/**
	 * Registers hooks.
	 */
	public function hooks() {
		add_action( 'admin_post_mlab_seo_save', array( $this, 'handle_settings' ) );
		add_action( 'admin_post_mlab_seo_redirects', array( $this, 'handle_redirects' ) );
		add_action( 'admin_post_mlab_seo_license', array( $this, 'handle_license' ) );
		add_action( 'admin_post_mlab_seo_check', array( $this, 'handle_check' ) );
		add_action( 'admin_post_mlab_seo_import', array( $this, 'handle_import' ) );
		add_action( 'admin_post_mlab_seo_redirect_import', array( $this, 'handle_redirect_import' ) );
		add_action( 'admin_post_mlab_seo_link_check', array( $this, 'handle_link_check' ) );
		add_action( 'admin_post_mlab_seo_bulk', array( $this, 'handle_bulk' ) );
	}

	/**
	 * Keys of a segment that saves settings, or null when the segment does not exist.
	 *
	 * @param string $page    Menu slug.
	 * @param string $segment Segment key.
	 * @return string[]|null
	 */
	public static function segment_keys( $page, $segment ) {
		$pages = mlab_seo_admin_pages();
		$keys  = $pages[ $page ]['segments'][ $segment ]['keys'] ?? null;
		return is_array( $keys ) ? $keys : null;
	}

	/**
	 * Redirect back to a screen with a notice.
	 *
	 * @param string $page    Menu slug.
	 * @param string $segment Segment key.
	 * @param string $notice  Notice key.
	 * @param array  $args    Extra query arguments.
	 */
	private function back( $page, $segment, $notice, array $args = array() ) {
		wp_safe_redirect( Admin::url( $page, $segment, array_merge( array( 'mlab_seo_notice' => $notice ), $args ) ) );
		exit;
	}

	/**
	 * Capability and nonce check for form handlers.
	 *
	 * @param string $action Nonce action.
	 */
	private function verify( $action ) {
		if ( ! current_user_can( Admin::capability() ) ) {
			wp_die( esc_html__( 'You are not allowed to change these settings.', 'mlab-seo' ), 403 );
		}
		check_admin_referer( $action );
	}

	/**
	 * Saves the settings of one segment (fallback when JavaScript is off).
	 */
	public function handle_settings() {
		$this->verify( 'mlab_seo_save' );
		$page    = isset( $_POST['mlab_seo_page'] ) ? sanitize_key( wp_unslash( $_POST['mlab_seo_page'] ) ) : '';
		$segment = isset( $_POST['mlab_seo_segment'] ) ? sanitize_key( wp_unslash( $_POST['mlab_seo_segment'] ) ) : '';
		$keys    = self::segment_keys( $page, $segment );
		if ( null === $keys ) {
			wp_die( esc_html__( 'Unknown settings form.', 'mlab-seo' ), 400 );
		}
		$values = isset( $_POST['mlab_seo'] ) && is_array( $_POST['mlab_seo'] ) ? $_POST['mlab_seo'] : array(); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized, WordPress.Security.ValidatedSanitizedInput.MissingUnslash -- Sanitised and unslashed in Settings::sanitize().
		Settings::save_keys( $values, $keys );
		$this->back( $page, $segment, 'saved' );
	}

	/**
	 * Saves the redirect rules, or handles one click in the 404 list (Pro).
	 */
	public function handle_redirects() {
		$this->verify( 'mlab_seo_redirects' );
		if ( License::is_pro() ) {
			Settings::save_keys( array( 'auto_redirect' => empty( $_POST['mlab_seo_auto_redirect'] ) ? 0 : 1 ), array( 'auto_redirect' ) );
			$rows = isset( $_POST['mlab_seo_redirects'] ) && is_array( $_POST['mlab_seo_redirects'] ) ? $_POST['mlab_seo_redirects'] : array(); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized, WordPress.Security.ValidatedSanitizedInput.MissingUnslash -- Sanitised and unslashed in Redirects::save().
			Redirects::save( $rows );
			$this->handle_404_click();
		}
		if ( ! empty( $_POST['mlab_seo_clear_log'] ) ) {
			delete_option( Redirects::LOG );
		}
		$this->back( 'mlab-seo-redirects', '', 'saved' );
	}

	/**
	 * One click in the 404 list: redirect to the suggested (or edited) target, or stop logging the address.
	 * Nonce and capability are checked in handle_redirects().
	 */
	private function handle_404_click() {
		$fix = isset( $_POST['mlab_seo_404_fix'] ) ? sanitize_text_field( wp_unslash( $_POST['mlab_seo_404_fix'] ) ) : '';
		if ( '' !== $fix ) {
			$targets = isset( $_POST['mlab_seo_404_target'] ) && is_array( $_POST['mlab_seo_404_target'] ) ? wp_unslash( $_POST['mlab_seo_404_target'] ) : array(); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Sanitised in Redirects::fix().
			$target  = (string) ( $targets[ md5( $fix ) ] ?? '' );
			$this->back( 'mlab-seo-redirects', '', Redirects::fix( $fix, $target ) ? 'redirect_added' : 'no_target' );
		}
		$ignore = isset( $_POST['mlab_seo_404_ignore'] ) ? sanitize_text_field( wp_unslash( $_POST['mlab_seo_404_ignore'] ) ) : '';
		if ( '' !== $ignore ) {
			Redirects::ignore( $ignore );
			$this->back( 'mlab-seo-redirects', '', 'ignored' );
		}
	}

	/**
	 * Activates or removes the licence key.
	 */
	public function handle_license() {
		$this->verify( 'mlab_seo_license' );
		if ( ! empty( $_POST['mlab_seo_license_remove'] ) ) {
			License::deactivate();
			$this->back( 'mlab-seo-settings', 'license', 'license_removed' );
		}
		$key  = isset( $_POST['mlab_seo_license_key'] ) ? sanitize_text_field( wp_unslash( $_POST['mlab_seo_license_key'] ) ) : '';
		$data = License::activate( $key );
		$this->back( 'mlab-seo-settings', 'license', 'valid' === $data['status'] ? 'license_ok' : 'license_failed' );
	}

	/**
	 * Runs the crawler access check.
	 */
	public function handle_check() {
		$this->verify( 'mlab_seo_check' );
		set_transient( Admin::CHECK_TRANSIENT, array( time(), Crawlers::access_check() ), WEEK_IN_SECONDS );
		$this->back( 'mlab-seo-ai', 'crawlers', 'checked' );
	}

	/**
	 * Starts the broken link check now (Pro).
	 */
	public function handle_link_check() {
		$this->verify( 'mlab_seo_link_check' );
		if ( License::is_pro() && ! Link_Check::running() ) {
			Link_Check::start();
		}
		$this->back( 'mlab-seo-pages', 'content', 'links_started' );
	}

	/**
	 * Imports SEO data from another plugin.
	 */
	public function handle_import() {
		$this->verify( 'mlab_seo_import' );
		$source = isset( $_POST['mlab_seo_import_source'] ) ? sanitize_key( wp_unslash( $_POST['mlab_seo_import_source'] ) ) : '';
		$result = Import::run( $source, ! empty( $_POST['mlab_seo_import_overwrite'] ) );
		Dashboard::flush_cache();
		$this->back(
			'mlab-seo-settings',
			'import',
			'imported',
			array(
				'mlab_seo_posts' => $result['posts'],
				'mlab_seo_terms' => $result['terms'],
			)
		);
	}

	/**
	 * Imports redirects from another plugin.
	 */
	public function handle_redirect_import() {
		$this->verify( 'mlab_seo_redirect_import' );
		$source = isset( $_POST['mlab_seo_redirect_source'] ) ? sanitize_key( wp_unslash( $_POST['mlab_seo_redirect_source'] ) ) : '';
		$this->back( 'mlab-seo-settings', 'import', 'redirects_imported', array( 'mlab_seo_imported' => Redirect_Import::run( $source ) ) );
	}

	/**
	 * Bulk action of the page list: hide from or show in search engines.
	 * Needs the settings capability for the screen and edit rights for every single page.
	 */
	public function handle_bulk() {
		$this->verify( 'mlab_seo_bulk' );
		$action = isset( $_POST['mlab_seo_bulk_action'] ) ? sanitize_key( wp_unslash( $_POST['mlab_seo_bulk_action'] ) ) : '';
		$ids    = isset( $_POST['post'] ) && is_array( $_POST['post'] ) ? array_map( 'absint', wp_unslash( $_POST['post'] ) ) : array();
		$done   = 0;
		if ( in_array( $action, array( 'noindex', 'index' ), true ) ) {
			foreach ( array_slice( array_filter( $ids ), 0, 200 ) as $id ) {
				if ( current_user_can( 'edit_post', $id ) && in_array( get_post_type( $id ), Post_Meta::post_types(), true ) ) {
					Meta_Box::store( $id, 'noindex', 'noindex' === $action ? 1 : 0 );
					++$done;
				}
			}
			Dashboard::flush_cache();
			Crawlers::flush_cache();
		}
		$this->back( 'mlab-seo-pages', '', 'bulk_done', array( 'mlab_seo_count' => $done ) );
	}

	/**
	 * Prints the notice after a form was sent.
	 */
	public static function notice() {
		$notice   = isset( $_GET['mlab_seo_notice'] ) ? sanitize_key( wp_unslash( $_GET['mlab_seo_notice'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Display only.
		$messages = self::notice_messages();
		if ( isset( $messages[ $notice ] ) ) {
			printf( '<div class="notice notice-%s is-dismissible"><p>%s</p></div>', esc_attr( $messages[ $notice ][0] ), esc_html( $messages[ $notice ][1] ) );
		}
	}

	/**
	 * A number from the query string, for notices.
	 *
	 * @param string $key Query key.
	 * @return int
	 */
	private static function query_number( $key ) {
		return isset( $_GET[ $key ] ) ? absint( $_GET[ $key ] ) : 0; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Display only.
	}

	/**
	 * Notice texts by notice key: array( type, text ).
	 *
	 * @return array<string,array{0:string,1:string}>
	 */
	private static function notice_messages() {
		return array(
			'saved'              => array( 'success', __( 'Settings saved.', 'mlab-seo' ) ),
			'checked'            => array( 'success', __( 'Access check finished.', 'mlab-seo' ) ),
			'license_ok'         => array( 'success', __( 'Licence activated. Pro features are unlocked.', 'mlab-seo' ) ),
			'license_failed'     => array( 'error', License::data()['message'] ? License::data()['message'] : __( 'The licence could not be activated.', 'mlab-seo' ) ),
			'license_removed'    => array( 'success', __( 'Licence key removed.', 'mlab-seo' ) ),
			'redirect_added'     => array( 'success', __( 'Redirect added. The address was removed from the 404 list.', 'mlab-seo' ) ),
			'no_target'          => array( 'error', __( 'Please enter a target for the redirect.', 'mlab-seo' ) ),
			'ignored'            => array( 'success', __( 'The address is not logged any more.', 'mlab-seo' ) ),
			'links_started'      => array( 'success', __( 'The link check runs in the background and takes a few minutes. Reload this page later.', 'mlab-seo' ) ),
			/* translators: %d: number of redirects. */
			'redirects_imported' => array( 'success', sprintf( __( 'Import finished: %d redirects added.', 'mlab-seo' ), self::query_number( 'mlab_seo_imported' ) ) ),
			/* translators: 1: number of pages and posts, 2: number of categories and tags. */
			'imported'           => array( 'success', sprintf( __( 'Import finished: %1$d pages and posts, %2$d categories and tags updated.', 'mlab-seo' ), self::query_number( 'mlab_seo_posts' ), self::query_number( 'mlab_seo_terms' ) ) ),
			/* translators: %d: number of pages. */
			'bulk_done'          => array( 'success', sprintf( _n( '%d page changed.', '%d pages changed.', self::query_number( 'mlab_seo_count' ), 'mlab-seo' ), self::query_number( 'mlab_seo_count' ) ) ),
		);
	}
}
