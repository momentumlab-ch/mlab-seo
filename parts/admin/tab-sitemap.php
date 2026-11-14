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
<div class="mlab-seo-card mlab-seo-address">
	<div>
		<p class="mlab-seo-hint"><?php esc_html_e( 'Address to submit in Google Search Console and Bing Webmaster Tools:', 'mlab-seo' ); ?></p>
		<code data-copy-source><?php echo esc_html( home_url( '/wp-sitemap.xml' ) ); ?></code>
	</div>
	<button type="button" class="mlab-seo-btn" data-copy><?php esc_html_e( 'Copy', 'mlab-seo' ); ?></button>
	<a class="mlab-seo-btn" href="<?php echo esc_url( home_url( '/wp-sitemap.xml' ) ); ?>" target="_blank" rel="noopener"><?php esc_html_e( 'Open', 'mlab-seo' ); ?> ↗</a>
</div>
<h2 class="mlab-seo-section"><?php esc_html_e( 'Who is told about it', 'mlab-seo' ); ?></h2>
<div class="mlab-seo-group">
	<div class="mlab-seo-row"><span class="mlab-seo-dot is-good"></span><div class="mlab-seo-row__label"><?php esc_html_e( 'All search engines: listed in robots.txt, they find the sitemap on their own.', 'mlab-seo' ); ?></div></div>
	<div class="mlab-seo-row">
		<span class="mlab-seo-dot is-<?php echo IndexNow::active() ? 'good' : 'ok'; ?>"></span>
		<div class="mlab-seo-row__label"><?php echo esc_html( IndexNow::active() ? __( 'Bing, Yandex, Seznam, Naver: new and changed pages are reported right after saving (IndexNow).', 'mlab-seo' ) : __( 'Bing and others: IndexNow is off, so they learn about new pages only at their next visit.', 'mlab-seo' ) ); ?></div>
		<?php if ( ! IndexNow::active() ) : ?>
			<a class="mlab-seo-btn" href="<?php echo esc_url( Admin::url( 'mlab-seo-ai', 'indexnow' ) ); ?>"><?php esc_html_e( 'Turn on', 'mlab-seo' ); ?></a>
		<?php endif; ?>
	</div>
	<div class="mlab-seo-row">
		<span class="mlab-seo-dot is-<?php echo '' !== Settings::get( 'verify_google' ) ? 'good' : 'ok'; ?>"></span>
		<div class="mlab-seo-row__label"><?php esc_html_e( 'Google: no automatic ping exists any more. Submit the address above once in Search Console.', 'mlab-seo' ); ?></div>
		<a class="mlab-seo-btn" href="https://search.google.com/search-console/sitemaps" target="_blank" rel="noopener"><?php esc_html_e( 'Search Console', 'mlab-seo' ); ?> ↗</a>
	</div>
</div>
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
