<?php
/**
 * AI and crawlers: which AI crawlers may read the website, with presets.
 *
 * @package MomentumLab\SEO
 * @var array $s   Settings.
 * @var bool  $pro Pro active.
 */

namespace MomentumLab\SEO;

defined( 'ABSPATH' ) || exit;
// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- Included inside Admin::part(), the variables are local.

?>
<p class="mlab-seo-hint"><?php esc_html_e( 'Allowed crawlers may read the website, so assistants like ChatGPT, Claude or Perplexity can answer questions about it and link to it. The rules go into robots.txt.', 'mlab-seo' ); ?></p>
<div class="mlab-seo-group">
	<div class="mlab-seo-row">
		<div class="mlab-seo-row__label"><?php esc_html_e( 'Quick choice', 'mlab-seo' ); ?><small><?php esc_html_e( '"Search only" allows AI search with a source link but blocks training.', 'mlab-seo' ); ?></small></div>
		<div class="mlab-seo-pills" role="group" aria-label="<?php esc_attr_e( 'Quick choice', 'mlab-seo' ); ?>">
			<button type="button" data-bots="all"><?php esc_html_e( 'All', 'mlab-seo' ); ?></button>
			<button type="button" data-bots="search"><?php esc_html_e( 'Search only', 'mlab-seo' ); ?></button>
			<button type="button" data-bots="none"><?php esc_html_e( 'None', 'mlab-seo' ); ?></button>
		</div>
	</div>
	<?php foreach ( Settings::AI_BOTS as $bot => $purpose ) : ?>
		<div class="mlab-seo-row">
			<div class="mlab-seo-row__label"><label for="mlab-seo-bot-<?php echo esc_attr( $bot ); ?>"><code><?php echo esc_html( $bot ); ?></code></label><small><?php echo esc_html( $purpose ); ?></small></div>
			<span class="mlab-seo-switch"><input type="checkbox" id="mlab-seo-bot-<?php echo esc_attr( $bot ); ?>" name="mlab_seo[ai_bots][<?php echo esc_attr( $bot ); ?>]" value="1" data-search="<?php echo in_array( $bot, Settings::AI_SEARCH_BOTS, true ) ? '1' : '0'; ?>" <?php checked( ! empty( $s['ai_bots'][ $bot ] ) ); ?> /><span></span></span>
		</div>
	<?php endforeach; ?>
</div>
