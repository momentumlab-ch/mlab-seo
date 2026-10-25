<?php
/**
 * AI DESC crawlers tab: AI crawlers, robots.txt, IndexNow, llms.txt.
 *
 * @package MomentumLab\SEO
 */

namespace MomentumLab\SEO;

defined( 'ABSPATH' ) || exit;
// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- Included inside Admin::part(), the variables are local.

?>
<h2><?php esc_html_e( 'AI crawlers', 'mlab-seo' ); ?></h2>
<p><?php esc_html_e( 'Allowed crawlers may read the website, so assistants like ChatGPT, Claude or Perplexity can answer questions about it and link to it. The rules go into robots.txt.', 'mlab-seo' ); ?></p>
<table class="widefat striped mlab-seo-bots">
	<thead><tr><th><?php esc_html_e( 'Allow', 'mlab-seo' ); ?></th><th><?php esc_html_e( 'Crawler', 'mlab-seo' ); ?></th><th><?php esc_html_e( 'Operator and purpose', 'mlab-seo' ); ?></th></tr></thead>
	<tbody>
		<?php foreach ( Settings::AI_BOTS as $bot => $purpose ) : ?>
			<tr>
				<td><input type="checkbox" id="mlab-seo-bot-<?php echo esc_attr( $bot ); ?>" name="mlab_seo[ai_bots][<?php echo esc_attr( $bot ); ?>]" value="1" <?php checked( ! empty( $s['ai_bots'][ $bot ] ) ); ?> /></td>
				<td><label for="mlab-seo-bot-<?php echo esc_attr( $bot ); ?>"><code><?php echo esc_html( $bot ); ?></code></label></td>
				<td><?php echo esc_html( $purpose ); ?></td>
			</tr>
		<?php endforeach; ?>
	</tbody>
</table>
<table class="form-table" role="presentation">
	<?php Fields::row( 'robots_extra' ); ?>
</table>

<h2><?php esc_html_e( 'IndexNow', 'mlab-seo' ); ?></h2>
<p><?php esc_html_e( 'Tells Bing, Yandex, Seznam and Naver about new and changed pages right after saving, instead of waiting for the next crawl. Bing also feeds ChatGPT search and Copilot.', 'mlab-seo' ); ?></p>
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

<h2><?php esc_html_e( 'llms.txt', 'mlab-seo' ); ?></h2>
<p><?php esc_html_e( 'A short Markdown summary of the website for AI assistants (llmstxt.org). The page list is built automatically from the SEO descriptions.', 'mlab-seo' ); ?></p>
<table class="form-table" role="presentation">
	<?php
	Fields::rows( array( 'llms_enabled', 'llms_summary', 'llms_facts' ) );
	Fields::row(
		'llms_full',
		array(
			'pro'      => true,
			'disabled' => ! $pro,
		)
	);
	?>
</table>
