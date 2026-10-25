<?php
/**
 * WooCommerce: products in the schema.org graph.
 *
 * @package MomentumLab\SEO
 */

namespace MomentumLab\SEO;

defined( 'ABSPATH' ) || exit;

/**
 * Product node with price, availability and ratings, linked to the web page and the publisher.
 */
final class WooCommerce {

	/**
	 * Registers hooks.
	 */
	public function hooks() {
		add_action( 'init', array( $this, 'replace_markup' ) );
	}

	/**
	 * Whether WooCommerce is active and the product schema is switched on.
	 *
	 * @return bool
	 */
	public static function active() {
		return function_exists( 'wc_get_product' ) && (bool) Settings::get( 'woo_schema' );
	}

	/**
	 * Stops the product markup of WooCommerce, so there is only one product node.
	 */
	public function replace_markup() {
		if ( self::active() ) {
			add_filter( 'woocommerce_structured_data_product', '__return_empty_array' );
		}
	}

	/**
	 * Product node for a product page.
	 *
	 * @param int    $post_id Post ID.
	 * @param string $url     Page URL.
	 * @return array List with zero or one node.
	 */
	public static function product_schema( $post_id, $url ) {
		if ( ! self::active() || 'product' !== get_post_type( $post_id ) ) {
			return array();
		}
		$product = wc_get_product( $post_id );
		if ( ! $product ) {
			return array();
		}
		$image = Context::image( (int) $product->get_image_id() );
		$node  = array(
			'@type'            => 'Product',
			'@id'              => $url . '#product',
			'name'             => wp_strip_all_tags( $product->get_name() ),
			'description'      => Context::current()['description'],
			'url'              => $url,
			'sku'              => (string) $product->get_sku(),
			'image'            => $image ? Schema::image_object( $image ) : null,
			'brand'            => self::brand( $post_id ),
			'mainEntityOfPage' => array( '@id' => $url . '#webpage' ),
			'offers'           => self::offer( $product, $url ),
		);
		return array( Schema::clean( array_merge( $node, self::rating( $product ) ) ) );
	}

	/**
	 * Offer of a product: price (lowest and highest for variable products), currency, availability.
	 *
	 * @param \WC_Product $product Product.
	 * @param string      $url     Page URL.
	 * @return array|null
	 */
	private static function offer( $product, $url ) {
		$price = (string) $product->get_price();
		if ( '' === $price ) {
			return null;
		}
		$offer = array(
			'@type'         => 'Offer',
			'price'         => wc_format_decimal( $price, wc_get_price_decimals() ),
			'priceCurrency' => get_woocommerce_currency(),
			'availability'  => 'https://schema.org/' . ( $product->is_in_stock() ? 'InStock' : 'OutOfStock' ),
			'url'           => $url,
			'seller'        => array( '@id' => Schema::publisher()['@id'] ?? home_url( '/' ) . '#organization' ),
		);
		if ( $product->is_type( 'variable' ) && $product->get_variation_price( 'min' ) !== $product->get_variation_price( 'max' ) ) {
			$offer['@type']     = 'AggregateOffer';
			$offer['lowPrice']  = wc_format_decimal( $product->get_variation_price( 'min' ), wc_get_price_decimals() );
			$offer['highPrice'] = wc_format_decimal( $product->get_variation_price( 'max' ), wc_get_price_decimals() );
			unset( $offer['price'] );
		}
		return $offer;
	}

	/**
	 * Average rating, when the product has reviews.
	 *
	 * @param \WC_Product $product Product.
	 * @return array
	 */
	private static function rating( $product ) {
		$count = (int) $product->get_review_count();
		if ( ! $count || ! wc_review_ratings_enabled() ) {
			return array();
		}
		return array(
			'aggregateRating' => array(
				'@type'       => 'AggregateRating',
				'ratingValue' => (float) $product->get_average_rating(),
				'reviewCount' => $count,
				'bestRating'  => 5,
				'worstRating' => 1,
			),
		);
	}

	/**
	 * Brand from the brand taxonomy of WooCommerce, when set.
	 *
	 * @param int $post_id Product ID.
	 * @return array|null
	 */
	private static function brand( $post_id ) {
		$brands = taxonomy_exists( 'product_brand' ) ? get_the_terms( $post_id, 'product_brand' ) : false;
		if ( ! is_array( $brands ) || ! $brands ) {
			return null;
		}
		return array(
			'@type' => 'Brand',
			'name'  => html_entity_decode( $brands[0]->name, ENT_QUOTES | ENT_HTML5, 'UTF-8' ),
		);
	}
}
