<?php
/**
 * Structured data: what is printed per page type, and a live preview of the JSON-LD of one page.
 *
 * @package MomentumLab\SEO
 * @var bool $pro Pro active.
 */

namespace MomentumLab\SEO;

defined( 'ABSPATH' ) || exit;
// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- Included inside Admin::part(), the variables are local.

$nodes = array(
	array( 'Organization / Person', __( 'Every page', 'mlab-seo' ), __( 'Search appearance › Organisation', 'mlab-seo' ), false, Admin::url( 'mlab-seo-search', 'publisher' ) ),
	array( 'WebSite', __( 'Every page', 'mlab-seo' ), __( 'Site name', 'mlab-seo' ), false, Admin::url( 'mlab-seo-search', 'home' ) ),
	array( 'WebPage, AboutPage, ContactPage …', __( 'Pages and posts', 'mlab-seo' ), __( 'Page type in the SEO box › Advanced', 'mlab-seo' ), false, '' ),
	array( 'BreadcrumbList', __( 'Pages and posts', 'mlab-seo' ), __( 'Automatic from the page hierarchy', 'mlab-seo' ), false, Admin::url( 'mlab-seo-settings', 'general' ) ),
	array( 'Article / BlogPosting', __( 'Posts', 'mlab-seo' ), __( 'Article type per content type', 'mlab-seo' ), false, Admin::url( 'mlab-seo-search', 'templates' ) ),
	array( 'FAQPage', __( 'Pages with an FAQ block', 'mlab-seo' ), __( 'FAQ block in the editor', 'mlab-seo' ), false, '' ),
	array( 'VideoObject', __( 'Pages with a video', 'mlab-seo' ), __( 'Automatic (YouTube, Vimeo, uploads)', 'mlab-seo' ), false, '' ),
	array( 'Product', __( 'WooCommerce products', 'mlab-seo' ), __( 'Automatic', 'mlab-seo' ), false, Admin::url( 'mlab-seo-settings', 'general' ) ),
	array( 'Event, SportsEvent, MusicEvent …', __( 'Pages with an event', 'mlab-seo' ), __( 'SEO box › Event & schema', 'mlab-seo' ), true, '' ),
	array( 'JSON-LD', __( 'Any page', 'mlab-seo' ), __( 'SEO box › Event & schema', 'mlab-seo' ), true, '' ),
);

$choices = get_posts(
	array(
		'post_type'      => Post_Meta::post_types(),
		'post_status'    => 'publish',
		'posts_per_page' => 40,
		'orderby'        => 'modified',
		'no_found_rows'  => true,
	)
);
$front   = (int) get_option( 'page_on_front' );
$chosen  = isset( $_GET['schema_post'] ) ? absint( $_GET['schema_post'] ) : $front; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Display only.
$post    = $chosen ? get_post( $chosen ) : null;
$seo     = $post && 'publish' === $post->post_status ? Context::for_post( $chosen ) : array(
	'post_id' => 0,
	'noindex' => false,
);
$graph   = Schema::graph( $seo );
$json    = (string) wp_json_encode( array( '@context' => 'https://schema.org', '@graph' => $graph ), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT );
$test    = $post ? (string) get_permalink( $post ) : home_url( '/' );
?>
<p><?php esc_html_e( 'Structured data tells Google, Bing and AI assistants what a page is: an article, an event, a product. The plugin prints it automatically as one block (JSON-LD) in the head of each page.', 'mlab-seo' ); ?></p>

<div class="mlab-seo-group">
	<?php foreach ( $nodes as $node ) : ?>
		<div class="mlab-seo-row">
			<div class="mlab-seo-row__label"><strong><?php echo esc_html( $node[0] ); ?></strong> <small><?php echo esc_html( $node[1] . ' · ' . $node[2] ); ?></small></div>
			<span class="mlab-seo-tag<?php echo $node[3] ? ' is-plan' : ''; ?>"><?php echo $node[3] ? 'Pro' : 'Free'; ?></span>
			<?php if ( '' !== $node[4] ) : ?>
				<a class="mlab-seo-btn" href="<?php echo esc_url( $node[4] ); ?>"><?php esc_html_e( 'Set up', 'mlab-seo' ); ?></a>
			<?php endif; ?>
		</div>
	<?php endforeach; ?>
</div>

<h2 class="mlab-seo-section"><?php esc_html_e( 'Preview', 'mlab-seo' ); ?></h2>
<form method="get" action="<?php echo esc_url( admin_url( 'admin.php#schema' ) ); ?>" class="mlab-seo-hint">
	<input type="hidden" name="page" value="mlab-seo-search" />
	<label for="mlab-seo-schema-post"><?php esc_html_e( 'Page', 'mlab-seo' ); ?></label>
	<select id="mlab-seo-schema-post" name="schema_post">
		<option value="0" <?php selected( $chosen, 0 ); ?>><?php esc_html_e( 'Website only', 'mlab-seo' ); ?></option>
		<?php foreach ( $choices as $choice ) : ?>
			<option value="<?php echo (int) $choice->ID; ?>" <?php selected( $chosen, $choice->ID ); ?>><?php echo esc_html( Context::plain_title( $choice ) ); ?></option>
		<?php endforeach; ?>
	</select>
	<button type="submit" class="button"><?php esc_html_e( 'Show', 'mlab-seo' ); ?></button>
	<a class="button" href="<?php echo esc_url( 'https://search.google.com/test/rich-results?url=' . rawurlencode( $test ) ); ?>" target="_blank" rel="noopener"><?php esc_html_e( 'Test at Google', 'mlab-seo' ); ?> ↗</a>
	<a class="button" href="<?php echo esc_url( 'https://validator.schema.org/#url=' . rawurlencode( $test ) ); ?>" target="_blank" rel="noopener">schema.org ↗</a>
</form>
<?php if ( $post && ! empty( $seo['noindex'] ) ) : ?>
	<p class="mlab-seo-hint is-note"><?php esc_html_e( 'This page is hidden from search engines (noindex), so only the website data is printed.', 'mlab-seo' ); ?></p>
<?php endif; ?>
<pre class="mlab-seo-code"><?php echo esc_html( $json ); ?></pre>
