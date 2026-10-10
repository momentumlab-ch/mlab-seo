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
				'title'       => '_yoast_wpseo_title',
				'description' => '_yoast_wpseo_metadesc',
				'focus'       => '_yoast_wpseo_focuskw',
				'noindex'     => '_yoast_wpseo_meta-robots-noindex',
				'nofollow'    => '_yoast_wpseo_meta-robots-nofollow',
				'canonical'   => '_yoast_wpseo_canonical',
				'image'       => '_yoast_wpseo_opengraph-image-id',
				'og_title'    => '_yoast_wpseo_opengraph-title',
				'og_desc'     => '_yoast_wpseo_opengraph-description',
			),
		),
		'rankmath' => array(
			'label' => 'Rank Math',
			'post'  => array(
				'title'       => 'rank_math_title',
				'description' => 'rank_math_description',
				'focus'       => 'rank_math_focus_keyword',
				'robots'      => 'rank_math_robots',
				'canonical'   => 'rank_math_canonical_url',
				'image'       => 'rank_math_facebook_image_id',
				'og_title'    => 'rank_math_facebook_title',
				'og_desc'     => 'rank_math_facebook_description',
			),
			'term'  => array(
				'title'       => 'rank_math_title',
				'description' => 'rank_math_description',
				'robots'      => 'rank_math_robots',
			),
		),
		'seopress' => array(
			'label' => 'SEOPress',
			'post'  => array(
				'title'       => '_seopress_titles_title',
				'description' => '_seopress_titles_desc',
				'focus'       => '_seopress_analysis_target_kw',
				'noindex'     => '_seopress_robots_index',
				'nofollow'    => '_seopress_robots_follow',
				'canonical'   => '_seopress_robots_canonical',
				'image'       => '_seopress_social_fb_img_attachment_id',
				'og_title'    => '_seopress_social_fb_title',
				'og_desc'     => '_seopress_social_fb_desc',
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
		$rows = 'aioseo' === $source ? self::aioseo_rows() : self::meta_rows( 'post', self::SOURCES[ $source ]['post'] );
		$done = array();
		foreach ( $rows as $post_id => $values ) {
			$post = get_post( $post_id );
			if ( ! $post || ! in_array( $post->post_type, Post_Meta::post_types(), true ) ) {
				continue;
			}
			$changed = false;
			foreach ( self::normalize( $values, $source, $post ) as $field => $value ) {
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
			if ( $changed ) {
				$done[ $post_id ] = true;
			}
		}

		$terms = 0;
		foreach ( self::term_rows( $source ) as $term_id => $values ) {
			$term = get_term( $term_id );
			if ( ! $term instanceof \WP_Term || ! in_array( $term->taxonomy, Term_Meta::taxonomies(), true ) ) {
				continue;
			}
			$changed = false;
			foreach ( self::normalize( $values, $source, null, $term ) as $field => $value ) {
				if ( ! isset( Term_Meta::FIELDS[ $field ] ) || '' === $value || false === $value ) {
					continue;
				}
				if ( ! $overwrite && '' !== (string) get_term_meta( $term_id, Post_Meta::PREFIX . $field, true ) ) {
					continue;
				}
				Term_Meta::set( $term_id, $field, $value );
				$changed = true;
			}
			$terms += (int) $changed;
		}

		Crawlers::flush_cache();
		return array(
			'posts' => count( $done ),
			'terms' => $terms,
		);
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
	 * @param array         $values Raw values.
	 * @param string        $source Source key.
	 * @param \WP_Post|null $post   Post.
	 * @param \WP_Term|null $term   Term.
	 * @return array
	 */
	private static function normalize( array $values, $source, $post = null, $term = null ) {
		$out = array();
		foreach ( array( 'title', 'description', 'og_title', 'og_desc' ) as $field ) {
			if ( isset( $values[ $field ] ) && is_string( $values[ $field ] ) ) {
				$out[ $field ] = self::replace_vars( $values[ $field ], $source, $post, $term );
			}
		}
		if ( isset( $values['focus'] ) && is_string( $values['focus'] ) ) {
			// Rank Math and SEOPress store several keywords separated by commas; the first is the focus.
			$out['focus'] = trim( explode( ',', $values['focus'] )[0] );
		}
		if ( isset( $values['canonical'] ) ) {
			$out['canonical'] = (string) $values['canonical'];
		}
		if ( isset( $values['image'] ) ) {
			$out['image'] = absint( $values['image'] );
		}

		$on = array(
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
		}
		if ( $term ) {
			unset( $out['nofollow'] );
		}
		return $out;
	}

	/**
	 * Replaces title variables of the other plugins.
	 *
	 * A value that is only the default pattern (title, separator, site name)
	 * is dropped, so the MomentumLab SEO default applies. Other variables are
	 * replaced by their current value; unknown ones are removed.
	 *
	 * @param string        $text   Value with variables.
	 * @param string        $source Source key.
	 * @param \WP_Post|null $post   Post.
	 * @param \WP_Term|null $term   Term.
	 * @return string
	 */
	public static function replace_vars( $text, $source, $post = null, $term = null ) {
		// Yoast and SEOPress use %%var%%, Rank Math %var%, All in One SEO #var.
		$pattern = 'aioseo' === $source ? '/()()#([a-z_]+)/i' : '/%%([a-z0-9_]+)%%|%([a-z0-9_]+)%()/i';
		if ( ! preg_match( $pattern, $text ) ) {
			return trim( $text );
		}
		$default = array( 'title', 'post_title', 'term_title', 'taxonomy_title', 'sitename', 'site_title', 'sitetitle', 'sep', 'separator_sa', 'page', 'pagenumber', 'term', 'category_title' );
		$rest    = preg_replace_callback(
			$pattern,
			function ( $m ) use ( $default ) {
				$name = strtolower( $m[1] . ( $m[2] ?? '' ) . ( $m[3] ?? '' ) );
				return in_array( $name, $default, true ) ? '' : $m[0];
			},
			$text
		);
		if ( '' === trim( $rest, " \t-–—|·•:/" ) ) {
			return '';
		}

		$values = array(
			'title'            => $post ? Context::plain_title( $post ) : ( $term ? $term->name : '' ),
			'post_title'       => $post ? Context::plain_title( $post ) : '',
			'term_title'       => $term ? $term->name : '',
			'taxonomy_title'   => $term ? $term->name : '',
			'category_title'   => $term ? $term->name : '',
			'term'             => $term ? $term->name : '',
			'sitename'         => Settings::site_name(),
			'site_title'       => Settings::site_name(),
			'sitetitle'        => Settings::site_name(),
			'sitedesc'         => wp_strip_all_tags( get_bloginfo( 'description' ) ),
			'tagline'          => wp_strip_all_tags( get_bloginfo( 'description' ) ),
			'site_description' => wp_strip_all_tags( get_bloginfo( 'description' ) ),
			'sep'              => (string) Settings::get( 'separator' ),
			'separator_sa'     => (string) Settings::get( 'separator' ),
			'excerpt'          => $post ? Context::auto_description( $post ) : '',
			'post_excerpt'     => $post ? Context::auto_description( $post ) : '',
			'excerpt_only'     => $post ? Context::auto_description( $post ) : '',
			'term_description' => $term ? wp_strip_all_tags( $term->description ) : '',
			'category'         => $post && get_the_category( $post->ID ) ? get_the_category( $post->ID )[0]->name : '',
			'post_category'    => $post && get_the_category( $post->ID ) ? get_the_category( $post->ID )[0]->name : '',
		);
		$text   = preg_replace_callback(
			$pattern,
			function ( $m ) use ( $values ) {
				$name = strtolower( $m[1] . ( $m[2] ?? '' ) . ( $m[3] ?? '' ) );
				return $values[ $name ] ?? '';
			},
			$text
		);
		return trim( preg_replace( '/\s+/', ' ', $text ) );
	}
}
