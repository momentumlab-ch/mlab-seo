<?php
/**
 * Structured data (schema.org) as one JSON-LD graph.
 *
 * @package MomentumLab\SEO
 */

namespace MomentumLab\SEO;

defined( 'ABSPATH' ) || exit;

/**
 * Builds the @graph: publisher, website, web page, breadcrumbs, article, event and custom nodes.
 */
final class Schema {

	/**
	 * Prints the JSON-LD script.
	 *
	 * @param array $seo SEO data of the current request.
	 */
	public static function print_graph( $seo ) {
		$graph = self::graph( $seo );
		if ( ! $graph ) {
			return;
		}
		$json = wp_json_encode(
			array(
				'@context' => 'https://schema.org',
				'@graph'   => $graph,
			),
			JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE
		);
		// The JSON is encoded; only a closing script tag could break out, so it is escaped.
		echo '<script type="application/ld+json">' . str_replace( '</', '<\/', (string) $json ) . "</script>\n"; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}

	/**
	 * The graph nodes.
	 *
	 * @param array $seo SEO data.
	 * @return array
	 */
	public static function graph( $seo ) {
		$home      = home_url( '/' );
		$publisher = self::publisher();
		$graph     = $publisher ? array( $publisher ) : array();
		$graph[]   = self::clean(
			array(
				'@type'      => 'WebSite',
				'@id'        => $home . '#website',
				'url'        => $home,
				'name'       => Settings::site_name(),
				'inLanguage' => Settings::language(),
				'publisher'  => $publisher ? array( '@id' => $publisher['@id'] ) : null,
			)
		);

		$post_id = (int) $seo['post_id'];
		if ( $post_id && ! $seo['noindex'] ) {
			$graph = array_merge( $graph, self::post_nodes( $post_id, $seo, $publisher ) );
		}

		/**
		 * Filters the schema.org graph.
		 *
		 * @param array $graph Nodes.
		 * @param array $seo   SEO data of the current request.
		 */
		return (array) apply_filters( 'mlab_seo_schema_graph', $graph, $seo );
	}

	/**
	 * Nodes of a post: web page, breadcrumbs, FAQ, videos, article, product, event and custom nodes.
	 *
	 * @param int        $post_id   Post ID.
	 * @param array      $seo       SEO data.
	 * @param array|null $publisher Publisher node.
	 * @return array
	 */
	private static function post_nodes( $post_id, array $seo, $publisher ) {
		$url    = '' !== $seo['url'] ? $seo['url'] : (string) get_permalink( $post_id );
		$page   = self::web_page( $post_id, $seo, $url );
		$faq    = FAQ::for_post( $post_id );
		$crumbs = Breadcrumbs::list_items( Breadcrumbs::for_post( $post_id ) );
		$nodes  = array();

		// FAQ blocks: on an FAQ page the questions belong to the page itself.
		if ( $faq && 'FAQPage' === $page['@type'] ) {
			$page['mainEntity'] = FAQ::questions( $faq );
			$faq                = array();
		}
		if ( $crumbs ) {
			$page['breadcrumb'] = array( '@id' => $url . '#breadcrumb' );
		}
		$nodes[] = self::clean( $page );
		if ( $crumbs ) {
			$nodes[] = array(
				'@type'           => 'BreadcrumbList',
				'@id'             => $url . '#breadcrumb',
				'itemListElement' => $crumbs,
			);
		}
		if ( $faq ) {
			$nodes[] = self::faq_page( $faq, $url );
		}
		$nodes = array_merge( $nodes, Media::video_schema( $post_id, $seo ), self::article( $post_id, $seo, $url, $publisher ) );
		$nodes = array_merge( $nodes, WooCommerce::product_schema( $post_id, $url ) );
		return License::is_pro() ? array_merge( $nodes, self::pro_nodes( $post_id, $publisher ) ) : $nodes;
	}

	/**
	 * Web page node.
	 *
	 * @param int    $post_id Post ID.
	 * @param array  $seo     SEO data.
	 * @param string $url     Page URL.
	 * @return array
	 */
	private static function web_page( $post_id, array $seo, $url ) {
		$img  = Context::image( $seo['image'] );
		$type = Post_Meta::get( $post_id, 'page_type' );
		return array(
			'@type'              => '' !== $type ? $type : 'WebPage',
			'@id'                => $url . '#webpage',
			'url'                => $url,
			'name'               => $seo['title'],
			'description'        => $seo['description'],
			'inLanguage'         => Settings::language(),
			'isPartOf'           => array( '@id' => home_url( '/' ) . '#website' ),
			'datePublished'      => get_post_time( 'c', true, $post_id ),
			'dateModified'       => get_post_modified_time( 'c', true, $post_id ),
			'primaryImageOfPage' => $img ? self::image_object( $img ) : null,
		);
	}

