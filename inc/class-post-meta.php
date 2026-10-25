<?php
/**
 * Per-post SEO fields: registration, reading and sanitising.
 *
 * @package MomentumLab\SEO
 */

namespace MomentumLab\SEO;

defined( 'ABSPATH' ) || exit;

/**
 * All SEO data of a post lives in post meta with the prefix _mlab_seo_.
 */
final class Post_Meta {

	/**
	 * Meta key prefix.
	 */
	const PREFIX = '_mlab_seo_';

	/**
	 * Scalar fields (key => type).
	 */
	const FIELDS = array(
		'title'        => 'string',
		'description'  => 'string',
		'focus'        => 'string',
		'image'        => 'integer',
		'noindex'      => 'boolean',
		'nofollow'     => 'boolean',
		'canonical'    => 'string',
		'page_type'    => 'string',
		'llms'         => 'string',
		'schema'       => 'string',
		'score'        => 'integer',
		'og_title'     => 'string',
		'og_desc'      => 'string',
		'robots'       => 'string',
		'primary_term' => 'integer',
		'breadcrumb'   => 'string',
		'article_type' => 'string',
	);

	/**
	 * Extra robots directives offered per page.
	 */
	const ROBOTS = array( 'noarchive', 'nosnippet', 'noimageindex' );

	/**
	 * Page types (schema.org) offered in the editor.
	 */
	const PAGE_TYPES = array( 'WebPage', 'AboutPage', 'ContactPage', 'CollectionPage', 'FAQPage', 'ItemPage', 'ProfilePage' );

	/**
	 * Event types offered in the editor (Pro).
	 */
	const EVENT_TYPES = array( 'Event', 'SportsEvent', 'MusicEvent', 'Festival', 'BusinessEvent', 'EducationEvent', 'ExhibitionEvent', 'SocialEvent' );

	/**
	 * Event status values.
	 */
	const EVENT_STATUS = array( 'EventScheduled', 'EventPostponed', 'EventRescheduled', 'EventMovedOnline', 'EventCancelled' );

	/**
	 * Registers hooks.
	 */
	public function hooks() {
		add_action( 'init', array( $this, 'register' ) );
	}

	/**
	 * Post types that get SEO fields.
	 *
	 * @return string[]
	 */
	public static function post_types() {
		$types = array_values( array_diff( get_post_types( array( 'public' => true ) ), array( 'attachment' ) ) );

		/**
		 * Filters the post types that get the SEO box.
		 *
		 * @param string[] $types Post type names.
		 */
		return (array) apply_filters( 'mlab_seo_post_types', $types );
	}

	/**
	 * Registers the meta keys (protected, REST-visible for editors).
	 */
	public function register() {
		foreach ( self::post_types() as $type ) {
			foreach ( self::FIELDS as $field => $data_type ) {
				register_post_meta(
					$type,
					self::PREFIX . $field,
					array(
						'type'              => $data_type,
						'single'            => true,
						'show_in_rest'      => 'schema' !== $field,
						'sanitize_callback' => function ( $value ) use ( $field ) {
							return Post_Meta::sanitize_field( $field, $value );
						},
						'auth_callback'     => array( self::class, 'can_edit' ),
					)
				);
			}
			foreach ( array( 'event', 'hreflang' ) as $field ) {
				register_post_meta(
					$type,
					self::PREFIX . $field,
					array(
						'type'          => 'object',
						'single'        => true,
						'show_in_rest'  => false,
						'auth_callback' => array( self::class, 'can_edit' ),
					)
				);
			}
		}
	}

	/**
	 * Meta capability check: only users who may edit the post change its SEO data.
	 *
	 * @param bool   $allowed  Whether allowed so far.
	 * @param string $meta_key Meta key.
	 * @param int    $post_id  Post ID.
	 * @return bool
	 */
	public static function can_edit( $allowed, $meta_key, $post_id ) {
		return current_user_can( 'edit_post', $post_id );
	}

	/**
	 * Extra robots directives of a post (noarchive, nosnippet, noimageindex).
	 *
	 * @param int $post_id Post ID.
	 * @return string[]
	 */
	public static function robots( $post_id ) {
		return array_values( array_intersect( self::ROBOTS, explode( ',', self::get( $post_id, 'robots' ) ) ) );
	}

	/**
	 * Reads one field.
	 *
	 * @param int    $post_id Post ID.
	 * @param string $field   Field name without prefix.
	 * @return mixed
	 */
	public static function get( $post_id, $field ) {
		$value = get_post_meta( $post_id, self::PREFIX . $field, true );
		switch ( self::FIELDS[ $field ] ?? 'array' ) {
			case 'integer':
				return absint( $value );
			case 'boolean':
				return (bool) $value;
			case 'array':
				return is_array( $value ) ? $value : array();
			default:
				return (string) $value;
		}
	}

