<?php
/**
 * The SEO box in the post editor.
 *
 * @package MomentumLab\SEO
 */

namespace MomentumLab\SEO;

defined( 'ABSPATH' ) || exit;
// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- Included inside Meta_Box::render(), the variables are local.
?>
<div class="mlab-seo" data-front="<?php echo esc_attr( (string) (int) Context::is_front( $id ) ); ?>">
	<div class="mlab-seo__tabs" role="tablist">
		<button type="button" role="tab" class="mlab-seo__tab is-active" aria-selected="true" data-tab="seo"><?php esc_html_e( 'Google', 'mlab-seo' ); ?> <span class="mlab-seo__score" aria-hidden="true"></span></button>
		<button type="button" role="tab" class="mlab-seo__tab" aria-selected="false" data-tab="social"><?php esc_html_e( 'Social media', 'mlab-seo' ); ?></button>
		<button type="button" role="tab" class="mlab-seo__tab" aria-selected="false" data-tab="advanced"><?php esc_html_e( 'Advanced', 'mlab-seo' ); ?></button>
		<button type="button" role="tab" class="mlab-seo__tab" aria-selected="false" data-tab="schema"><?php esc_html_e( 'Event & schema', 'mlab-seo' ); ?> <span class="mlab-seo__pro">Pro</span></button>
	</div>
	<?php require __DIR__ . '/panel-seo.php'; ?>
	<?php require __DIR__ . '/panel-social.php'; ?>
	<?php require __DIR__ . '/panel-advanced.php'; ?>
	<?php require __DIR__ . '/panel-schema.php'; ?>
</div>