	/**
	 * FAQPage node for FAQ blocks on a page that is not an FAQ page itself.
	 *
	 * @param array  $faq Questions and answers.
	 * @param string $url Page URL.
	 * @return array
	 */
	private static function faq_page( array $faq, $url ) {
		return array(
			'@type'      => 'FAQPage',
			'@id'        => $url . '#faq',
			'url'        => $url,
			'isPartOf'   => array( '@id' => $url . '#webpage' ),
			'inLanguage' => Settings::language(),
			'mainEntity' => FAQ::questions( $faq ),
		);
	}

	/**
	 * Article node (BlogPosting, Article or NewsArticle), when the post or its content type is set to one.
	 *
	 * @param int        $post_id   Post ID.
	 * @param array      $seo       SEO data.
	 * @param string     $url       Page URL.
	 * @param array|null $publisher Publisher node.
	 * @return array List with zero or one node.
	 */
	private static function article( $post_id, array $seo, $url, $publisher ) {
		$type = Titles::article_type( $post_id );
		if ( '' === $type ) {
			return array();
		}
		$img      = Context::image( $seo['image'] );
		$author   = (int) get_post_field( 'post_author', $post_id );
		$sections = wp_list_pluck( get_the_category( $post_id ), 'name' );
		$words    = count( preg_split( '/\s+/u', trim( wp_strip_all_tags( excerpt_remove_blocks( strip_shortcodes( (string) get_post_field( 'post_content', $post_id ) ) ) ) ), -1, PREG_SPLIT_NO_EMPTY ) );
		return array(
			self::clean(
				array(
					'@type'            => $type,
					'@id'              => $url . '#article',
					'headline'         => Context::plain_title( $post_id ),
					'mainEntityOfPage' => array( '@id' => $url . '#webpage' ),
					'datePublished'    => get_post_time( 'c', true, $post_id ),
					'dateModified'     => get_post_modified_time( 'c', true, $post_id ),
					'image'            => $img ? $img['url'] : null,
					'inLanguage'       => Settings::language(),
					'articleSection'   => $sections ? $sections : null,
					'wordCount'        => $words ? $words : null,
					'author'           => $author ? array(
						'@type' => 'Person',
						'name'  => get_the_author_meta( 'display_name', $author ),
					) : null,
					'publisher'        => $publisher ? array( '@id' => $publisher['@id'] ) : null,
				)
			),
		);
	}

	/**
	 * Pro nodes: event and custom JSON-LD of the post.
	 *
	 * @param int        $post_id   Post ID.
	 * @param array|null $publisher Publisher node.
	 * @return array
	 */
	private static function pro_nodes( $post_id, $publisher ) {
		$event  = self::event( $post_id, $publisher );
		$nodes  = $event ? array( $event ) : array();
		$custom = json_decode( Post_Meta::get( $post_id, 'schema' ), true );
		if ( ! is_array( $custom ) ) {
			return $nodes;
		}
		if ( isset( $custom['@graph'] ) && is_array( $custom['@graph'] ) ) {
			$custom = $custom['@graph'];
		}
		foreach ( isset( $custom['@type'] ) ? array( $custom ) : $custom as $node ) {
			if ( is_array( $node ) ) {
				unset( $node['@context'] );
				$nodes[] = $node;
			}
		}
		return $nodes;
	}

	/**
	 * Publisher node (organisation or person), or null when no name is set.
	 *
	 * @return array|null
	 */
	public static function publisher() {
		$s    = Settings::all();
		$type = $s['org_type'];
		$home = home_url( '/' );
		$logo = Context::image( $s['org_logo'] ? $s['org_logo'] : (int) get_theme_mod( 'custom_logo' ), 'full' );

		$node = array(
			'@type'         => $type,
			'@id'           => $home . ( 'Person' === $type ? '#person' : '#organization' ),
			'name'          => '' !== $s['org_name'] ? $s['org_name'] : Settings::site_name(),
			'alternateName' => $s['org_alt_name'],
			'url'           => $home,
			'email'         => $s['org_email'],
			'telephone'     => $s['org_phone'],
			'address'       => self::address( $s['org_street'], $s['org_zip'], $s['org_city'], $s['org_country'], $s['org_region'] ),
			'sameAs'        => Settings::lines( 'org_same_as' ),
		);
		if ( in_array( $type, Settings::LOCAL_TYPES, true ) ) {
			$node = array_merge( $node, self::local_business( $s, $logo ) );
		}
		if ( $logo ) {
			$node[ 'Person' === $type ? 'image' : 'logo' ] = self::image_object( $logo );
		}
		$extra = json_decode( (string) $s['org_extra'], true );
		if ( is_array( $extra ) ) {
			unset( $extra['@context'], $extra['@id'] );
			$node = array_merge( $node, $extra );
		}
		return self::clean( $node );
	}

