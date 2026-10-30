<?php
/**
 * Breadcrumbs: the path to the current page, for schema.org and as visible navigation.
 *
 * @package MomentumLab\SEO
 */

namespace MomentumLab\SEO;

defined( 'ABSPATH' ) || exit;

/**
 * Builds breadcrumb items and prints them via [mlab_seo_breadcrumbs] or mlab_seo_breadcrumbs().
 */
final class Breadcrumbs {

	/**
	 * Registers hooks.
	 */
	public function hooks() {
		add_shortcode( 'mlab_seo_breadcrumbs', array( self::class, 'render' ) );
	}

	/**
	 * Items for the current request: home, parents, current page.
	 *
	 * @return array<int,array{name:string,url:string}> Empty on the front page.
	 */
	public static function current() {
		if ( is_front_page() ) {
			return array();
		}
		if ( is_singular() ) {
			return self::for_post( get_queried_object_id() );
		}
		if ( is_home() && get_option( 'page_for_posts' ) ) {
			return self::for_post( (int) get_option( 'page_for_posts' ) );
		}
		$term = get_queried_object();
		if ( ( is_category() || is_tag() || is_tax() ) && $term instanceof \WP_Term ) {
			return array_merge( array( self::home() ), self::term_path( $term ) );
		}
		return array(
			self::home(),
			array(
				'name' => self::archive_name(),
				'url'  => '',
			),
		);
	}

	/**
	 * Name of the last item on archive, search and error pages.
	 *
	 * @return string
	 */
	private static function archive_name() {
		if ( is_post_type_archive() ) {
			return post_type_archive_title( '', false );
		}
		if ( is_search() ) {
			/* translators: %s: search term. */
			return sprintf( __( 'Search: %s', 'mlab-seo' ), get_search_query( false ) );
		}
		if ( is_404() ) {
			return __( 'Page not found', 'mlab-seo' );
		}
		return wp_get_document_title();
	}

	/**
	 * Items for a post: home, blog page or post type archive, category, ancestors, post.
	 *
	 * @param int $post_id Post ID.
	 * @return array<int,array{name:string,url:string}> Empty for the front page.
	 */
	public static function for_post( $post_id ) {
		$post_id = (int) $post_id;
		if ( Context::is_front( $post_id ) ) {
			return array();
		}
		$items = array_merge( array( self::home() ), self::post_parents( $post_id ) );
		foreach ( array_reverse( get_post_ancestors( $post_id ) ) as $ancestor ) {
			$items[] = self::post_item( $ancestor );
		}
		$items[] = self::post_item( $post_id );
		return $items;
	}

	/**
	 * Items between home and a post: the blog page and category for posts, the archive for other types.
	 *
	 * @param int $post_id Post ID.
	 * @return array
	 */
	private static function post_parents( $post_id ) {
		$type = get_post_type( $post_id );
		if ( 'post' === $type ) {
			$items = array();
			$blog  = (int) get_option( 'page_for_posts' );
			if ( $blog && $blog !== $post_id ) {
				$items[] = self::post_item( $blog );
			}
			$category = self::primary_category( $post_id );
			return $category ? array_merge( $items, self::term_path( $category ) ) : $items;
		}
		$object  = $type && 'page' !== $type ? get_post_type_object( $type ) : null;
		$archive = $object && $object->has_archive ? get_post_type_archive_link( $type ) : '';
		if ( ! $archive ) {
			return array();
		}
		return array(
			array(
				'name' => html_entity_decode( $object->labels->name, ENT_QUOTES | ENT_HTML5, 'UTF-8' ),
				'url'  => $archive,
			),
		);
	}

	/**
	 * Item for a post, with its breadcrumb title when set.
	 *
	 * @param int $post_id Post ID.
	 * @return array{name:string,url:string}
	 */
	private static function post_item( $post_id ) {
		$name = Post_Meta::get( $post_id, 'breadcrumb' );
		return array(
			'name' => '' !== $name ? $name : Context::plain_title( $post_id ),
			'url'  => (string) get_permalink( $post_id ),
		);
	}

