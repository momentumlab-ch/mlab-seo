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
		$home  = home_url( '/' );
		$graph = array();

		$publisher = self::publisher();
		if ( $publisher ) {
			$graph[] = $publisher;
		}

		$graph[] = self::clean(
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
			$url  = '' !== $seo['url'] ? $seo['url'] : (string) get_permalink( $post_id );
			$img  = Context::image( $seo['image'] );
			$type = Post_Meta::get( $post_id, 'page_type' );
			$page = array(
				'@type'              => '' !== $type ? $type : 'WebPage',
				'@id'                => $url . '#webpage',
				'url'                => $url,
				'name'               => $seo['title'],
				'description'        => $seo['description'],
				'inLanguage'         => Settings::language(),
				'isPartOf'           => array( '@id' => $home . '#website' ),
				'datePublished'      => get_post_time( 'c', true, $post_id ),
				'dateModified'       => get_post_modified_time( 'c', true, $post_id ),
				'primaryImageOfPage' => $img ? array(
					'@type'  => 'ImageObject',
					'url'    => $img['url'],
					'width'  => $img['width'],
					'height' => $img['height'],
				) : null,
			);

			$crumbs = self::breadcrumbs( $post_id );
			if ( $crumbs ) {
				$page['breadcrumb'] = array( '@id' => $url . '#breadcrumb' );
			}
			$graph[] = self::clean( $page );
			if ( $crumbs ) {
				$graph[] = array(
					'@type'           => 'BreadcrumbList',
					'@id'             => $url . '#breadcrumb',
					'itemListElement' => $crumbs,
				);
			}

			if ( 'post' === get_post_type( $post_id ) ) {
				$author  = (int) get_post_field( 'post_author', $post_id );
				$graph[] = self::clean(
					array(
						'@type'            => 'BlogPosting',
						'@id'              => $url . '#article',
						'headline'         => Context::plain_title( $post_id ),
						'mainEntityOfPage' => array( '@id' => $url . '#webpage' ),
						'datePublished'    => get_post_time( 'c', true, $post_id ),
						'dateModified'     => get_post_modified_time( 'c', true, $post_id ),
						'image'            => $img ? $img['url'] : null,
						'author'           => array(
							'@type' => 'Person',
							'name'  => get_the_author_meta( 'display_name', $author ),
						),
						'publisher'        => $publisher ? array( '@id' => $publisher['@id'] ) : null,
					)
				);
			}

			if ( License::is_pro() ) {
				$event = self::event( $post_id, $publisher );
				if ( $event ) {
					$graph[] = $event;
				}
				$custom = json_decode( Post_Meta::get( $post_id, 'schema' ), true );
				if ( is_array( $custom ) ) {
					if ( isset( $custom['@graph'] ) && is_array( $custom['@graph'] ) ) {
						$custom = $custom['@graph'];
					}
					foreach ( isset( $custom['@type'] ) ? array( $custom ) : $custom as $node ) {
						if ( is_array( $node ) ) {
							unset( $node['@context'] );
							$graph[] = $node;
						}
					}
				}
			}
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
	 * Publisher node (organisation or person), or null when no name is set.
	 *
	 * @return array|null
	 */
	public static function publisher() {
		$s    = Settings::all();
		$name = '' !== $s['org_name'] ? $s['org_name'] : Settings::site_name();
		$type = $s['org_type'];
		$home = home_url( '/' );
		$logo = Context::image( $s['org_logo'] ? $s['org_logo'] : (int) get_theme_mod( 'custom_logo' ), 'full' );

		$node = array(
			'@type'         => $type,
			'@id'           => $home . ( 'Person' === $type ? '#person' : '#organization' ),
			'name'          => $name,
			'alternateName' => $s['org_alt_name'],
			'url'           => $home,
			'email'         => $s['org_email'],
			'telephone'     => $s['org_phone'],
			'address'       => self::address( $s['org_street'], $s['org_zip'], $s['org_city'], $s['org_country'], $s['org_region'] ),
			'sameAs'        => Settings::lines( 'org_same_as' ),
		);
		if ( $logo ) {
			$node[ 'Person' === $type ? 'image' : 'logo' ] = array(
				'@type'  => 'ImageObject',
				'url'    => $logo['url'],
				'width'  => $logo['width'],
				'height' => $logo['height'],
			);
		}
		$extra = json_decode( (string) $s['org_extra'], true );
		if ( is_array( $extra ) ) {
			unset( $extra['@context'], $extra['@id'] );
			$node = array_merge( $node, $extra );
		}
		return self::clean( $node );
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
	 * Breadcrumb items for a post (home, ancestors, post). Empty on the front page.
	 *
	 * @param int $post_id Post ID.
	 * @return array
	 */
	private static function breadcrumbs( $post_id ) {
		if ( 'page' === get_option( 'show_on_front' ) && (int) get_option( 'page_on_front' ) === $post_id ) {
			return array();
		}
		$front = (int) get_option( 'page_on_front' );
		$items = array(
			array(
				'name' => $front ? Context::plain_title( $front ) : __( 'Home', 'mlab-seo' ),
				'url'  => home_url( '/' ),
			),
		);
		foreach ( array_reverse( get_post_ancestors( $post_id ) ) as $ancestor ) {
			$items[] = array(
				'name' => Context::plain_title( $ancestor ),
				'url'  => get_permalink( $ancestor ),
			);
		}
		$items[] = array(
			'name' => Context::plain_title( $post_id ),
			'url'  => get_permalink( $post_id ),
		);

		$list = array();
		foreach ( $items as $i => $item ) {
			$list[] = array(
				'@type'    => 'ListItem',
				'position' => $i + 1,
				'name'     => $item['name'],
				'item'     => $item['url'],
			);
		}
		return $list;
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
		$place = self::clean(
			array(
				'@type'   => 'Place',
				'name'    => '' !== $e['location'] ? $e['location'] : $e['city'],
				'address' => self::address( $e['street'], $e['zip'], $e['city'], $e['country'] ),
			)
		);
		$img   = Context::image( Context::current()['image'] );
		$tz    = wp_timezone();
		$date  = function ( $value ) use ( $tz ) {
			if ( ! str_contains( $value, 'T' ) ) {
				return $value;
			}
			$dt = date_create_immutable( $value, $tz );
			return $dt ? $dt->format( 'c' ) : $value;
		};

		$node = array(
			'@type'               => $e['type'],
			'@id'                 => $url . '#event',
			'name'                => $e['name'],
			'description'         => $e['description'],
			'startDate'           => $date( $e['start'] ),
			'endDate'             => '' !== $e['end'] ? $date( $e['end'] ) : null,
			'eventStatus'         => 'https://schema.org/' . $e['status'],
			'eventAttendanceMode' => 'https://schema.org/OfflineEventAttendanceMode',
			'sport'               => 'SportsEvent' === $e['type'] ? $e['sport'] : null,
			'location'            => $place,
			'image'               => $img ? array( $img['url'] ) : null,
			'url'                 => $url,
			'organizer'           => $publisher ? array( '@id' => $publisher['@id'] ) : null,
		);

		if ( '' !== $e['offer_url'] || '' !== $e['price'] ) {
			$node['offers'] = self::clean(
				array(
					'@type'         => 'Offer',
					'url'           => $e['offer_url'],
					'price'         => $e['price'],
					'priceCurrency' => $e['currency'],
					'availability'  => 'https://schema.org/InStock',
				)
			);
		}

		foreach ( $e['sub'] as $sub ) {
			$node['subEvent'][] = self::clean(
				array(
					'@type'     => $e['type'],
					'name'      => $sub['name'],
					'startDate' => $date( $sub['start'] ),
					'sport'     => 'SportsEvent' === $e['type'] ? $e['sport'] : null,
					'location'  => '' !== $sub['location'] ? array_merge( (array) $place, array( 'name' => $sub['location'] ) ) : $place,
				)
			);
		}
		return self::clean( $node );
	}

	/**
	 * Removes empty values.
	 *
	 * @param array $node Node.
	 * @return array
	 */
	private static function clean( $node ) {
		return array_filter(
			$node,
			function ( $value ) {
				return null !== $value && '' !== $value && array() !== $value;
			}
		);
	}
}
