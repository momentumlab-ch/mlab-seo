<?php
/**
 * Rich result types per page (Pro): job posting, recipe, course, review. One type per page, driven by a field list.
 *
 * @package MomentumLab\SEO
 */

namespace MomentumLab\SEO;

defined( 'ABSPATH' ) || exit;

/**
 * Field lists, sanitising, missing-data check and schema.org nodes of the rich result types.
 */
final class Rich_Results {

	/**
	 * Employment types of Google for Jobs.
	 */
	const EMPLOYMENT = array( 'FULL_TIME', 'PART_TIME', 'CONTRACTOR', 'TEMPORARY', 'INTERN', 'VOLUNTEER', 'PER_DIEM', 'OTHER' );

	/**
	 * Types a review can be about.
	 */
	const REVIEWED = array( 'Product', 'Book', 'Movie', 'Course', 'Recipe', 'SoftwareApplication', 'Service', 'LocalBusiness', 'Organization' );

	/**
	 * Categories of a software application (schema.org).
	 */
	const SOFTWARE = array( 'BusinessApplication', 'DeveloperApplication', 'UtilitiesApplication', 'MultimediaApplication', 'DesignApplication', 'EducationalApplication', 'FinanceApplication', 'HealthApplication', 'GameApplication', 'SecurityApplication', 'BrowserApplication', 'CommunicationApplication', 'SocialNetworkingApplication', 'LifestyleApplication', 'TravelApplication', 'ShoppingApplication', 'HomeApplication', 'ReferenceApplication', 'EntertainmentApplication', 'SportsApplication' );

	/**
	 * Types with label.
	 *
	 * @return array<string,string>
	 */
	public static function types() {
		return array(
			'job'    => __( 'Job posting (Google for Jobs, Indeed)', 'mlab-seo' ),
			'recipe' => __( 'Recipe', 'mlab-seo' ),
			'course' => __( 'Course', 'mlab-seo' ),
			'review' => __( 'Review', 'mlab-seo' ),
			'software' => __( 'Software app', 'mlab-seo' ),
		);
	}

