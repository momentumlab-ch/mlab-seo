<?php
/**
 * Plugin Name:       MomentumLab SEO
 * Plugin URI:        https://momentumlab.ch/
 * Description:       Search engine and AI optimisation: titles, meta descriptions, Google preview with SEO check, social previews, schema.org, sitemap, robots.txt and llms.txt for AI assistants.
 * Version:           1.5.0
 * Requires at least: 6.5
 * Requires PHP:      8.0
 * Author:            MomentumLab
 * Author URI:        https://momentumlab.ch/
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       mlab-seo
 * Domain Path:       /languages
 * Update URI:        https://github.com/momentumlab-ch/wp-mlab-seo
 *
 * @package MomentumLab\SEO
 */

defined( 'ABSPATH' ) || exit;

define( 'MLAB_SEO_VERSION', '1.5.0' );
define( 'MLAB_SEO_FILE', __FILE__ );
define( 'MLAB_SEO_DIR', plugin_dir_path( __FILE__ ) );
define( 'MLAB_SEO_URL', plugin_dir_url( __FILE__ ) );

require_once MLAB_SEO_DIR . 'includes/class-settings.php';
require_once MLAB_SEO_DIR . 'includes/class-license.php';
require_once MLAB_SEO_DIR . 'includes/class-post-meta.php';
require_once MLAB_SEO_DIR . 'includes/class-term-meta.php';
require_once MLAB_SEO_DIR . 'includes/class-context.php';
require_once MLAB_SEO_DIR . 'includes/class-head.php';
require_once MLAB_SEO_DIR . 'includes/class-schema.php';
require_once MLAB_SEO_DIR . 'includes/class-breadcrumbs.php';
require_once MLAB_SEO_DIR . 'includes/class-crawlers.php';
require_once MLAB_SEO_DIR . 'includes/class-sitemap.php';
require_once MLAB_SEO_DIR . 'includes/class-redirects.php';
require_once MLAB_SEO_DIR . 'includes/class-link-check.php';
require_once MLAB_SEO_DIR . 'includes/class-indexnow.php';
require_once MLAB_SEO_DIR . 'includes/class-import.php';
require_once MLAB_SEO_DIR . 'includes/class-faq.php';
require_once MLAB_SEO_DIR . 'includes/class-media.php';
require_once MLAB_SEO_DIR . 'includes/class-hreflang.php';
require_once MLAB_SEO_DIR . 'includes/class-content-audit.php';
require_once MLAB_SEO_DIR . 'includes/class-meta-box.php';
require_once MLAB_SEO_DIR . 'includes/class-admin.php';
require_once MLAB_SEO_DIR . 'includes/class-updater.php';
require_once MLAB_SEO_DIR . 'includes/class-plugin.php';

\MomentumLab\SEO\Plugin::instance();

if ( ! function_exists( 'mlab_seo_breadcrumbs' ) ) {
	/**
	 * Prints the breadcrumb navigation, for use in theme templates.
	 *
	 * @param array $args Optional: separator.
	 */
	function mlab_seo_breadcrumbs( $args = array() ) {
		echo \MomentumLab\SEO\Breadcrumbs::render( $args ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped in render().
	}
}

register_activation_hook( __FILE__, array( '\MomentumLab\SEO\Plugin', 'activate' ) );
register_deactivation_hook( __FILE__, array( '\MomentumLab\SEO\Plugin', 'deactivate' ) );
