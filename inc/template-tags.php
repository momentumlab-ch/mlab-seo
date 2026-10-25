<?php
/**
 * Template functions for themes.
 *
 * @package MomentumLab\SEO
 */

defined( 'ABSPATH' ) || exit;

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
