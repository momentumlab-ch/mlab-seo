<?php
/**
 * Updates from GitHub releases (Plugin Update Checker v5).
 *
 * The source is set only here. The "Update URI" header in mlab-seo.php names the same URL.
 *
 * @package MomentumLab\SEO
 */

defined( 'ABSPATH' ) || exit;

require_once dirname( __DIR__ ) . '/lib/plugin-update-checker/plugin-update-checker.php';

use YahnisElsts\PluginUpdateChecker\v5\PucFactory;

add_action( 'init', 'mlab_seo_register_updater' );

/**
 * Registers the update checker. Private repos need the token constant in wp-config.php.
 */
function mlab_seo_register_updater(): void {
	$private = false;
	$token   = defined( 'MLAB_SEO_GITHUB_TOKEN' ) ? (string) constant( 'MLAB_SEO_GITHUB_TOKEN' ) : '';

	if ( $private && '' === $token ) {
		add_action( 'admin_notices', 'mlab_seo_missing_token_notice' );
		return;
	}

	$checker = PucFactory::buildUpdateChecker(
		'https://github.com/momentumlab-ch/wp-mlab-seo/',
		dirname( __DIR__ ) . '/mlab-seo.php',
		'mlab-seo'
	);
	$checker->setBranch( 'main' );
	$checker->addResultFilter( 'mlab_seo_update_icons' );

	$api = $checker->getVcsApi();
	// 2 = Api::REQUIRE_RELEASE_ASSETS: no update without the built ZIP, never GitHub's source code.
	$api->enableReleaseAssets( '/^mlab-seo\.zip$/', 2 );
	// A token also lifts the GitHub limit of 60 requests per hour on shared hosting.
	if ( '' !== $token ) {
		$api->setAuthentication( $token );
	}
}

/**
 * Plugin icons for Dashboard > Updates and the details dialog.
 *
 * @param object|null $info Plugin info from the update checker.
 * @return object|null
 */
function mlab_seo_update_icons( $info ) {
	if ( ! is_object( $info ) ) {
		return $info;
	}
	$base        = plugins_url( 'assets/img/', dirname( __DIR__ ) . '/mlab-seo.php' );
	$info->icons = array(
		'svg' => $base . 'icon.svg',
		'1x'  => $base . 'icon-128x128.png',
		'2x'  => $base . 'icon-256x256.png',
	);
	return $info;
}

/**
 * Admin-only hint, shown when a private repo has no token constant.
 */
function mlab_seo_missing_token_notice(): void {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}
	printf(
		'<div class="notice notice-warning"><p>%s</p></div>',
		esc_html__( 'Updates are not active: the constant MLAB_SEO_GITHUB_TOKEN is missing in wp-config.php.', 'mlab-seo' )
	);
}
