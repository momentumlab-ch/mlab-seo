<?php
/**
 * Title and description templates per content type and taxonomy, with variables like %title%.
 *
 * @package MomentumLab\SEO
 */

namespace MomentumLab\SEO;

defined( 'ABSPATH' ) || exit;

/**
 * Reads the templates from the settings and replaces the variables.
 */
final class Titles {

	/**
	 * Schema.org types offered for articles.
	 */
	const ARTICLE_TYPES = array( 'Article', 'BlogPosting', 'NewsArticle' );

	/**
	 * Other spellings of the variables, also those of Yoast SEO, Rank Math and SEOPress.
	 */
	const ALIASES = array(
		'post_title'       => 'title',
		'site_title'       => 'sitename',
		'sitetitle'        => 'sitename',
		'tagline'          => 'sitedesc',
		'site_description' => 'sitedesc',
		'post_excerpt'     => 'excerpt',
		'excerpt_only'     => 'excerpt',
		'term_title'       => 'term',
		'category_title'   => 'term',
		'taxonomy_title'   => 'term',
		'primary_category' => 'category',
		'post_category'    => 'category',
		'author'           => 'name',
		'post_author'      => 'name',
		'year'             => 'currentyear',
		'month'            => 'currentmonth',
		'searchphrase'     => 'search_query',
		'search_keywords'  => 'search_query',
		'pagenumber'       => 'page',
		'separator_sa'     => 'sep',
		'pt_singular'      => 'pt_single',
		'focuskeyword'     => 'focuskw',
		'parent'           => 'parent_title',
		'date_published'   => 'date',
		'date_modified'    => 'modified',
	);

	/**
	 * Variables with a short explanation, for the settings screen.
	 *
	 * @return array<string,string>
	 */
	public static function variables() {
		return array(
			'%title%'            => __( 'Title of the page, post or term', 'mlab-seo' ),
			'%sep%'              => __( 'Title separator', 'mlab-seo' ),
			'%sitename%'         => __( 'Website name', 'mlab-seo' ),
			'%sitedesc%'         => __( 'Tagline of the website', 'mlab-seo' ),
			'%excerpt%'          => __( 'Excerpt or start of the text', 'mlab-seo' ),
			'%category%'         => __( 'Main category', 'mlab-seo' ),
			'%term%'             => __( 'Name of the category or tag', 'mlab-seo' ),
			'%term_description%' => __( 'Description of the category or tag', 'mlab-seo' ),
			'%parent_title%'     => __( 'Title of the parent page', 'mlab-seo' ),
			'%pt_single%'        => __( 'Content type, singular', 'mlab-seo' ),
			'%pt_plural%'        => __( 'Content type, plural', 'mlab-seo' ),
			'%name%'             => __( 'Author', 'mlab-seo' ),
			'%date%'             => __( 'Publication date', 'mlab-seo' ),
			'%modified%'         => __( 'Date of the last change', 'mlab-seo' ),
			'%currentyear%'      => __( 'Current year', 'mlab-seo' ),
			'%currentmonth%'     => __( 'Current month', 'mlab-seo' ),
			'%focuskw%'          => __( 'Focus keyword', 'mlab-seo' ),
			'%page%'             => __( 'Page number on page 2 and later', 'mlab-seo' ),
		);
	}

	/**
	 * Templates and defaults of a content type.
	 *
	 * @param string $type Post type.
	 * @return array{title:string,description:string,noindex:int,schema:string}
	 */
	public static function post_type( $type ) {
		$stored = Settings::get( 'titles' )['types'][ $type ] ?? array();
		return array_merge( self::post_type_defaults( $type ), array_filter( (array) $stored, 'strlen' ) );
	}

	/**
	 * Templates and defaults of a taxonomy.
	 *
	 * @param string $taxonomy Taxonomy.
	 * @return array{title:string,description:string,noindex:int}
	 */
	public static function taxonomy( $taxonomy ) {
		$stored = Settings::get( 'titles' )['taxonomies'][ $taxonomy ] ?? array();
		return array_merge( self::taxonomy_defaults(), array_filter( (array) $stored, 'strlen' ) );
	}

	/**
	 * Defaults of a content type: the same titles and descriptions as before templates existed.
	 *
	 * @param string $type Post type.
	 * @return array
	 */
	public static function post_type_defaults( $type ) {
		return array(
			'title'       => '%title% %sep% %sitename%',
			'description' => '%excerpt%',
			'noindex'     => 0,
			'schema'      => 'post' === $type ? 'BlogPosting' : 'none',
		);
	}

