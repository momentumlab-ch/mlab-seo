<?php
/**
 * Licence tab.
 *
 * @package MomentumLab\SEO
 * @var bool $pro Pro active.
 */

namespace MomentumLab\SEO;

defined( 'ABSPATH' ) || exit;
// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- Included inside Admin::part(), the variables are local.

$via_filter = $pro && ! mlab_license()->unlocked( License::PRODUCT );
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
	<?php mlab_license()->render_box( License::PRODUCT ); ?>
<?php endif; ?>
