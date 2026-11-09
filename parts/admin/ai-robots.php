<?php
/**
 * AI and crawlers: own robots.txt rules with a live preview of the whole file.
 *
 * @package MomentumLab\SEO
 * @var array $s   Settings.
 * @var bool  $pro Pro active.
 */

namespace MomentumLab\SEO;

defined( 'ABSPATH' ) || exit;
// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- Included inside Admin::part(), the variables are local.

?>
<div class="mlab-seo-grid is-2">
	<div>
		<table class="form-table" role="presentation">
			<?php Fields::row( 'robots_extra' ); ?>
		</table>
		<p class="mlab-seo-hint"><?php esc_html_e( 'The rules for AI crawlers and the sitemaps are added by the plugin itself.', 'mlab-seo' ); ?></p>
	</div>
	<div>
		<div class="mlab-seo-preview-head"><b><?php esc_html_e( 'This is robots.txt', 'mlab-seo' ); ?></b> <a href="<?php echo esc_url( home_url( '/robots.txt' ) ); ?>" target="_blank" rel="noopener"><?php esc_html_e( 'Open', 'mlab-seo' ); ?> ↗</a></div>
		<pre class="mlab-seo-code" data-preview="<?php echo esc_url( home_url( '/robots.txt' ) ); ?>"><?php esc_html_e( 'Loading …', 'mlab-seo' ); ?></pre>
	</div>
</div>