	/**
	 * Defaults of a taxonomy.
	 *
	 * @return array
	 */
	public static function taxonomy_defaults() {
		return array(
			'title'       => '%term% %sep% %sitename%',
			'description' => '%term_description%',
			'noindex'     => 0,
		);
	}

	/**
	 * Article type (schema.org) of a post: its own choice, else the one of its content type.
	 *
	 * @param int $post_id Post ID.
	 * @return string Empty when the post is no article.
	 */
	public static function article_type( $post_id ) {
		$type = Post_Meta::get( $post_id, 'article_type' );
		if ( '' === $type ) {
			$type = self::post_type( (string) get_post_type( $post_id ) )['schema'];
		}
		return in_array( $type, self::ARTICLE_TYPES, true ) ? $type : '';
	}

	/**
	 * Sanitises the templates from the settings form. Values equal to the default are not stored.
	 *
	 * @param mixed $in Raw values: types and taxonomies, each name => fields.
	 * @return array{types:array,taxonomies:array}
	 */
	public static function sanitize( $in ) {
		$in  = is_array( $in ) ? wp_unslash( $in ) : array();
		$out = array(
			'types'      => array(),
			'taxonomies' => array(),
		);
		foreach ( Post_Meta::post_types() as $type ) {
			$out['types'][ $type ] = self::sanitize_fields( $in['types'][ $type ] ?? array(), self::post_type_defaults( $type ) );
		}
		foreach ( Term_Meta::taxonomies() as $taxonomy ) {
			$out['taxonomies'][ $taxonomy ] = self::sanitize_fields( $in['taxonomies'][ $taxonomy ] ?? array(), self::taxonomy_defaults() );
		}
		return array_map( 'array_filter', $out );
	}

	/**
	 * Sanitises the fields of one content type or taxonomy.
	 *
	 * @param mixed $fields   Raw fields.
	 * @param array $defaults Defaults.
	 * @return array Only the values that differ from the defaults.
	 */
	private static function sanitize_fields( $fields, array $defaults ) {
		$fields = is_array( $fields ) ? $fields : array();
		$clean  = array(
			'title'       => sanitize_text_field( (string) ( $fields['title'] ?? '' ) ),
			'description' => sanitize_textarea_field( (string) ( $fields['description'] ?? '' ) ),
			'noindex'     => empty( $fields['noindex'] ) ? 0 : 1,
		);
		if ( isset( $defaults['schema'] ) ) {
			$schema          = (string) ( $fields['schema'] ?? '' );
			$clean['schema'] = in_array( $schema, self::ARTICLE_TYPES, true ) ? $schema : 'none';
		}
		$changed = array();
		foreach ( $clean as $key => $value ) {
			if ( '' !== $value && $value !== $defaults[ $key ] ) {
				$changed[ $key ] = $value;
			}
		}
		return $changed;
	}

	/**
	 * Replaces the variables in a title or description. Unknown variables stay as they are.
	 *
	 * @param string            $text Text with variables like %title% (or %%title%%).
	 * @param \WP_Post|int|null $post Post the text belongs to.
	 * @param \WP_Term|null     $term Term the text belongs to.
	 * @return string
	 */
	public static function replace( $text, $post = null, $term = null ) {
		$text = (string) $text;
		if ( ! str_contains( $text, '%' ) ) {
			return $text;
		}
		$post = $post ? get_post( $post ) : null;
		$text = preg_replace_callback(
			'/%%?([a-z_]+)%%?/i',
			function ( $m ) use ( $post, $term ) {
				$value = self::value( strtolower( $m[1] ), $post, $term );
				return null === $value ? $m[0] : $value;
			},
			$text
		);
		// Empty variables leave double spaces or a separator at the end.
		$sep  = preg_quote( (string) Settings::get( 'separator' ), '/' );
		$text = preg_replace( '/\s+/', ' ', (string) $text );
		return trim( (string) preg_replace( '/^(\s*' . $sep . '\s*)+|(\s*' . $sep . '\s*)+$/u', '', $text ) );
	}

