<?php
/**
 * Access check below the AI DESC crawlers tab.
 *
 * @package MomentumLab\SEO
 */

namespace MomentumLab\SEO;

defined( 'ABSPATH' ) || exit;
// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- Included inside Admin::part(), the variables are local.


$check = get_transient( Admin::CHECK_TRANSIENT );
?>
<h2><?php esc_html_e( 'Access check', 'mlab-seo' ); ?></h2>
<p><?php esc_html_e( 'Requests the home page as Google, Bing, ChatGPT, Claude and Perplexity. Shows whether a firewall on the server blocks them, which robots.txt cannot fix.', 'mlab-seo' ); ?></p>
<?php if ( is_array( $check ) ) : ?>
	<table class="widefat striped mlab-seo-bots">
		<tbody>
			<?php foreach ( $check[1] as $bot => $code ) : ?>
				<tr><td><code><?php echo esc_html( $bot ); ?></code></td><td><span class="mlab-seo-badge <?php echo 200 === $code ? 'is-good' : 'is-bad'; ?>"><?php echo esc_html( (string) $code ); ?></span></td></tr>
			<?php endforeach; ?>
		</tbody>
	</table>
	<p class="description">
		<?php
		/* translators: %s: date and time. */
		printf( esc_html__( 'Last check: %s', 'mlab-seo' ), esc_html( wp_date( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), (int) $check[0] ) ) );
		?>
	</p>
<?php endif; ?>
<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
	<input type="hidden" name="action" value="mlab_seo_check" />
	<?php wp_nonce_field( 'mlab_seo_check' ); ?>
	<?php submit_button( __( 'Check access now', 'mlab-seo' ), 'secondary', 'submit', false ); ?>
</form>
