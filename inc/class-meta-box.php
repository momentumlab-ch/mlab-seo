<?php
/**
 * The SEO box in the post editor: Google preview, SEO check, social image, advanced and Pro fields.
 *
 * @package MomentumLab\SEO
 */

namespace MomentumLab\SEO;

defined( 'ABSPATH' ) || exit;

/**
 * Editor meta box and list column.
 */
final class Meta_Box {

	/**
	 * Nonce action and field.
	 */
	const NONCE = 'mlab_seo_meta_box';

	/**
	 * Registers hooks.
	 */
	public function hooks() {
		add_action( 'add_meta_boxes', array( $this, 'add' ) );
		add_action( 'save_post', array( $this, 'save' ), 10, 2 );
		add_action( 'admin_enqueue_scripts', array( $this, 'assets' ) );
		add_action( 'admin_init', array( $this, 'columns' ) );
	}

	/**
	 * Adds the SEO column to the lists, once all content types are registered.
	 */
	public function columns() {
		foreach ( Post_Meta::post_types() as $type ) {
			add_filter( "manage_{$type}_posts_columns", array( $this, 'column' ) );
			add_action( "manage_{$type}_posts_custom_column", array( $this, 'column_value' ), 10, 2 );
		}
	}

	/**
	 * Adds the box to all supported post types.
	 */
	public function add() {
		foreach ( Post_Meta::post_types() as $type ) {
			add_meta_box( 'mlab-seo', __( 'MomentumLab SEO', 'mlab-seo' ), array( $this, 'render' ), $type, 'normal', 'high' );
		}
	}

	/**
	 * Scripts and styles on the edit screen.
	 *
	 * @param string $hook Admin page hook.
	 */
	public function assets( $hook ) {
		$screen = get_current_screen();
		if ( ! in_array( $hook, array( 'post.php', 'post-new.php' ), true ) || ! $screen || ! in_array( $screen->post_type, Post_Meta::post_types(), true ) ) {
			return;
		}
		$post = get_post();
		wp_enqueue_media();
		wp_enqueue_style( 'mlab-seo-admin', MLAB_SEO_URL . 'assets/css/admin.css', array(), Admin::asset_version( 'assets/css/admin.css' ) );
		wp_enqueue_script(
			'mlab-seo-editor',
			MLAB_SEO_URL . 'assets/js/editor.js',
			array( 'jquery', 'wp-i18n' ),
			Admin::asset_version( 'assets/js/editor.js' ),
			array(
				'in_footer' => true,
				'strategy'  => 'defer',
			)
		);
		wp_set_script_translations( 'mlab-seo-editor', 'mlab-seo', MLAB_SEO_DIR . 'languages' );
		wp_localize_script(
			'mlab-seo-editor',
			'mlabSeoEditor',
			array(
				'separator'    => Settings::get( 'separator' ),
				'siteName'     => Settings::site_name(),
				'homeUrl'      => home_url( '/' ),
				'titlePattern' => Titles::post_type( $screen->post_type )['title'],
				'vars'         => self::variable_values( $post ),
			)
		);
	}

	/**
	 * Values of the title variables for the preview, except %title%, which follows the editor.
	 *
	 * @param \WP_Post|null $post Post being edited.
	 * @return array<string,string>
	 */
	private static function variable_values( $post ) {
		$values = array();
		foreach ( array_keys( Titles::variables() ) as $variable ) {
			$name            = trim( $variable, '%' );
			$values[ $name ] = (string) Titles::variable( $name, $post );
		}
		unset( $values['title'] );
		return $values;
	}

	/**
	 * Renders the box.
	 *
	 * @param \WP_Post $post Post.
	 */
	public function render( $post ) {
		$id       = $post->ID;
		$title    = Post_Meta::get( $id, 'title' );
		$desc     = Post_Meta::get( $id, 'description' );
		$focus    = Post_Meta::get( $id, 'focus' );
		$image    = Post_Meta::get( $id, 'image' );
		$fallback = Context::for_post( $id );
		$img      = Context::image( $image, 'medium' );
		$pro      = License::is_pro();
		$event    = array_merge( self::empty_event(), Post_Meta::get( $id, 'event' ) );

		wp_nonce_field( self::NONCE, self::NONCE );
		include MLAB_SEO_DIR . 'parts/meta-box/box.php';
	}

	/**
	 * Event fields with empty values.
	 *
	 * @return array
	 */
	private static function empty_event() {
		$event = array_fill_keys( array( 'name', 'description', 'start', 'end', 'sport', 'location', 'street', 'zip', 'city', 'country', 'url', 'offer_url', 'price', 'currency' ), '' );
		return array_merge(
			$event,
			array(
				'type'   => 'Event',
				'status' => 'EventScheduled',
				'sub'    => array(),
			)
		);
	}