	/**
	 * ImageObject node.
	 *
	 * @param array $img Image with url, width and height.
	 * @return array
	 */
	public static function image_object( array $img ) {
		return array(
			'@type'  => 'ImageObject',
			'url'    => $img['url'],
			'width'  => $img['width'],
			'height' => $img['height'],
		);
	}

	/**
	 * Local business properties: opening hours, price range, map position, image.
	 *
	 * @param array      $s    Settings.
	 * @param array|null $logo Logo image.
	 * @return array
	 */
	private static function local_business( array $s, $logo ) {
		$props = array(
			'openingHoursSpecification' => self::opening_hours( (string) $s['org_hours'] ),
			'priceRange'                => $s['org_price_range'],
		);
		if ( '' !== $s['org_lat'] && '' !== $s['org_lng'] ) {
			$props['geo'] = array(
				'@type'     => 'GeoCoordinates',
				'latitude'  => (float) $s['org_lat'],
				'longitude' => (float) $s['org_lng'],
			);
		}
		if ( $logo ) {
			$props['image'] = $logo['url'];
		}
		return $props;
	}

	/**
	 * Opening hours from lines like "Mo-Fr 08:00-12:00, 13:30-17:30" or "Sa 09:00-16:00".
	 *
	 * @param string $text One rule per line.
	 * @return array OpeningHoursSpecification nodes.
	 */
	public static function opening_hours( $text ) {
		$specs = array();
		foreach ( preg_split( '/\R/', $text ) as $line ) {
			if ( ! preg_match( '/^\s*([a-z,\-\s]+?)\s+(\d.*)$/i', trim( $line ), $m ) ) {
				continue;
			}
			$days = self::days( $m[1] );
			if ( ! $days || ! preg_match_all( '/(\d{1,2})[:.](\d{2})\s*-\s*(\d{1,2})[:.](\d{2})/', $m[2], $times, PREG_SET_ORDER ) ) {
				continue;
			}
			foreach ( $times as $t ) {
				$specs[] = array(
					'@type'     => 'OpeningHoursSpecification',
					'dayOfWeek' => $days,
					'opens'     => sprintf( '%02d:%s', $t[1], $t[2] ),
					'closes'    => sprintf( '%02d:%s', $t[3], $t[4] ),
				);
			}
		}
		return $specs;
	}

	/**
	 * Day names from a rule like "Mo-Fr", "Sa, So" or "Mo bis Mi" (English or German).
	 *
	 * @param string $text Days part of the rule.
	 * @return string[] schema.org day names.
	 */
	private static function days( $text ) {
		$names = array(
			'mo' => 'Monday',
			'tu' => 'Tuesday',
			'we' => 'Wednesday',
			'th' => 'Thursday',
			'fr' => 'Friday',
			'sa' => 'Saturday',
			'su' => 'Sunday',
		);
		$keys  = array_keys( $names );
		$days  = array();
		foreach ( explode( ',', strtolower( str_replace( ' ', '', str_ireplace( array( ' bis ', ' to ' ), '-', $text ) ) ) ) as $part ) {
			$range = array_map( array( self::class, 'day_key' ), explode( '-', $part ) );
			$from  = array_search( $range[0], $keys, true );
			$to    = array_search( end( $range ), $keys, true );
			if ( false === $from || false === $to ) {
				continue;
			}
			for ( $i = $from; $i <= ( $to >= $from ? $to : $to + 7 ); $i++ ) {
				$days[] = $names[ $keys[ $i % 7 ] ];
			}
		}
		return array_values( array_unique( $days ) );
	}

	/**
	 * Two-letter English day key, also from German abbreviations.
	 *
	 * @param string $day Day as written.
	 * @return string
	 */
	private static function day_key( $day ) {
		$alias = array(
			'di' => 'tu',
			'mi' => 'we',
			'do' => 'th',
			'so' => 'su',
		);
		$day   = substr( $day, 0, 2 );
		return $alias[ $day ] ?? $day;
	}

