<?php
/**
 * SEO fields for categories, tags and other public taxonomies.
 *
 * @package MomentumLab\SEO
 */

namespace MomentumLab\SEO;

defined( 'ABSPATH' ) || exit;

/**
 * SEO title, meta description and noindex per term (term meta with the prefix _mlab_seo_).
 */
final class Term_Meta {

	/**
	 * Fields (key => type).
	 */
	const FIELDS = array(
		'title'       => 'string',
		'description' => 'string',
		'noindex'     => 'boolean',
		'og_title'    => 'string',
		'og_desc'     => 'string',
		'image'       => 'integer',
		'breadcrumb'  => 'string',
	);

	/**
	 * Nonce action and field.
	 */
	const NONCE = 'mlab_seo_term';

	/**
	 * Name of the nonce field. Differs from the fields array, or PHP would overwrite it.
	 */
	const NONCE_FIELD = 'mlab_seo_term_nonce';

	/**
	 * Registers hooks.
	 */
	public function hooks() {
		add_action( 'admin_init', array( $this, 'register_screens' ) );
	}

	/**
	 * Taxonomies with SEO fields.
	 *
	 * @return string[]
	 */
	public static function taxonomies() {
		$taxonomies = array_values(
			array_diff(
				get_taxonomies(
					array(
						'public'  => true,
						'show_ui' => true,
					)
				),
				array( 'post_format' )
			)
		);

		/**
		 * Filters the taxonomies that get SEO fields.
		 *
		 * @param string[] $taxonomies Taxonomy names.
		 */
		return (array) apply_filters( 'mlab_seo_taxonomies', $taxonomies );
	}

	/**
	 * Adds the fields to the edit screen of every taxonomy.
	 */
	public function register_screens() {
		foreach ( self::taxonomies() as $taxonomy ) {
			add_action( "{$taxonomy}_edit_form_fields", array( $this, 'render' ) );
			add_action( "edited_{$taxonomy}", array( $this, 'save' ) );
		}
	}

	/**
	 * Reads one field.
	 *
	 * @param int    $term_id Term ID.
	 * @param string $field   Field name without prefix.
	 * @return string|bool|int
	 */
	public static function get( $term_id, $field ) {
		$value = get_term_meta( $term_id, Post_Meta::PREFIX . $field, true );
		switch ( self::FIELDS[ $field ] ?? '' ) {
			case 'boolean':
				return (bool) $value;
			case 'integer':
				return absint( $value );
		}
		return (string) $value;
	}

	/**
	 * Stores one field; empty values are deleted.
	 *
	 * @param int    $term_id Term ID.
	 * @param string $field   Field name.
	 * @param mixed  $value   Raw value.
	 */
	public static function set( $term_id, $field, $value ) {
		switch ( $field ) {
			case 'noindex':
				$value = $value ? 1 : '';
				break;
			case 'image':
				$value = absint( $value ) ? absint( $value ) : '';
				break;
			case 'og_desc':
			case 'description':
				$value = sanitize_textarea_field( (string) $value );
				break;
			default:
				$value = sanitize_text_field( (string) $value );
		}
		if ( '' === $value ) {
			delete_term_meta( $term_id, Post_Meta::PREFIX . $field );
		} else {
			update_term_meta( $term_id, Post_Meta::PREFIX . $field, $value );
		}
	}

	/**
	 * Rows on the term edit screen.
	 *
	 * @param \WP_Term $term Term.
	 */
	public function render( $term ) {
		wp_enqueue_media();
		wp_enqueue_script(
			'mlab-seo-settings',
			MLAB_SEO_URL . 'assets/js/settings.js',
			array( 'jquery', 'wp-i18n' ),
			Admin::asset_version( 'assets/js/settings.js' ),
			array(
				'in_footer' => true,
				'strategy'  => 'defer',
			)
		);
		wp_set_script_translations( 'mlab-seo-settings', 'mlab-seo', MLAB_SEO_DIR . 'languages' );
		wp_enqueue_style( 'mlab-seo-admin', MLAB_SEO_URL . 'assets/css/admin.css', array(), Admin::asset_version( 'assets/css/admin.css' ) );
		wp_nonce_field( self::NONCE, self::NONCE_FIELD );
		$template = Titles::taxonomy( $term->taxonomy );
		include MLAB_SEO_DIR . 'parts/term-fields.php';
	}

	/**
	 * Saves the rows.
	 *
	 * @param int $term_id Term ID.
	 */
	public function save( $term_id ) {
		if ( ! isset( $_POST[ self::NONCE_FIELD ] ) || ! wp_verify_nonce( sanitize_key( wp_unslash( $_POST[ self::NONCE_FIELD ] ) ), self::NONCE ) ) {
			return;
		}
		if ( ! current_user_can( 'edit_term', $term_id ) ) {
			return;
		}
		$in = isset( $_POST['mlab_seo_term'] ) && is_array( $_POST['mlab_seo_term'] ) ? wp_unslash( $_POST['mlab_seo_term'] ) : array(); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Sanitised in set().
		foreach ( array_keys( self::FIELDS ) as $field ) {
			self::set( $term_id, $field, $in[ $field ] ?? '' );
		}
	}
}
