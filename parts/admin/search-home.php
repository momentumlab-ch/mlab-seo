<?php
/**
 * Search appearance, home page: website name, separator, home page texts, social defaults.
 *
 * @package MomentumLab\SEO
 * @var array $s   Settings.
 * @var bool  $pro Pro active.
 */

namespace MomentumLab\SEO;

defined( 'ABSPATH' ) || exit;
// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- Included inside Admin::part(), the variables are local.

?>
<table class="form-table" role="presentation">
	<?php
	Fields::row( 'site_name', array( 'placeholder' => wp_strip_all_tags( get_bloginfo( 'name' ) ) ) );
	Fields::rows( array( 'separator', 'home_title', 'home_description', 'default_image' ) );
	Fields::row( 'og_locale', array( 'placeholder' => get_locale() ) );
	Fields::row( 'twitter_site' );
	?>
</table>
