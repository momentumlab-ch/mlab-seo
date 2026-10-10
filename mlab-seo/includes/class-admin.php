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
		wp_enqueue_script( 'mlab-seo-settings', MLAB_SEO_URL . 'assets/js/settings.js', array( 'jquery' ), self::asset_version( 'assets/js/settings.js' ), true );
		wp_localize_script(
			'mlab-seo-settings',
			'mlabSeoSettings',
			array(
				'choose' => __( 'Choose image', 'mlab-seo' ),
				'use'    => __( 'Use this image', 'mlab-seo' ),
			)
		);
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
	 * Notice after a form was sent.
	 */
	private function notice() {
		$notice   = isset( $_GET['mlab_seo_notice'] ) ? sanitize_key( wp_unslash( $_GET['mlab_seo_notice'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Display only.
		$messages = array(
			'saved'           => array( 'success', __( 'Settings saved.', 'mlab-seo' ) ),
			'checked'         => array( 'success', __( 'Access check finished.', 'mlab-seo' ) ),
			'license_ok'      => array( 'success', __( 'Licence activated. Pro features are unlocked.', 'mlab-seo' ) ),
			'license_failed'  => array( 'error', License::data()['message'] ? License::data()['message'] : __( 'The licence could not be activated.', 'mlab-seo' ) ),
			'license_removed' => array( 'success', __( 'Licence key removed.', 'mlab-seo' ) ),
			'imported'        => array(
				'success',
				sprintf(
					/* translators: 1: number of pages and posts, 2: number of categories and tags. */
					__( 'Import finished: %1$d pages and posts, %2$d categories and tags updated.', 'mlab-seo' ),
					isset( $_GET['mlab_seo_posts'] ) ? absint( $_GET['mlab_seo_posts'] ) : 0, // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Display only.
					isset( $_GET['mlab_seo_terms'] ) ? absint( $_GET['mlab_seo_terms'] ) : 0 // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Display only.
				),
			),
		);
		if ( isset( $messages[ $notice ] ) ) {
			printf( '<div class="notice notice-%s is-dismissible"><p>%s</p></div>', esc_attr( $messages[ $notice ][0] ), esc_html( $messages[ $notice ][1] ) );
		}
	}

	/**
	 * Health checks shown at the top.
	 *
	 * @return array<int,array{0:string,1:string}> State (good|ok|bad) and message.
	 */
	private function status() {
		$items   = array();
		$items[] = get_option( 'blog_public' )
			? array( 'good', __( 'Search engines may index the website.', 'mlab-seo' ) )
			: array( 'bad', __( 'Search engines are discouraged from indexing the website (Settings › Reading).', 'mlab-seo' ) );
		$items[] = get_option( 'permalink_structure' )
			? array( 'good', __( 'Readable URLs (permalinks) are active.', 'mlab-seo' ) )
			: array( 'bad', __( 'Plain URLs like ?p=123 are in use. Choose a permalink structure under Settings › Permalinks.', 'mlab-seo' ) );
		$items[] = '' === trim( (string) wp_parse_url( home_url( '/' ), PHP_URL_PATH ), '/' )
			? array( 'good', __( 'The website runs at the root of its domain, so robots.txt is served.', 'mlab-seo' ) )
			: array( 'ok', __( 'The website runs in a subfolder. Search engines only read robots.txt at the root of a domain, so the robots.txt rules take effect once the website moves to its own domain.', 'mlab-seo' ) );
		$items[] = str_starts_with( home_url( '/' ), 'https://' )
			? array( 'good', __( 'The website uses HTTPS.', 'mlab-seo' ) )
			: array( 'bad', __( 'The website does not use HTTPS.', 'mlab-seo' ) );

		$thin = Content_Audit::thin_count();
		if ( $thin ) {
			/* translators: 1: number of pages, 2: minimum number of words. */
			$items[] = array( 'ok', sprintf( _n( '%1$d page has fewer than %2$d words. Search engines and AI assistants find little to show for it (see Content).', '%1$d pages have fewer than %2$d words. Search engines and AI assistants find little to show for them (see Content).', $thin, 'mlab-seo' ), $thin, Content_Audit::THIN ) );
		}

		foreach ( self::other_seo_plugins() as $name ) {
			/* translators: %s: plugin name. */
			$items[] = array( 'bad', sprintf( __( '%s is active as well. Two SEO plugins print duplicate titles and tags. Import its data under Import, then deactivate it.', 'mlab-seo' ), $name ) );
		}

		$check = get_transient( self::CHECK_TRANSIENT );
		if ( is_array( $check ) ) {
			$blocked = array();
			foreach ( $check[1] as $bot => $code ) {
				if ( 200 !== $code ) {
					$blocked[] = $bot;
				}
			}
			$items[] = $blocked
				/* translators: %s: list of crawler names. */
				? array( 'bad', sprintf( __( 'The server blocks these crawlers: %s. This is usually a firewall or bot protection setting at the hosting provider (for example in Plesk), not WordPress.', 'mlab-seo' ), implode( ', ', $blocked ) ) )
				: array( 'good', __( 'Search engines and AI crawlers can reach the website.', 'mlab-seo' ) );
		}
		return $items;
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
	 * Image picker field.
	 *
	 * @param string $name  Setting key.
	 * @param int    $value Attachment ID.
	 */
	private function image_field( $name, $value ) {
		$img = Context::image( (int) $value, 'medium' );
		?>
		<div class="mlab-seo-picker">
			<div class="mlab-seo-picker__preview"><?php echo $img ? '<img src="' . esc_url( $img['url'] ) . '" alt="" />' : ''; ?></div>
			<input type="hidden" name="mlab_seo[<?php echo esc_attr( $name ); ?>]" value="<?php echo esc_attr( (string) $value ); ?>" />
			<button type="button" class="button mlab-seo-picker__pick"><?php esc_html_e( 'Choose image', 'mlab-seo' ); ?></button>
			<button type="button" class="button-link mlab-seo-picker__remove" <?php echo $img ? '' : 'hidden'; ?>><?php esc_html_e( 'Remove image', 'mlab-seo' ); ?></button>
		</div>
		<?php
	}

	/**
	 * Text input row.
	 *
	 * @param string $key         Setting key.
	 * @param string $label       Label.
	 * @param string $help        Help text.
	 * @param string $type        Input type.
	 * @param string $placeholder Placeholder.
	 */
	private function text_row( $key, $label, $help = '', $type = 'text', $placeholder = '' ) {
		printf(
			'<tr><th scope="row"><label for="mlab-seo-%1$s">%2$s</label></th><td><input type="%3$s" class="regular-text" id="mlab-seo-%1$s" name="mlab_seo[%1$s]" value="%4$s" placeholder="%5$s" />%6$s</td></tr>',
			esc_attr( $key ),
			esc_html( $label ),
			esc_attr( $type ),
			esc_attr( (string) Settings::get( $key ) ),
			esc_attr( $placeholder ),
			$help ? '<p class="description">' . esc_html( $help ) . '</p>' : ''
		);
	}

	/**
	 * Textarea row.
	 *
	 * @param string $key   Setting key.
	 * @param string $label Label.
	 * @param string $help  Help text.
	 * @param int    $rows  Rows.
	 * @param string $css_class Extra class.
	 */
	private function textarea_row( $key, $label, $help = '', $rows = 3, $css_class = '' ) {
		printf(
			'<tr><th scope="row"><label for="mlab-seo-%1$s">%2$s</label></th><td><textarea class="large-text %5$s" rows="%3$d" id="mlab-seo-%1$s" name="mlab_seo[%1$s]">%4$s</textarea>%6$s</td></tr>',
			esc_attr( $key ),
			esc_html( $label ),
			(int) $rows,
			esc_textarea( (string) Settings::get( $key ) ),
			esc_attr( $css_class ),
			$help ? '<p class="description">' . esc_html( $help ) . '</p>' : ''
		);
	}

	/**
	 * Checkbox row.
	 *
	 * @param string $key   Setting key.
	 * @param string $label Row label.
	 * @param string $text  Checkbox text.
	 */
	private function checkbox_row( $key, $label, $text ) {
		printf(
			'<tr><th scope="row">%1$s</th><td><label><input type="checkbox" name="mlab_seo[%2$s]" value="1" %3$s /> %4$s</label></td></tr>',
			esc_html( $label ),
			esc_attr( $key ),
			checked( (bool) Settings::get( $key ), true, false ),
			esc_html( $text )
		);
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
			'publisher' => __( 'Publisher', 'mlab-seo' ),
			'ai'        => __( 'AI & crawlers', 'mlab-seo' ),
			'sitemap'   => __( 'Sitemap', 'mlab-seo' ),
			'redirects' => __( 'Redirects', 'mlab-seo' ),
			'content'   => __( 'Content', 'mlab-seo' ),
			'import'    => __( 'Import', 'mlab-seo' ),
			'license'   => $pro ? __( 'Licence', 'mlab-seo' ) : __( 'Get Pro', 'mlab-seo' ),
		);
		?>
		<div class="wrap mlab-seo-settings">
			<h1><?php esc_html_e( 'MomentumLab SEO', 'mlab-seo' ); ?> <span class="mlab-seo__pro<?php echo $pro ? '' : ' is-free'; ?>"><?php echo $pro ? 'Pro' : 'Free'; ?></span></h1>
			<?php $this->notice(); ?>

			<div class="mlab-seo-status">
				<h2><?php esc_html_e( 'Status', 'mlab-seo' ); ?></h2>
				<ul class="mlab-seo__checks">
					<?php foreach ( $this->status() as $item ) : ?>
						<li data-state="<?php echo esc_attr( $item[0] ); ?>"><?php echo esc_html( $item[1] ); ?></li>
					<?php endforeach; ?>
				</ul>
				<p>
					<a href="<?php echo esc_url( home_url( '/wp-sitemap.xml' ) ); ?>" target="_blank" rel="noopener"><?php esc_html_e( 'View sitemap', 'mlab-seo' ); ?></a>
					<?php foreach ( Media::sitemap_urls() as $media_url ) : ?>
						· <a href="<?php echo esc_url( $media_url ); ?>" target="_blank" rel="noopener"><?php echo esc_html( basename( $media_url ) ); ?></a>
					<?php endforeach; ?>
					<?php if ( $s['llms_enabled'] ) : ?>
						· <a href="<?php echo esc_url( Crawlers::llms_url() ); ?>" target="_blank" rel="noopener"><?php esc_html_e( 'View llms.txt', 'mlab-seo' ); ?></a>
					<?php endif; ?>
					· <a href="<?php echo esc_url( 'https://search.google.com/test/rich-results?url=' . rawurlencode( home_url( '/' ) ) ); ?>" target="_blank" rel="noopener"><?php esc_html_e( 'Test with Google', 'mlab-seo' ); ?></a>
				</p>
			</div>

			<nav class="nav-tab-wrapper mlab-seo-nav" aria-label="<?php esc_attr_e( 'SEO settings', 'mlab-seo' ); ?>">
				<?php foreach ( $tabs as $key => $label ) : ?>
					<a href="#<?php echo esc_attr( $key ); ?>" class="nav-tab" data-tab="<?php echo esc_attr( $key ); ?>"><?php echo esc_html( $label ); ?><?php echo in_array( $key, array( 'redirects' ), true ) && ! $pro ? ' <span class="mlab-seo__pro">Pro</span>' : ''; ?></a>
				<?php endforeach; ?>
			</nav>

			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="mlab-seo-form">
				<input type="hidden" name="action" value="mlab_seo_save" />
				<input type="hidden" name="mlab_seo_tab" value="general" class="mlab-seo-current-tab" />
				<?php wp_nonce_field( 'mlab_seo_save' ); ?>

				<section class="mlab-seo-tab" data-tab="general">
					<table class="form-table" role="presentation">
						<?php
						$this->text_row( 'site_name', __( 'Website name', 'mlab-seo' ), __( 'Used in titles, social previews and schema.org. Empty: the site title from WordPress.', 'mlab-seo' ), 'text', wp_strip_all_tags( get_bloginfo( 'name' ) ) );
						$this->text_row( 'separator', __( 'Title separator', 'mlab-seo' ), __( 'Between page title and website name, for example – or |.', 'mlab-seo' ) );
						$this->text_row( 'home_title', __( 'Title of the home page', 'mlab-seo' ), __( 'Can also be set in the SEO box of the home page.', 'mlab-seo' ) );
						$this->textarea_row( 'home_description', __( 'Description of the home page', 'mlab-seo' ), __( '120 to 160 characters. Also used as summary for AI assistants.', 'mlab-seo' ) );
						?>
						<tr><th scope="row"><?php esc_html_e( 'Default social image', 'mlab-seo' ); ?></th><td><?php $this->image_field( 'default_image', (int) $s['default_image'] ); ?><p class="description"><?php esc_html_e( 'For pages without their own image. 1200 × 630 px.', 'mlab-seo' ); ?></p></td></tr>
						<?php
						$this->text_row( 'og_locale', __( 'Language for social media', 'mlab-seo' ), __( 'For example de_CH. Empty: the WordPress language.', 'mlab-seo' ), 'text', get_locale() );
						$this->text_row( 'twitter_site', __( 'X (Twitter) account', 'mlab-seo' ), '', 'text', '@account' );
						$this->checkbox_row( 'remove_generator', __( 'Source code', 'mlab-seo' ), __( 'Hide the WordPress version', 'mlab-seo' ) );
						$this->checkbox_row( 'attachment_pages', __( 'Attachment pages', 'mlab-seo' ), __( 'Keep the pages WordPress creates for every uploaded file (not recommended: thin content). Off: they redirect to the file.', 'mlab-seo' ) );
						?>
					</table>

					<h2><?php esc_html_e( 'Site verification', 'mlab-seo' ); ?></h2>
					<p><?php esc_html_e( 'Confirms that you own the website, for example in Google Search Console. Paste the code or the whole meta tag.', 'mlab-seo' ); ?></p>
					<table class="form-table" role="presentation">
						<?php
						$this->text_row( 'verify_google', __( 'Google Search Console', 'mlab-seo' ) );
						$this->text_row( 'verify_bing', __( 'Bing Webmaster Tools', 'mlab-seo' ) );
						$this->text_row( 'verify_yandex', __( 'Yandex', 'mlab-seo' ) );
						$this->text_row( 'verify_pinterest', __( 'Pinterest', 'mlab-seo' ) );
						?>
					</table>

					<h2><?php esc_html_e( 'Languages (hreflang)', 'mlab-seo' ); ?></h2>
					<p><?php esc_html_e( 'For websites with a separate site or domain per language. Enter the other languages; each page then gets a field for the address of its translation. Set the links on the other language sites too, so the pages point to each other.', 'mlab-seo' ); ?></p>
					<?php if ( Hreflang::handled_elsewhere() ) : ?>
						<p class="description"><?php esc_html_e( 'A multilingual plugin (WPML, Polylang or TranslatePress) is active and prints hreflang itself. These settings are not used.', 'mlab-seo' ); ?></p>
					<?php endif; ?>
					<table class="form-table" role="presentation">
						<?php
						/* translators: %s: language code of this website, for example de-CH. */
						$this->text_row( 'hreflang_langs', __( 'Other languages', 'mlab-seo' ), sprintf( __( 'Language codes separated by commas, for example en, fr. This website: %s.', 'mlab-seo' ), Hreflang::own() ), 'text', 'en, fr' );
						?>
						<tr><th scope="row"><label for="mlab-seo-hreflang_default"><?php esc_html_e( 'Default language (x-default)', 'mlab-seo' ); ?></label></th><td>
							<select id="mlab-seo-hreflang_default" name="mlab_seo[hreflang_default]">
								<option value=""><?php esc_html_e( 'None', 'mlab-seo' ); ?></option>
								<?php foreach ( array_merge( array( Hreflang::own() ), Hreflang::languages() ) as $code ) : ?>
									<option value="<?php echo esc_attr( $code ); ?>" <?php selected( $s['hreflang_default'], $code ); ?>><?php echo esc_html( $code ); ?></option>
								<?php endforeach; ?>
							</select>
							<p class="description"><?php esc_html_e( 'The version for visitors whose language is not offered.', 'mlab-seo' ); ?></p>
						</td></tr>
					</table>
				</section>

				<section class="mlab-seo-tab" data-tab="publisher" hidden>
					<p><?php esc_html_e( 'Who is behind the website. Google uses this for the knowledge panel, AI assistants to answer questions about you.', 'mlab-seo' ); ?></p>
					<table class="form-table" role="presentation">
						<tr><th scope="row"><label for="mlab-seo-org_type"><?php esc_html_e( 'Type', 'mlab-seo' ); ?></label></th><td>
							<select id="mlab-seo-org_type" name="mlab_seo[org_type]">
								<?php foreach ( Settings::ORG_TYPES as $type ) : ?>
									<option value="<?php echo esc_attr( $type ); ?>" <?php selected( $s['org_type'], $type ); ?>><?php echo esc_html( $type ); ?></option>
								<?php endforeach; ?>
							</select></td></tr>
						<?php
						$this->text_row( 'org_name', __( 'Name', 'mlab-seo' ) );
						$this->text_row( 'org_alt_name', __( 'Other name', 'mlab-seo' ), __( 'For example a brand or short name.', 'mlab-seo' ) );
						?>
						<tr><th scope="row"><?php esc_html_e( 'Logo', 'mlab-seo' ); ?></th><td><?php $this->image_field( 'org_logo', (int) $s['org_logo'] ); ?><p class="description"><?php esc_html_e( 'Empty: the site logo from the theme.', 'mlab-seo' ); ?></p></td></tr>
						<?php
						$this->text_row( 'org_email', __( 'Email', 'mlab-seo' ), '', 'email' );
						$this->text_row( 'org_phone', __( 'Phone', 'mlab-seo' ), '', 'tel', '+41 …' );
						$this->text_row( 'org_street', __( 'Street', 'mlab-seo' ) );
						$this->text_row( 'org_zip', __( 'Postcode', 'mlab-seo' ) );
						$this->text_row( 'org_city', __( 'City', 'mlab-seo' ) );
						$this->text_row( 'org_region', __( 'Region', 'mlab-seo' ), __( 'For example the canton: BE.', 'mlab-seo' ) );
						$this->text_row( 'org_country', __( 'Country code', 'mlab-seo' ), '', 'text', 'CH' );
						$this->textarea_row( 'org_hours', __( 'Opening hours', 'mlab-seo' ), __( 'For local businesses. One rule per line, for example: Mo-Fr 08:00-12:00, 13:30-17:30 or Sa 09:00-16:00.', 'mlab-seo' ), 3 );
						$this->text_row( 'org_lat', __( 'Latitude', 'mlab-seo' ), '', 'text', '46.9480' );
						$this->text_row( 'org_lng', __( 'Longitude', 'mlab-seo' ), __( 'For local businesses: the position on the map, for example from Google Maps (right-click on the place).', 'mlab-seo' ), 'text', '7.4474' );
						$this->text_row( 'org_price_range', __( 'Price range', 'mlab-seo' ), __( 'For local businesses, for example $$ or CHF 20–50.', 'mlab-seo' ) );
						$this->textarea_row( 'org_same_as', __( 'Profiles', 'mlab-seo' ), __( 'One URL per line: Instagram, Facebook, LinkedIn, Wikipedia …', 'mlab-seo' ) );
						$this->textarea_row( 'org_extra', __( 'Additional properties (JSON)', 'mlab-seo' ), __( 'Optional, for experts: properties merged into the publisher, for example {"sport": "Skimo"}.', 'mlab-seo' ), 3, 'code' );
						?>
					</table>
				</section>

				<section class="mlab-seo-tab" data-tab="ai" hidden>
					<h2><?php esc_html_e( 'AI crawlers', 'mlab-seo' ); ?></h2>
					<p><?php esc_html_e( 'Allowed crawlers may read the website, so assistants like ChatGPT, Claude or Perplexity can answer questions about it and link to it. The rules go into robots.txt.', 'mlab-seo' ); ?></p>
					<table class="widefat striped mlab-seo-bots">
						<thead><tr><th><?php esc_html_e( 'Allow', 'mlab-seo' ); ?></th><th><?php esc_html_e( 'Crawler', 'mlab-seo' ); ?></th><th><?php esc_html_e( 'Operator and purpose', 'mlab-seo' ); ?></th></tr></thead>
						<tbody>
							<?php foreach ( Settings::AI_BOTS as $bot => $purpose ) : ?>
								<tr>
									<td><input type="checkbox" id="mlab-seo-bot-<?php echo esc_attr( $bot ); ?>" name="mlab_seo[ai_bots][<?php echo esc_attr( $bot ); ?>]" value="1" <?php checked( ! empty( $s['ai_bots'][ $bot ] ) ); ?> /></td>
									<td><label for="mlab-seo-bot-<?php echo esc_attr( $bot ); ?>"><code><?php echo esc_html( $bot ); ?></code></label></td>
									<td><?php echo esc_html( $purpose ); ?></td>
								</tr>
							<?php endforeach; ?>
						</tbody>
					</table>
					<table class="form-table" role="presentation">
						<?php
						$this->textarea_row( 'robots_extra', __( 'Additional robots.txt rules', 'mlab-seo' ), '', 3, 'code' );
						?>
					</table>

					<h2><?php esc_html_e( 'IndexNow', 'mlab-seo' ); ?></h2>
					<p><?php esc_html_e( 'Tells Bing, Yandex, Seznam and Naver about new and changed pages right after saving, instead of waiting for the next crawl. Bing also feeds ChatGPT search and Copilot.', 'mlab-seo' ); ?></p>
					<table class="form-table" role="presentation">
						<?php $this->checkbox_row( 'indexnow', __( 'IndexNow', 'mlab-seo' ), __( 'Submit changed pages automatically', 'mlab-seo' ) ); ?>
						<?php if ( $s['indexnow'] && '' !== $s['indexnow_key'] ) : ?>
							<tr><th scope="row"><?php esc_html_e( 'Key file', 'mlab-seo' ); ?></th><td>
								<a href="<?php echo esc_url( IndexNow::key_url() ); ?>" target="_blank" rel="noopener"><code><?php echo esc_html( $s['indexnow_key'] ); ?>.txt</code></a>
								<?php
								$last = get_option( IndexNow::LAST );
								if ( ! IndexNow::active() ) {
									echo '<p class="description">' . esc_html__( 'Paused: submissions only run on a public production website.', 'mlab-seo' ) . '</p>';
								} elseif ( is_array( $last ) ) {
									/* translators: 1: date and time, 2: number of addresses. */
									echo '<p class="description">' . esc_html( sprintf( __( 'Last submission: %1$s (%2$d addresses)', 'mlab-seo' ), wp_date( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), (int) $last['time'] ), count( (array) $last['urls'] ) ) ) . '</p>';
								}
								?>
							</td></tr>
						<?php endif; ?>
					</table>

					<h2><?php esc_html_e( 'llms.txt', 'mlab-seo' ); ?></h2>
					<p><?php esc_html_e( 'A short Markdown summary of the website for AI assistants (llmstxt.org). The page list is built automatically from the SEO descriptions.', 'mlab-seo' ); ?></p>
					<table class="form-table" role="presentation">
						<?php
						$this->checkbox_row( 'llms_enabled', __( 'llms.txt', 'mlab-seo' ), __( 'Provide /llms.txt', 'mlab-seo' ) );
						$this->textarea_row( 'llms_summary', __( 'Summary', 'mlab-seo' ), __( 'Two or three sentences: who you are, what you offer, where. Empty: the description of the home page.', 'mlab-seo' ) );
						$this->textarea_row( 'llms_facts', __( 'Key facts', 'mlab-seo' ), __( 'One fact per line: dates, places, prices, contact. AI assistants quote these directly.', 'mlab-seo' ), 7 );
						?>
						<tr><th scope="row"><?php esc_html_e( 'llms-full.txt', 'mlab-seo' ); ?> <span class="mlab-seo__pro">Pro</span></th><td><label><input type="checkbox" name="mlab_seo[llms_full]" value="1" <?php checked( (bool) $s['llms_full'] ); ?> <?php disabled( ! $pro ); ?> /> <?php esc_html_e( 'Also provide the full text of all pages as /llms-full.txt', 'mlab-seo' ); ?></label></td></tr>
					</table>
				</section>

				<section class="mlab-seo-tab" data-tab="sitemap" hidden>
					<p><?php esc_html_e( 'WordPress creates the XML sitemap. Here you choose what goes in. Pages set to noindex are left out automatically.', 'mlab-seo' ); ?></p>
					<table class="form-table" role="presentation">
						<tr><th scope="row"><?php esc_html_e( 'Content types', 'mlab-seo' ); ?></th><td>
							<?php foreach ( get_post_types( array( 'public' => true ), 'objects' ) as $type ) : ?>
								<?php
								if ( 'attachment' === $type->name ) {
									continue;
								}
								?>
								<label><input type="checkbox" name="mlab_seo[sitemap_types][]" value="<?php echo esc_attr( $type->name ); ?>" <?php checked( in_array( $type->name, (array) $s['sitemap_types'], true ) ); ?> /> <?php echo esc_html( $type->labels->name ); ?></label><br />
							<?php endforeach; ?>
						</td></tr>
						<?php
						$this->checkbox_row( 'sitemap_taxonomy', __( 'Categories and tags', 'mlab-seo' ), __( 'Include archive pages of categories and tags', 'mlab-seo' ) );
						$this->checkbox_row( 'sitemap_users', __( 'Authors', 'mlab-seo' ), __( 'Include author pages (shows user names, not recommended)', 'mlab-seo' ) );
						$this->checkbox_row( 'sitemap_images', __( 'Image sitemap', 'mlab-seo' ), __( 'List the images of every page in /sitemap-images.xml (for Google Images)', 'mlab-seo' ) );
						$this->checkbox_row( 'sitemap_videos', __( 'Video sitemap', 'mlab-seo' ), __( 'List YouTube, Vimeo and uploaded videos in /sitemap-videos.xml (for Google video results)', 'mlab-seo' ) );
						?>
					</table>
				</section>

				<p class="submit mlab-seo-submit"><?php submit_button( __( 'Save settings', 'mlab-seo' ), 'primary', 'submit', false ); ?></p>
			</form>

			<section class="mlab-seo-tab" data-tab="ai" hidden>
				<h2><?php esc_html_e( 'Access check', 'mlab-seo' ); ?></h2>
				<p><?php esc_html_e( 'Requests the home page as Google, Bing, ChatGPT, Claude and Perplexity. Shows whether a firewall on the server blocks them, which robots.txt cannot fix.', 'mlab-seo' ); ?></p>
				<?php
				$check = get_transient( self::CHECK_TRANSIENT );
				if ( is_array( $check ) ) :
					?>
					<table class="widefat striped mlab-seo-bots">
						<tbody>
							<?php foreach ( $check[1] as $bot => $code ) : ?>
								<tr><td><code><?php echo esc_html( $bot ); ?></code></td><td><span class="mlab-seo-badge <?php echo 200 === $code ? 'is-good' : 'is-bad'; ?>"><?php echo esc_html( (string) $code ); ?></span></td></tr>
							<?php endforeach; ?>
						</tbody>
					</table>
					<p class="description">
						<?php
						/* translators: %s: date and time. */
						printf( esc_html__( 'Last check: %s', 'mlab-seo' ), esc_html( wp_date( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), (int) $check[0] ) ) );
						?>
					</p>
				<?php endif; ?>
				<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
					<input type="hidden" name="action" value="mlab_seo_check" />
					<?php wp_nonce_field( 'mlab_seo_check' ); ?>
					<?php submit_button( __( 'Check access now', 'mlab-seo' ), 'secondary', 'submit', false ); ?>
				</form>
			</section>

			<section class="mlab-seo-tab" data-tab="redirects" hidden>
				<?php $this->render_redirects( $pro ); ?>
			</section>

			<section class="mlab-seo-tab" data-tab="content" hidden>
				<?php $this->render_content(); ?>
			</section>

			<section class="mlab-seo-tab" data-tab="import" hidden>
				<?php $this->render_import(); ?>
			</section>

			<section class="mlab-seo-tab" data-tab="license" hidden>
				<?php $this->render_license( $pro ); ?>
			</section>

			<p class="mlab-seo-credit"><?php esc_html_e( 'MomentumLab SEO by MomentumLab', 'mlab-seo' ); ?> · <a href="https://momentumlab.ch/" target="_blank" rel="noopener">momentumlab.ch</a> · <?php echo esc_html( MLAB_SEO_VERSION ); ?></p>
		</div>
		<?php
	}

	/**
	 * Redirects tab.
	 *
	 * @param bool $pro Pro active.
	 */
	private function render_redirects( $pro ) {
		$rules = Redirects::rules();
		$log   = Redirects::log();
		?>
		<p><?php esc_html_e( 'Send visitors and search engines from old addresses to the new page, so links and rankings are kept.', 'mlab-seo' ); ?></p>
		<?php if ( ! $pro ) : ?>
			<div class="mlab-seo__locked"><p><strong><?php esc_html_e( 'Redirects and the 404 log are Pro features.', 'mlab-seo' ); ?></strong></p><p><a class="button button-primary" href="#license" data-tab-link="license"><?php esc_html_e( 'Activate Pro', 'mlab-seo' ); ?></a></p></div>
		<?php endif; ?>
		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
			<input type="hidden" name="action" value="mlab_seo_redirects" />
			<?php wp_nonce_field( 'mlab_seo_redirects' ); ?>
			<fieldset <?php disabled( ! $pro ); ?>>
				<p><label><input type="checkbox" name="mlab_seo_auto_redirect" value="1" <?php checked( (bool) Settings::get( 'auto_redirect' ) ); ?> /> <?php esc_html_e( 'Add a redirect automatically when the address of a published page changes', 'mlab-seo' ); ?></label></p>
				<table class="widefat striped mlab-seo-redirects">
					<thead><tr><th><?php esc_html_e( 'Old address', 'mlab-seo' ); ?></th><th><?php esc_html_e( 'Target', 'mlab-seo' ); ?></th><th><?php esc_html_e( 'Type', 'mlab-seo' ); ?></th><th><?php esc_html_e( 'Hits', 'mlab-seo' ); ?></th><th><span class="screen-reader-text"><?php esc_html_e( 'Remove', 'mlab-seo' ); ?></span></th></tr></thead>
					<tbody>
						<?php foreach ( array_merge( $rules, array( array() ) ) as $i => $rule ) : ?>
							<tr>
								<td><input type="text" class="widefat" name="mlab_seo_redirects[<?php echo (int) $i; ?>][from]" value="<?php echo esc_attr( $rule['from'] ?? '' ); ?>" placeholder="/alte-seite" aria-label="<?php esc_attr_e( 'Old address', 'mlab-seo' ); ?>" /></td>
								<td><input type="text" class="widefat" name="mlab_seo_redirects[<?php echo (int) $i; ?>][to]" value="<?php echo esc_attr( $rule['to'] ?? '' ); ?>" placeholder="/neue-seite/" aria-label="<?php esc_attr_e( 'Target', 'mlab-seo' ); ?>" /></td>
								<td><select name="mlab_seo_redirects[<?php echo (int) $i; ?>][code]" aria-label="<?php esc_attr_e( 'Type', 'mlab-seo' ); ?>">
									<option value="301" <?php selected( (int) ( $rule['code'] ?? 301 ), 301 ); ?>><?php esc_html_e( '301 permanent', 'mlab-seo' ); ?></option>
									<option value="302" <?php selected( (int) ( $rule['code'] ?? 301 ), 302 ); ?>><?php esc_html_e( '302 temporary', 'mlab-seo' ); ?></option>
									<option value="410" <?php selected( (int) ( $rule['code'] ?? 301 ), 410 ); ?>><?php esc_html_e( '410 deleted', 'mlab-seo' ); ?></option>
								</select></td>
								<td><?php echo (int) ( $rule['hits'] ?? 0 ); ?></td>
								<td><button type="button" class="button-link mlab-seo-row-remove" aria-label="<?php esc_attr_e( 'Remove', 'mlab-seo' ); ?>">×</button></td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
				<p><button type="button" class="button mlab-seo-row-add"><?php esc_html_e( 'Add redirect', 'mlab-seo' ); ?></button> <?php submit_button( __( 'Save redirects', 'mlab-seo' ), 'primary', 'submit', false ); ?></p>

				<h2><?php esc_html_e( 'Pages not found (404)', 'mlab-seo' ); ?></h2>
				<?php if ( $log ) : ?>
					<table class="widefat striped">
						<thead><tr><th><?php esc_html_e( 'Address', 'mlab-seo' ); ?></th><th><?php esc_html_e( 'Requests', 'mlab-seo' ); ?></th><th><?php esc_html_e( 'Last', 'mlab-seo' ); ?></th><th></th></tr></thead>
						<tbody>
							<?php foreach ( array_slice( $log, 0, 30, true ) as $path => $row ) : ?>
								<tr><td><code><?php echo esc_html( $path ); ?></code></td><td><?php echo (int) $row['count']; ?></td><td><?php echo esc_html( wp_date( get_option( 'date_format' ), (int) $row['last'] ) ); ?></td><td><button type="button" class="button-link mlab-seo-log-use" data-path="<?php echo esc_attr( $path ); ?>"><?php esc_html_e( 'Redirect', 'mlab-seo' ); ?></button></td></tr>
							<?php endforeach; ?>
						</tbody>
					</table>
					<p><label><input type="checkbox" name="mlab_seo_clear_log" value="1" /> <?php esc_html_e( 'Clear the log when saving', 'mlab-seo' ); ?></label></p>
				<?php else : ?>
					<p class="description"><?php esc_html_e( 'No missing pages recorded yet.', 'mlab-seo' ); ?></p>
				<?php endif; ?>
			</fieldset>
		</form>
		<?php
	}

	/**
	 * Content tab: pages with little text.
	 */
	private function render_content() {
		$rows = Content_Audit::results();
		?>
		<p>
			<?php
			/* translators: 1: minimum number of words, 2: recommended number of words. */
			echo esc_html( sprintf( __( 'Indexable pages with little text. Below %1$d words a page is thin: it rarely ranks and gives AI assistants nothing to quote. From about %2$d words a page can answer a question well.', 'mlab-seo' ), Content_Audit::THIN, Content_Audit::SHORT ) );
			?>
		</p>
		<p><?php esc_html_e( 'For each page: add an introduction, answers to typical questions (FAQ block) or descriptions of images and tables. Pages that only serve visitors, like a gallery without text, can also be hidden from search engines (noindex).', 'mlab-seo' ); ?></p>
		<?php if ( $rows ) : ?>
			<table class="widefat striped">
				<thead><tr><th><?php esc_html_e( 'Page', 'mlab-seo' ); ?></th><th><?php esc_html_e( 'Words', 'mlab-seo' ); ?></th><th><?php esc_html_e( 'Images', 'mlab-seo' ); ?></th><th><?php esc_html_e( 'Rating', 'mlab-seo' ); ?></th></tr></thead>
				<tbody>
					<?php foreach ( $rows as $row ) : ?>
						<tr>
							<td><a href="<?php echo esc_url( (string) get_edit_post_link( $row['id'] ) ); ?>"><?php echo esc_html( Context::plain_title( $row['id'] ) ); ?></a> <span class="description">(<?php echo esc_html( (string) get_post_type_object( (string) get_post_type( $row['id'] ) )->labels->singular_name ); ?>)</span></td>
							<td><?php echo (int) $row['words']; ?></td>
							<td><?php echo (int) $row['images']; ?></td>
							<td>
								<?php if ( $row['words'] < Content_Audit::THIN ) : ?>
									<span class="mlab-seo-badge is-bad"><?php esc_html_e( 'Thin', 'mlab-seo' ); ?></span>
								<?php else : ?>
									<span class="mlab-seo-badge is-ok"><?php esc_html_e( 'Short', 'mlab-seo' ); ?></span>
								<?php endif; ?>
							</td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		<?php else : ?>
			<p><span class="mlab-seo-badge is-good"><?php esc_html_e( 'Good', 'mlab-seo' ); ?></span> <?php esc_html_e( 'All indexable pages have enough text.', 'mlab-seo' ); ?></p>
		<?php endif; ?>
		<?php
	}

	/**
	 * Import tab.
	 */
	private function render_import() {
		$counts = Import::detect();
		?>
		<p><?php esc_html_e( 'Switching from another SEO plugin? Take over SEO titles, meta descriptions, focus keywords, noindex and nofollow, canonical URLs and social titles, descriptions and images. Categories and tags are included for Yoast SEO, Rank Math and SEOPress.', 'mlab-seo' ); ?></p>
		<p><?php esc_html_e( 'The data of the other plugin is not changed, so you can repeat the import. Title variables like %%title%% are replaced by their values; titles that only repeat the default pattern are left empty, so the MomentumLab SEO default applies.', 'mlab-seo' ); ?></p>
		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
			<input type="hidden" name="action" value="mlab_seo_import" />
			<?php wp_nonce_field( 'mlab_seo_import' ); ?>
			<table class="widefat striped mlab-seo-bots">
				<thead><tr><th><?php esc_html_e( 'Import from', 'mlab-seo' ); ?></th><th><?php esc_html_e( 'Plugin', 'mlab-seo' ); ?></th><th><?php esc_html_e( 'Pages with data', 'mlab-seo' ); ?></th></tr></thead>
				<tbody>
					<?php foreach ( Import::SOURCES as $key => $source ) : ?>
						<tr>
							<td><input type="radio" id="mlab-seo-import-<?php echo esc_attr( $key ); ?>" name="mlab_seo_import_source" value="<?php echo esc_attr( $key ); ?>" <?php disabled( 0 === $counts[ $key ] ); ?> /></td>
							<td><label for="mlab-seo-import-<?php echo esc_attr( $key ); ?>"><?php echo esc_html( $source['label'] ); ?></label></td>
							<td><?php echo $counts[ $key ] ? (int) $counts[ $key ] : '<span aria-hidden="true">–</span><span class="screen-reader-text">' . esc_html__( 'No data found', 'mlab-seo' ) . '</span>'; ?></td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
			<p><label><input type="checkbox" name="mlab_seo_import_overwrite" value="1" /> <?php esc_html_e( 'Overwrite values already set in MomentumLab SEO', 'mlab-seo' ); ?></label></p>
			<?php submit_button( __( 'Import now', 'mlab-seo' ), 'primary', 'submit', false, array_sum( $counts ) ? array() : array( 'disabled' => 'disabled' ) ); ?>
		</form>
		<?php
	}

	/**
	 * Licence tab.
	 *
	 * @param bool $pro Pro active.
	 */
	private function render_license( $pro ) {
		$data       = License::data();
		$via_filter = $pro && 'valid' !== $data['status'];
		?>
		<div class="mlab-seo-plans">
			<div class="mlab-seo-plan">
				<h3><?php esc_html_e( 'Free', 'mlab-seo' ); ?></h3>
				<ul>
					<li><?php esc_html_e( 'SEO title and meta description per page', 'mlab-seo' ); ?></li>
					<li><?php esc_html_e( 'Google preview with pixel check', 'mlab-seo' ); ?></li>
					<li><?php esc_html_e( 'SEO check with focus keyword and score', 'mlab-seo' ); ?></li>
					<li><?php esc_html_e( 'Social previews (Open Graph, X)', 'mlab-seo' ); ?></li>
					<li><?php esc_html_e( 'schema.org: publisher, website, page, breadcrumbs, articles', 'mlab-seo' ); ?></li>
					<li><?php esc_html_e( 'Sitemap, robots.txt, AI crawler control, llms.txt', 'mlab-seo' ); ?></li>
					<li><?php esc_html_e( 'Access check for search engines and AI crawlers', 'mlab-seo' ); ?></li>
					<li><?php esc_html_e( 'SEO for categories and tags, breadcrumbs, IndexNow', 'mlab-seo' ); ?></li>
					<li><?php esc_html_e( 'Import from Yoast SEO, Rank Math, SEOPress and All in One SEO', 'mlab-seo' ); ?></li>
				</ul>
			</div>
			<div class="mlab-seo-plan is-pro">
				<h3><?php esc_html_e( 'Pro', 'mlab-seo' ); ?></h3>
				<ul>
					<li><?php esc_html_e( 'Events in Google results (date, place, tickets, sub-events)', 'mlab-seo' ); ?></li>
					<li><?php esc_html_e( 'Custom schema.org per page (FAQ, products, recipes …)', 'mlab-seo' ); ?></li>
					<li><?php esc_html_e( 'Redirect manager (301, 302, 410) with hit counter', 'mlab-seo' ); ?></li>
					<li><?php esc_html_e( 'Automatic redirect when a page address changes', 'mlab-seo' ); ?></li>
					<li><?php esc_html_e( '404 log with one-click redirect', 'mlab-seo' ); ?></li>
					<li><?php esc_html_e( 'llms-full.txt: the whole website as text for AI', 'mlab-seo' ); ?></li>
					<li><?php esc_html_e( 'Updates and support by MomentumLab', 'mlab-seo' ); ?></li>
				</ul>
			</div>
		</div>

		<h2><?php esc_html_e( 'Licence key', 'mlab-seo' ); ?></h2>
		<?php if ( $via_filter ) : ?>
			<p><span class="mlab-seo-badge is-good"><?php esc_html_e( 'Active', 'mlab-seo' ); ?></span> <?php esc_html_e( 'Pro is unlocked for this website by MomentumLab.', 'mlab-seo' ); ?></p>
		<?php else : ?>
			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
				<input type="hidden" name="action" value="mlab_seo_license" />
				<?php wp_nonce_field( 'mlab_seo_license' ); ?>
				<?php if ( '' !== $data['key'] ) : ?>
					<p>
						<code><?php echo esc_html( License::masked_key() ); ?></code>
						<?php if ( 'valid' === $data['status'] ) : ?>
							<span class="mlab-seo-badge is-good"><?php esc_html_e( 'Active', 'mlab-seo' ); ?></span>
							<?php
							if ( $data['expires'] ) {
								/* translators: %s: expiry date. */
								printf( ' ' . esc_html__( 'valid until %s', 'mlab-seo' ), esc_html( $data['expires'] ) );
							}
							?>
						<?php else : ?>
							<span class="mlab-seo-badge is-bad"><?php esc_html_e( 'Not active', 'mlab-seo' ); ?></span> <?php echo esc_html( $data['message'] ); ?>
						<?php endif; ?>
					</p>
					<p><?php submit_button( __( 'Remove licence key', 'mlab-seo' ), 'secondary', 'mlab_seo_license_remove', false ); ?></p>
				<?php else : ?>
					<p><label for="mlab-seo-license-key" class="screen-reader-text"><?php esc_html_e( 'Licence key', 'mlab-seo' ); ?></label>
						<input type="text" class="regular-text code" id="mlab-seo-license-key" name="mlab_seo_license_key" autocomplete="off" placeholder="XXXX-XXXX-XXXX-XXXX" />
						<?php submit_button( __( 'Activate', 'mlab-seo' ), 'primary', 'submit', false ); ?></p>
					<p class="description">
						<?php esc_html_e( 'No licence yet?', 'mlab-seo' ); ?>
						<a href="https://momentumlab.ch/" target="_blank" rel="noopener"><?php esc_html_e( 'Get MomentumLab SEO Pro', 'mlab-seo' ); ?></a>
					</p>
				<?php endif; ?>
			</form>
		<?php endif; ?>
		<?php
	}
}