	/**
	 * Fields per type: label, kind (text, textarea, url, date, number, check, select), required, optional choices.
	 *
	 * @param string $type Type key.
	 * @return array<string,array>
	 */
	public static function fields( $type ) {
		$text = fn ( $label, $req = false, $kind = 'text' ) => array(
			'label'    => $label,
			'kind'     => $kind,
			'required' => $req,
		);
		switch ( $type ) {
			case 'job':
				return array(
					'title'        => $text( __( 'Job title', 'mlab-seo' ), true ),
					'description'  => $text( __( 'Job description', 'mlab-seo' ), true, 'textarea' ),
					'posted'       => $text( __( 'Posted on (empty: date of the page)', 'mlab-seo' ), false, 'date' ),
					'valid_through' => $text( __( 'Apply until', 'mlab-seo' ), false, 'date' ),
					'employment'   => array(
						'label'   => __( 'Employment type', 'mlab-seo' ),
						'kind'    => 'select',
						'choices' => self::EMPLOYMENT,
					),
					'org_name'     => $text( __( 'Employer (empty: organisation of the website)', 'mlab-seo' ) ),
					'remote'       => $text( __( 'Remote work possible', 'mlab-seo' ), false, 'check' ),
					'street'       => $text( __( 'Street', 'mlab-seo' ) ),
					'zip'          => $text( __( 'Postcode', 'mlab-seo' ) ),
					'city'         => $text( __( 'City', 'mlab-seo' ), true ),
					'region'       => $text( __( 'Region', 'mlab-seo' ) ),
					'country'      => $text( __( 'Country code', 'mlab-seo' ) ),
					'salary_min'   => $text( __( 'Salary from', 'mlab-seo' ), false, 'number' ),
					'salary_max'   => $text( __( 'Salary up to', 'mlab-seo' ), false, 'number' ),
					'currency'     => $text( __( 'Currency', 'mlab-seo' ) ),
					'unit'         => array(
						'label'   => __( 'Salary per', 'mlab-seo' ),
						'kind'    => 'select',
						'choices' => array( 'YEAR', 'MONTH', 'WEEK', 'HOUR' ),
					),
					'apply_url'    => $text( __( 'Apply URL', 'mlab-seo' ), false, 'url' ),
					'direct_apply' => $text( __( 'Applicants apply directly on this page (Indeed)', 'mlab-seo' ), false, 'check' ),
					'identifier'   => $text( __( 'Job number', 'mlab-seo' ) ),
				);
			case 'recipe':
				return array(
					'name'         => $text( __( 'Name', 'mlab-seo' ), true ),
					'description'  => $text( __( 'Description', 'mlab-seo' ), false, 'textarea' ),
					'author'       => $text( __( 'Author', 'mlab-seo' ) ),
					'prep_min'     => $text( __( 'Preparation (minutes)', 'mlab-seo' ), false, 'number' ),
					'cook_min'     => $text( __( 'Cooking (minutes)', 'mlab-seo' ), false, 'number' ),
					'yield'        => $text( __( 'Servings', 'mlab-seo' ) ),
					'calories'     => $text( __( 'Calories per serving', 'mlab-seo' ), false, 'number' ),
					'ingredients'  => $text( __( 'Ingredients (one per line)', 'mlab-seo' ), true, 'textarea' ),
					'steps'        => $text( __( 'Steps (one per line)', 'mlab-seo' ), true, 'textarea' ),
					'rating_value' => $text( __( 'Average rating (1 to 5)', 'mlab-seo' ), false, 'number' ),
					'rating_count' => $text( __( 'Number of ratings', 'mlab-seo' ), false, 'number' ),
				);
			case 'course':
				return array(
					'name'         => $text( __( 'Course name', 'mlab-seo' ), true ),
					'description'  => $text( __( 'Description', 'mlab-seo' ), true, 'textarea' ),
					'provider'     => $text( __( 'Provider', 'mlab-seo' ), true ),
					'provider_url' => $text( __( 'Provider website', 'mlab-seo' ), false, 'url' ),
				);
			case 'review':
				return array(
					'item_name' => $text( __( 'Name of the reviewed item', 'mlab-seo' ), true ),
					'item_type' => array(
						'label'   => __( 'Type of the reviewed item', 'mlab-seo' ),
						'kind'    => 'select',
						'choices' => self::REVIEWED,
					),
					'rating'    => $text( __( 'Rating', 'mlab-seo' ), true, 'number' ),
					'best'      => $text( __( 'Best possible rating (empty: 5)', 'mlab-seo' ), false, 'number' ),
					'author'    => $text( __( 'Reviewer (empty: author of the page)', 'mlab-seo' ) ),
					'body'      => $text( __( 'Review text', 'mlab-seo' ), false, 'textarea' ),
				);
			case 'software':
				return array(
					'name'         => $text( __( 'App name', 'mlab-seo' ), true ),
					'description'  => $text( __( 'Description', 'mlab-seo' ), false, 'textarea' ),
					'category'     => array(
						'label'   => __( 'Category', 'mlab-seo' ),
						'kind'    => 'select',
						'choices' => self::SOFTWARE,
					),
					'os'           => $text( __( 'Operating system (for example Windows, macOS, Android)', 'mlab-seo' ), true ),
					'price'        => $text( __( 'Price (0 for free)', 'mlab-seo' ), true, 'number' ),
					'currency'     => $text( __( 'Currency', 'mlab-seo' ) ),
					'rating_value' => $text( __( 'Average rating (1 to 5)', 'mlab-seo' ), false, 'number' ),
					'rating_count' => $text( __( 'Number of ratings', 'mlab-seo' ), false, 'number' ),
				);
		}
		return array();
	}

