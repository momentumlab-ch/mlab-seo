<?php
/**
 * SEO title, meta description, focus keyword and noindex in Quick Edit and Bulk Edit of the post lists.
 *
 * @package MomentumLab\SEO
 */

namespace MomentumLab\SEO;

defined( 'ABSPATH' ) || exit;

/**
 * Fields in the inline editors of the lists; values come from the SEO column.
 */
final class Quick_Edit {

	/**
	 * Nonce action and field.
	 */
	const NONCE = 'mlab_seo_quick_edit';

	/**
	 * Registers hooks.
	 */
	public function hooks() {
		add_action( 'quick_edit_custom_box', array( $this, 'quick_fields' ), 10, 2 );
		add_action( 'bulk_edit_custom_box', array( $this, 'bulk_fields' ), 10, 2 );
		add_action( 'save_post', array( $this, 'save' ), 10, 2 );
		add_action( 'admin_enqueue_scripts', array( $this, 'assets' ) );
	}

	/**
	 * Script on the post lists.
	 *
	 * @param string $hook Admin page hook.
	 */
	public function assets( $hook ) {
		if ( 'edit.php' !== $hook ) {
			return;
		}
		wp_enqueue_script(
			'mlab-seo-quick-edit',
			MLAB_SEO_URL . 'assets/js/quick-edit.js',
			array( 'inline-edit-post' ),
			Admin::asset_version( 'assets/js/quick-edit.js' ),
			array(
				'in_footer' => true,
				'strategy'  => 'defer',
			)
		);
	}

	/**
	 * Current values for the Quick Edit fields, printed into the SEO column.
	 *
	 * @param int $post_id Post ID.
	 */
	public static function data( $post_id ) {
		printf(
			'<div class="hidden mlab-seo-quick-data" data-title="%s" data-description="%s" data-focus="%s" data-noindex="%d"></div>',
			esc_attr( Post_Meta::get( $post_id, 'title' ) ),
			esc_attr( Post_Meta::get( $post_id, 'description' ) ),
			esc_attr( Post_Meta::get( $post_id, 'focus' ) ),
			(int) Post_Meta::get( $post_id, 'noindex' )
		);
	}

	/**
	 * Fields in Quick Edit.
	 *
	 * @param string $column    Column name.
	 * @param string $post_type Post type.
	 */
	public function quick_fields( $column, $post_type ) {
		if ( 'mlab_seo' !== $column || ! in_array( $post_type, Post_Meta::post_types(), true ) ) {
			return;
		}
		wp_nonce_field( self::NONCE, self::NONCE );
		include MLAB_SEO_DIR . 'parts/quick-edit.php';
	}

	/**
	 * Field in Bulk Edit: noindex for all selected posts.
	 *
	 * @param string $column    Column name.
	 * @param string $post_type Post type.
	 */
	public function bulk_fields( $column, $post_type ) {
		if ( 'mlab_seo' !== $column || ! in_array( $post_type, Post_Meta::post_types(), true ) ) {
			return;
		}
		wp_nonce_field( self::NONCE, self::NONCE );
		?>
		<fieldset class="inline-edit-col-right"><div class="inline-edit-col"><label class="inline-edit-group">
			<span class="title"><?php esc_html_e( 'Search engines', 'mlab-seo' ); ?></span>
			<select name="mlab_seo_bulk_noindex">
				<option value=""><?php esc_html_e( '— No change —', 'mlab-seo' ); ?></option>
				<option value="0"><?php esc_html_e( 'Show in search results', 'mlab-seo' ); ?></option>
				<option value="1"><?php esc_html_e( 'Hide from search engines (noindex)', 'mlab-seo' ); ?></option>
			</select>
		</label></div></fieldset>
		<?php
	}

	/**
	 * Saves Quick Edit and Bulk Edit values.
	 *
	 * @param int      $post_id Post ID.
	 * @param \WP_Post $post    Post.
	 */
	public function save( $post_id, $post ) {
		if ( ! isset( $_REQUEST[ self::NONCE ] ) || ! wp_verify_nonce( sanitize_key( wp_unslash( $_REQUEST[ self::NONCE ] ) ), self::NONCE ) ) {
			return;
		}
		if ( wp_is_post_revision( $post_id ) || ! current_user_can( 'edit_post', $post_id ) || ! in_array( $post->post_type, Post_Meta::post_types(), true ) ) {
			return;
		}
		if ( isset( $_REQUEST['mlab_seo_quick'] ) && is_array( $_REQUEST['mlab_seo_quick'] ) ) {
			$in = wp_unslash( $_REQUEST['mlab_seo_quick'] ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Sanitised per field below.
			foreach ( array( 'title', 'description', 'focus', 'noindex' ) as $field ) {
				Meta_Box::store( $post_id, $field, Post_Meta::sanitize_field( $field, $in[ $field ] ?? '' ) );
			}
		}
		$bulk = isset( $_REQUEST['mlab_seo_bulk_noindex'] ) ? sanitize_key( wp_unslash( $_REQUEST['mlab_seo_bulk_noindex'] ) ) : '';
		if ( '' !== $bulk ) {
			Meta_Box::store( $post_id, 'noindex', Post_Meta::sanitize_field( 'noindex', '1' === $bulk ) );
		}
		Crawlers::flush_cache();
	}
}
