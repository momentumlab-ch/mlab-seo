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