	/**
	 * PostalAddress or null.
	 *
	 * @param string $street  Street.
	 * @param string $zip     Postal code.
	 * @param string $city    City.
	 * @param string $country ISO country code.
	 * @param string $region  Region.
	 * @return array|null
	 */
	private static function address( $street, $zip, $city, $country, $region = '' ) {
		if ( '' === $city && '' === $street ) {
			return null;
		}
		return self::clean(
			array(
				'@type'           => 'PostalAddress',
				'streetAddress'   => $street,
				'postalCode'      => $zip,
				'addressLocality' => $city,
				'addressRegion'   => $region,
				'addressCountry'  => $country,
			)
		);
	}

	/**
	 * Event node from the post's event fields (Pro).
	 *
	 * @param int        $post_id   Post ID.
	 * @param array|null $publisher Publisher node.
	 * @return array|null
	 */
	public static function event( $post_id, $publisher ) {
		$e = Post_Meta::get( $post_id, 'event' );
		if ( empty( $e['name'] ) || empty( $e['start'] ) ) {
			return null;
		}
		$url   = '' !== $e['url'] ? $e['url'] : (string) get_permalink( $post_id );
		$place = self::event_place( $e );
		$img   = Context::image( Context::current()['image'] );
		$sport = 'SportsEvent' === $e['type'] ? $e['sport'] : null;

		$node             = array(
			'@type'               => $e['type'],
			'@id'                 => $url . '#event',
			'name'                => $e['name'],
			'description'         => $e['description'],
			'startDate'           => self::event_date( $e['start'] ),
			'endDate'             => '' !== $e['end'] ? self::event_date( $e['end'] ) : null,
			'eventStatus'         => 'https://schema.org/' . $e['status'],
			'eventAttendanceMode' => 'https://schema.org/OfflineEventAttendanceMode',
			'sport'               => $sport,
			'location'            => $place,
			'image'               => $img ? array( $img['url'] ) : null,
			'url'                 => $url,
			'organizer'           => $publisher ? array( '@id' => $publisher['@id'] ) : null,
			'offers'              => self::event_offer( $e ),
		);
		$node['subEvent'] = self::sub_events( $e, $place, $sport );
		return self::clean( $node );
	}

	/**
	 * Place of an event: venue name (or city) and address.
	 *
	 * @param array $e Event fields.
	 * @return array
	 */
	private static function event_place( array $e ) {
		return self::clean(
			array(
				'@type'   => 'Place',
				'name'    => '' !== $e['location'] ? $e['location'] : $e['city'],
				'address' => self::address( $e['street'], $e['zip'], $e['city'], $e['country'] ),
			)
		);
	}

	/**
	 * Sub-event nodes (races, concerts, sessions).
	 *
	 * @param array       $e     Event data.
	 * @param array       $place Place of the main event.
	 * @param string|null $sport Sport of a sports event.
	 * @return array
	 */
	private static function sub_events( array $e, array $place, $sport ) {
		$nodes = array();
		foreach ( $e['sub'] as $sub ) {
			$nodes[] = self::clean(
				array(
					'@type'     => $e['type'],
					'name'      => $sub['name'],
					'startDate' => self::event_date( $sub['start'] ),
					'sport'     => $sport,
					'location'  => '' !== $sub['location'] ? array_merge( $place, array( 'name' => $sub['location'] ) ) : $place,
				)
			);
		}
		return $nodes;
	}

	/**
	 * Date with time zone when a time is given (2026-05-01T10:00 becomes ISO 8601), else unchanged.
	 *
	 * @param string $value Date or date and time.
	 * @return string
	 */
	private static function event_date( $value ) {
		if ( ! str_contains( $value, 'T' ) ) {
			return $value;
		}
		$dt = date_create_immutable( $value, wp_timezone() );
		return $dt ? $dt->format( 'c' ) : $value;
	}

	/**
	 * Ticket offer of an event, or null without ticket URL and price.
	 *
	 * @param array $e Event data.
	 * @return array|null
	 */
	private static function event_offer( array $e ) {
		if ( '' === $e['offer_url'] && '' === $e['price'] ) {
			return null;
		}
		return self::clean(
			array(
				'@type'         => 'Offer',
				'url'           => $e['offer_url'],
				'price'         => $e['price'],
				'priceCurrency' => $e['currency'],
				'availability'  => 'https://schema.org/InStock',
			)
		);
	}

	/**
	 * Removes empty values.
	 *
	 * @param array $node Node.
	 * @return array
	 */
	public static function clean( $node ) {
		return array_filter(
			$node,
			function ( $value ) {
				return null !== $value && '' !== $value && array() !== $value;
			}
		);
	}
}
