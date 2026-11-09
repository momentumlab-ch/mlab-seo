<?php
/**
 * Overview: state of the website, tasks with a direct action, quick links, recently changed pages.
 *
 * @package MomentumLab\SEO
 * @var array $s Settings.
 */

namespace MomentumLab\SEO;

defined( 'ABSPATH' ) || exit;
// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- Included inside Admin::part(), the variables are local.

$stats  = Dashboard::stats();
$ai     = Dashboard::ai();
$checks = Dashboard::checks();
$open   = array_filter( $checks, fn ( $item ) => 'good' !== $item['state'] );
$good   = array_filter( $checks, fn ( $item ) => 'good' === $item['state'] );
$score  = (int) $stats['score'];
$state  = Dashboard::state( $score );
$links  = array_merge(
	array(
		'sitemap.xml' => home_url( '/wp-sitemap.xml' ),
		'robots.txt'  => home_url( '/robots.txt' ),
	),
	$s['llms_enabled'] ? array( 'llms.txt' => Crawlers::llms_url() ) : array(),
	array(
		__( 'Test rich results', 'mlab-seo' ) => 'https://search.google.com/test/rich-results?url=' . rawurlencode( home_url( '/' ) ),
		'Search Console'                      => 'https://search.google.com/search-console',
	)
);
?>
<div class="mlab-seo-grid is-3">
	<section class="mlab-seo-card mlab-seo-score" aria-label="<?php esc_attr_e( 'SEO score', 'mlab-seo' ); ?>">
		<div class="mlab-seo-ring is-<?php echo esc_attr( $state ); ?>" style="--p: <?php echo (int) $score; ?>"><b><?php echo $score ? (int) $score : '–'; ?></b></div>
		<div>
			<h2><?php echo esc_html( $score ? __( 'Average SEO score', 'mlab-seo' ) : __( 'No page checked yet', 'mlab-seo' ) ); ?></h2>
			<p>
				<?php
				/* translators: 1: number of indexable pages, 2: number of pages not checked. */
				echo esc_html( sprintf( __( '%1$d pages in search engines, %2$d not checked yet.', 'mlab-seo' ), $stats['indexed'], $stats['unchecked'] ) );
				?>
			</p>
		</div>
	</section>
	<section class="mlab-seo-card mlab-seo-stats" aria-label="<?php esc_attr_e( 'Pages', 'mlab-seo' ); ?>">
		<a href="<?php echo esc_url( Admin::pages_url() ); ?>"><b><?php echo (int) $stats['indexed']; ?></b><span><?php esc_html_e( 'in search engines', 'mlab-seo' ); ?></span></a>
		<a href="<?php echo esc_url( Admin::pages_url( 'no_description' ) ); ?>" class="<?php echo $stats['no_description'] ? 'is-ok' : ''; ?>"><b><?php echo (int) $stats['no_description']; ?></b><span><?php esc_html_e( 'without description', 'mlab-seo' ); ?></span></a>
		<a href="<?php echo esc_url( Admin::pages_url( 'noindex' ) ); ?>"><b><?php echo (int) $stats['noindex']; ?></b><span><?php esc_html_e( 'hidden (noindex)', 'mlab-seo' ); ?></span></a>
		<a href="<?php echo esc_url( Admin::pages_url( 'poor' ) ); ?>" class="<?php echo $stats['poor'] ? 'is-ok' : ''; ?>"><b><?php echo (int) $stats['poor']; ?></b><span><?php esc_html_e( 'with low score', 'mlab-seo' ); ?></span></a>
	</section>
	<section class="mlab-seo-card" aria-label="<?php esc_attr_e( 'AI visibility', 'mlab-seo' ); ?>">
		<h2><?php esc_html_e( 'AI visibility', 'mlab-seo' ); ?></h2>
		<ul class="mlab-seo-checks">
			<li data-state="<?php echo $ai['allowed'] ? 'good' : 'ok'; ?>">
				<?php
				/* translators: 1: number of allowed AI crawlers, 2: number of known AI crawlers. */
				echo esc_html( sprintf( __( '%1$d of %2$d AI crawlers allowed', 'mlab-seo' ), $ai['allowed'], $ai['bots'] ) );
				?>
			</li>
			<li data-state="<?php echo $ai['llms'] ? 'good' : 'ok'; ?>"><?php echo esc_html( $ai['llms'] ? __( 'llms.txt is active', 'mlab-seo' ) : __( 'llms.txt is off', 'mlab-seo' ) ); ?></li>
			<li data-state="<?php echo $ai['indexnow'] ? 'good' : 'ok'; ?>">
				<?php
				if ( $ai['indexnow'] ) {
					/* translators: 1: time span like "2 hours", 2: number of addresses. */
					echo esc_html( sprintf( __( 'IndexNow: %1$s ago, %2$d addresses', 'mlab-seo' ), human_time_diff( (int) $ai['indexnow']['time'] ), count( (array) $ai['indexnow']['urls'] ) ) );
				} else {
					esc_html_e( 'IndexNow is off', 'mlab-seo' );
				}
				?>
			</li>
		</ul>
	</section>
