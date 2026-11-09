<?php
/**
 * Pages: filters, list and the edit panel that opens on a row.
 *
 * @package MomentumLab\SEO
 */

namespace MomentumLab\SEO;

defined( 'ABSPATH' ) || exit;
// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- Included inside Admin::part(), the variables are local.

require_once MLAB_SEO_DIR . 'inc/class-pages-table.php';

$table   = new Pages_Table();
$current = Pages_Table::current_filter();
$type    = Pages_Table::current_type();
$table->prepare_items();
?>
<div class="mlab-seo-toolbar">
	<nav class="mlab-seo-filters" aria-label="<?php esc_attr_e( 'Filter', 'mlab-seo' ); ?>">
		<?php foreach ( Pages_Table::filters() as $key => $filter ) : ?>
			<a href="<?php echo esc_url( Admin::pages_url( $key ) ); ?>" <?php echo $key === $current ? 'aria-current="page"' : ''; ?>><?php echo esc_html( $filter[0] ); ?> <span><?php echo (int) $filter[1]; ?></span></a>
		<?php endforeach; ?>
	</nav>
	<form method="get" class="mlab-seo-search">
		<input type="hidden" name="page" value="mlab-seo-pages" />
		<?php if ( '' !== $current ) : ?>
			<input type="hidden" name="filter" value="<?php echo esc_attr( $current ); ?>" />
		<?php endif; ?>
		<label for="mlab-seo-type" class="screen-reader-text"><?php esc_html_e( 'Content type', 'mlab-seo' ); ?></label>
		<select name="post_type" id="mlab-seo-type" data-autosubmit>
			<option value=""><?php esc_html_e( 'All content types', 'mlab-seo' ); ?></option>
			<?php foreach ( Post_Meta::post_types() as $type_name ) : ?>
				<option value="<?php echo esc_attr( $type_name ); ?>" <?php selected( $type, $type_name ); ?>><?php echo esc_html( (string) get_post_type_object( $type_name )->labels->name ); ?></option>
			<?php endforeach; ?>
		</select>
		<?php $table->search_box( __( 'Search pages', 'mlab-seo' ), 'mlab-seo-search' ); ?>
	</form>
</div>

<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="mlab-seo-list">
	<input type="hidden" name="action" value="mlab_seo_bulk" />
	<?php wp_nonce_field( 'mlab_seo_bulk' ); ?>
	<?php $table->display(); ?>
</form>
<p class="description"><?php esc_html_e( 'Pages without a score are checked as soon as they appear in this list. Event, schema, robots rules and translations are edited in the editor.', 'mlab-seo' ); ?></p>

<?php Admin::part( 'pages-sheet' ); ?>
