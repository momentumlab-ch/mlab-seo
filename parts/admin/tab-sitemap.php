<?php
/**
 * Sitemap tab.
 *
 * @package MomentumLab\SEO
 */

namespace MomentumLab\SEO;

defined( 'ABSPATH' ) || exit;
// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- Included inside Admin, the variables are local.

?>
<p><?php esc_html_e( 'WordPress creates the XML sitemap. Here you choose what goes in. Pages set to noindex are left out automatically.', 'mlab-seo' ); ?></p>
<p><?php esc_html_e( 'Address to submit in Google Search Console and Bing Webmaster Tools:', 'mlab-seo' ); ?> <code><?php echo esc_html( home_url( '/wp-sitemap.xml' ) ); ?></code></p>
<table class="form-table" role="presentation">
	<tr><th scope="row"><?php esc_html_e( 'Content types', 'mlab-seo' ); ?></th><td>
		<?php foreach ( get_post_types( array( 'public' => true ), 'objects' ) as $type_object ) : ?>
			<?php
			if ( 'attachment' === $type_object->name ) {
				continue;
			}
			?>
			<label><input type="checkbox" name="mlab_seo[sitemap_types][]" value="<?php echo esc_attr( $type_object->name ); ?>" <?php checked( in_array( $type_object->name, (array) $s['sitemap_types'], true ) ); ?> /> <?php echo esc_html( $type_object->labels->name ); ?></label><br />
		<?php endforeach; ?>
	</td></tr>
	<?php Fields::rows( array( 'sitemap_taxonomy', 'sitemap_users', 'sitemap_images', 'sitemap_videos' ) ); ?>
</table>