</div>

<h2 class="mlab-seo-section"><?php esc_html_e( 'To do', 'mlab-seo' ); ?></h2>
<div class="mlab-seo-group">
	<?php if ( ! $open ) : ?>
		<div class="mlab-seo-row"><span class="mlab-seo-dot is-good"></span><div class="mlab-seo-row__label"><?php esc_html_e( 'Nothing to do. Everything is in order.', 'mlab-seo' ); ?></div></div>
	<?php endif; ?>
	<?php foreach ( $open as $item ) : ?>
		<div class="mlab-seo-row">
			<span class="mlab-seo-dot is-<?php echo esc_attr( $item['state'] ); ?>"></span>
			<div class="mlab-seo-row__label"><?php echo esc_html( $item['text'] ); ?></div>
			<?php if ( isset( $item['url'] ) ) : ?>
				<a class="mlab-seo-btn<?php echo 'bad' === $item['state'] ? ' is-primary' : ''; ?>" href="<?php echo esc_url( $item['url'] ); ?>"><?php echo esc_html( $item['action'] ); ?></a>
			<?php endif; ?>
		</div>
	<?php endforeach; ?>
	<?php if ( $good ) : ?>
		<details class="mlab-seo-row is-stack">
			<summary>
				<?php
				/* translators: %d: number of checks. */
				echo esc_html( sprintf( _n( '%d check in order', '%d checks in order', count( $good ), 'mlab-seo' ), count( $good ) ) );
				?>
			</summary>
			<ul class="mlab-seo-checks">
				<?php foreach ( $good as $item ) : ?>
					<li data-state="good"><?php echo esc_html( $item['text'] ); ?></li>
				<?php endforeach; ?>
			</ul>
		</details>
	<?php endif; ?>
</div>

<h2 class="mlab-seo-section"><?php esc_html_e( 'Open quickly', 'mlab-seo' ); ?></h2>
<div class="mlab-seo-chips">
	<?php foreach ( $links as $label => $url ) : ?>
		<a class="mlab-seo-chip" href="<?php echo esc_url( $url ); ?>" target="_blank" rel="noopener"><?php echo esc_html( $label ); ?> <span aria-hidden="true">↗</span></a>
	<?php endforeach; ?>
	<?php foreach ( Media::sitemap_urls() as $media_url ) : ?>
		<a class="mlab-seo-chip" href="<?php echo esc_url( $media_url ); ?>" target="_blank" rel="noopener"><?php echo esc_html( basename( $media_url ) ); ?> <span aria-hidden="true">↗</span></a>
	<?php endforeach; ?>
</div>

<h2 class="mlab-seo-section"><?php esc_html_e( 'Recently changed', 'mlab-seo' ); ?></h2>
<div class="mlab-seo-group">
	<?php foreach ( Dashboard::recent() as $recent ) : ?>
		<a class="mlab-seo-row is-link" href="<?php echo esc_url( Admin::url( 'mlab-seo-pages', '', array( 'edit' => $recent->ID ) ) ); ?>">
			<div class="mlab-seo-row__label">
				<?php echo esc_html( Context::plain_title( $recent ) ); ?>
				<small>
					<?php
					/* translators: 1: content type, 2: time span like "2 hours". */
					echo esc_html( sprintf( __( '%1$s · %2$s ago', 'mlab-seo' ), get_post_type_object( $recent->post_type )->labels->singular_name, human_time_diff( (int) get_post_modified_time( 'U', true, $recent ) ) ) );
					?>
				</small>
			</div>
			<?php echo Post_Meta::get( $recent->ID, 'noindex' ) ? '<span class="mlab-seo-tag">noindex</span>' : Dashboard::score_badge( (int) Post_Meta::get( $recent->ID, 'score' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped in score_badge(). ?>
		</a>
	<?php endforeach; ?>
</div>
