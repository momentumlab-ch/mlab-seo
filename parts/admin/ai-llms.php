<?php
/**
 * AI and crawlers: llms.txt and llms-full.txt with a live preview.
 *
 * @package MomentumLab\SEO
 * @var array $s   Settings.
 * @var bool  $pro Pro active.
 */

namespace MomentumLab\SEO;

defined( 'ABSPATH' ) || exit;
// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- Included inside Admin::part(), the variables are local.

?>
<p class="mlab-seo-hint"><?php esc_html_e( 'A short Markdown summary of the website for AI assistants (llmstxt.org). The page list is built automatically from the SEO descriptions.', 'mlab-seo' ); ?></p>
<div class="mlab-seo-grid is-2">
	<div>
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
	</div>
	<div>
		<div class="mlab-seo-preview-head"><b><?php esc_html_e( 'This is llms.txt', 'mlab-seo' ); ?></b> <a href="<?php echo esc_url( Crawlers::llms_url() ); ?>" target="_blank" rel="noopener"><?php esc_html_e( 'Open', 'mlab-seo' ); ?> ↗</a></div>
		<pre class="mlab-seo-code" data-preview="<?php echo esc_url( Crawlers::llms_url() ); ?>"><?php esc_html_e( 'Loading …', 'mlab-seo' ); ?></pre>
	</div>
</div>
