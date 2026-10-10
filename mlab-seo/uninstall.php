<?php
/**
 * Uninstall: removes settings, licence, redirects, the 404 log and all SEO fields.
 *
 * @package MomentumLab\SEO
 */

defined( 'WP_UNINSTALL_PLUGIN' ) || exit;

/**
 * Set MLAB_SEO_KEEP_DATA to true in wp-config.php to keep all SEO data on uninstall.
 */
if ( defined( 'MLAB_SEO_KEEP_DATA' ) && MLAB_SEO_KEEP_DATA ) {
	return;
}

foreach ( array( 'mlab_seo_settings', 'mlab_seo_license', 'mlab_seo_redirects', 'mlab_seo_404_log', 'mlab_seo_version' ) as $mlab_seo_option ) {
	delete_option( $mlab_seo_option );
}
delete_transient( 'mlab_seo_llms_full' );
delete_transient( 'mlab_seo_access_check' );
wp_clear_scheduled_hook( 'mlab_seo_license_check' );
foreach ( array( 'title', 'description', 'focus', 'image', 'noindex', 'nofollow', 'canonical', 'page_type', 'llms', 'schema', 'score', 'event' ) as $mlab_seo_field ) {
	delete_post_meta_by_key( '_mlab_seo_' . $mlab_seo_field );
}
