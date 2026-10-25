<?php
/**
 * Broken links (Pro), below the content check.
 *
 * @package MomentumLab\SEO
 */

namespace MomentumLab\SEO;

defined( 'ABSPATH' ) || exit;
// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- Included inside Admin::part(), the variables are local.

$state    = Link_Check::state();
$problems = array(
	'offline' => __( 'No answer', 'mlab-seo' ),
	'410'     => __( 'Deleted (410)', 'mlab-seo' ),
	'404'     => __( 'Not found (404)', 'mlab-seo' ),
);
?>
<h2><?php esc_html_e( 'Broken links', 'mlab-seo' ); ?><?php echo $pro ? '' : ' <span class="mlab-seo__pro">Pro</span>'; ?></h2>
<p><?php esc_html_e( 'Once a week all links on indexable pages and in the menus are checked in the background. Listed are links to pages that do not exist (404) or were deleted (410), and links to websites that do not answer.', 'mlab-seo' ); ?></p>
<?php if ( ! $pro ) : ?>
	<?php return; ?>
<?php endif; ?>
<?php if ( $state['queue'] ) : ?>
	<p class="description">
		<?php
		/* translators: 1: links checked, 2: links in total. */
		echo esc_html( sprintf( __( 'Check in progress: %1$d of %2$d links.', 'mlab-seo' ), $state['total'] - count( $state['queue'] ), $state['total'] ) );
		?>
	</p>
<?php elseif ( $state['checked'] ) : ?>
	<p class="description">
		<?php
		/* translators: %s: date and time. */
		echo esc_html( sprintf( __( 'Last check: %s', 'mlab-seo' ), wp_date( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), $state['checked'] ) ) );
		?>
	</p>
<?php endif; ?>
<?php $broken = Link_Check::broken(); ?>
<?php if ( $broken ) : ?>
	<table class="widefat striped">
		<thead><tr><th><?php esc_html_e( 'Link', 'mlab-seo' ); ?></th><th><?php esc_html_e( 'Problem', 'mlab-seo' ); ?></th><th><?php esc_html_e( 'On', 'mlab-seo' ); ?></th></tr></thead>
		<tbody>
			<?php foreach ( array_slice( $broken, 0, 50, true ) as $url => $row ) : ?>
				<tr>
					<td><a href="<?php echo esc_url( $url ); ?>" target="_blank" rel="noopener"><code><?php echo esc_html( $url ); ?></code></a></td>
					<td><?php echo esc_html( $problems[ $row['code'] ] ?? __( 'Server error', 'mlab-seo' ) . ' (' . $row['code'] . ')' ); ?></td>
					<td>
						<?php
						$links = array();
						foreach ( $row['pages'] as $page_id ) {
							$links[] = 0 === $page_id
								? '<a href="' . esc_url( admin_url( 'nav-menus.php' ) ) . '">' . esc_html__( 'Menu', 'mlab-seo' ) . '</a>'
								: '<a href="' . esc_url( (string) get_edit_post_link( $page_id ) ) . '">' . esc_html( Context::plain_title( $page_id ) ) . '</a>';
						}
						echo wp_kses_post( implode( ', ', $links ) );
						?>
					</td>
				</tr>
			<?php endforeach; ?>
		</tbody>
	</table>
<?php elseif ( $state['checked'] ) : ?>
	<p><span class="mlab-seo-badge is-good"><?php esc_html_e( 'Good', 'mlab-seo' ); ?></span> <?php esc_html_e( 'No broken links found.', 'mlab-seo' ); ?></p>
<?php endif; ?>
<?php if ( ! $state['queue'] ) : ?>
	<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
		<input type="hidden" name="action" value="mlab_seo_link_check" />
		<?php wp_nonce_field( 'mlab_seo_link_check' ); ?>
		<p><?php submit_button( __( 'Check links now', 'mlab-seo' ), 'secondary', 'submit', false ); ?></p>
	</form>
<?php endif; ?>
