<?php
/**
 * SEO rows on the edit screen of a category, tag or other term.
 *
 * @package MomentumLab\SEO
 *
 * @var \WP_Term $term     Term.
 * @var array    $template Settings of the taxonomy.
 */

namespace MomentumLab\SEO;

defined( 'ABSPATH' ) || exit;

$mlab_seo_image = Context::image( Term_Meta::get( $term->term_id, 'image' ), 'medium' );
?>
<tr class="form-field"><th scope="row" colspan="2"><h2><?php esc_html_e( 'MomentumLab SEO', 'mlab-seo' ); ?></h2></th></tr>
<tr class="form-field">
	<th scope="row"><label for="mlab-seo-term-title"><?php esc_html_e( 'SEO title', 'mlab-seo' ); ?></label></th>
	<td><input type="text" id="mlab-seo-term-title" name="mlab_seo_term[title]" value="<?php echo esc_attr( Term_Meta::get( $term->term_id, 'title' ) ); ?>" placeholder="<?php echo esc_attr( Titles::replace( $template['title'], null, $term ) ); ?>" />
	<p class="description"><?php esc_html_e( 'Up to about 60 characters.', 'mlab-seo' ); ?></p></td>
</tr>
<tr class="form-field">
	<th scope="row"><label for="mlab-seo-term-description"><?php esc_html_e( 'Meta description', 'mlab-seo' ); ?></label></th>
	<td><textarea id="mlab-seo-term-description" name="mlab_seo_term[description]" rows="3"><?php echo esc_textarea( Term_Meta::get( $term->term_id, 'description' ) ); ?></textarea>
	<p class="description"><?php esc_html_e( '120 to 160 characters. Empty: the description of the term.', 'mlab-seo' ); ?></p></td>
</tr>
<tr class="form-field">
	<th scope="row"><?php esc_html_e( 'Search engines', 'mlab-seo' ); ?></th>
	<td><label><input type="checkbox" name="mlab_seo_term[noindex]" value="1" <?php checked( Term_Meta::get( $term->term_id, 'noindex' ) ); ?> /> <?php esc_html_e( 'Hide from search engines (noindex)', 'mlab-seo' ); ?></label></td>
</tr>
<tr class="form-field">
	<th scope="row"><label for="mlab-seo-term-breadcrumb"><?php esc_html_e( 'Breadcrumb title', 'mlab-seo' ); ?></label></th>
	<td><input type="text" id="mlab-seo-term-breadcrumb" name="mlab_seo_term[breadcrumb]" value="<?php echo esc_attr( Term_Meta::get( $term->term_id, 'breadcrumb' ) ); ?>" placeholder="<?php echo esc_attr( $term->name ); ?>" />
	<p class="description"><?php esc_html_e( 'Shorter name in the breadcrumbs. Empty: the name.', 'mlab-seo' ); ?></p></td>
</tr>
<tr class="form-field">
	<th scope="row"><label for="mlab-seo-term-og-title"><?php esc_html_e( 'Social title', 'mlab-seo' ); ?></label></th>
	<td><input type="text" id="mlab-seo-term-og-title" name="mlab_seo_term[og_title]" value="<?php echo esc_attr( Term_Meta::get( $term->term_id, 'og_title' ) ); ?>" placeholder="<?php esc_attr_e( 'Empty: the SEO title', 'mlab-seo' ); ?>" /></td>
</tr>
<tr class="form-field">
	<th scope="row"><label for="mlab-seo-term-og-desc"><?php esc_html_e( 'Social description', 'mlab-seo' ); ?></label></th>
	<td><textarea id="mlab-seo-term-og-desc" name="mlab_seo_term[og_desc]" rows="2" placeholder="<?php esc_attr_e( 'Empty: the meta description', 'mlab-seo' ); ?>"><?php echo esc_textarea( Term_Meta::get( $term->term_id, 'og_desc' ) ); ?></textarea></td>
</tr>
<tr class="form-field">
	<th scope="row"><?php esc_html_e( 'Social image', 'mlab-seo' ); ?></th>
	<td>
		<div class="mlab-seo-picker">
			<div class="mlab-seo-picker__preview"><?php echo $mlab_seo_image ? '<img src="' . esc_url( $mlab_seo_image['url'] ) . '" alt="" />' : ''; ?></div>
			<input type="hidden" name="mlab_seo_term[image]" value="<?php echo esc_attr( (string) Term_Meta::get( $term->term_id, 'image' ) ); ?>" />
			<button type="button" class="button mlab-seo-picker__pick"><?php esc_html_e( 'Choose image', 'mlab-seo' ); ?></button>
			<button type="button" class="button-link mlab-seo-picker__remove" <?php echo $mlab_seo_image ? '' : 'hidden'; ?>><?php esc_html_e( 'Remove image', 'mlab-seo' ); ?></button>
		</div>
		<p class="description"><?php esc_html_e( 'Recommended: 1200 × 630 px.', 'mlab-seo' ); ?></p>
	</td>
</tr>
