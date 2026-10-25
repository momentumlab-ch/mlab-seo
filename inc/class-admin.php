<?php
/**
 * Settings screen: general, publisher, AI and crawlers, sitemap, redirects, licence.
 *
 * @package MomentumLab\SEO
 */

namespace MomentumLab\SEO;

defined( 'ABSPATH' ) || exit;

/**
 * Admin menu, settings forms and status checks.
 */
final class Admin {

	/**
	 * Menu slug.
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
		add_action( 'admin_post_mlab_seo_save', array( $this, 'handle_settings' ) );
		add_action( 'admin_post_mlab_seo_redirects', array( $this, 'handle_redirects' ) );
		add_action( 'admin_post_mlab_seo_license', array( $this, 'handle_license' ) );
		add_action( 'admin_post_mlab_seo_check', array( $this, 'handle_check' ) );
		add_action( 'admin_post_mlab_seo_import', array( $this, 'handle_import' ) );
		add_action( 'admin_post_mlab_seo_redirect_import', array( $this, 'handle_redirect_import' ) );
		add_action( 'admin_post_mlab_seo_link_check', array( $this, 'handle_link_check' ) );
		add_filter( 'plugin_action_links_' . plugin_basename( MLAB_SEO_FILE ), array( $this, 'action_links' ) );
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
	 * Adds the menu entry.
	 */
	public function menu() {
		$icon = 'data:image/svg+xml;base64,' . base64_encode( '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20"><path fill="black" d="M8.5 2a6.5 6.5 0 0 1 5.2 10.4l4 4-1.3 1.3-4-4A6.5 6.5 0 1 1 8.5 2Zm0 2a4.5 4.5 0 1 0 0 9 4.5 4.5 0 0 0 0-9ZM6 10.5 7.6 7l1.6 2.2L10.4 8 11.5 10.5Z"/></svg>' ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode -- Menu icon.
		add_menu_page( __( 'MomentumLab SEO', 'mlab-seo' ), __( 'SEO', 'mlab-seo' ), self::capability(), self::PAGE, array( $this, 'render' ), $icon, 81 );
	}

	/**
	 * Settings link on the plugins screen.
	 *
	 * @param array $links Links.
	 * @return array
	 */
	public function action_links( $links ) {
		array_unshift( $links, '<a href="' . esc_url( admin_url( 'admin.php?page=' . self::PAGE ) ) . '">' . esc_html__( 'Settings', 'mlab-seo' ) . '</a>' );
		return $links;
	}

	/**
	 * Assets for the settings screen and the list column.
	 *
	 * @param string $hook Admin page hook.
	 */
	public function assets( $hook ) {
		if ( 'edit.php' === $hook ) {
			wp_enqueue_style( 'mlab-seo-admin', MLAB_SEO_URL . 'assets/css/admin.css', array(), self::asset_version( 'assets/css/admin.css' ) );
		}
		if ( 'toplevel_page_' . self::PAGE !== $hook ) {
			return;
		}
		wp_enqueue_media();
		wp_enqueue_style( 'mlab-seo-admin', MLAB_SEO_URL . 'assets/css/admin.css', array(), self::asset_version( 'assets/css/admin.css' ) );
		wp_enqueue_script(
			'mlab-seo-settings',
			MLAB_SEO_URL . 'assets/js/settings.js',
			array( 'jquery', 'wp-i18n' ),
			self::asset_version( 'assets/js/settings.js' ),
			array(
				'in_footer' => true,
				'strategy'  => 'defer',
			)
		);
		wp_set_script_translations( 'mlab-seo-settings', 'mlab-seo', MLAB_SEO_DIR . 'languages' );
	}

	/**
	 * Redirect back to the screen with a notice.
	 *
	 * @param string $tab    Tab to open.
	 * @param string $notice Notice key.
	 */
	private function back( $tab, $notice ) {
		wp_safe_redirect( admin_url( 'admin.php?page=' . self::PAGE . '&mlab_seo_notice=' . rawurlencode( $notice ) . '#' . $tab ) );
		exit;
	}

	/**
	 * Capability check for form handlers.
	 */
	private function verify() {
		if ( ! current_user_can( self::capability() ) ) {
			wp_die( esc_html__( 'You are not allowed to change these settings.', 'mlab-seo' ), 403 );
		}
	}

	/**
	 * Saves the settings form.
	 */
	public function handle_settings() {
		$this->verify();
		check_admin_referer( 'mlab_seo_save' );
		$values = isset( $_POST['mlab_seo'] ) && is_array( $_POST['mlab_seo'] ) ? $_POST['mlab_seo'] : array(); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized, WordPress.Security.ValidatedSanitizedInput.MissingUnslash -- Sanitised and unslashed in Settings::sanitize().
		Settings::save( $values );
		$tab = isset( $_POST['mlab_seo_tab'] ) ? sanitize_key( wp_unslash( $_POST['mlab_seo_tab'] ) ) : 'general';
		$this->back( $tab, 'saved' );
	}

	/**
	 * Saves the redirect rules.
	 */
	public function handle_redirects() {
		$this->verify();
		check_admin_referer( 'mlab_seo_redirects' );
		if ( License::is_pro() ) {
			// Stored directly: the other settings are already sanitised.
			$stored                  = get_option( Settings::OPTION, array() );
			$stored                  = is_array( $stored ) ? $stored : array();
			$stored['auto_redirect'] = empty( $_POST['mlab_seo_auto_redirect'] ) ? 0 : 1;
			update_option( Settings::OPTION, $stored, true );
			Settings::flush();
			$rows = isset( $_POST['mlab_seo_redirects'] ) && is_array( $_POST['mlab_seo_redirects'] ) ? $_POST['mlab_seo_redirects'] : array(); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized, WordPress.Security.ValidatedSanitizedInput.MissingUnslash -- Sanitised and unslashed in Redirects::save().
			Redirects::save( $rows );
			// One click in the 404 list: redirect to the suggested (or edited) target, or stop logging the address.
			$fix = isset( $_POST['mlab_seo_404_fix'] ) ? sanitize_text_field( wp_unslash( $_POST['mlab_seo_404_fix'] ) ) : '';
			if ( '' !== $fix ) {
				$targets = isset( $_POST['mlab_seo_404_target'] ) && is_array( $_POST['mlab_seo_404_target'] ) ? wp_unslash( $_POST['mlab_seo_404_target'] ) : array(); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Sanitised in Redirects::fix().
				$target  = (string) ( $targets[ md5( $fix ) ] ?? '' );
				$this->back( 'redirects', Redirects::fix( $fix, $target ) ? 'redirect_added' : 'no_target' );
			}
			$ignore = isset( $_POST['mlab_seo_404_ignore'] ) ? sanitize_text_field( wp_unslash( $_POST['mlab_seo_404_ignore'] ) ) : '';
			if ( '' !== $ignore ) {
				Redirects::ignore( $ignore );
				$this->back( 'redirects', 'ignored' );
			}
		}
		if ( ! empty( $_POST['mlab_seo_clear_log'] ) ) {
			delete_option( Redirects::LOG );
		}
		$this->back( 'redirects', 'saved' );
	}

	/**
	 * Activates or removes the licence key.
	 */
	public function handle_license() {
		$this->verify();
		check_admin_referer( 'mlab_seo_license' );
		if ( ! empty( $_POST['mlab_seo_license_remove'] ) ) {
			License::deactivate();
			$this->back( 'license', 'license_removed' );
		}
		$key  = isset( $_POST['mlab_seo_license_key'] ) ? sanitize_text_field( wp_unslash( $_POST['mlab_seo_license_key'] ) ) : '';
		$data = License::activate( $key );
		$this->back( 'license', 'valid' === $data['status'] ? 'license_ok' : 'license_failed' );
	}

	/**
	 * Runs the crawler access check.
	 */
	public function handle_check() {
		$this->verify();
		check_admin_referer( 'mlab_seo_check' );
		set_transient( self::CHECK_TRANSIENT, array( time(), Crawlers::access_check() ), WEEK_IN_SECONDS );
		$this->back( 'ai', 'checked' );
	}

	/**
	 * Starts the broken link check now (Pro).
	 */
	public function handle_link_check() {
		$this->verify();
		check_admin_referer( 'mlab_seo_link_check' );
		if ( License::is_pro() && ! Link_Check::running() ) {
			Link_Check::start();
		}
		$this->back( 'content', 'links_started' );
	}

	/**
	 * Imports SEO data from another plugin.
	 */
	public function handle_import() {
		$this->verify();
		check_admin_referer( 'mlab_seo_import' );
		$source = isset( $_POST['mlab_seo_import_source'] ) ? sanitize_key( wp_unslash( $_POST['mlab_seo_import_source'] ) ) : '';
		$result = Import::run( $source, ! empty( $_POST['mlab_seo_import_overwrite'] ) );
		wp_safe_redirect(
			add_query_arg(
				array(
					'page'            => self::PAGE,
					'mlab_seo_notice' => 'imported',
					'mlab_seo_posts'  => $result['posts'],
					'mlab_seo_terms'  => $result['terms'],
				),
				admin_url( 'admin.php' )
			) . '#import'
		);
		exit;
	}

	/**
	 * Imports redirects from another plugin.
	 */
	public function handle_redirect_import() {
		$this->verify();
		check_admin_referer( 'mlab_seo_redirect_import' );
		$source = isset( $_POST['mlab_seo_redirect_source'] ) ? sanitize_key( wp_unslash( $_POST['mlab_seo_redirect_source'] ) ) : '';
		wp_safe_redirect(
			add_query_arg(
				array(
					'page'              => self::PAGE,
					'mlab_seo_notice'   => 'redirects_imported',
					'mlab_seo_imported' => Redirect_Import::run( $source ),
				),
				admin_url( 'admin.php' )
			) . '#import'
		);
		exit;
	}

	/**
	 * Notice after a form was sent.
	 */
	public function notice() {
		$notice   = isset( $_GET['mlab_seo_notice'] ) ? sanitize_key( wp_unslash( $_GET['mlab_seo_notice'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Display only.
		$messages = self::notice_messages();
		if ( isset( $messages[ $notice ] ) ) {
			printf( '<div class="notice notice-%s is-dismissible"><p>%s</p></div>', esc_attr( $messages[ $notice ][0] ), esc_html( $messages[ $notice ][1] ) );
		}
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
			'redirects_imported' => array(
				'success',
				/* translators: %d: number of redirects. */
				sprintf( __( 'Import finished: %d redirects added.', 'mlab-seo' ), isset( $_GET['mlab_seo_imported'] ) ? absint( $_GET['mlab_seo_imported'] ) : 0 ), // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Display only.
			),
			'imported'           => array(
				'success',
				sprintf(
					/* translators: 1: number of pages and posts, 2: number of categories and tags. */
					__( 'Import finished: %1$d pages and posts, %2$d categories and tags updated.', 'mlab-seo' ),
					isset( $_GET['mlab_seo_posts'] ) ? absint( $_GET['mlab_seo_posts'] ) : 0, // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Display only.
					isset( $_GET['mlab_seo_terms'] ) ? absint( $_GET['mlab_seo_terms'] ) : 0 // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Display only.
				),
			),
		);
	}

	/**
	 * Health checks shown at the top.
	 *
	 * @return array<int,array{0:string,1:string}> State (good|ok|bad) and message.
	 */
	public function status() {
		$items = array(
			get_option( 'blog_public' )
				? array( 'good', __( 'Search engines may index the website.', 'mlab-seo' ) )
				: array( 'bad', __( 'Search engines are discouraged from indexing the website (Settings › Reading).', 'mlab-seo' ) ),
			get_option( 'permalink_structure' )
				? array( 'good', __( 'Readable URLs (permalinks) are active.', 'mlab-seo' ) )
				: array( 'bad', __( 'Plain URLs like ?p=123 are in use. Choose a permalink structure under Settings › Permalinks.', 'mlab-seo' ) ),
			'' === trim( (string) wp_parse_url( home_url( '/' ), PHP_URL_PATH ), '/' )
				? array( 'good', __( 'The website runs at the root of its domain, so robots.txt is served.', 'mlab-seo' ) )
				: array( 'ok', __( 'The website runs in a subfolder. Search engines only read robots.txt at the root of a domain, so the robots.txt rules take effect once the website moves to its own domain.', 'mlab-seo' ) ),
			str_starts_with( home_url( '/' ), 'https://' )
				? array( 'good', __( 'The website uses HTTPS.', 'mlab-seo' ) )
				: array( 'bad', __( 'The website does not use HTTPS.', 'mlab-seo' ) ),
		);
		$items = array_merge( $items, $this->content_status() );
		foreach ( self::other_seo_plugins() as $name ) {
			/* translators: %s: plugin name. */
			$items[] = array( 'bad', sprintf( __( '%s is active as well. Two SEO plugins print duplicate titles and tags. Import its data under Import, then deactivate it.', 'mlab-seo' ), $name ) );
		}
		$check = $this->access_status();
		return $check ? array_merge( $items, array( $check ) ) : $items;
	}

	/**
	 * Status of thin pages and broken links.
	 *
	 * @return array
	 */
	private function content_status() {
		$items = array();
		$thin  = Content_Audit::thin_count();
		if ( $thin ) {
			/* translators: 1: number of pages, 2: minimum number of words. */
			$items[] = array( 'ok', sprintf( _n( '%1$d page has fewer than %2$d words. Search engines and AI assistants find little to show for it (see Content).', '%1$d pages have fewer than %2$d words. Search engines and AI assistants find little to show for them (see Content).', $thin, 'mlab-seo' ), $thin, Content_Audit::THIN ) );
		}
		$broken = License::is_pro() ? count( Link_Check::broken() ) : 0;
		if ( $broken ) {
			/* translators: %d: number of links. */
			$items[] = array( 'ok', sprintf( _n( '%d link leads nowhere (see Content).', '%d links lead nowhere (see Content).', $broken, 'mlab-seo' ), $broken ) );
		}
		return $items;
	}

	/**
	 * Result of the last crawler access check, or null when there is none.
	 *
	 * @return array|null
	 */
	private function access_status() {
		$check = get_transient( self::CHECK_TRANSIENT );
		if ( ! is_array( $check ) ) {
			return null;
		}
		$blocked = array_keys(
			array_filter(
				$check[1],
				function ( $code ) {
					return 200 !== $code;
				}
			)
		);
		return $blocked
			/* translators: %s: list of crawler names. */
			? array( 'bad', sprintf( __( 'The server blocks these crawlers: %s. This is usually a firewall or bot protection setting at the hosting provider (for example in Plesk), not WordPress.', 'mlab-seo' ), implode( ', ', $blocked ) ) )
			: array( 'good', __( 'Search engines and AI crawlers can reach the website.', 'mlab-seo' ) );
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
	 * Renders the settings screen.
	 */
	public function render() {
		if ( ! current_user_can( self::capability() ) ) {
			return;
		}
		$s    = Settings::all();
		$pro  = License::is_pro();
		$tabs = array(
			'general'   => __( 'General', 'mlab-seo' ),
			'titles'    => __( 'Titles & meta', 'mlab-seo' ),
			'publisher' => __( 'Publisher', 'mlab-seo' ),
			'ai'        => __( 'AI & crawlers', 'mlab-seo' ),
			'sitemap'   => __( 'Sitemap', 'mlab-seo' ),
			'redirects' => __( 'Redirects', 'mlab-seo' ),
			'content'   => __( 'Content', 'mlab-seo' ),
			'import'    => __( 'Import', 'mlab-seo' ),
			'license'   => $pro ? __( 'Licence', 'mlab-seo' ) : __( 'Get Pro', 'mlab-seo' ),
		);
		include MLAB_SEO_DIR . 'parts/admin/page.php';
	}

	/**
	 * Prints a template part of the settings screen.
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
