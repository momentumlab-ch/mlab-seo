<?php
/**
 * SEO box, Google tab: preview, title, description, focus keyword, SEO check.
 *
 * @package MomentumLab\SEO
 */

namespace MomentumLab\SEO;

defined( 'ABSPATH' ) || exit;
// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- Included inside Meta_Box::render(), the variables are local.
?>
<div class="mlab-seo__panel is-active" data-panel="seo" role="tabpanel">
	<p class="mlab-seo__label"><?php esc_html_e( 'Google preview', 'mlab-seo' ); ?></p>
	<div class="mlab-seo__serp" aria-live="polite" data-crumbs="<?php echo esc_attr( (string) wp_json_encode( $crumbs ) ); ?>" data-date="<?php echo esc_attr( $date ); ?>">
		<div class="mlab-seo__serp-site"><span class="mlab-seo__serp-name"><?php echo esc_html( Settings::site_name() ); ?></span><span class="mlab-seo__serp-url"><?php echo esc_html( $fallback['url'] ); ?></span></div>
		<div class="mlab-seo__serp-title"></div>
		<div class="mlab-seo__serp-desc"><span class="mlab-seo__serp-date"></span><span class="mlab-seo__serp-text"></span></div>
		<div class="mlab-seo__serp-rich"></div>
	</div>
	<p class="description mlab-seo__rich-note" hidden></p>

	<p>
		<label for="mlab-seo-seo-title" class="mlab-seo__label"><?php esc_html_e( 'SEO title', 'mlab-seo' ); ?></label>
		<input type="text" class="widefat" id="mlab-seo-seo-title" name="mlab_seo[title]" value="<?php echo esc_attr( $title ); ?>" placeholder="<?php echo esc_attr( '' === $title ? $fallback['title'] : '' ); ?>" />
		<span class="mlab-seo__meter" data-for="title"><span></span></span>
		<span class="mlab-seo__hint" data-hint="title"></span>
	</p>
	<p>
		<label for="mlab-seo-description" class="mlab-seo__label"><?php esc_html_e( 'Meta description', 'mlab-seo' ); ?></label>
		<textarea class="widefat" rows="3" id="mlab-seo-description" name="mlab_seo[description]" placeholder="<?php echo esc_attr( '' === $desc ? $fallback['description'] : '' ); ?>"><?php echo esc_textarea( $desc ); ?></textarea>
		<span class="mlab-seo__meter" data-for="description"><span></span></span>
		<span class="mlab-seo__hint" data-hint="description"></span>
	</p>
	<p>
		<label for="mlab-seo-focus" class="mlab-seo__label"><?php esc_html_e( 'Focus keyword', 'mlab-seo' ); ?></label>
		<input type="text" class="widefat" id="mlab-seo-focus" name="mlab_seo[focus]" value="<?php echo esc_attr( $focus ); ?>" placeholder="<?php esc_attr_e( 'The search term this page should be found for', 'mlab-seo' ); ?>" />
	</p>
	<p class="mlab-seo__label"><?php esc_html_e( 'SEO check', 'mlab-seo' ); ?></p>
	<ul class="mlab-seo__checks"></ul>
	<input type="hidden" name="mlab_seo[score]" id="mlab-seo-score" value="<?php echo esc_attr( (string) Post_Meta::get( $id, 'score' ) ); ?>" />
</div>
