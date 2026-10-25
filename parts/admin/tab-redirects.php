<?php
/**
 * Redirects tab: rules and 404 log (Pro).
 *
 * @package MomentumLab\SEO
 * @var bool $pro Pro active.
 */

namespace MomentumLab\SEO;

defined( 'ABSPATH' ) || exit;
// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- Included inside Admin::part(), the variables are local.

Redirects::tidy_log();
$rules = Redirects::rules();
$log   = Redirects::log();
?>
<p><?php esc_html_e( 'Send visitors and search engines from old addresses to the new page, so links and rankings are kept.', 'mlab-seo' ); ?></p>
<?php if ( ! $pro ) : ?>
	<div class="mlab-seo__locked"><p><strong><?php esc_html_e( 'Redirects and the 404 log are Pro features.', 'mlab-seo' ); ?></strong></p><p><a class="button button-primary" href="#license" data-tab-link="license"><?php esc_html_e( 'Activate Pro', 'mlab-seo' ); ?></a></p></div>
<?php endif; ?>
<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
	<input type="hidden" name="action" value="mlab_seo_redirects" />
	<?php wp_nonce_field( 'mlab_seo_redirects' ); ?>
	<fieldset <?php disabled( ! $pro ); ?>>
		<p><label><input type="checkbox" name="mlab_seo_auto_redirect" value="1" <?php checked( (bool) Settings::get( 'auto_redirect' ) ); ?> /> <?php esc_html_e( 'Add a redirect automatically when the address of a published page changes', 'mlab-seo' ); ?></label></p>
		<table class="widefat striped mlab-seo-redirects">
			<thead><tr><th><?php esc_html_e( 'Old address', 'mlab-seo' ); ?></th><th><?php esc_html_e( 'Target', 'mlab-seo' ); ?></th><th><?php esc_html_e( 'Type', 'mlab-seo' ); ?></th><th><?php esc_html_e( 'Pattern', 'mlab-seo' ); ?></th><th><?php esc_html_e( 'Hits', 'mlab-seo' ); ?></th><th><span class="screen-reader-text"><?php esc_html_e( 'Remove', 'mlab-seo' ); ?></span></th></tr></thead>
			<tbody>
				<?php foreach ( array_merge( $rules, array( array() ) ) as $i => $rule ) : ?>
					<tr>
						<td><input type="text" class="widefat" name="mlab_seo_redirects[<?php echo (int) $i; ?>][from]" value="<?php echo esc_attr( $rule['from'] ?? '' ); ?>" placeholder="/alte-seite" aria-label="<?php esc_attr_e( 'Old address', 'mlab-seo' ); ?>" /></td>
						<td><input type="text" class="widefat" name="mlab_seo_redirects[<?php echo (int) $i; ?>][to]" value="<?php echo esc_attr( $rule['to'] ?? '' ); ?>" placeholder="/neue-seite/" aria-label="<?php esc_attr_e( 'Target', 'mlab-seo' ); ?>" /></td>
						<td><select name="mlab_seo_redirects[<?php echo (int) $i; ?>][code]" aria-label="<?php esc_attr_e( 'Type', 'mlab-seo' ); ?>">
							<option value="301" <?php selected( (int) ( $rule['code'] ?? 301 ), 301 ); ?>><?php esc_html_e( '301 permanent', 'mlab-seo' ); ?></option>
							<option value="302" <?php selected( (int) ( $rule['code'] ?? 301 ), 302 ); ?>><?php esc_html_e( '302 temporary', 'mlab-seo' ); ?></option>
							<option value="410" <?php selected( (int) ( $rule['code'] ?? 301 ), 410 ); ?>><?php esc_html_e( '410 deleted', 'mlab-seo' ); ?></option>
						</select></td>
						<td><label><input type="checkbox" name="mlab_seo_redirects[<?php echo (int) $i; ?>][regex]" value="1" <?php checked( ! empty( $rule['regex'] ) ); ?> /> <?php esc_html_e( 'Regex', 'mlab-seo' ); ?></label></td>
						<td><?php echo (int) ( $rule['hits'] ?? 0 ); ?></td>
						<td><button type="button" class="button-link mlab-seo-row-remove" aria-label="<?php esc_attr_e( 'Remove', 'mlab-seo' ); ?>">×</button></td>
					</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
		<p class="description"><?php esc_html_e( 'Regex: the old address is a regular expression, for example ^/blog/(.*)$ with the target /news/$1.', 'mlab-seo' ); ?></p>
		<p><button type="button" class="button mlab-seo-row-add"><?php esc_html_e( 'Add redirect', 'mlab-seo' ); ?></button> <?php submit_button( __( 'Save redirects', 'mlab-seo' ), 'primary', 'submit', false ); ?></p>

		<h2><?php esc_html_e( 'Pages not found (404)', 'mlab-seo' ); ?></h2>
		<?php if ( $log ) : ?>
			<p class="description"><?php esc_html_e( 'Addresses that led nowhere, with a suggested target. Check the target and click "Redirect": the redirect is added and the address leaves the list. "Ignore" stops logging an address, for example for bot requests.', 'mlab-seo' ); ?></p>
			<table class="widefat striped mlab-seo-404">
				<thead><tr><th><?php esc_html_e( 'Address', 'mlab-seo' ); ?></th><th><?php esc_html_e( 'Requests', 'mlab-seo' ); ?></th><th><?php esc_html_e( 'Last', 'mlab-seo' ); ?></th><th><?php esc_html_e( 'Redirect to', 'mlab-seo' ); ?></th><th></th></tr></thead>
				<tbody>
					<?php foreach ( array_slice( $log, 0, 30, true ) as $log_path => $row ) : ?>
						<?php
						$ref = (string) ( $row['ref'] ?? '' );
						?>
						<tr>
							<td><code><?php echo esc_html( $log_path ); ?></code>
							<?php if ( '' !== $ref ) : ?>
								<br /><span class="description">
								<?php
								/* translators: %s: page or website the visitor came from */
								echo esc_html( sprintf( str_starts_with( $ref, '/' ) ? __( 'Broken link on %s', 'mlab-seo' ) : __( 'Link from %s', 'mlab-seo' ), $ref ) );
								?>
								</span>
							<?php endif; ?>
							</td>
							<td><?php echo (int) $row['count']; ?></td>
							<td><?php echo esc_html( wp_date( get_option( 'date_format' ), (int) $row['last'] ) ); ?></td>
							<td><input type="text" class="widefat" name="mlab_seo_404_target[<?php echo esc_attr( md5( $log_path ) ); ?>]" value="<?php echo esc_attr( Redirects::suggest( $log_path ) ); ?>" placeholder="/neue-seite/" aria-label="<?php esc_attr_e( 'Redirect to', 'mlab-seo' ); ?>" /></td>
							<td class="mlab-seo-404__actions"><button type="submit" class="button button-small" name="mlab_seo_404_fix" value="<?php echo esc_attr( $log_path ); ?>"><?php esc_html_e( 'Redirect', 'mlab-seo' ); ?></button> <button type="submit" class="button-link" name="mlab_seo_404_ignore" value="<?php echo esc_attr( $log_path ); ?>"><?php esc_html_e( 'Ignore', 'mlab-seo' ); ?></button></td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
			<p><label><input type="checkbox" name="mlab_seo_clear_log" value="1" /> <?php esc_html_e( 'Clear the log when saving', 'mlab-seo' ); ?></label></p>
		<?php else : ?>
			<p class="description"><?php esc_html_e( 'No missing pages recorded yet.', 'mlab-seo' ); ?></p>
		<?php endif; ?>
	</fieldset>
</form>