	/**
	 * Home item.
	 *
	 * @return array{name:string,url:string}
	 */
	private static function home() {
		$label = (string) Settings::get( 'breadcrumb_home' );
		$front = 'page' === get_option( 'show_on_front' ) ? (int) get_option( 'page_on_front' ) : 0;
		if ( '' === $label ) {
			$label = $front ? Context::plain_title( $front ) : __( 'Home', 'mlab-seo' );
		}
		return array(
			'name' => $label,
			'url'  => Multilingual::home_url(),
		);
	}

	/**
	 * The main category of a post: the one chosen in the SEO box, else the first one that is not "Uncategorised".
	 *
	 * @param int $post_id Post ID.
	 * @return \WP_Term|null
	 */
	public static function primary_category( $post_id ) {
		$terms   = get_the_category( $post_id );
		$primary = Post_Meta::get( $post_id, 'primary_term' );
		foreach ( $terms as $term ) {
			if ( $term->term_id === $primary ) {
				return $term;
			}
		}
		$default = (int) get_option( 'default_category' );
		foreach ( $terms as $term ) {
			if ( $term->term_id !== $default ) {
				return $term;
			}
		}
		return null;
	}

	/**
	 * A term with its parent terms.
	 *
	 * @param \WP_Term $term Term.
	 * @return array<int,array{name:string,url:string}>
	 */
	private static function term_path( $term ) {
		$items = array();
		foreach ( array_merge( array_reverse( get_ancestors( $term->term_id, $term->taxonomy, 'taxonomy' ) ), array( $term->term_id ) ) as $id ) {
			$t    = get_term( $id, $term->taxonomy );
			$link = $t instanceof \WP_Term ? get_term_link( $t ) : '';
			if ( $t instanceof \WP_Term && ! is_wp_error( $link ) ) {
				$short   = Term_Meta::get( $t->term_id, 'breadcrumb' );
				$items[] = array(
					'name' => '' !== $short ? $short : html_entity_decode( $t->name, ENT_QUOTES | ENT_HTML5, 'UTF-8' ),
					'url'  => $link,
				);
			}
		}
		return $items;
	}

	/**
	 * ListItem entries for schema.org.
	 *
	 * @param array $items Breadcrumb items.
	 * @return array
	 */
	public static function list_items( array $items ) {
		$list = array();
		foreach ( array_values( $items ) as $i => $item ) {
			$list[] = array_filter(
				array(
					'@type'    => 'ListItem',
					'position' => $i + 1,
					'name'     => $item['name'],
					'item'     => '' !== $item['url'] ? $item['url'] : null,
				)
			);
		}
		return $list;
	}

	/**
	 * HTML navigation for the current page.
	 *
	 * @param array|string $atts Shortcode attributes: separator.
	 * @return string
	 */
	public static function render( $atts = array() ) {
		$atts  = shortcode_atts( array( 'separator' => Settings::get( 'breadcrumb_sep' ) ), (array) $atts, 'mlab_seo_breadcrumbs' );
		$items = self::current();
		if ( count( $items ) < 2 ) {
			return '';
		}
		$parts = array();
		$last  = count( $items ) - 1;
		foreach ( $items as $i => $item ) {
			$parts[] = $i === $last || '' === $item['url']
				? '<span' . ( $i === $last ? ' aria-current="page"' : '' ) . '>' . esc_html( $item['name'] ) . '</span>'
				: '<a href="' . esc_url( $item['url'] ) . '">' . esc_html( $item['name'] ) . '</a>';
		}
		$sep = ' <span class="mlab-seo-breadcrumbs__sep" aria-hidden="true">' . esc_html( $atts['separator'] ) . '</span> ';
		return '<nav class="mlab-seo-breadcrumbs" aria-label="' . esc_attr__( 'Breadcrumb', 'mlab-seo' ) . '">' . implode( $sep, $parts ) . '</nav>';
	}
}