	/**
	 * Sanitises one scalar field.
	 *
	 * @param string $field Field name.
	 * @param mixed  $value Raw value.
	 * @return mixed
	 */
	public static function sanitize_field( $field, $value ) {
		switch ( $field ) {
			case 'image':
			case 'primary_term':
				return absint( $value );
			case 'score':
				return min( 100, absint( $value ) );
			case 'noindex':
			case 'nofollow':
				return $value ? 1 : 0;
			case 'canonical':
				return esc_url_raw( (string) $value, array( 'https', 'http' ) );
			case 'page_type':
				return in_array( $value, self::PAGE_TYPES, true ) ? $value : '';
			case 'llms':
				return in_array( $value, array( 'optional', 'hide' ), true ) ? $value : '';
			case 'article_type':
				return in_array( $value, array_merge( Titles::ARTICLE_TYPES, array( 'none' ) ), true ) ? $value : '';
			case 'robots':
				$list = is_array( $value ) ? $value : explode( ',', (string) $value );
				return implode( ',', array_intersect( self::ROBOTS, array_map( 'trim', $list ) ) );
			case 'description':
			case 'og_desc':
				return sanitize_textarea_field( (string) $value );
			case 'schema':
				$json = json_decode( (string) $value, true );
				return is_array( $json ) ? (string) wp_json_encode( $json, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT ) : '';
			default:
				return sanitize_text_field( (string) $value );
		}
	}

	/**
	 * Sanitises the event structure.
	 *
	 * @param array $in Raw event data.
	 * @return array Empty when no event name is given.
	 */
	public static function sanitize_event( $in ) {
		if ( ! is_array( $in ) || '' === trim( (string) ( $in['name'] ?? '' ) ) ) {
			return array();
		}
		$text = function ( $key ) use ( $in ) {
			return isset( $in[ $key ] ) ? sanitize_text_field( (string) $in[ $key ] ) : '';
		};
		$url  = function ( $key ) use ( $in ) {
			return isset( $in[ $key ] ) ? esc_url_raw( (string) $in[ $key ], array( 'https', 'http' ) ) : '';
		};

		return array(
			'type'        => in_array( $in['type'] ?? '', self::EVENT_TYPES, true ) ? $in['type'] : 'Event',
			'name'        => $text( 'name' ),
			'description' => isset( $in['description'] ) ? sanitize_textarea_field( (string) $in['description'] ) : '',
			'start'       => self::event_date( $in['start'] ?? '' ),
			'end'         => self::event_date( $in['end'] ?? '' ),
			'status'      => in_array( $in['status'] ?? '', self::EVENT_STATUS, true ) ? $in['status'] : 'EventScheduled',
			'sport'       => $text( 'sport' ),
			'location'    => $text( 'location' ),
			'street'      => $text( 'street' ),
			'zip'         => $text( 'zip' ),
			'city'        => $text( 'city' ),
			'country'     => strtoupper( substr( $text( 'country' ), 0, 2 ) ),
			'url'         => $url( 'url' ),
			'offer_url'   => $url( 'offer_url' ),
			'price'       => preg_replace( '/[^0-9.]/', '', $text( 'price' ) ),
			'currency'    => strtoupper( substr( preg_replace( '/[^A-Za-z]/', '', $text( 'currency' ) ), 0, 3 ) ),
			'sub'         => self::sanitize_sub_events( $in['sub'] ?? array() ),
		);
	}

	/**
	 * Date or date and time (YYYY-MM-DD or YYYY-MM-DDTHH:MM), else empty.
	 *
	 * @param mixed $value Raw value.
	 * @return string
	 */
	private static function event_date( $value ) {
		$value = sanitize_text_field( (string) $value );
		return preg_match( '/^\d{4}-\d{2}-\d{2}(T\d{2}:\d{2})?$/', $value ) ? $value : '';
	}

	/**
	 * Sub-events with a name.
	 *
	 * @param mixed $rows Raw rows.
	 * @return array
	 */
	private static function sanitize_sub_events( $rows ) {
		$subs = array();
		foreach ( is_array( $rows ) ? $rows : array() as $sub ) {
			$name = isset( $sub['name'] ) ? sanitize_text_field( (string) $sub['name'] ) : '';
			if ( '' === $name ) {
				continue;
			}
			$subs[] = array(
				'name'     => $name,
				'start'    => self::event_date( $sub['start'] ?? '' ),
				'location' => isset( $sub['location'] ) ? sanitize_text_field( (string) $sub['location'] ) : '',
			);
		}
		return $subs;
	}
}
