<?php
/**
 * Resolves the SEO data of the current request (or of a given post).
 *
 * @package MomentumLab\SEO
 */

namespace MomentumLab\SEO;

defined( 'ABSPATH' ) || exit;

/**
 * Title, description, image, URL and robots for the page being rendered.
 */
final class Context {

	/**
	 * Cached result for the current request.
	 *
	 * @var array|null
	 */
	private static $current = null;

	/**
	 * SEO data of the current request.
	 *
	 * @return array{post_id:int,title:string,description:string,image:int,url:string,noindex:bool,nofollow:bool,robots:string[],og_type:string,og_title:string,og_desc:string}
	 */
	public static function current() {
		if ( null !== self::$current ) {
			return self::$current;
		}

		$data = array_merge( self::empty_data(), self::for_request() );
		if ( 0 === $data['image'] ) {
			$data['image'] = (int) Settings::get( 'default_image' );
		}
		if ( is_wp_error( $data['url'] ) ) {
			$data['url'] = '';
		}
		$data = self::paginate( $data );

		/**
		 * Filters the SEO data of the current request.
		 *
		 * @param array $data SEO data.
		 */
		self::$current = (array) apply_filters( 'mlab_seo_context', $data );
		return self::$current;
	}

	/**
	 * Data keys with empty values.
	 *
	 * @return array
	 */
	private static function empty_data() {
		return array(
			'post_id'     => 0,
			'title'       => '',
			'description' => '',
			'image'       => 0,
			'url'         => '',
			'noindex'     => false,
			'nofollow'    => false,
			'robots'      => array(),
			'og_type'     => 'website',
			'og_title'    => '',
			'og_desc'     => '',
		);
	}

	/**
	 * SEO data by the type of the request.
	 *
	 * @return array
	 */
	private static function for_request() {
		if ( is_front_page() && ! is_page() ) {
			return array(
				'title'       => (string) Settings::get( 'home_title' ),
				'description' => (string) Settings::get( 'home_description' ),
				'url'         => home_url( '/' ),
			);
		}
		if ( is_singular() || ( is_home() && get_option( 'page_for_posts' ) ) ) {
			return self::for_post( is_home() ? (int) get_option( 'page_for_posts' ) : get_queried_object_id() );
		}
		if ( is_category() || is_tag() || is_tax() ) {
			return self::for_term( get_queried_object() );
		}
		if ( is_post_type_archive() ) {
			$type = get_query_var( 'post_type' );
			$type = is_array( $type ) ? reset( $type ) : $type;
			return array(
				'title' => post_type_archive_title( '', false ) . ' ' . Settings::get( 'separator' ) . ' ' . Settings::site_name(),
				'url'   => (string) get_post_type_archive_link( $type ),
			);
		}
		$hidden = is_search() || is_404();
		$hidden = $hidden || ( is_author() && 'index' !== Settings::get( 'author_archive' ) );
		$hidden = $hidden || ( is_date() && 'index' !== Settings::get( 'date_archive' ) );
		return array( 'noindex' => $hidden );
	}

	/**
	 * Archive pages 2, 3 …: own title and a self-referencing canonical URL.
	 *
	 * @param array $data SEO data.
	 * @return array
	 */
	private static function paginate( array $data ) {
		$paged = (int) get_query_var( 'paged' );
		if ( $paged < 2 || is_singular() || $data['noindex'] ) {
			return $data;
		}
		if ( '' !== $data['title'] && ! str_contains( $data['title'], Titles::replace( '%page%' ) ) ) {
			/* translators: %d: page number. */
			$data['title'] .= ' ' . Settings::get( 'separator' ) . ' ' . sprintf( __( 'Page %d', 'mlab-seo' ), $paged );
		}
		if ( '' !== $data['url'] ) {
			$data['url'] = self::paged_url( $data['url'], $paged );
		}
		return $data;
	}

	/**
	 * SEO data of one post, with all fallbacks applied.
	 *
	 * @param int $post_id Post ID.
	 * @return array
	 */
	public static function for_post( $post_id ) {
		$post      = get_post( $post_id );
		$is_front  = self::is_front( $post_id );
		$canonical = Post_Meta::get( $post_id, 'canonical' );
		$image     = Post_Meta::get( $post_id, 'image' );
		$type      = Titles::post_type( (string) get_post_type( $post_id ) );

		return array(
			'post_id'     => (int) $post_id,
			'title'       => self::post_title( $post, $is_front ),
			'description' => self::post_description( $post, $is_front ),
			'image'       => $image ? $image : (int) get_post_thumbnail_id( $post_id ),
			'url'         => '' !== $canonical ? $canonical : ( $is_front ? home_url( '/' ) : (string) get_permalink( $post_id ) ),
			'noindex'     => Post_Meta::get( $post_id, 'noindex' ) || ( $type['noindex'] && ! $is_front ) || ( $post && post_password_required( $post ) ),
			'nofollow'    => Post_Meta::get( $post_id, 'nofollow' ),
			'robots'      => Post_Meta::robots( $post_id ),
			'og_type'     => $is_front || 'page' === get_post_type( $post_id ) ? 'website' : 'article',
			'og_title'    => Titles::replace( Post_Meta::get( $post_id, 'og_title' ), $post ),
			'og_desc'     => Titles::replace( Post_Meta::get( $post_id, 'og_desc' ), $post ),
		);
	}

	/**
	 * Whether a post is the static front page.
	 *
	 * @param int $post_id Post ID.
	 * @return bool
	 */
	public static function is_front( $post_id ) {
		return 'page' === get_option( 'show_on_front' ) && (int) get_option( 'page_on_front' ) === (int) $post_id;
	}

