<?php
/**
 * Titles & meta tab: templates per content type and taxonomy, archives.
 *
 * @package MomentumLab\SEO
 */

namespace MomentumLab\SEO;

defined( 'ABSPATH' ) || exit;
// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- Included inside Admin::part(), the variables are local.

$article_types = array(
	'none'        => __( 'None', 'mlab-seo' ),
	'Article'     => 'Article',
	'BlogPosting' => 'BlogPosting',
	'NewsArticle' => 'NewsArticle',
);
?>
<p><?php esc_html_e( 'Default SEO title and meta description per content type. Pages with their own title or description in the SEO box keep it. Variables are replaced on every page:', 'mlab-seo' ); ?></p>
<details class="mlab-seo-variables">
	<summary><?php esc_html_e( 'Show variables', 'mlab-seo' ); ?></summary>
	<dl>
		<?php foreach ( Titles::variables() as $variable => $meaning ) : ?>
			<dt><code><?php echo esc_html( $variable ); ?></code></dt><dd><?php echo esc_html( $meaning ); ?></dd>
		<?php endforeach; ?>
	</dl>
</details>

<?php foreach ( Post_Meta::post_types() as $type_name ) : ?>
	<?php
	$object = get_post_type_object( $type_name );
	if ( ! $object ) {
		continue;
	}
	$values   = Titles::post_type( $type_name );
	$defaults = Titles::post_type_defaults( $type_name );
	$name     = 'mlab_seo[titles][types][' . $type_name . ']';
	?>
	<h2><?php echo esc_html( $object->labels->name ); ?></h2>
	<table class="form-table" role="presentation">
		<tr><th scope="row"><label for="mlab-seo-title-<?php echo esc_attr( $type_name ); ?>"><?php esc_html_e( 'SEO title', 'mlab-seo' ); ?></label></th>
			<td><input type="text" class="large-text code" id="mlab-seo-title-<?php echo esc_attr( $type_name ); ?>" name="<?php echo esc_attr( $name ); ?>[title]" value="<?php echo esc_attr( $values['title'] ); ?>" placeholder="<?php echo esc_attr( $defaults['title'] ); ?>" /></td></tr>
		<tr><th scope="row"><label for="mlab-seo-desc-<?php echo esc_attr( $type_name ); ?>"><?php esc_html_e( 'Meta description', 'mlab-seo' ); ?></label></th>
			<td><input type="text" class="large-text code" id="mlab-seo-desc-<?php echo esc_attr( $type_name ); ?>" name="<?php echo esc_attr( $name ); ?>[description]" value="<?php echo esc_attr( $values['description'] ); ?>" placeholder="<?php echo esc_attr( $defaults['description'] ); ?>" /></td></tr>
		<tr><th scope="row"><?php esc_html_e( 'Search engines', 'mlab-seo' ); ?></th>
			<td><label><input type="checkbox" name="<?php echo esc_attr( $name ); ?>[noindex]" value="1" <?php checked( (bool) $values['noindex'] ); ?> /> <?php esc_html_e( 'Hide all of them from search engines (noindex)', 'mlab-seo' ); ?></label></td></tr>
		<tr><th scope="row"><label for="mlab-seo-schema-<?php echo esc_attr( $type_name ); ?>"><?php esc_html_e( 'Article type (schema.org)', 'mlab-seo' ); ?></label></th>
			<td><?php Fields::select( 'schema-' . $type_name, $name . '[schema]', $values['schema'], $article_types ); ?>
			<p class="description"><?php esc_html_e( 'For blog posts and news. Google shows author and date for articles.', 'mlab-seo' ); ?></p></td></tr>
	</table>
<?php endforeach; ?>

<?php foreach ( Term_Meta::taxonomies() as $tax_name ) : ?>
	<?php
	$object = get_taxonomy( $tax_name );
	if ( ! $object ) {
		continue;
	}
	$values   = Titles::taxonomy( $tax_name );
	$defaults = Titles::taxonomy_defaults();
	$name     = 'mlab_seo[titles][taxonomies][' . $tax_name . ']';
	?>
	<h2><?php echo esc_html( $object->labels->name ); ?></h2>
	<table class="form-table" role="presentation">
		<tr><th scope="row"><label for="mlab-seo-title-tax-<?php echo esc_attr( $tax_name ); ?>"><?php esc_html_e( 'SEO title', 'mlab-seo' ); ?></label></th>
			<td><input type="text" class="large-text code" id="mlab-seo-title-tax-<?php echo esc_attr( $tax_name ); ?>" name="<?php echo esc_attr( $name ); ?>[title]" value="<?php echo esc_attr( $values['title'] ); ?>" placeholder="<?php echo esc_attr( $defaults['title'] ); ?>" /></td></tr>
		<tr><th scope="row"><label for="mlab-seo-desc-tax-<?php echo esc_attr( $tax_name ); ?>"><?php esc_html_e( 'Meta description', 'mlab-seo' ); ?></label></th>
			<td><input type="text" class="large-text code" id="mlab-seo-desc-tax-<?php echo esc_attr( $tax_name ); ?>" name="<?php echo esc_attr( $name ); ?>[description]" value="<?php echo esc_attr( $values['description'] ); ?>" placeholder="<?php echo esc_attr( $defaults['description'] ); ?>" /></td></tr>
		<tr><th scope="row"><?php esc_html_e( 'Search engines', 'mlab-seo' ); ?></th>
			<td><label><input type="checkbox" name="<?php echo esc_attr( $name ); ?>[noindex]" value="1" <?php checked( (bool) $values['noindex'] ); ?> /> <?php esc_html_e( 'Hide all of them from search engines (noindex)', 'mlab-seo' ); ?></label></td></tr>
	</table>
<?php endforeach; ?>

<h2><?php esc_html_e( 'Archives', 'mlab-seo' ); ?></h2>
<table class="form-table" role="presentation">
	<?php Fields::rows( array( 'author_archive', 'date_archive', 'noindex_empty' ) ); ?>
</table>
