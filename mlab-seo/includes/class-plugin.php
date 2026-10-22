<?php
/**
 * Bootstrap: wires the components together and runs version upgrades.
 *
 * @package MomentumLab\SEO
 */

namespace MomentumLab\SEO;

defined( 'ABSPATH' ) || exit;

/**
 * Main plugin class.
 */
final class Plugin {

	/**
	 * Option that stores the installed plugin version (for upgrade routines).
	 */
	const VERSION_OPTION = 'mlab_seo_version';

	/**
	 * Singleton instance.
	 *
	 * @var Plugin|null
	 */
	private static $instance = null;

	/**
	 * Returns the plugin instance.
	 *
	 * @return Plugin
	 */
	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Registers hooks.
	 */
	private function __construct() {
		add_action( 'init', array( $this, 'load_textdomain' ), 0 );
		add_action( 'init', array( $this, 'maybe_upgrade' ), 20 );

		( new Post_Meta() )->hooks();
		( new Breadcrumbs() )->hooks();
		( new IndexNow() )->hooks();
		( new FAQ() )->hooks();
		( new Media() )->hooks();
		( new Hreflang() )->hooks();
		( new Content_Audit() )->hooks();
		( new License() )->hooks();
		( new Head() )->hooks();
		( new Crawlers() )->hooks();
		( new Sitemap() )->hooks();
		( new Redirects() )->hooks();
		( new Link_Check() )->hooks();
		( new Updater() )->hooks();

		if ( is_admin() ) {
			( new Meta_Box() )->hooks();
			( new Term_Meta() )->hooks();
			( new Admin() )->hooks();
		}
	}

	/**
	 * Loads the bundled translations (.l10n.php files, WordPress 6.5+).
	 */
	public function load_textdomain() {
		load_plugin_textdomain( 'mlab-seo', false, dirname( plugin_basename( MLAB_SEO_FILE ) ) . '/languages' );
	}

	/**
	 * Activation: stores the version and fills the publisher from the site.
	 */
	public static function activate() {
		if ( false === get_option( Settings::OPTION ) ) {
			add_option( Settings::OPTION, Settings::sanitize( array_merge( Settings::defaults(), array( 'org_name' => get_bloginfo( 'name' ) ) ) ) );
		}
		update_option( self::VERSION_OPTION, MLAB_SEO_VERSION, false );
	}

	/**
	 * Deactivation: stops the licence check.
	 */
	public static function deactivate() {
		wp_clear_scheduled_hook( License::CRON );
		Link_Check::unschedule();
	}

	/**
	 * Runs upgrade routines when the stored version differs from the code version.
	 */
	public function maybe_upgrade() {
		$installed = get_option( self::VERSION_OPTION, '0' );
		if ( version_compare( $installed, MLAB_SEO_VERSION, '>=' ) ) {
			return;
		}

		/*
		 * Future migrations go here, for example:
		 * if ( version_compare( $installed, '1.1.0', '<' ) ) { ... }
		 */

		update_option( self::VERSION_OPTION, MLAB_SEO_VERSION, false );
	}
}
