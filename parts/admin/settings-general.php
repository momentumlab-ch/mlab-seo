<?php
/**
 * Settings, general: links, images, feed, breadcrumbs and technical options.
 *
 * @package MomentumLab\SEO
 * @var array $s   Settings.
 * @var bool  $pro Pro active.
 */

namespace MomentumLab\SEO;

defined( 'ABSPATH' ) || exit;
// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- Included inside Admin::part(), the variables are local.

?>
<table class="form-table" role="presentation">
	<?php Fields::rows( array( 'external_nofollow', 'external_blank', 'image_alt', 'rss_source' ) ); ?>
</table>
<h2 class="mlab-seo-section"><?php esc_html_e( 'Breadcrumbs', 'mlab-seo' ); ?></h2>
<table class="form-table" role="presentation">
	<?php Fields::rows( array( 'breadcrumb_home', 'breadcrumb_sep' ) ); ?>
	<tr><th scope="row"><?php esc_html_e( 'In the theme', 'mlab-seo' ); ?></th><td><code>[mlab_seo_breadcrumbs]</code> <code>mlab_seo_breadcrumbs()</code></td></tr>
</table>
<h2 class="mlab-seo-section"><?php esc_html_e( 'Technical', 'mlab-seo' ); ?></h2>
<table class="form-table" role="presentation">
	<?php Fields::rows( array( 'remove_generator', 'attachment_pages', 'woo_schema' ) ); ?>
</table>
