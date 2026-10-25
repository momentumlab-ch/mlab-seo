<?php
/**
 * Import of SEO data from Yoast SEO, Rank Math, SEOPress and All in One SEO.
 *
 * @package MomentumLab\SEO
 */

namespace MomentumLab\SEO;

defined( 'ABSPATH' ) || exit;

/**
 * Copies titles, descriptions, focus keywords, robots, canonical and social fields.
 *
 * The data of the other plugin stays untouched, so the import can be repeated.
 */
final class Import {

	/**
	 * Sources: label, post meta keys and term meta keys per field.
	 */
	const SOURCES = array(
		'yoast'    => array(
			'label' => 'Yoast SEO',
			'post'  => array(
				'title'        => '_yoast_wpseo_title',
				'description'  => '_yoast_wpseo_metadesc',
				'focus'        => '_yoast_wpseo_focuskw',
				'noindex'      => '_yoast_wpseo_meta-robots-noindex',
				'nofollow'     => '_yoast_wpseo_meta-robots-nofollow',
				'canonical'    => '_yoast_wpseo_canonical',
				'image'        => '_yoast_wpseo_opengraph-image-id',
				'og_title'     => '_yoast_wpseo_opengraph-title',
				'og_desc'      => '_yoast_wpseo_opengraph-description',
				'robots_adv'   => '_yoast_wpseo_meta-robots-adv',
				'primary_term' => '_yoast_wpseo_primary_category',
				'breadcrumb'   => '_yoast_wpseo_bctitle',
			),
		),
		'rankmath' => array(
			'label' => 'Rank Math',
			'post'  => array(
				'title'        => 'rank_math_title',
				'description'  => 'rank_math_description',
				'focus'        => 'rank_math_focus_keyword',
				'robots'       => 'rank_math_robots',
				'canonical'    => 'rank_math_canonical_url',
				'image'        => 'rank_math_facebook_image_id',
				'og_title'     => 'rank_math_facebook_title',
				'og_desc'      => 'rank_math_facebook_description',
				'primary_term' => 'rank_math_primary_category',
				'breadcrumb'   => 'rank_math_breadcrumb_title',
			),
			'term'  => array(
				'title'       => 'rank_math_title',
				'description' => 'rank_math_description',
				'robots'      => 'rank_math_robots',
				'og_title'    => 'rank_math_facebook_title',
				'og_desc'     => 'rank_math_facebook_description',
				'image'       => 'rank_math_facebook_image_id',
				'breadcrumb'  => 'rank_math_breadcrumb_title',
			),
		),
		'seopress' => array(
			'label' => 'SEOPress',
			'post'  => array(
				'title'        => '_seopress_titles_title',
				'description'  => '_seopress_titles_desc',
				'focus'        => '_seopress_analysis_target_kw',
				'noindex'      => '_seopress_robots_index',
				'nofollow'     => '_seopress_robots_follow',
				'canonical'    => '_seopress_robots_canonical',
				'image'        => '_seopress_social_fb_img_attachment_id',
				'og_title'     => '_seopress_social_fb_title',
				'og_desc'      => '_seopress_social_fb_desc',
				'primary_term' => '_seopress_robots_primary_cat',
				'breadcrumb'   => '_seopress_robots_breadcrumbs',
			),
			'term'  => array(
				'title'       => '_seopress_titles_title',
				'description' => '_seopress_titles_desc',
				'noindex'     => '_seopress_robots_index',
			),
		),
		'aioseo'   => array(
			'label' => 'All in One SEO',
			'post'  => array(
				'title'       => '_aioseo_title',
				'description' => '_aioseo_description',
				'og_title'    => '_aioseo_og_title',
				'og_desc'     => '_aioseo_og_description',
			),
		),
	);

