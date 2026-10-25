<?php
/**
 * Plugin Name:       MomentumLab SEO
 * Plugin URI:        https://momentumlab.ch/
 * Description:       Search engine and AI optimisation: titles, meta descriptions, Google preview with SEO check, social previews, schema.org, sitemap, robots.txt and llms.txt for AI assistants.
 * Version:           1.6.0
 * Requires at least: 6.5
 * Requires PHP:      8.0
 * Author:            MomentumLab
 * Author URI:        https://momentumlab.ch/
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       mlab-seo
 * Domain Path:       /languages
 * Update URI:        https://github.com/momentumlab-ch/mlab-seo
 *
 * @package MomentumLab\SEO
 */

defined( 'ABSPATH' ) || exit;

define( 'MLAB_SEO_VERSION', '1.6.0' );
define( 'MLAB_SEO_FILE', __FILE__ );
define( 'MLAB_SEO_DIR', plugin_dir_path( __FILE__ ) );
define( 'MLAB_SEO_URL', plugin_dir_url( __FILE__ ) );

require_once MLAB_SEO_DIR . 'inc/options.php';
require_once MLAB_SEO_DIR . 'inc/class-settings.php';
require_once MLAB_SEO_DIR . 'inc/class-license.php';
require_once MLAB_SEO_DIR . 'inc/class-post-meta.php';
require_once MLAB_SEO_DIR . 'inc/class-term-meta.php';
require_once MLAB_SEO_DIR . 'inc/class-titles.php';
require_once MLAB_SEO_DIR . 'inc/class-context.php';
require_once MLAB_SEO_DIR . 'inc/class-head.php';
require_once MLAB_SEO_DIR . 'inc/class-schema.php';
require_once MLAB_SEO_DIR . 'inc/class-breadcrumbs.php';
require_once MLAB_SEO_DIR . 'inc/class-crawlers.php';
require_once MLAB_SEO_DIR . 'inc/class-sitemap.php';
require_once MLAB_SEO_DIR . 'inc/class-redirects.php';
require_once MLAB_SEO_DIR . 'inc/class-link-check.php';
require_once MLAB_SEO_DIR . 'inc/class-indexnow.php';
require_once MLAB_SEO_DIR . 'inc/class-import.php';
require_once MLAB_SEO_DIR . 'inc/class-redirect-import.php';
require_once MLAB_SEO_DIR . 'inc/class-woocommerce.php';
require_once MLAB_SEO_DIR . 'inc/class-content-filters.php';
require_once MLAB_SEO_DIR . 'inc/class-faq.php';
require_once MLAB_SEO_DIR . 'inc/class-media.php';
require_once MLAB_SEO_DIR . 'inc/class-hreflang.php';
require_once MLAB_SEO_DIR . 'inc/class-content-audit.php';
require_once MLAB_SEO_DIR . 'inc/class-fields.php';
require_once MLAB_SEO_DIR . 'inc/class-meta-box.php';
require_once MLAB_SEO_DIR . 'inc/class-quick-edit.php';
require_once MLAB_SEO_DIR . 'inc/class-admin.php';
require_once MLAB_SEO_DIR . 'inc/class-plugin.php';
require_once MLAB_SEO_DIR . 'inc/template-tags.php';
require_once MLAB_SEO_DIR . 'inc/updater.php';

\MomentumLab\SEO\Plugin::instance();

register_activation_hook( __FILE__, array( '\MomentumLab\SEO\Plugin', 'activate' ) );
register_deactivation_hook( __FILE__, array( '\MomentumLab\SEO\Plugin', 'deactivate' ) );