	/**
	 * One event input.
	 *
	 * @param string $key         Field key.
	 * @param string $label       Label.
	 * @param array  $event       Event data.
	 * @param string $type        Input type.
	 * @param string $placeholder Placeholder.
	 */
	public static function event_field( $key, $label, $event, $type = 'text', $placeholder = '' ) {
		printf(
			'<p><label class="mlab-seo__label" for="mlab-seo-event-%1$s">%2$s</label><input type="%3$s" class="widefat" id="mlab-seo-event-%1$s" name="mlab_seo_event[%1$s]" value="%4$s" placeholder="%5$s" /></p>',
			esc_attr( $key ),
			esc_html( $label ),
			esc_attr( $type ),
			esc_attr( (string) $event[ $key ] ),
			esc_attr( $placeholder )
		);
	}

	/**
	 * Saves the box.
	 *
	 * @param int      $post_id Post ID.
	 * @param \WP_Post $post    Post.
	 */
	public function save( $post_id, $post ) {
		if ( ! isset( $_POST[ self::NONCE ] ) || ! wp_verify_nonce( sanitize_key( wp_unslash( $_POST[ self::NONCE ] ) ), self::NONCE ) ) {
			return;
		}
		if ( wp_is_post_autosave( $post_id ) || wp_is_post_revision( $post_id ) || ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}
		if ( ! in_array( $post->post_type, Post_Meta::post_types(), true ) ) {
			return;
		}

		// Values are sanitised per field in Post_Meta::sanitize_field().
		$in = isset( $_POST['mlab_seo'] ) && is_array( $_POST['mlab_seo'] ) ? wp_unslash( $_POST['mlab_seo'] ) : array(); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		foreach ( array_keys( Post_Meta::FIELDS ) as $field ) {
			if ( 'schema' !== $field || License::is_pro() ) {
				self::store( $post_id, $field, Post_Meta::sanitize_field( $field, $in[ $field ] ?? '' ) );
			}
		}
		if ( License::is_pro() ) {
			self::store( $post_id, 'event', Post_Meta::sanitize_event( isset( $_POST['mlab_seo_event'] ) ? wp_unslash( $_POST['mlab_seo_event'] ) : array() ) ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Sanitised in sanitize_event().
		}
		if ( Hreflang::languages() && isset( $_POST['mlab_seo_hreflang'] ) ) {
			self::store( $post_id, 'hreflang', Hreflang::sanitize( wp_unslash( $_POST['mlab_seo_hreflang'] ) ) ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Sanitised in Hreflang::sanitize().
		}
		Crawlers::flush_cache();
	}

	/**
	 * Stores a sanitised value; empty values are deleted.
	 *
	 * @param int    $post_id Post ID.
	 * @param string $field   Field name.
	 * @param mixed  $value   Sanitised value.
	 */
	public static function store( $post_id, $field, $value ) {
		if ( '' === $value || 0 === $value || array() === $value ) {
			delete_post_meta( $post_id, Post_Meta::PREFIX . $field );
		} else {
			update_post_meta( $post_id, Post_Meta::PREFIX . $field, $value );
		}
	}

	/**
	 * Adds the SEO column.
	 *
	 * @param array $columns Columns.
	 * @return array
	 */
	public function column( $columns ) {
		$columns['mlab_seo'] = __( 'SEO', 'mlab-seo' );
		return $columns;
	}

	/**
	 * Prints the SEO column.
	 *
	 * @param string $column  Column.
	 * @param int    $post_id Post ID.
	 */
	public function column_value( $column, $post_id ) {
		if ( 'mlab_seo' !== $column ) {
			return;
		}
		Quick_Edit::data( $post_id );
		if ( Post_Meta::get( $post_id, 'noindex' ) ) {
			echo '<span class="mlab-seo-badge is-off">' . esc_html__( 'noindex', 'mlab-seo' ) . '</span>';
			return;
		}
		$score = Post_Meta::get( $post_id, 'score' );
		if ( ! $score ) {
			echo '<span aria-hidden="true">–</span><span class="screen-reader-text">' . esc_html__( 'Not checked yet', 'mlab-seo' ) . '</span>';
			return;
		}
		$class = 'is-bad';
		if ( $score >= 50 ) {
			$class = $score >= 80 ? 'is-good' : 'is-ok';
		}
		printf( '<span class="mlab-seo-badge %s">%d</span>', esc_attr( $class ), (int) $score );
	}
}