	/**
	 * Sanitises the saved structure.
	 *
	 * @param mixed $in Raw data with type and one value per field.
	 * @return array Empty when no type or no main field is given.
	 */
	public static function sanitize( $in ) {
		$type = is_array( $in ) && isset( $in['type'] ) && isset( self::types()[ $in['type'] ] ) ? $in['type'] : '';
		if ( '' === $type ) {
			return array();
		}
		$out = array( 'type' => $type );
		foreach ( self::fields( $type ) as $key => $field ) {
			$raw = isset( $in[ $key ] ) ? (string) $in[ $key ] : '';
			switch ( $field['kind'] ) {
				case 'textarea':
					$out[ $key ] = sanitize_textarea_field( $raw );
					break;
				case 'url':
					$out[ $key ] = esc_url_raw( $raw, array( 'https', 'http' ) );
					break;
				case 'number':
					$out[ $key ] = preg_replace( '/[^0-9.]/', '', $raw );
					break;
				case 'check':
					$out[ $key ] = '' !== $raw ? '1' : '';
					break;
				case 'date':
					$out[ $key ] = preg_match( '/^\d{4}-\d{2}-\d{2}$/', $raw ) ? $raw : '';
					break;
				case 'select':
					$out[ $key ] = in_array( $raw, $field['choices'], true ) ? $raw : $field['choices'][0];
					break;
				default:
					$out[ $key ] = 'country' === $key || 'currency' === $key ? strtoupper( preg_replace( '/[^A-Za-z]/', '', $raw ) ) : sanitize_text_field( $raw );
			}
		}
		$out['country']  = isset( $out['country'] ) ? substr( $out['country'], 0, 2 ) : null;
		$out['currency'] = isset( $out['currency'] ) ? substr( $out['currency'], 0, 3 ) : null;
		return array_filter( $out, fn ( $value ) => null !== $value );
	}

	/**
	 * Missing required fields as labels, for the hint in the editor and the overview.
	 *
	 * @param array $data Saved data.
	 * @return string[]
	 */
	public static function gaps( array $data ) {
		$type = $data['type'] ?? '';
		$gaps = array();
		foreach ( self::fields( $type ) as $key => $field ) {
			if ( empty( $field['required'] ) || '' !== trim( (string) ( $data[ $key ] ?? '' ) ) ) {
				continue;
			}
			if ( 'job' === $type && 'city' === $key && ! empty( $data['remote'] ) ) {
				continue;
			}
			$gaps[] = $field['label'];
		}
		return $gaps;
	}

	/**
	 * Schema.org node of the page's rich result, or null.
	 *
	 * @param int        $post_id   Post ID.
	 * @param array|null $publisher Publisher node.
	 * @return array|null
	 */
	public static function node( $post_id, $publisher ) {
		$d = Post_Meta::get( $post_id, 'rich' );
		if ( ! $d || self::gaps( $d ) ) {
			return null;
		}
		$url = (string) get_permalink( $post_id );
		$img = Context::image( Context::current()['image'] ?? 0 );
		switch ( $d['type'] ) {
			case 'job':
				return self::job( $d, $post_id, $publisher, $url );
			case 'recipe':
				return self::recipe( $d, $url, $img );
			case 'course':
				return Schema::clean(
					array(
						'@type'       => 'Course',
						'@id'         => $url . '#course',
						'name'        => $d['name'],
						'description' => $d['description'],
						'url'         => $url,
						'provider'    => Schema::clean(
							array(
								'@type'  => 'Organization',
								'name'   => $d['provider'],
								'sameAs' => $d['provider_url'] ?? '',
							)
						),
					)
				);
			case 'review':
				return self::review( $d, $post_id, $url, $img );
			case 'software':
				return self::software( $d, $url );
		}
		return null;
	}

