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
	 * @return array{post_id:int,title:string,description:string,image:int,url:string,noindex:bool,nofollow:bool,og_type:string,og_title:string,og_desc:string}
	 */
	public static function current() {
		if ( null !== self::$current ) {
			return self::$current;
		}

		$sep  = ' ' . Settings::get( 'separator' ) . ' ';
		$site = Settings::site_name();
		$data = array(
			'post_id'     => 0,
			'title'       => '',
			'description' => '',
			'image'       => 0,
			'url'         => '',
			'noindex'     => false,
			'nofollow'    => false,
			'og_type'     => 'website',
			'og_title'    => '',
			'og_desc'     => '',
		);

		if ( is_front_page() && ! is_page() ) {
			$data['title']       = (string) Settings::get( 'home_title' );
			$data['description'] = (string) Settings::get( 'home_description' );
			$data['url']         = home_url( '/' );
		} elseif ( is_singular() || ( is_home() && get_option( 'page_for_posts' ) ) ) {
			$id   = is_home() ? (int) get_option( 'page_for_posts' ) : get_queried_object_id();
			$data = array_merge( $data, self::for_post( $id ) );
		} elseif ( is_category() || is_tag() || is_tax() ) {
			$data = array_merge( $data, self::for_term( get_queried_object() ) );
		} elseif ( is_post_type_archive() ) {
			$type          = get_query_var( 'post_type' );
			$type          = is_array( $type ) ? reset( $type ) : $type;
			$data['title'] = post_type_archive_title( '', false ) . $sep . $site;
			$data['url']   = (string) get_post_type_archive_link( $type );
		} elseif ( is_search() || is_404() ) {
			$data['noindex'] = true;
		} elseif ( is_author() || is_date() ) {
			$data['noindex'] = true;
		}

		if ( 0 === $data['image'] ) {
			$data['image'] = (int) Settings::get( 'default_image' );
		}
		if ( is_wp_error( $data['url'] ) ) {
			$data['url'] = '';
		}

		// Archive pages 2, 3 …: own title and a self-referencing canonical URL.
		$paged = (int) get_query_var( 'paged' );
		if ( $paged > 1 && ! is_singular() && ! $data['noindex'] ) {
			if ( '' !== $data['title'] ) {
				/* translators: %d: page number. */
				$data['title'] .= $sep . sprintf( __( 'Page %d', 'mlab-seo' ), $paged );
			}
			if ( '' !== $data['url'] ) {
				$data['url'] = self::paged_url( $data['url'], $paged );
			}
		}

		/**
		 * Filters the SEO data of the current request.
		 *
		 * @param array $data SEO data.
		 */
		self::$current = (array) apply_filters( 'mlab_seo_context', $data );
		return self::$current;
	}

	/**
	 * SEO data of one post, with all fallbacks applied.
	 *
	 * @param int $post_id Post ID.
	 * @return array
	 */
	public static function for_post( $post_id ) {
		$post     = get_post( $post_id );
		$is_front = 'page' === get_option( 'show_on_front' ) && (int) get_option( 'page_on_front' ) === (int) $post_id;
		$sep      = ' ' . Settings::get( 'separator' ) . ' ';
		$site     = Settings::site_name();

		$title = Post_Meta::get( $post_id, 'title' );
		if ( '' === $title && $is_front ) {
			$title = (string) Settings::get( 'home_title' );
		}
		if ( '' === $title ) {
			$title = $is_front
				? $site . ( get_bloginfo( 'description' ) ? $sep . wp_strip_all_tags( get_bloginfo( 'description' ) ) : '' )
				: self::plain_title( $post ) . $sep . $site;
		}

		$description = Post_Meta::get( $post_id, 'description' );
		if ( '' === $description && $is_front ) {
			$description = (string) Settings::get( 'home_description' );
		}
		if ( '' === $description && $post ) {
			$description = self::auto_description( $post );
		}

		$image = Post_Meta::get( $post_id, 'image' );
		if ( ! $image ) {
			$image = (int) get_post_thumbnail_id( $post_id );
		}

		$canonical = Post_Meta::get( $post_id, 'canonical' );

		return array(
			'post_id'     => (int) $post_id,
			'title'       => $title,
			'description' => $description,
			'image'       => $image,
			'url'         => '' !== $canonical ? $canonical : ( $is_front ? home_url( '/' ) : (string) get_permalink( $post_id ) ),
			'noindex'     => Post_Meta::get( $post_id, 'noindex' ),
			'nofollow'    => Post_Meta::get( $post_id, 'nofollow' ),
			'og_type'     => $is_front || 'page' === get_post_type( $post_id ) ? 'website' : 'article',
			'og_title'    => Post_Meta::get( $post_id, 'og_title' ),
			'og_desc'     => Post_Meta::get( $post_id, 'og_desc' ),
		);
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
		$title       = Term_Meta::get( $term->term_id, 'title' );
		$description = Term_Meta::get( $term->term_id, 'description' );
		if ( '' === $title ) {
			$title = html_entity_decode( $term->name, ENT_QUOTES | ENT_HTML5, 'UTF-8' ) . ' ' . Settings::get( 'separator' ) . ' ' . Settings::site_name();
		}
		if ( '' === $description ) {
			$description = wp_trim_words( wp_strip_all_tags( $term->description ), 30, '…' );
		}
		$url = get_term_link( $term );
		return array(
			'title'       => $title,
			'description' => $description,
			'url'         => is_wp_error( $url ) ? '' : $url,
			'noindex'     => (bool) Term_Meta::get( $term->term_id, 'noindex' ),
		);
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
