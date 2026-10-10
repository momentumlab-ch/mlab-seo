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
	);

	/**
	 * Nonce action and field.
	 */
	const NONCE = 'mlab_seo_term';

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
	 * @return string|bool
	 */
	public static function get( $term_id, $field ) {
		$value = get_term_meta( $term_id, Post_Meta::PREFIX . $field, true );
		return 'boolean' === ( self::FIELDS[ $field ] ?? '' ) ? (bool) $value : (string) $value;
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
		$sep = ' ' . Settings::get( 'separator' ) . ' ';
		wp_nonce_field( self::NONCE, self::NONCE );
		?>
		<tr class="form-field"><th scope="row" colspan="2"><h2><?php esc_html_e( 'MomentumLab SEO', 'mlab-seo' ); ?></h2></th></tr>
		<tr class="form-field">
			<th scope="row"><label for="mlab-seo-term-title"><?php esc_html_e( 'SEO title', 'mlab-seo' ); ?></label></th>
			<td><input type="text" id="mlab-seo-term-title" name="mlab_seo_term[title]" value="<?php echo esc_attr( self::get( $term->term_id, 'title' ) ); ?>" placeholder="<?php echo esc_attr( $term->name . $sep . Settings::site_name() ); ?>" />
			<p class="description"><?php esc_html_e( 'Up to about 60 characters.', 'mlab-seo' ); ?></p></td>
		</tr>
		<tr class="form-field">
			<th scope="row"><label for="mlab-seo-term-description"><?php esc_html_e( 'Meta description', 'mlab-seo' ); ?></label></th>
			<td><textarea id="mlab-seo-term-description" name="mlab_seo_term[description]" rows="3"><?php echo esc_textarea( self::get( $term->term_id, 'description' ) ); ?></textarea>
			<p class="description"><?php esc_html_e( '120 to 160 characters. Empty: the description of the term.', 'mlab-seo' ); ?></p></td>
		</tr>
		<tr class="form-field">
			<th scope="row"><?php esc_html_e( 'Search engines', 'mlab-seo' ); ?></th>
			<td><label><input type="checkbox" name="mlab_seo_term[noindex]" value="1" <?php checked( self::get( $term->term_id, 'noindex' ) ); ?> /> <?php esc_html_e( 'Hide from search engines (noindex)', 'mlab-seo' ); ?></label></td>
		</tr>
		<?php
	}

	/**
	 * Saves the rows.
	 *
	 * @param int $term_id Term ID.
	 */
	public function save( $term_id ) {
		if ( ! isset( $_POST[ self::NONCE ] ) || ! wp_verify_nonce( sanitize_key( wp_unslash( $_POST[ self::NONCE ] ) ), self::NONCE ) ) {
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