	/**
	 * JobPosting node. Google for Jobs and Indeed read the same markup.
	 *
	 * @param array      $d         Data.
	 * @param int        $post_id   Post ID.
	 * @param array|null $publisher Publisher node.
	 * @param string     $url       Page URL.
	 * @return array
	 */
	private static function job( array $d, $post_id, $publisher, $url ) {
		$org    = array(
			'@type'  => 'Organization',
			'name'   => ! empty( $d['org_name'] ) ? $d['org_name'] : ( $publisher['name'] ?? Settings::site_name() ),
			'sameAs' => home_url( '/' ),
			'logo'   => $publisher['logo']['url'] ?? null,
		);
		$remote = ! empty( $d['remote'] );
		$salary = null;
		if ( ! empty( $d['salary_min'] ) || ! empty( $d['salary_max'] ) ) {
			$salary = array(
				'@type'    => 'MonetaryAmount',
				'currency' => ! empty( $d['currency'] ) ? $d['currency'] : 'CHF',
				'value'    => Schema::clean(
					array(
						'@type'    => 'QuantitativeValue',
						'minValue' => $d['salary_min'] ?? '',
						'maxValue' => $d['salary_max'] ?? '',
						'unitText' => $d['unit'],
					)
				),
			);
		}
		return Schema::clean(
			array(
				'@type'                      => 'JobPosting',
				'@id'                        => $url . '#job',
				'title'                      => $d['title'],
				'description'                => $d['description'],
				'datePosted'                 => ! empty( $d['posted'] ) ? $d['posted'] : get_post_time( 'Y-m-d', true, $post_id ),
				'validThrough'               => $d['valid_through'] ?? '',
				'employmentType'             => $d['employment'],
				'identifier'                 => ! empty( $d['identifier'] ) ? array(
					'@type' => 'PropertyValue',
					'name'  => $org['name'],
					'value' => $d['identifier'],
				) : null,
				'hiringOrganization'         => Schema::clean( $org ),
				'jobLocation'                => ! empty( $d['city'] ) ? array(
					'@type'   => 'Place',
					'address' => Schema::address( $d['street'] ?? '', $d['zip'] ?? '', $d['city'], $d['country'] ?? '', $d['region'] ?? '' ),
				) : null,
				'jobLocationType'            => $remote ? 'TELECOMMUTE' : null,
				'applicantLocationRequirements' => $remote && ! empty( $d['country'] ) ? array(
					'@type' => 'Country',
					'name'  => $d['country'],
				) : null,
				'baseSalary'                 => $salary,
				'directApply'                => ! empty( $d['direct_apply'] ) ? true : null,
				'url'                        => ! empty( $d['apply_url'] ) ? $d['apply_url'] : $url,
			)
		);
	}

	/**
	 * Recipe node.
	 *
	 * @param array      $d   Data.
	 * @param string     $url Page URL.
	 * @param array|null $img Image data.
	 * @return array
	 */
	private static function recipe( array $d, $url, $img ) {
		$lines = fn ( $text ) => array_values( array_filter( array_map( 'trim', explode( "\n", (string) $text ) ) ) );
		$prep  = (int) ( $d['prep_min'] ?? 0 );
		$cook  = (int) ( $d['cook_min'] ?? 0 );
		$steps = array();
		foreach ( $lines( $d['steps'] ) as $step ) {
			$steps[] = array(
				'@type' => 'HowToStep',
				'text'  => $step,
			);
		}
		return Schema::clean(
			array(
				'@type'              => 'Recipe',
				'@id'                => $url . '#recipe',
				'name'               => $d['name'],
				'description'        => $d['description'] ?? '',
				'image'              => $img ? array( $img['url'] ) : null,
				'author'             => ! empty( $d['author'] ) ? array(
					'@type' => 'Person',
					'name'  => $d['author'],
				) : null,
				'prepTime'           => $prep ? 'PT' . $prep . 'M' : null,
				'cookTime'           => $cook ? 'PT' . $cook . 'M' : null,
				'totalTime'          => $prep + $cook ? 'PT' . ( $prep + $cook ) . 'M' : null,
				'recipeYield'        => $d['yield'] ?? '',
				'recipeIngredient'   => $lines( $d['ingredients'] ),
				'recipeInstructions' => $steps,
				'nutrition'          => ! empty( $d['calories'] ) ? array(
					'@type'    => 'NutritionInformation',
					'calories' => $d['calories'] . ' calories',
				) : null,
				'aggregateRating'    => ! empty( $d['rating_value'] ) && ! empty( $d['rating_count'] ) ? array(
					'@type'       => 'AggregateRating',
					'ratingValue' => $d['rating_value'],
					'ratingCount' => $d['rating_count'],
				) : null,
			)
		);
	}