	/**
	 * Raw value of one variable, without the clean-up of replace().
	 *
	 * @param string            $name Variable name without percent signs.
	 * @param \WP_Post|int|null $post Post.
	 * @return string|null Null for unknown variables.
	 */
	public static function variable( $name, $post = null ) {
		return self::value( $name, $post ? get_post( $post ) : null, null );
	}

	/**
	 * Value of one variable.
	 *
	 * @param string        $name Variable name without percent signs.
	 * @param \WP_Post|null $post Post.
	 * @param \WP_Term|null $term Term.
	 * @return string|null Null for unknown variables.
	 */
	private static function value( $name, $post, $term ) {
		$name  = self::ALIASES[ $name ] ?? $name;
		$value = self::site_value( $name );
		if ( null === $value && $post instanceof \WP_Post ) {
			$value = self::post_value( $name, $post );
		}
		if ( null === $value && $term instanceof \WP_Term ) {
			$value = self::term_value( $name, $term );
		}
		if ( null === $value && in_array( $name, array( 'title', 'excerpt', 'category', 'term', 'term_description', 'parent_title', 'pt_single', 'pt_plural', 'name', 'date', 'modified', 'focuskw' ), true ) ) {
			$value = '';
		}
		return $value;
	}

	/**
	 * Variables that do not depend on the page.
	 *
	 * @param string $name Variable name.
	 * @return string|null
	 */
	private static function site_value( $name ) {
		switch ( $name ) {
			case 'sep':
				return (string) Settings::get( 'separator' );
			case 'sitename':
				return Settings::site_name();
			case 'sitedesc':
				return html_entity_decode( wp_strip_all_tags( get_bloginfo( 'description' ) ), ENT_QUOTES | ENT_HTML5, 'UTF-8' );
			case 'currentyear':
				return wp_date( 'Y' );
			case 'currentmonth':
				return wp_date( 'F' );
			case 'page':
				return self::page();
			case 'search_query':
				return is_search() ? get_search_query( false ) : '';
		}
		return null;
	}

	/**
	 * Variables of a post.
	 *
	 * @param string   $name Variable name.
	 * @param \WP_Post $post Post.
	 * @return string|null
	 */
	private static function post_value( $name, $post ) {
		switch ( $name ) {
			case 'title':
				return Context::plain_title( $post );
			case 'excerpt':
				return Context::auto_description( $post );
			case 'category':
				$category = Breadcrumbs::primary_category( $post->ID );
				return $category ? html_entity_decode( $category->name, ENT_QUOTES | ENT_HTML5, 'UTF-8' ) : '';
			case 'parent_title':
				return $post->post_parent ? Context::plain_title( $post->post_parent ) : '';
			case 'name':
				return (string) get_the_author_meta( 'display_name', (int) $post->post_author );
			case 'date':
				return (string) get_the_date( '', $post );
			case 'modified':
				return (string) get_the_modified_date( '', $post );
			case 'focuskw':
				return Post_Meta::get( $post->ID, 'focus' );
		}
		return self::type_value( $name, $post->post_type );
	}

	/**
	 * Variables of a term.
	 *
	 * @param string   $name Variable name.
	 * @param \WP_Term $term Term.
	 * @return string|null
	 */
	private static function term_value( $name, $term ) {
		switch ( $name ) {
			case 'title':
			case 'term':
				return html_entity_decode( $term->name, ENT_QUOTES | ENT_HTML5, 'UTF-8' );
			case 'term_description':
				return wp_trim_words( wp_strip_all_tags( $term->description ), 30, '…' );
		}
		return null;
	}

	/**
	 * Name of a content type, singular or plural.
	 *
	 * @param string $name Variable name (pt_single or pt_plural).
	 * @param string $type Post type.
	 * @return string|null
	 */
	private static function type_value( $name, $type ) {
		$object = get_post_type_object( $type );
		if ( ! $object || ! in_array( $name, array( 'pt_single', 'pt_plural' ), true ) ) {
			return null;
		}
		return 'pt_single' === $name ? $object->labels->singular_name : $object->labels->name;
	}

	/**
	 * "Page 2" on page 2 and later of an archive or a split post, else empty.
	 *
	 * @return string
	 */
	private static function page() {
		$paged = max( (int) get_query_var( 'paged' ), (int) get_query_var( 'page' ) );
		/* translators: %d: page number. */
		return $paged > 1 ? sprintf( __( 'Page %d', 'mlab-seo' ), $paged ) : '';
	}
}
