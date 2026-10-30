<?php
/**
 * General tab: titles, social media, verification, languages, links and images.
 *
 * @package MomentumLab\SEO
 */

namespace MomentumLab\SEO;

defined( 'ABSPATH' ) || exit;
// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- Included inside Admin, the variables are local.

?>
<table class="form-table" role="presentation">
	<?php
	Fields::row( 'site_name', array( 'placeholder' => wp_strip_all_tags( get_bloginfo( 'name' ) ) ) );
	Fields::rows( array( 'separator', 'home_title', 'home_description', 'default_image' ) );
	Fields::row( 'og_locale', array( 'placeholder' => get_locale() ) );
	Fields::rows( array( 'twitter_site', 'remove_generator', 'attachment_pages' ) );
	?>
</table>

<h2><?php esc_html_e( 'Links, images and feed', 'mlab-seo' ); ?></h2>
<table class="form-table" role="presentation">
	<?php Fields::rows( array( 'external_nofollow', 'external_blank', 'image_alt', 'rss_source', 'breadcrumb_home', 'breadcrumb_sep' ) ); ?>
	<?php Fields::row( 'woo_schema' ); ?>
</table>

<h2><?php esc_html_e( 'Site verification', 'mlab-seo' ); ?></h2>
<p><?php esc_html_e( 'Confirms that you own the website, for example in Google Search Console. Paste the code or the whole meta tag.', 'mlab-seo' ); ?></p>
<table class="form-table" role="presentation">
	<?php Fields::rows( array_keys( Settings::VERIFY ) ); ?>
</table>

<h2><?php esc_html_e( 'Languages (hreflang)', 'mlab-seo' ); ?></h2>
<p><?php esc_html_e( 'For websites with a separate site or domain per language. Enter the other languages; each page then gets a field for the address of its translation. Set the links on the other language sites too, so the pages point to each other.', 'mlab-seo' ); ?></p>
<?php if ( Hreflang::handled_elsewhere() ) : ?>
	<?php /* translators: %s: name of the multilingual plugin, for example WPML. */ ?>
	<p class="description"><?php echo esc_html( sprintf( __( '%s is active and links the language versions itself (hreflang). These settings are not used.', 'mlab-seo' ), Multilingual::plugin_name() ) . ' ' . Multilingual::texts_hint() ); ?></p>
<?php endif; ?>
<table class="form-table" role="presentation">
	<?php
	/* translators: %s: language code of this website, for example de-CH. */
	Fields::row( 'hreflang_langs', array( 'help' => sprintf( __( 'Language codes separated by commas, for example en, fr. This website: %s.', 'mlab-seo' ), Hreflang::own() ) ) );
	$languages = array_merge( array( Hreflang::own() ), Hreflang::languages() );
	Fields::row(
		'hreflang_default',
		array(
			'type'    => 'select',
			'choices' => array( '' => __( 'None', 'mlab-seo' ) ) + array_combine( $languages, $languages ),
		)
	);
	?>
</table>