	/**
	 * SEO title of a post: its own, the home page title, or the template of its content type.
	 *
	 * @param \WP_Post|null $post     Post.
	 * @param bool          $is_front Whether the post is the front page.
	 * @return string
	 */
	private static function post_title( $post, $is_front ) {
		if ( ! $post ) {
			return '';
		}
		$title = Post_Meta::get( $post->ID, 'title' );
		if ( '' === $title && $is_front ) {
			$title = (string) Settings::get( 'home_title' );
		}
		if ( '' === $title && $is_front ) {
			$tagline = wp_strip_all_tags( get_bloginfo( 'description' ) );
			return Settings::site_name() . ( $tagline ? ' ' . Settings::get( 'separator' ) . ' ' . $tagline : '' );
		}
		if ( '' === $title ) {
			$title = Titles::post_type( $post->post_type )['title'];
		}
		return Titles::replace( $title, $post );
	}

	/**
	 * Meta description of a post: its own, the home page description, or the template of its content type.
	 *
	 * @param \WP_Post|null $post     Post.
	 * @param bool          $is_front Whether the post is the front page.
	 * @return string
	 */
	private static function post_description( $post, $is_front ) {
		if ( ! $post ) {
			return '';
		}
		$description = Post_Meta::get( $post->ID, 'description' );
		if ( '' === $description && $is_front ) {
			$description = (string) Settings::get( 'home_description' );
		}
		if ( '' === $description ) {
			$description = Titles::post_type( $post->post_type )['description'];
		}
		return Titles::replace( $description, $post );
	}

	/**
	 * SEO data of a category, tag or other term.
	 *
	 * @param \WP_Term $term Term.
	 * @return array
	 */
	public static function for_term( $term ) {
		if ( ! $term instanceof \WP_Term ) {
			return array();
		}
		$template    = Titles::taxonomy( $term->taxonomy );
		$title       = Term_Meta::get( $term->term_id, 'title' );
		$description = Term_Meta::get( $term->term_id, 'description' );
		$url         = get_term_link( $term );
		return array(
			'title'       => Titles::replace( '' !== $title ? $title : $template['title'], null, $term ),
			'description' => Titles::replace( '' !== $description ? $description : $template['description'], null, $term ),
			'url'         => is_wp_error( $url ) ? '' : $url,
			'noindex'     => self::term_noindex( $term, $template ),
			'image'       => (int) Term_Meta::get( $term->term_id, 'image' ),
			'og_title'    => Titles::replace( Term_Meta::get( $term->term_id, 'og_title' ), null, $term ),
			'og_desc'     => Titles::replace( Term_Meta::get( $term->term_id, 'og_desc' ), null, $term ),
		);
	}

	/**
	 * Whether a term is hidden from search engines: by itself, its taxonomy, or because it is empty.
	 *
	 * @param \WP_Term $term     Term.
	 * @param array    $template Settings of the taxonomy.
	 * @return bool
	 */
	public static function term_noindex( $term, array $template ) {
		if ( Term_Meta::get( $term->term_id, 'noindex' ) || $template['noindex'] ) {
			return true;
		}
		return Settings::get( 'noindex_empty' ) && 0 === (int) $term->count;
	}

	/**
	 * URL of page n of an archive.
	 *
	 * @param string $url   URL of the first page.
	 * @param int    $paged Page number.
	 * @return string
	 */
	public static function paged_url( $url, $paged ) {
		global $wp_rewrite;
		if ( $wp_rewrite && $wp_rewrite->using_permalinks() && ! str_contains( $url, '?' ) ) {
			return trailingslashit( $url ) . user_trailingslashit( $wp_rewrite->pagination_base . '/' . $paged, 'paged' );
		}
		return add_query_arg( 'paged', $paged, $url );
	}

	/**
	 * Description from the excerpt or the start of the content.
	 *
	 * @param \WP_Post $post Post.
	 * @return string
	 */
	public static function auto_description( $post ) {
		$text = '' !== $post->post_excerpt ? $post->post_excerpt : excerpt_remove_blocks( strip_shortcodes( $post->post_content ) );
		$text = trim( preg_replace( '/\s+/', ' ', wp_strip_all_tags( $text ) ) );
		if ( mb_strlen( $text ) <= 160 ) {
			return $text;
		}
		$cut = mb_substr( $text, 0, 157 );
		$pos = mb_strrpos( $cut, ' ' );
		return ( $pos ? mb_substr( $cut, 0, $pos ) : $cut ) . '…';
	}

	/**
	 * Post title as plain text (no tags, entities decoded).
	 *
	 * @param int|\WP_Post $post Post.
	 * @return string
	 */
	public static function plain_title( $post ) {
		return html_entity_decode( wp_strip_all_tags( get_the_title( $post ) ), ENT_QUOTES | ENT_HTML5, 'UTF-8' );
	}

	/**
	 * Image source of an attachment.
	 *
	 * @param int    $id   Attachment ID.
	 * @param string $size Image size.
	 * @return array{url:string,width:int,height:int,alt:string}|null
	 */
	public static function image( $id, $size = 'large' ) {
		$src = $id ? wp_get_attachment_image_src( $id, $size ) : false;
		if ( ! $src ) {
			return null;
		}
		return array(
			'url'    => $src[0],
			'width'  => (int) $src[1],
			'height' => (int) $src[2],
			'alt'    => (string) get_post_meta( $id, '_wp_attachment_image_alt', true ),
		);
	}
}
