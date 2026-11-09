<?php
/**
 * List of all pages with their SEO state, on the "Pages" screen.
 *
 * @package MomentumLab\SEO
 */

namespace MomentumLab\SEO;

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( '\WP_List_Table' ) ) {
	require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

/**
 * Pages with title, description and score. A click on a row opens the edit panel (assets/js/pages.js).
 */
final class Pages_Table extends \WP_List_Table {

	/**
	 * Rows per page.
	 */
	const PER_PAGE = 20;

	/**
	 * Constructor.
	 */
	public function __construct() {
		parent::__construct(
			array(
				'singular' => 'post',
				'plural'   => 'posts',
				'ajax'     => false,
				'screen'   => 'mlab-seo-pages',
			)
		);
	}

	/**
	 * Filters with their label and count, keyed by filter.
	 *
	 * @return array<string,array{0:string,1:int}>
	 */
	public static function filters() {
		$stats = Dashboard::stats();
		return array(
			''               => array( __( 'All', 'mlab-seo' ), $stats['total'] ),
			'no_description' => array( __( 'Without description', 'mlab-seo' ), $stats['no_description'] ),
			'poor'           => array( __( 'Low score', 'mlab-seo' ), $stats['poor'] ),
			'unchecked'      => array( __( 'Not checked', 'mlab-seo' ), $stats['unchecked'] ),
			'noindex'        => array( __( 'Hidden', 'mlab-seo' ), $stats['noindex'] ),
		);
	}

	/**
	 * Current filter from the query string.
	 *
	 * @return string
	 */
	public static function current_filter() {
		$filter = isset( $_GET['filter'] ) ? sanitize_key( wp_unslash( $_GET['filter'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- List filter.
		return isset( self::filters()[ $filter ] ) ? $filter : '';
	}

	/**
	 * Post type chosen in the list, or empty for all.
	 *
	 * @return string
	 */
	public static function current_type() {
		$type = isset( $_GET['post_type'] ) ? sanitize_key( wp_unslash( $_GET['post_type'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- List filter.
		return in_array( $type, Post_Meta::post_types(), true ) ? $type : '';
	}

	/**
	 * Columns.
	 *
	 * @return array
	 */
	public function get_columns() {
		return array(
			'cb'    => '<input type="checkbox" />',
			'title' => __( 'Page and Google text', 'mlab-seo' ),
			'type'  => __( 'Type', 'mlab-seo' ),
			'score' => __( 'Check', 'mlab-seo' ),
		);
	}

	/**
	 * Sortable columns.
	 *
	 * @return array
	 */
	protected function get_sortable_columns() {
		return array(
			'title' => array( 'title', false ),
			'score' => array( 'score', false ),
		);
	}

	/**
	 * Post types the filter applies to: hidden types never need a description or a score.
	 *
	 * @param string $filter Filter key.
	 * @return string[]
	 */
	private static function filter_types( $filter ) {
		$selected = self::current_type();
		$types    = '' !== $selected ? array( $selected ) : Post_Meta::post_types();
		if ( '' === $filter || 'noindex' === $filter ) {
			return $types;
		}
		return array_values(
			array_filter(
				$types,
				function ( $type ) use ( $filter ) {
					$template = Titles::post_type( $type );
					return ! $template['noindex'] && ( 'no_description' !== $filter || '' === trim( (string) $template['description'] ) );
				}
			)
		);
	}

	/**
	 * Meta query of a filter.
	 *
	 * @param string $filter Filter key.
	 * @return array
	 */
	private static function meta_query( $filter ) {
		$key     = fn ( $field ) => Post_Meta::PREFIX . $field;
		$visible = array(
			'key'     => $key( 'noindex' ),
			'compare' => 'NOT EXISTS',
		);
		switch ( $filter ) {
			case 'noindex':
				return array(
					array(
						'key'   => $key( 'noindex' ),
						'value' => '1',
					),
				);
			case 'no_description':
				return array(
					$visible,
					array(
						'key'     => $key( 'description' ),
						'compare' => 'NOT EXISTS',
					),
				);
			case 'unchecked':
				return array(
					$visible,
					array(
						'key'     => $key( 'score' ),
						'compare' => 'NOT EXISTS',
					),
				);
			case 'poor':
				return array(
					$visible,
					array(
						'key'     => $key( 'score' ),
						'value'   => Dashboard::POOR,
						'compare' => '<',
						'type'    => 'NUMERIC',
					),
				);
		}
		return array();
	}

	/**
	 * Loads the rows.
	 */
	public function prepare_items() {
		$filter = self::current_filter();
		$types  = self::filter_types( $filter );
		// phpcs:disable WordPress.Security.NonceVerification.Recommended -- List search and sorting.
		$search  = isset( $_GET['s'] ) ? sanitize_text_field( wp_unslash( $_GET['s'] ) ) : '';
		$orderby = isset( $_GET['orderby'] ) ? sanitize_key( wp_unslash( $_GET['orderby'] ) ) : '';
		$orderby = in_array( $orderby, array( 'title', 'score' ), true ) ? $orderby : 'modified';
		$order   = isset( $_GET['order'] ) && 'asc' === strtolower( sanitize_key( wp_unslash( $_GET['order'] ) ) ) ? 'ASC' : 'DESC';
		// phpcs:enable WordPress.Security.NonceVerification.Recommended
		$args = array(
			'post_type'              => $types ? $types : array( '__none__' ),
			'post_status'            => array( 'publish', 'draft', 'future', 'pending', 'private' ),
			'posts_per_page'         => self::PER_PAGE,
			'paged'                  => $this->get_pagenum(),
			's'                      => $search,
			'meta_query'             => self::meta_query( $filter ), // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query -- Filters of the list, paged.
			'orderby'                => $orderby,
			'order'                  => $order,
			'update_post_term_cache' => false,
		);
		if ( 'score' === $orderby ) {
			$args['meta_key'] = Post_Meta::PREFIX . 'score'; // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key -- Sorting by score, paged.
			$args['orderby']  = 'meta_value_num';
		}
		if ( '' !== $filter ) {
			// The counts of the filters cover published pages only.
			$args['post_status'] = array( 'publish' );
		}
		$query                 = new \WP_Query( $args );
		$this->items           = $query->posts;
		$this->_column_headers = array( $this->get_columns(), array(), $this->get_sortable_columns(), 'title' );
		$this->set_pagination_args(
			array(
				'total_items' => (int) $query->found_posts,
				'per_page'    => self::PER_PAGE,
			)
		);
	}

	/**
	 * Row with the post ID for the edit panel.
	 *
	 * @param \WP_Post $item Post.
	 */
	public function single_row( $item ) {
		printf( '<tr data-id="%d" tabindex="0">', (int) $item->ID );
		$this->single_row_columns( $item );
		echo '</tr>';
	}

	/**
	 * Checkbox column.
	 *
	 * @param \WP_Post $item Post.
	 * @return string
	 */
	protected function column_cb( $item ) {
		/* translators: %s: page title. */
		return sprintf( '<label class="screen-reader-text" for="mlab-seo-cb-%1$d">%2$s</label><input type="checkbox" id="mlab-seo-cb-%1$d" name="post[]" value="%1$d" />', (int) $item->ID, esc_html( sprintf( __( 'Select %s', 'mlab-seo' ), Context::plain_title( $item ) ) ) );
	}

	/**
	 * Title column with the Google text below.
	 *
	 * @param \WP_Post $item Post.
	 * @return string
	 */
	protected function column_title( $item ) {
		$desc   = Post_Meta::get( $item->ID, 'description' );
		$status = 'publish' !== $item->post_status ? ' <span class="mlab-seo-tag">' . esc_html( (string) get_post_status_object( $item->post_status )->label ) . '</span>' : '';
		$line   = '' !== $desc
			? '<span class="mlab-seo-row__desc">' . esc_html( $desc ) . '</span>'
			: '<span class="mlab-seo-row__desc is-missing">' . esc_html__( 'No own meta description', 'mlab-seo' ) . '</span>';
		return '<button type="button" class="mlab-seo-row__open">' . esc_html( Context::plain_title( $item ) ) . '</button>' . $status . $line;
	}

	/**
	 * Type column.
	 *
	 * @param \WP_Post $item Post.
	 * @return string
	 */
	protected function column_type( $item ) {
		return esc_html( (string) get_post_type_object( $item->post_type )->labels->singular_name );
	}

	/**
	 * Score column.
	 *
	 * @param \WP_Post $item Post.
	 * @return string
	 */
	protected function column_score( $item ) {
		if ( Post_Meta::get( $item->ID, 'noindex' ) ) {
			return '<span class="mlab-seo-tag">' . esc_html__( 'noindex', 'mlab-seo' ) . '</span>';
		}
		return Dashboard::score_badge( (int) Post_Meta::get( $item->ID, 'score' ) );
	}

	/**
	 * Own bulk actions; the form posts to admin-post.php, which needs its own action field.
	 *
	 * @param string $which Top or bottom.
	 */
	protected function bulk_actions( $which = '' ) {
		if ( 'top' !== $which ) {
			return;
		}
		?>
		<label for="mlab-seo-bulk" class="screen-reader-text"><?php esc_html_e( 'Bulk action', 'mlab-seo' ); ?></label>
		<select name="mlab_seo_bulk_action" id="mlab-seo-bulk">
			<option value=""><?php esc_html_e( 'Bulk action', 'mlab-seo' ); ?></option>
			<option value="noindex"><?php esc_html_e( 'Hide from search engines', 'mlab-seo' ); ?></option>
			<option value="index"><?php esc_html_e( 'Show in search engines', 'mlab-seo' ); ?></option>
		</select>
		<?php
		submit_button( __( 'Apply', 'mlab-seo' ), 'action', '', false );
	}

	/**
	 * Text when the list is empty.
	 */
	public function no_items() {
		esc_html_e( 'No pages match this filter.', 'mlab-seo' );
	}
}
