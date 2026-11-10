<?php
/**
 * SEO screens: menu with one page per area, assets, rendering.
 *
 * @package MomentumLab\SEO
 */

namespace MomentumLab\SEO;

defined( 'ABSPATH' ) || exit;

/**
 * Admin menu and screens. Form handlers live in Admin_Actions, the structure in inc/admin-pages.php.
 */
final class Admin {

	/**
	 * Slug of the main page (overview).
	 */
	const PAGE = 'mlab-seo';

	/**
	 * Transient with the last crawler access check.
	 */
	const CHECK_TRANSIENT = 'mlab_seo_access_check';

	/**
	 * Registers hooks.
	 */
	public function hooks() {
		add_action( 'admin_menu', array( $this, 'menu' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'assets' ) );
		add_filter( 'plugin_action_links_' . plugin_basename( MLAB_SEO_FILE ), array( $this, 'action_links' ) );
		add_filter( 'admin_body_class', array( $this, 'body_class' ) );
		( new Admin_Actions() )->hooks();
	}

	/**
	 * Asset version: plugin version plus file time, so caches update on deploy.
	 *
	 * @param string $relative Path relative to the plugin directory.
	 * @return string
	 */
	public static function asset_version( $relative ) {
		$mtime = @filemtime( MLAB_SEO_DIR . $relative ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
		return MLAB_SEO_VERSION . ( $mtime ? '.' . $mtime : '' );
	}

	/**
	 * Capability for the settings.
	 *
	 * @return string
	 */
	public static function capability() {
		/**
		 * Filters the capability needed for the SEO settings.
		 *
		 * @param string $capability Default 'manage_options'.
		 */
		return (string) apply_filters( 'mlab_seo_settings_capability', 'manage_options' );
	}

	/**
	 * Address of a page, optionally with a segment.
	 *
	 * @param string $page    Menu slug.
	 * @param string $segment Segment key.
	 * @param array  $args    Extra query arguments.
	 * @return string
	 */
	public static function url( $page = self::PAGE, $segment = '', array $args = array() ) {
		$url = add_query_arg( array_merge( array( 'page' => $page ), $args ), admin_url( 'admin.php' ) );
		return '' !== $segment ? $url . '#' . $segment : $url;
	}

	/**
	 * Address of the page list with a filter.
	 *
	 * @param string $filter Filter key, see Pages_Table::FILTERS.
	 * @return string
	 */
	public static function pages_url( $filter = '' ) {
		return self::url( 'mlab-seo-pages', '', '' !== $filter ? array( 'filter' => $filter ) : array() );
	}

	/**
	 * Adds the menu entry with one sub entry per page.
	 */
	public function menu() {
		$icon = 'data:image/svg+xml;base64,' . base64_encode( '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20"><path fill="black" d="M8.5 2a6.5 6.5 0 0 1 5.2 10.4l4 4-1.3 1.3-4-4A6.5 6.5 0 1 1 8.5 2Zm0 2a4.5 4.5 0 1 0 0 9 4.5 4.5 0 0 0 0-9ZM6 10.5 7.6 7l1.6 2.2L10.4 8 11.5 10.5Z"/></svg>' ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode -- Menu icon.
		$cap  = self::capability();
		add_menu_page( __( 'MomentumLab SEO', 'mlab-seo' ), __( 'SEO', 'mlab-seo' ), $cap, self::PAGE, array( $this, 'render' ), $icon, '58.2' ); // Right below the MomentumLab menu of MomentumLab Core (58.1).
		foreach ( mlab_seo_admin_pages() as $slug => $page ) {
			$label = $page['menu'];
			if ( 'mlab-seo-redirects' === $slug && ! License::is_pro() ) {
				$label .= ' <span class="mlab-seo-menu-pro">Pro</span>';
			}
			add_submenu_page( self::PAGE, $page['title'] . ' ‹ ' . __( 'SEO', 'mlab-seo' ), $label, $cap, $slug, array( $this, 'render' ) );
		}
	}

	/**
	 * Settings link on the plugins screen.
	 *
	 * @param array $links Links.
	 * @return array
	 */
	public function action_links( $links ) {
		array_unshift( $links, '<a href="' . esc_url( self::url() ) . '">' . esc_html__( 'Settings', 'mlab-seo' ) . '</a>' );
		return $links;
	}

	/**
	 * Slug of the current SEO page, or an empty string on other screens.
	 *
	 * @return string
	 */
	public static function current_page() {
		$page = isset( $_GET['page'] ) ? sanitize_key( wp_unslash( $_GET['page'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Screen routing only.
		return isset( mlab_seo_admin_pages()[ $page ] ) ? $page : '';
	}

	/**
	 * Marks the SEO screens for the styles.
	 *
	 * @param string $classes Body classes.
	 * @return string
	 */
	public function body_class( $classes ) {
		return '' !== self::current_page() ? $classes . ' mlab-seo-screen' : $classes;
	}

	/**
	 * Assets for the SEO screens and the list column. Nothing loads on other screens.
	 *
	 * @param string $hook Admin page hook.
	 */
	public function assets( $hook ) {
		if ( 'edit.php' === $hook ) {
			wp_enqueue_style( 'mlab-seo-admin', MLAB_SEO_URL . 'assets/css/admin.css', array(), self::asset_version( 'assets/css/admin.css' ) );
		}
		$page = self::current_page();
		if ( '' === $page ) {
			return;
		}
		wp_enqueue_media();
		wp_enqueue_style( 'mlab-seo-admin', MLAB_SEO_URL . 'assets/css/admin.css', array(), self::asset_version( 'assets/css/admin.css' ) );
		self::script( 'mlab-seo-settings', 'assets/js/settings.js', array( 'jquery', 'wp-i18n', 'wp-api-fetch' ) );
		wp_localize_script(
			'mlab-seo-settings',
			'mlabSeoSettings',
			array(
				'page'   => $page,
				'legacy' => self::PAGE === $page ? self::legacy_tabs() : new \stdClass(),
			)
		);
		if ( 'mlab-seo-pages' === $page ) {
			self::script( 'mlab-seo-analysis', 'assets/js/analysis.js', array( 'wp-i18n' ) );
			self::script( 'mlab-seo-pages', 'assets/js/pages.js', array( 'mlab-seo-analysis', 'wp-api-fetch', 'wp-i18n' ) );
			wp_localize_script(
				'mlab-seo-pages',
				'mlabSeoPages',
				array(
					'homeUrl'  => home_url( '/' ),
					'siteName' => Settings::site_name(),
				)
			);
		}
	}

	/**
	 * Enqueues a deferred footer script with translations.
	 *
	 * @param string   $handle Handle.
	 * @param string   $file   Path relative to the plugin directory.
	 * @param string[] $deps   Dependencies.
	 */
	public static function script( $handle, $file, array $deps ) {
		wp_enqueue_script(
			$handle,
			MLAB_SEO_URL . $file,
			$deps,
			self::asset_version( $file ),
			array(
				'in_footer' => true,
				'strategy'  => 'defer',
			)
		);
		wp_set_script_translations( $handle, 'mlab-seo', MLAB_SEO_DIR . 'languages' );
	}

	/**
	 * Old tab anchors of the single settings screen (before 1.8.0) and where they live now.
	 *
	 * @return array<string,string>
	 */
	private static function legacy_tabs() {
		return array(
			'general'   => self::url( 'mlab-seo-search', 'home' ),
			'titles'    => self::url( 'mlab-seo-search', 'templates' ),
			'publisher' => self::url( 'mlab-seo-search', 'publisher' ),
			'ai'        => self::url( 'mlab-seo-ai', 'crawlers' ),
			'sitemap'   => self::url( 'mlab-seo-sitemap' ),
			'redirects' => self::url( 'mlab-seo-redirects' ),
			'content'   => self::url( 'mlab-seo-pages', 'content' ),
			'import'    => self::url( 'mlab-seo-settings', 'import' ),
			'license'   => self::url( 'mlab-seo-settings', 'license' ),
		);
	}

	/**
	 * Other active SEO plugins.
	 *
	 * @return string[] Plugin names.
	 */
	public static function other_seo_plugins() {
		$plugins = array(
			'WPSEO_VERSION'             => 'Yoast SEO',
			'RANK_MATH_VERSION'         => 'Rank Math',
			'SEOPRESS_VERSION'          => 'SEOPress',
			'AIOSEO_VERSION'            => 'All in One SEO',
			'THE_SEO_FRAMEWORK_VERSION' => 'The SEO Framework',
		);
		$found   = array();
		foreach ( $plugins as $constant => $name ) {
			if ( defined( $constant ) ) {
				$found[] = $name;
			}
		}
		return $found;
	}

	/**
	 * Renders the current SEO page.
	 */
	public function render() {
		$slug = self::current_page();
		if ( '' === $slug || ! current_user_can( self::capability() ) ) {
			return;
		}
		$page = mlab_seo_admin_pages()[ $slug ];
		self::part(
			'page',
			array(
				'slug'       => $slug,
				'admin_page' => $page,
			)
		);
	}

	/**
	 * Prints a template part of the SEO screens.
	 *
	 * @param string $name Part name in parts/admin/.
	 * @param array  $vars Variables for the part.
	 */
	public static function part( $name, array $vars = array() ) {
		$s   = Settings::all();
		$pro = License::is_pro();
		extract( $vars ); // phpcs:ignore WordPress.PHP.DontExtract.extract_extract -- Template variables from this class only.
		include MLAB_SEO_DIR . 'parts/admin/' . $name . '.php';
	}
}
