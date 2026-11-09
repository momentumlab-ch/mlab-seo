<?php
/**
 * AI and crawlers: IndexNow with key file and last submission.
 *
 * @package MomentumLab\SEO
 * @var array $s   Settings.
 * @var bool  $pro Pro active.
 */

namespace MomentumLab\SEO;

defined( 'ABSPATH' ) || exit;
// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- Included inside Admin::part(), the variables are local.

?>
<p class="mlab-seo-hint"><?php esc_html_e( 'Tells Bing, Yandex, Seznam and Naver about new and changed pages right after saving, instead of waiting for the next crawl. Bing also feeds ChatGPT search and Copilot.', 'mlab-seo' ); ?></p>
<table class="form-table" role="presentation">
	<?php Fields::row( 'indexnow' ); ?>
	<?php if ( $s['indexnow'] && '' !== $s['indexnow_key'] ) : ?>
		<tr><th scope="row"><?php esc_html_e( 'Key file', 'mlab-seo' ); ?></th><td>
			<a href="<?php echo esc_url( IndexNow::key_url() ); ?>" target="_blank" rel="noopener"><code><?php echo esc_html( $s['indexnow_key'] ); ?>.txt</code></a>
			<?php
			$last = get_option( IndexNow::LAST );
			if ( ! IndexNow::active() ) {
				echo '<p class="description">' . esc_html__( 'Paused: submissions only run on a public production website.', 'mlab-seo' ) . '</p>';
			} elseif ( is_array( $last ) ) {
				/* translators: 1: date and time, 2: number of addresses. */
				echo '<p class="description">' . esc_html( sprintf( __( 'Last submission: %1$s (%2$d addresses)', 'mlab-seo' ), wp_date( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), (int) $last['time'] ), count( (array) $last['urls'] ) ) ) . '</p>';
			}
			?>
		</td></tr>
	<?php endif; ?>
</table>
