<?php
/**
 * One SEO screen: title, segments, and per segment either a settings form or a part with own forms.
 *
 * @package MomentumLab\SEO
 * @var string $slug       Menu slug.
 * @var array  $admin_page Page definition from inc/admin-pages.php.
 * @var bool   $pro        Pro active.
 */

namespace MomentumLab\SEO;

defined( 'ABSPATH' ) || exit;
// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- Included inside Admin::part(), the variables are local.

$segments = $admin_page['segments'];
$first    = (string) array_key_first( $segments );
?>
<div class="wrap mlab-seo-app" data-page="<?php echo esc_attr( $slug ); ?>">
	<header class="mlab-seo-head">
		<div>
			<h1><?php echo esc_html( $admin_page['title'] ); ?> <span class="mlab-seo-tag is-plan<?php echo $pro ? ' is-pro' : ''; ?>"><?php echo $pro ? 'Pro' : 'Free'; ?></span></h1>
			<?php if ( '' !== $admin_page['intro'] ) : ?>
				<p class="mlab-seo-head__intro"><?php echo esc_html( $admin_page['intro'] ); ?></p>
			<?php endif; ?>
		</div>
		<span class="mlab-seo-saved" role="status" aria-live="polite"></span>
	</header>
	<?php Admin_Actions::notice(); ?>

	<?php if ( count( $segments ) > 1 ) : ?>
		<nav class="mlab-seo-seg" aria-label="<?php echo esc_attr( $admin_page['title'] ); ?>">
			<?php foreach ( $segments as $key => $segment ) : ?>
				<a href="#<?php echo esc_attr( $key ); ?>" data-segment="<?php echo esc_attr( $key ); ?>" <?php echo $key === $first ? 'aria-current="page"' : ''; ?>><?php echo esc_html( $segment['label'] ); ?></a>
			<?php endforeach; ?>
		</nav>
	<?php endif; ?>

	<?php foreach ( $segments as $key => $segment ) : ?>
		<section class="mlab-seo-segment" id="mlab-seo-<?php echo esc_attr( $key ); ?>" data-segment="<?php echo esc_attr( $key ); ?>" <?php echo $key === $first ? '' : 'hidden'; ?>>
			<?php if ( isset( $segment['keys'] ) ) : ?>
				<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="mlab-seo-autosave" data-page="<?php echo esc_attr( $slug ); ?>" data-segment="<?php echo esc_attr( $key ); ?>">
					<input type="hidden" name="action" value="mlab_seo_save" />
					<input type="hidden" name="mlab_seo_page" value="<?php echo esc_attr( $slug ); ?>" />
					<input type="hidden" name="mlab_seo_segment" value="<?php echo esc_attr( $key ); ?>" />
					<?php wp_nonce_field( 'mlab_seo_save' ); ?>
					<?php Admin::part( $segment['part'] ); ?>
					<p class="mlab-seo-submit"><?php submit_button( __( 'Save', 'mlab-seo' ), 'primary', 'submit', false ); ?></p>
				</form>
			<?php else : ?>
				<?php Admin::part( $segment['part'] ); ?>
			<?php endif; ?>
			<?php
			if ( isset( $segment['after'] ) ) {
				Admin::part( $segment['after'] );
			}
			?>
		</section>
	<?php endforeach; ?>

	<p class="mlab-seo-credit"><?php esc_html_e( 'MomentumLab SEO by MomentumLab', 'mlab-seo' ); ?> · <a href="https://momentumlab.ch/" target="_blank" rel="noopener">momentumlab.ch</a> · <?php echo esc_html( MLAB_SEO_VERSION ); ?></p>
</div>
