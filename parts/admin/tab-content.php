<?php
/**
 * Content tab: pages with little text.
 *
 * @package MomentumLab\SEO
 */

namespace MomentumLab\SEO;

defined( 'ABSPATH' ) || exit;
// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- Included inside Admin::part(), the variables are local.

$rows = Content_Audit::results();
?>
<p>
	<?php
	/* translators: 1: minimum number of words, 2: recommended number of words. */
	echo esc_html( sprintf( __( 'Indexable pages with little text. Below %1$d words a page is thin: it rarely ranks and gives AI assistants nothing to quote. From about %2$d words a page can answer a question well.', 'mlab-seo' ), Content_Audit::THIN, Content_Audit::SHORT ) );
	?>
</p>
<p><?php esc_html_e( 'For each page: add an introduction, answers to typical questions (FAQ block) or descriptions of images and tables. Pages that only serve visitors, like a gallery without text, can also be hidden from search engines (noindex).', 'mlab-seo' ); ?></p>
<?php if ( $rows ) : ?>
	<table class="widefat striped">
		<thead><tr><th><?php esc_html_e( 'Page', 'mlab-seo' ); ?></th><th><?php esc_html_e( 'Words', 'mlab-seo' ); ?></th><th><?php esc_html_e( 'Images', 'mlab-seo' ); ?></th><th><?php esc_html_e( 'Rating', 'mlab-seo' ); ?></th></tr></thead>
		<tbody>
			<?php foreach ( $rows as $row ) : ?>
				<tr>
					<td><a href="<?php echo esc_url( (string) get_edit_post_link( $row['id'] ) ); ?>"><?php echo esc_html( Context::plain_title( $row['id'] ) ); ?></a> <span class="description">(<?php echo esc_html( (string) get_post_type_object( (string) get_post_type( $row['id'] ) )->labels->singular_name ); ?>)</span></td>
					<td><?php echo (int) $row['words']; ?></td>
					<td><?php echo (int) $row['images']; ?></td>
					<td>
						<?php if ( $row['words'] < Content_Audit::THIN ) : ?>
							<span class="mlab-seo-badge is-bad"><?php esc_html_e( 'Thin', 'mlab-seo' ); ?></span>
						<?php else : ?>
							<span class="mlab-seo-badge is-ok"><?php esc_html_e( 'Short', 'mlab-seo' ); ?></span>
						<?php endif; ?>
					</td>
				</tr>
			<?php endforeach; ?>
		</tbody>
	</table>
<?php else : ?>
	<p><span class="mlab-seo-badge is-good"><?php esc_html_e( 'Good', 'mlab-seo' ); ?></span> <?php esc_html_e( 'All indexable pages have enough text.', 'mlab-seo' ); ?></p>
<?php endif; ?>
<?php Admin::part( 'links' ); ?>
