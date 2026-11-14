<?php
/**
 * Quick setup, step 4: what is still open, where to see results, finish.
 *
 * @package MomentumLab\SEO
 */

namespace MomentumLab\SEO;

defined( 'ABSPATH' ) || exit;
// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- Included inside Admin::part(), the variables are local.

$open = array_slice( array_filter( Dashboard::checks(), fn ( $item ) => 'good' !== $item['state'] && Admin::url( 'mlab-seo-setup' ) !== ( $item['url'] ?? '' ) ), 0, 6 );
?>
<p><?php esc_html_e( 'The basics are set. This is what is still open:', 'mlab-seo' ); ?></p>
<div class="mlab-seo-group">
	<?php if ( ! $open ) : ?>
		<div class="mlab-seo-row"><span class="mlab-seo-dot is-good"></span><div class="mlab-seo-row__label"><?php esc_html_e( 'Nothing open. Everything is in order.', 'mlab-seo' ); ?></div></div>
	<?php endif; ?>
	<?php foreach ( $open as $item ) : ?>
		<div class="mlab-seo-row">
			<span class="mlab-seo-dot is-<?php echo esc_attr( $item['state'] ); ?>"></span>
			<div class="mlab-seo-row__label"><?php echo esc_html( $item['text'] ); ?></div>
			<?php if ( isset( $item['url'] ) ) : ?>
				<a class="mlab-seo-btn" href="<?php echo esc_url( $item['url'] ); ?>"><?php echo esc_html( $item['action'] ); ?></a>
			<?php endif; ?>
		</div>
	<?php endforeach; ?>
	<div class="mlab-seo-row">
		<span class="mlab-seo-dot is-ok"></span>
		<div class="mlab-seo-row__label"><?php esc_html_e( 'Submit the sitemap once in Google Search Console. Google has no automatic way for this any more.', 'mlab-seo' ); ?></div>
		<a class="mlab-seo-btn" href="https://search.google.com/search-console/sitemaps" target="_blank" rel="noopener"><?php esc_html_e( 'Search Console', 'mlab-seo' ); ?> ↗</a>
	</div>
</div>
<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
	<input type="hidden" name="action" value="mlab_seo_setup_done" />
	<?php wp_nonce_field( 'mlab_seo_setup_done' ); ?>
	<p class="mlab-seo-submit"><?php submit_button( __( 'Finish setup', 'mlab-seo' ), 'primary', 'submit', false ); ?></p>
</form>
