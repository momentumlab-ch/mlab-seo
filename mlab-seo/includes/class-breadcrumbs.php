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
		$items = array( self::home() );
		if ( is_category() || is_tag() || is_tax() ) {
			$term = get_queried_object();
			if ( $term instanceof \WP_Term ) {
				$items = array_merge( $items, self::term_path( $term ) );
			}
		} elseif ( is_post_type_archive() ) {
			$items[] = array(
				'name' => post_type_archive_title( '', false ),
				'url'  => '',
			);
		} elseif ( is_search() ) {
			$items[] = array(
				/* translators: %s: search term. */
				'name' => sprintf( __( 'Search: %s', 'mlab-seo' ), get_search_query( false ) ),
				'url'  => '',
			);
		} elseif ( is_404() ) {
			$items[] = array(
				'name' => __( 'Page not found', 'mlab-seo' ),
				'url'  => '',
			);
		} else {
			$items[] = array(
				'name' => wp_get_document_title(),
				'url'  => '',
			);
		}
		return $items;
	}

	/**
	 * Items for a post: home, blog page or post type archive, category, ancestors, post.
	 *
	 * @param int $post_id Post ID.
	 * @return array<int,array{name:string,url:string}> Empty for the front page.
	 */
	public static function for_post( $post_id ) {
		$post_id = (int) $post_id;
		if ( 'page' === get_option( 'show_on_front' ) && (int) get_option( 'page_on_front' ) === $post_id ) {
			return array();
		}
		$items = array( self::home() );
		$type  = get_post_type( $post_id );

		if ( 'post' === $type ) {
			$blog = (int) get_option( 'page_for_posts' );
			if ( $blog && $blog !== $post_id ) {
				$items[] = array(
					'name' => Context::plain_title( $blog ),
					'url'  => (string) get_permalink( $blog ),
				);
			}
			$category = self::primary_category( $post_id );
			if ( $category ) {
				$items = array_merge( $items, self::term_path( $category ) );
			}
		} elseif ( $type && 'page' !== $type ) {
			$object  = get_post_type_object( $type );
			$archive = get_post_type_archive_link( $type );
			if ( $object && $archive && $object->has_archive ) {
				$items[] = array(
					'name' => html_entity_decode( $object->labels->name, ENT_QUOTES | ENT_HTML5, 'UTF-8' ),
					'url'  => $archive,
				);
			}
		}

		foreach ( array_reverse( get_post_ancestors( $post_id ) ) as $ancestor ) {
			$items[] = array(
				'name' => Context::plain_title( $ancestor ),
				'url'  => (string) get_permalink( $ancestor ),
			);
		}
		$items[] = array(
			'name' => Context::plain_title( $post_id ),
			'url'  => (string) get_permalink( $post_id ),
		);
		return $items;
	}

	/**
	 * Home item.
	 *
	 * @return array{name:string,url:string}
	 */
	private static function home() {
		$front = 'page' === get_option( 'show_on_front' ) ? (int) get_option( 'page_on_front' ) : 0;
		return array(
			'name' => $front ? Context::plain_title( $front ) : __( 'Home', 'mlab-seo' ),
			'url'  => home_url( '/' ),
		);
	}

	/**
	 * The category shown for a post: the first one that is not "Uncategorised".
	 *
	 * @param int $post_id Post ID.
	 * @return \WP_Term|null
	 */
	private static function primary_category( $post_id ) {
		$default = (int) get_option( 'default_category' );
		$terms   = get_the_category( $post_id );
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
				$items[] = array(
					'name' => html_entity_decode( $t->name, ENT_QUOTES | ENT_HTML5, 'UTF-8' ),
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
		$atts  = shortcode_atts( array( 'separator' => '›' ), (array) $atts, 'mlab_seo_breadcrumbs' );
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
