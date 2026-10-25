<?php
/**
 * Settings screen with status and tabs.
 *
 * @package MomentumLab\SEO
 */

namespace MomentumLab\SEO;

defined( 'ABSPATH' ) || exit;
// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- Included inside Admin, the variables are local.

?>
<div class="wrap mlab-seo-settings">
	<h1><?php esc_html_e( 'MomentumLab SEO', 'mlab-seo' ); ?> <span class="mlab-seo__pro<?php echo $pro ? '' : ' is-free'; ?>"><?php echo $pro ? 'Pro' : 'Free'; ?></span></h1>
	<?php $this->notice(); ?>

	<div class="mlab-seo-status">
		<h2><?php esc_html_e( 'Status', 'mlab-seo' ); ?></h2>
		<ul class="mlab-seo__checks">
			<?php foreach ( $this->status() as $item ) : ?>
				<li data-state="<?php echo esc_attr( $item[0] ); ?>"><?php echo esc_html( $item[1] ); ?></li>
			<?php endforeach; ?>
		</ul>
		<p>
			<a href="<?php echo esc_url( home_url( '/wp-sitemap.xml' ) ); ?>" target="_blank" rel="noopener"><?php esc_html_e( 'View sitemap', 'mlab-seo' ); ?></a>
			<?php foreach ( Media::sitemap_urls() as $media_url ) : ?>
				· <a href="<?php echo esc_url( $media_url ); ?>" target="_blank" rel="noopener"><?php echo esc_html( basename( $media_url ) ); ?></a>
			<?php endforeach; ?>
			<?php if ( $s['llms_enabled'] ) : ?>
				· <a href="<?php echo esc_url( Crawlers::llms_url() ); ?>" target="_blank" rel="noopener"><?php esc_html_e( 'View llms.txt', 'mlab-seo' ); ?></a>
			<?php endif; ?>
			· <a href="<?php echo esc_url( 'https://search.google.com/test/rich-results?url=' . rawurlencode( home_url( '/' ) ) ); ?>" target="_blank" rel="noopener"><?php esc_html_e( 'Test with Google', 'mlab-seo' ); ?></a>
		</p>
	</div>

	<nav class="nav-tab-wrapper mlab-seo-nav" aria-label="<?php esc_attr_e( 'SEO settings', 'mlab-seo' ); ?>">
		<?php foreach ( $tabs as $key => $label ) : ?>
			<a href="#<?php echo esc_attr( $key ); ?>" class="nav-tab" data-tab="<?php echo esc_attr( $key ); ?>"><?php echo esc_html( $label ); ?><?php echo 'redirects' === $key && ! $pro ? ' <span class="mlab-seo__pro">Pro</span>' : ''; ?></a>
		<?php endforeach; ?>
	</nav>

	<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="mlab-seo-form">
		<input type="hidden" name="action" value="mlab_seo_save" />
		<input type="hidden" name="mlab_seo_tab" value="general" class="mlab-seo-current-tab" />
		<?php wp_nonce_field( 'mlab_seo_save' ); ?>
		<?php foreach ( array( 'general', 'titles', 'publisher', 'ai', 'sitemap' ) as $form_tab ) : ?>
			<section class="mlab-seo-tab" data-tab="<?php echo esc_attr( $form_tab ); ?>" <?php echo 'general' === $form_tab ? '' : 'hidden'; ?>>
				<?php self::part( 'tab-' . $form_tab ); ?>
			</section>
		<?php endforeach; ?>
		<p class="submit mlab-seo-submit"><?php submit_button( __( 'Save settings', 'mlab-seo' ), 'primary', 'submit', false ); ?></p>
	</form>

	<?php
	foreach ( array(
		'ai'        => 'access-check',
		'redirects' => 'tab-redirects',
		'content'   => 'tab-content',
		'import'    => 'tab-import',
		'license'   => 'tab-license',
	) as $tab_key => $part ) :
		?>
		<section class="mlab-seo-tab" data-tab="<?php echo esc_attr( $tab_key ); ?>" hidden>
												<?php self::part( $part ); ?>
		</section>
	<?php endforeach; ?>

	<p class="mlab-seo-credit"><?php esc_html_e( 'MomentumLab SEO by MomentumLab', 'mlab-seo' ); ?> · <a href="https://momentumlab.ch/" target="_blank" rel="noopener">momentumlab.ch</a> · <?php echo esc_html( MLAB_SEO_VERSION ); ?></p>
</div>
