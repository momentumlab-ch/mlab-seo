<?php
/**
 * Settings, verification codes for Search Console, Bing and others.
 *
 * @package MomentumLab\SEO
 * @var array $s   Settings.
 * @var bool  $pro Pro active.
 */

namespace MomentumLab\SEO;

defined( 'ABSPATH' ) || exit;
// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- Included inside Admin::part(), the variables are local.

?>
<p class="mlab-seo-hint"><?php esc_html_e( 'Confirms that you own the website, for example in Google Search Console. Paste the code or the whole meta tag.', 'mlab-seo' ); ?></p>
<table class="form-table" role="presentation">
	<?php Fields::rows( array_keys( Settings::VERIFY ) ); ?>
</table>
