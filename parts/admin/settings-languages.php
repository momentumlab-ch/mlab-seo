<?php
/**
 * Settings, languages: hreflang for websites with one site or domain per language.
 *
 * @package MomentumLab\SEO
 * @var array $s   Settings.
 * @var bool  $pro Pro active.
 */

namespace MomentumLab\SEO;

defined( 'ABSPATH' ) || exit;
// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- Included inside Admin::part(), the variables are local.

?>
<p class="mlab-seo-hint"><?php esc_html_e( 'For websites with a separate site or domain per language. Enter the other languages; each page then gets a field for the address of its translation. Set the links on the other language sites too, so the pages point to each other.', 'mlab-seo' ); ?></p>
<?php if ( Hreflang::handled_elsewhere() ) : ?>
	<?php /* translators: %s: name of the multilingual plugin, for example WPML. */ ?>
	<p class="mlab-seo-hint is-note"><?php echo esc_html( sprintf( __( '%s is active and links the language versions itself (hreflang). These settings are not used.', 'mlab-seo' ), Multilingual::plugin_name() ) . ' ' . Multilingual::texts_hint() ); ?></p>
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