	/**
	 * Number of posts with data per source.
	 *
	 * @return array<string,int>
	 */
	public static function detect() {
		global $wpdb;
		$counts = array();
		foreach ( self::SOURCES as $key => $source ) {
			$keys         = array_values( $source['post'] );
			$placeholders = implode( ',', array_fill( 0, count( $keys ), '%s' ) );
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQLPlaceholders.ReplacementsWrongNumber, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare -- One-off count in the admin; placeholders are built above.
			$count = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(DISTINCT post_id) FROM {$wpdb->postmeta} WHERE meta_key IN ($placeholders) AND meta_value <> ''", $keys ) );
			if ( 'aioseo' === $key && self::aioseo_table() ) {
				$table = self::aioseo_table();
				$count = max( $count, (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$table} WHERE COALESCE(title,'') <> '' OR COALESCE(description,'') <> '' OR COALESCE(keyphrases,'') <> '' OR robots_noindex = 1" ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Table name from $wpdb->prefix.
			}
			if ( 'yoast' === $key && is_array( get_option( 'wpseo_taxonomy_meta' ) ) ) {
				$count = max( $count, 1 );
			}
			$counts[ $key ] = $count;
		}
		return $counts;
	}

	/**
	 * The All in One SEO table, when it exists.
	 *
	 * @return string Table name or empty.
	 */
	private static function aioseo_table() {
		global $wpdb;
		$table = $wpdb->prefix . 'aioseo_posts';
		return $table === $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $wpdb->esc_like( $table ) ) ) ? $table : ''; // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
	}

	/**
	 * Runs the import.
	 *
	 * @param string $source    Source key.
	 * @param bool   $overwrite Replace values that are already set.
	 * @return array{posts:int,terms:int}
	 */
	public static function run( $source, $overwrite ) {
		if ( ! isset( self::SOURCES[ $source ] ) ) {
			return array(
				'posts' => 0,
				'terms' => 0,
			);
		}
		if ( function_exists( 'set_time_limit' ) ) {
			set_time_limit( 300 ); // phpcs:ignore Squiz.PHP.DiscouragedFunctions.Discouraged -- Large sites.
		}
		$rows  = 'aioseo' === $source ? self::aioseo_rows() : self::meta_rows( 'post', self::SOURCES[ $source ]['post'] );
		$posts = 0;
		foreach ( $rows as $post_id => $values ) {
			$posts += (int) self::import_post( $post_id, $values, $source, $overwrite );
		}
		$terms = 0;
		foreach ( self::term_rows( $source ) as $term_id => $values ) {
			$terms += (int) self::import_term( $term_id, $values, $source, $overwrite );
		}
		Crawlers::flush_cache();
		return array(
			'posts' => $posts,
			'terms' => $terms,
		);
	}

	/**
	 * Imports the values of one post.
	 *
	 * @param int    $post_id   Post ID.
	 * @param array  $values    Raw values.
	 * @param string $source    Source key.
	 * @param bool   $overwrite Replace values that are already set.
	 * @return bool Whether a value changed.
	 */
	private static function import_post( $post_id, array $values, $source, $overwrite ) {
		$post = get_post( $post_id );
		if ( ! $post || ! in_array( $post->post_type, Post_Meta::post_types(), true ) ) {
			return false;
		}
		$changed = false;
		foreach ( self::normalize( $values, $source ) as $field => $value ) {
			$current = get_post_meta( $post_id, Post_Meta::PREFIX . $field, true );
			if ( '' === $value || false === $value || ( ! $overwrite && '' !== $current && false !== $current ) ) {
				continue;
			}
			$value = Post_Meta::sanitize_field( $field, $value );
			if ( '' !== $value && 0 !== $value ) {
				update_post_meta( $post_id, Post_Meta::PREFIX . $field, $value );
				$changed = true;
			}
		}
		return $changed;
	}

	/**
	 * Imports the values of one term.
	 *
	 * @param int    $term_id   Term ID.
	 * @param array  $values    Raw values.
	 * @param string $source    Source key.
	 * @param bool   $overwrite Replace values that are already set.
	 * @return bool Whether a value changed.
	 */
	private static function import_term( $term_id, array $values, $source, $overwrite ) {
		$term = get_term( $term_id );
		if ( ! $term instanceof \WP_Term || ! in_array( $term->taxonomy, Term_Meta::taxonomies(), true ) ) {
			return false;
		}
		$changed = false;
		foreach ( self::normalize( $values, $source ) as $field => $value ) {
			if ( ! isset( Term_Meta::FIELDS[ $field ] ) || '' === $value || false === $value ) {
				continue;
			}
			if ( ! $overwrite && '' !== (string) get_term_meta( $term_id, Post_Meta::PREFIX . $field, true ) ) {
				continue;
			}
			Term_Meta::set( $term_id, $field, $value );
			$changed = true;
		}
		return $changed;
	}

	/**
	 * Meta values grouped by object: [id => [field => raw value]].
	 *
	 * @param string $type   'post' or 'term'.
	 * @param array  $fields Field => meta key.
	 * @return array
	 */
	private static function meta_rows( $type, array $fields ) {
		global $wpdb;
		$table  = 'post' === $type ? $wpdb->postmeta : $wpdb->termmeta;
		$column = 'post' === $type ? 'post_id' : 'term_id';
		$rows   = array();
		foreach ( $fields as $field => $key ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Core table names.
			$results = $wpdb->get_results( $wpdb->prepare( "SELECT {$column} AS id, meta_value AS value FROM {$table} WHERE meta_key = %s AND meta_value <> ''", $key ) );
			foreach ( $results as $row ) {
				$rows[ (int) $row->id ][ $field ] = maybe_unserialize( $row->value );
			}
		}
		return $rows;
	}

	/**
	 * Term values of a source.
	 *
	 * @param string $source Source key.
	 * @return array
	 */
	private static function term_rows( $source ) {
		if ( 'yoast' === $source ) {
			$rows = array();
			$meta = get_option( 'wpseo_taxonomy_meta' );
			foreach ( is_array( $meta ) ? $meta : array() as $terms ) {
				foreach ( (array) $terms as $term_id => $values ) {
					$rows[ (int) $term_id ] = array(
						'title'       => $values['wpseo_title'] ?? '',
						'description' => $values['wpseo_desc'] ?? '',
						'noindex'     => $values['wpseo_noindex'] ?? '',
					);
				}
			}
			return $rows;
		}
		return isset( self::SOURCES[ $source ]['term'] ) ? self::meta_rows( 'term', self::SOURCES[ $source ]['term'] ) : array();
	}

	/**
	 * Rows from the All in One SEO table, with the post meta as fallback.
	 *
	 * @return array
	 */
	private static function aioseo_rows() {
		global $wpdb;
		$rows  = self::meta_rows( 'post', self::SOURCES['aioseo']['post'] );
		$table = self::aioseo_table();
		if ( ! $table ) {
			return $rows;
		}
		$results = $wpdb->get_results( "SELECT post_id, title, description, keyphrases, canonical_url, robots_default, robots_noindex, robots_nofollow, og_title, og_description FROM {$table}" ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Table name from $wpdb->prefix.
		foreach ( (array) $results as $r ) {
			$custom                    = ! (int) $r->robots_default;
			$keyphrases                = json_decode( (string) $r->keyphrases, true );
			$rows[ (int) $r->post_id ] = array_filter(
				array(
					'title'       => (string) $r->title,
					'description' => (string) $r->description,
					'focus'       => (string) ( $keyphrases['focus']['keyphrase'] ?? '' ),
					'canonical'   => (string) $r->canonical_url,
					'noindex'     => $custom ? (int) $r->robots_noindex : 0,
					'nofollow'    => $custom ? (int) $r->robots_nofollow : 0,
					'og_title'    => (string) $r->og_title,
					'og_desc'     => (string) $r->og_description,
				)
			) + ( $rows[ (int) $r->post_id ] ?? array() );
		}
		return $rows;
	}

	/**
	 * Maps raw values of a source to MomentumLab SEO fields.
	 *
	 * @param array  $values Raw values.
	 * @param string $source Source key.
	 * @return array
	 */
	private static function normalize( array $values, $source ) {
		$out = array();
		foreach ( array( 'title', 'description', 'og_title', 'og_desc', 'breadcrumb' ) as $field ) {
			if ( isset( $values[ $field ] ) && is_string( $values[ $field ] ) ) {
				$out[ $field ] = self::replace_vars( $values[ $field ], $source );
			}
		}
		if ( isset( $values['focus'] ) && is_string( $values['focus'] ) ) {
			// Rank Math and SEOPress store several keywords separated by commas; the first is the focus.
			$out['focus'] = trim( explode( ',', $values['focus'] )[0] );
		}
		foreach ( array(
			'canonical'    => 'strval',
			'image'        => 'absint',
			'primary_term' => 'absint',
		) as $field => $cast ) {
			if ( isset( $values[ $field ] ) && is_scalar( $values[ $field ] ) ) {
				$out[ $field ] = $cast( $values[ $field ] );
			}
		}
		$out = array_merge( $out, self::normalize_robots( $values, $source ) );
		if ( $term ) {
			unset( $out['nofollow'], $out['robots'] );
		}
		return $out;
	}

	/**
	 * Maps the robots settings of a source: noindex, nofollow and extra directives.
	 *
	 * @param array  $values Raw values.
	 * @param string $source Source key.
	 * @return array
	 */
	private static function normalize_robots( array $values, $source ) {
		$out = array();
		$on  = array(
			'yoast'    => array( '1', 'noindex', 'nofollow' ),
			'seopress' => array( 'yes' ),
			'aioseo'   => array( '1', 1 ),
		);
		foreach ( array( 'noindex', 'nofollow' ) as $field ) {
			if ( isset( $values[ $field ] ) && isset( $on[ $source ] ) ) {
				$out[ $field ] = in_array( $values[ $field ], $on[ $source ], true );
			}
		}
		if ( isset( $values['robots'] ) && is_array( $values['robots'] ) ) {
			$out['noindex']  = in_array( 'noindex', $values['robots'], true );
			$out['nofollow'] = in_array( 'nofollow', $values['robots'], true );
			$out['robots']   = implode( ',', array_intersect( Post_Meta::ROBOTS, $values['robots'] ) );
		}
		if ( isset( $values['robots_adv'] ) && is_string( $values['robots_adv'] ) ) {
			$out['robots'] = implode( ',', array_intersect( Post_Meta::ROBOTS, explode( ',', $values['robots_adv'] ) ) );
		}
		return $out;
	}

	/**
	 * Converts the title variables of the other plugins to MomentumLab SEO variables (%title%).
	 *
	 * A value that is only the default pattern (title, separator, site name)
	 * is dropped, so the template of the content type applies. Variables without
	 * an equivalent are removed.
	 *
	 * @param string $text   Value with variables.
	 * @param string $source Source key.
	 * @return string
	 */
	public static function replace_vars( $text, $source ) {
		// Yoast and SEOPress use %%var%%, Rank Math %var%, All in One SEO #var.
		$pattern = 'aioseo' === $source ? '/()()#([a-z_]+)/i' : '/%%([a-z0-9_]+)%%|%([a-z0-9_]+)%()/i';
		if ( ! preg_match( $pattern, $text ) ) {
			return trim( $text );
		}
		$default = array( 'title', 'sitename', 'sep', 'page', 'term' );
		$known   = array_map(
			function ( $name ) {
				return trim( $name, '%' );
			},
			array_keys( Titles::variables() )
		);
		$names   = array();
		$text    = preg_replace_callback(
			$pattern,
			function ( $m ) use ( $known, &$names ) {
				$name    = self::variable_name( $m[1] . ( $m[2] ?? '' ) . ( $m[3] ?? '' ) );
				$names[] = $name;
				return in_array( $name, $known, true ) ? '%' . $name . '%' : '';
			},
			$text
		);
		$rest    = trim( (string) preg_replace( '/%[a-z_]+%/', '', $text ), " \t-–—|·•:/" );
		if ( '' === $rest && ! array_diff( $names, $default ) ) {
			return '';
		}
		return trim( (string) preg_replace( '/\s+/', ' ', $text ) );
	}

	/**
	 * Name of a variable in MomentumLab SEO, from the spelling of another plugin.
	 *
	 * @param string $name Variable name without delimiters.
	 * @return string
	 */
	private static function variable_name( $name ) {
		$name = strtolower( $name );
		return Titles::ALIASES[ $name ] ?? $name;
	}
}