	/**
	 * Review node.
	 *
	 * @param array      $d       Data.
	 * @param int        $post_id Post ID.
	 * @param string     $url     Page URL.
	 * @param array|null $img     Image data.
	 * @return array
	 */
	private static function review( array $d, $post_id, $url, $img ) {
		$author = ! empty( $d['author'] ) ? $d['author'] : get_the_author_meta( 'display_name', (int) get_post_field( 'post_author', $post_id ) );
		return Schema::clean(
			array(
				'@type'         => 'Review',
				'@id'           => $url . '#review',
				'itemReviewed'  => Schema::clean(
					array(
						'@type' => $d['item_type'],
						'name'  => $d['item_name'],
						'image' => $img ? $img['url'] : null,
					)
				),
				'reviewRating'  => array(
					'@type'       => 'Rating',
					'ratingValue' => $d['rating'],
					'bestRating'  => ! empty( $d['best'] ) ? $d['best'] : '5',
				),
				'author'        => array(
					'@type' => 'Person',
					'name'  => $author,
				),
				'reviewBody'    => $d['body'] ?? '',
				'datePublished' => get_post_time( 'Y-m-d', true, $post_id ),
			)
		);
	}

	/**
	 * SoftwareApplication node with price and rating.
	 *
	 * @param array  $d   Data.
	 * @param string $url Page URL.
	 * @return array
	 */
	private static function software( array $d, $url ) {
		return Schema::clean(
			array(
				'@type'               => 'SoftwareApplication',
				'@id'                 => $url . '#software',
				'name'                => $d['name'],
				'description'         => $d['description'] ?? '',
				'applicationCategory' => $d['category'],
				'operatingSystem'     => $d['os'],
				'url'                 => $url,
				'offers'              => array(
					'@type'         => 'Offer',
					'price'         => $d['price'],
					'priceCurrency' => ! empty( $d['currency'] ) ? $d['currency'] : 'CHF',
				),
				'aggregateRating'     => ! empty( $d['rating_value'] ) && ! empty( $d['rating_count'] ) ? array(
					'@type'       => 'AggregateRating',
					'ratingValue' => $d['rating_value'],
					'ratingCount' => $d['rating_count'],
				) : null,
			)
		);
	}

	/**
	 * Prints the input of a field.
	 *
	 * @param string $type  Type key.
	 * @param string $key   Field key.
	 * @param array  $field Field definition.
	 * @param array  $data  Saved data.
	 */
	public static function input( $type, $key, array $field, array $data ) {
		$id    = 'mlab-seo-rich-' . $type . '-' . $key;
		$name  = 'mlab_seo_rich[' . $key . ']';
		$value = (string) ( $data['type'] ?? '' ) === $type ? (string) ( $data[ $key ] ?? '' ) : '';
		$label = esc_html( $field['label'] ) . ( ! empty( $field['required'] ) ? ' <abbr title="' . esc_attr__( 'Required', 'mlab-seo' ) . '">*</abbr>' : '' );
		if ( 'check' === $field['kind'] ) {
			printf( '<p><label><input type="checkbox" name="%s" value="1" %s /> %s</label></p>', esc_attr( $name ), checked( '1', $value, false ), $label ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped above.
			return;
		}
		printf( '<p><label class="mlab-seo__label" for="%s">%s</label>', esc_attr( $id ), $label ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped above.
		if ( 'textarea' === $field['kind'] ) {
			printf( '<textarea class="widefat" rows="3" id="%s" name="%s">%s</textarea>', esc_attr( $id ), esc_attr( $name ), esc_textarea( $value ) );
		} elseif ( 'select' === $field['kind'] ) {
			printf( '<select id="%s" name="%s">', esc_attr( $id ), esc_attr( $name ) );
			foreach ( $field['choices'] as $choice ) {
				printf( '<option value="%1$s" %2$s>%1$s</option>', esc_attr( $choice ), selected( $value, $choice, false ) );
			}
			echo '</select>';
		} else {
			$kind = in_array( $field['kind'], array( 'url', 'date' ), true ) ? $field['kind'] : 'text';
			printf( '<input type="%s" class="widefat" id="%s" name="%s" value="%s" %s />', esc_attr( $kind ), esc_attr( $id ), esc_attr( $name ), esc_attr( $value ), 'number' === $field['kind'] ? 'inputmode="decimal"' : '' );
		}
		echo '</p>';
	}
}
