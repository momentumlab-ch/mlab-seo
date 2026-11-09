<?php
/**
 * REST routes of the SEO screens: save one settings segment, read and save the SEO data of a page.
 *
 * All routes need a logged-in user with the right capability; WordPress checks the REST nonce.
 *
 * @package MomentumLab\SEO
 */

namespace MomentumLab\SEO;

defined( 'ABSPATH' ) || exit;

/**
 * Routes under mlab-seo/v1.
 */
final class Rest {

	/**
	 * Namespace.
	 */
	const NS = 'mlab-seo/v1';

	/**
	 * Fields the page list may change. Everything else stays with the editor.
	 */
	const SHEET_FIELDS = array( 'title', 'description', 'focus', 'noindex', 'llms', 'canonical', 'page_type', 'breadcrumb', 'og_title', 'og_desc', 'image', 'score' );

	/**
	 * Most pages read or scored in one request.
	 */
	const MAX_BATCH = 50;

	/**
	 * Registers hooks.
	 */
	public function hooks() {
		add_action( 'rest_api_init', array( $this, 'routes' ) );
	}

	/**
	 * Registers the routes.
	 */
	public function routes() {
		register_rest_route(
			self::NS,
			'/settings',
			array(
				'methods'             => \WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'save_settings' ),
				'permission_callback' => fn () => current_user_can( Admin::capability() ),
				'args'                => array(
					'page'    => array(
						'type'     => 'string',
						'required' => true,
					),
					'segment' => array(
						'type'     => 'string',
						'required' => true,
					),
					'values'  => array(
						'type'    => 'object',
						'default' => array(),
					),
				),
			)
		);
		register_rest_route(
			self::NS,
			'/posts',
			array(
				'methods'             => \WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_posts' ),
				'permission_callback' => fn () => current_user_can( 'edit_posts' ),
				'args'                => array(
					'ids' => array(
						'type'     => 'array',
						'items'    => array( 'type' => 'integer' ),
						'required' => true,
					),
				),
			)
		);
		register_rest_route(
			self::NS,
			'/posts/(?P<id>\d+)',
			array(
				'methods'             => \WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'save_post' ),
				'permission_callback' => fn ( $request ) => self::can_edit( (int) $request['id'] ),
				'args'                => array(
					'fields' => array(
						'type'     => 'object',
						'required' => true,
					),
				),
			)
		);
	}

	/**
	 * Whether the current user may edit the SEO data of a post.
	 *
	 * @param int $post_id Post ID.
	 * @return bool
	 */
	private static function can_edit( $post_id ) {
		return $post_id > 0
			&& in_array( get_post_type( $post_id ), Post_Meta::post_types(), true )
			&& current_user_can( 'edit_post', $post_id );
	}

	/**
	 * Saves the settings of one segment.
	 *
	 * @param \WP_REST_Request $request Request.
	 * @return \WP_REST_Response|\WP_Error
	 */
	public function save_settings( $request ) {
		$keys = Admin_Actions::segment_keys( sanitize_key( $request['page'] ), sanitize_key( $request['segment'] ) );
		if ( null === $keys ) {
			return new \WP_Error( 'mlab_seo_segment', __( 'Unknown settings form.', 'mlab-seo' ), array( 'status' => 400 ) );
		}
		$values = is_array( $request['values'] ) ? $request['values'] : array();
		// REST values are unslashed; the settings sanitiser expects form data as in $_POST.
		Settings::save_keys( wp_slash( $values ), $keys );
		return rest_ensure_response( array( 'saved' => true ) );
	}

	/**
	 * SEO data of several pages for the page list (editing and scoring). Pages the user may not edit are left out.
	 *
	 * @param \WP_REST_Request $request Request.
	 * @return \WP_REST_Response
	 */
	public function get_posts( $request ) {
		$ids  = array_slice( array_unique( array_map( 'absint', (array) $request['ids'] ) ), 0, self::MAX_BATCH );
		$data = array();
		foreach ( $ids as $id ) {
			if ( self::can_edit( $id ) ) {
				$data[] = self::post_data( get_post( $id ) );
			}
		}
		return rest_ensure_response( $data );
	}

	/**
	 * Saves fields of one page.
	 *
	 * @param \WP_REST_Request $request Request.
	 * @return \WP_REST_Response
	 */
	public function save_post( $request ) {
		$id     = (int) $request['id'];
		$fields = is_array( $request['fields'] ) ? $request['fields'] : array();
		foreach ( array_intersect_key( $fields, array_flip( self::SHEET_FIELDS ) ) as $field => $value ) {
			Meta_Box::store( $id, $field, Post_Meta::sanitize_field( $field, is_scalar( $value ) ? $value : '' ) );
		}
		Dashboard::flush_cache();
		Crawlers::flush_cache();
		return rest_ensure_response( self::post_data( get_post( $id ) ) );
	}

	/**
	 * Everything the page list needs for one page: fields, fallbacks, previews and the input of the SEO check.
	 *
	 * @param \WP_Post $post Post.
	 * @return array
	 */
	public static function post_data( $post ) {
		$id       = $post->ID;
		$fallback = Context::for_post( $id );
		$fields   = array();
		foreach ( self::SHEET_FIELDS as $field ) {
			$fields[ $field ] = Post_Meta::get( $id, $field );
		}
		$image = Context::image( (int) $fallback['image'], 'medium' );
		if ( ! $image ) {
			$image = Context::image( (int) Settings::get( 'default_image' ), 'medium' );
		}
		return array(
			'id'        => $id,
			'name'      => Context::plain_title( $post ),
			'type'      => (string) get_post_type_object( $post->post_type )->labels->singular_name,
			'edit'      => (string) get_edit_post_link( $id, 'raw' ),
			'url'       => (string) get_permalink( $id ),
			'slug'      => $post->post_name,
			'isFront'   => Context::is_front( $id ),
			'fields'    => $fields,
			'fallback'  => array(
				'title'       => $fallback['title'],
				'description' => $fallback['description'],
			),
			'image'     => $image ? $image['url'] : '',
			'featured'  => has_post_thumbnail( $id ),
			'crumbs'    => wp_list_pluck( array_slice( Breadcrumbs::for_post( $id ), 1, -1 ), 'name' ),
			'content'   => self::analysis_content( $post ),
			'noindexed' => (bool) $fallback['noindex'],
			'vars'      => Meta_Box::variable_values( $post ),
			'pattern'   => Titles::post_type( $post->post_type )['title'],
			'date'      => Meta_Box::preview_date( $post ),
		);
	}

	/**
	 * Content for the SEO check: the post content plus the text of FAQ blocks, which is stored in block attributes.
	 *
	 * @param \WP_Post $post Post.
	 * @return string
	 */
	private static function analysis_content( $post ) {
		$faq = '';
		foreach ( FAQ::for_post( $post->ID ) as $item ) {
			$faq .= ' <h3>' . esc_html( $item['question'] ) . '</h3> <p>' . wp_strip_all_tags( $item['answer'] ) . '</p> ';
		}
		return $post->post_content . $faq;
	}
}
