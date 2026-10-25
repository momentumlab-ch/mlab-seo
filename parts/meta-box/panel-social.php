<?php
/**
 * SEO box, social media tab.
 *
 * @package MomentumLab\SEO
 */

namespace MomentumLab\SEO;

defined( 'ABSPATH' ) || exit;
// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- Included inside Meta_Box::render(), the variables are local.
?>
<div class="mlab-seo__panel" data-panel="social" role="tabpanel" hidden>
	<p class="description"><?php esc_html_e( 'Shown when the page is shared on Facebook, LinkedIn, WhatsApp, X and in messengers. Without an own image the featured image or the default image from the settings is used.', 'mlab-seo' ); ?></p>
	<p>
		<label for="mlab-seo-og-title" class="mlab-seo__label"><?php esc_html_e( 'Social title', 'mlab-seo' ); ?></label>
		<input type="text" class="widefat" id="mlab-seo-og-title" name="mlab_seo[og_title]" value="<?php echo esc_attr( Post_Meta::get( $id, 'og_title' ) ); ?>" placeholder="<?php esc_attr_e( 'Empty: the SEO title', 'mlab-seo' ); ?>" />
	</p>
	<p>
		<label for="mlab-seo-og-desc" class="mlab-seo__label"><?php esc_html_e( 'Social description', 'mlab-seo' ); ?></label>
		<textarea class="widefat" rows="2" id="mlab-seo-og-desc" name="mlab_seo[og_desc]" placeholder="<?php esc_attr_e( 'Empty: the meta description', 'mlab-seo' ); ?>"><?php echo esc_textarea( Post_Meta::get( $id, 'og_desc' ) ); ?></textarea>
	</p>
	<div class="mlab-seo__image">
		<div class="mlab-seo__image-preview"><?php echo $img ? '<img src="' . esc_url( $img['url'] ) . '" alt="" />' : ''; ?></div>
		<input type="hidden" name="mlab_seo[image]" id="mlab-seo-image" value="<?php echo esc_attr( (string) $image ); ?>" />
		<button type="button" class="button mlab-seo__image-pick"><?php esc_html_e( 'Choose image', 'mlab-seo' ); ?></button>
		<button type="button" class="button-link mlab-seo__image-remove" <?php echo $image ? '' : 'hidden'; ?>><?php esc_html_e( 'Remove image', 'mlab-seo' ); ?></button>
		<p class="description"><?php esc_html_e( 'Recommended: 1200 × 630 px.', 'mlab-seo' ); ?></p>
	</div>
</div>
