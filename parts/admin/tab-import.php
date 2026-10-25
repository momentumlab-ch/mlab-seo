<?php
/**
 * Import tab.
 *
 * @package MomentumLab\SEO
 */

namespace MomentumLab\SEO;

defined( 'ABSPATH' ) || exit;
// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- Included inside Admin::part(), the variables are local.

$counts          = Import::detect();
$redirect_counts = Redirect_Import::detect();
?>
<p><?php esc_html_e( 'Switching from another SEO plugin? Take over SEO titles, meta descriptions, focus keywords, noindex and nofollow, canonical URLs and social titles, descriptions and images. Categories and tags are included for Yoast SEO, Rank Math and SEOPress.', 'mlab-seo' ); ?></p>
<p>
	<?php
	/* translators: 1: variable as written by other plugins, 2: the same variable in MomentumLab SEO. */
	echo esc_html( sprintf( __( 'The data of the other plugin is not changed, so you can repeat the import. Title variables like %1$s are taken over as %2$s; titles that only repeat the default pattern are left empty, so the template under Titles & meta applies.', 'mlab-seo' ), '%%title%%', '%title%' ) );
	?>
</p>
<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
	<input type="hidden" name="action" value="mlab_seo_import" />
	<?php wp_nonce_field( 'mlab_seo_import' ); ?>
	<table class="widefat striped mlab-seo-bots">
		<thead><tr><th><?php esc_html_e( 'Import from', 'mlab-seo' ); ?></th><th><?php esc_html_e( 'Plugin', 'mlab-seo' ); ?></th><th><?php esc_html_e( 'Pages with data', 'mlab-seo' ); ?></th></tr></thead>
		<tbody>
			<?php foreach ( Import::SOURCES as $key => $source ) : ?>
				<tr>
					<td><input type="radio" id="mlab-seo-import-<?php echo esc_attr( $key ); ?>" name="mlab_seo_import_source" value="<?php echo esc_attr( $key ); ?>" <?php disabled( 0 === $counts[ $key ] ); ?> /></td>
					<td><label for="mlab-seo-import-<?php echo esc_attr( $key ); ?>"><?php echo esc_html( $source['label'] ); ?></label></td>
					<td><?php echo $counts[ $key ] ? (int) $counts[ $key ] : '<span aria-hidden="true">–</span><span class="screen-reader-text">' . esc_html__( 'No data found', 'mlab-seo' ) . '</span>'; ?></td>
				</tr>
			<?php endforeach; ?>
		</tbody>
	</table>
	<p><label><input type="checkbox" name="mlab_seo_import_overwrite" value="1" /> <?php esc_html_e( 'Overwrite values already set in MomentumLab SEO', 'mlab-seo' ); ?></label></p>
	<?php submit_button( __( 'Import now', 'mlab-seo' ), 'primary', 'submit', false, array_sum( $counts ) ? array() : array( 'disabled' => 'disabled' ) ); ?>
</form>

<h2><?php esc_html_e( 'Redirects', 'mlab-seo' ); ?></h2>
<p><?php esc_html_e( 'Take over the active redirects of Rank Math or the Redirection plugin. Rules for the same old address are replaced. Redirects work with Pro.', 'mlab-seo' ); ?></p>
<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
	<input type="hidden" name="action" value="mlab_seo_redirect_import" />
	<?php wp_nonce_field( 'mlab_seo_redirect_import' ); ?>
	<table class="widefat striped mlab-seo-bots">
		<thead><tr><th><?php esc_html_e( 'Import from', 'mlab-seo' ); ?></th><th><?php esc_html_e( 'Plugin', 'mlab-seo' ); ?></th><th><?php esc_html_e( 'Redirects', 'mlab-seo' ); ?></th></tr></thead>
		<tbody>
			<?php foreach ( Redirect_Import::SOURCES as $key => $source ) : ?>
				<tr>
					<td><input type="radio" id="mlab-seo-redirect-import-<?php echo esc_attr( $key ); ?>" name="mlab_seo_redirect_source" value="<?php echo esc_attr( $key ); ?>" <?php disabled( 0 === $redirect_counts[ $key ] ); ?> /></td>
					<td><label for="mlab-seo-redirect-import-<?php echo esc_attr( $key ); ?>"><?php echo esc_html( $source['label'] ); ?></label></td>
					<td><?php echo $redirect_counts[ $key ] ? (int) $redirect_counts[ $key ] : '<span aria-hidden="true">–</span><span class="screen-reader-text">' . esc_html__( 'No data found', 'mlab-seo' ) . '</span>'; ?></td>
				</tr>
			<?php endforeach; ?>
		</tbody>
	</table>
	<?php submit_button( __( 'Import redirects', 'mlab-seo' ), 'secondary', 'submit', false, array_sum( $redirect_counts ) ? array() : array( 'disabled' => 'disabled' ) ); ?>
</form>
