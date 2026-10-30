<?php
/**
 * Hreflang: links between the language versions of a page (for example DE, EN, FR).
 *
 * For websites that keep each language on its own site or domain. When a
 * multilingual plugin is active, it prints hreflang itself and this stays off.
 *
 * @package MomentumLab\SEO
 */

namespace MomentumLab\SEO;

defined( 'ABSPATH' ) || exit;

/**
 * Stores the address of each translation per page and prints the alternate links.
 */
final class Hreflang {

	/**
	 * Registers hooks.
	 */
	public function hooks() {
		add_action( 'wp_head', array( $this, 'print_links' ), 3 );
	}

	/**
	 * Whether a multilingual plugin handles hreflang.
	 *
	 * @return bool
	 */
	public static function handled_elsewhere() {
		return '' !== Multilingual::plugin();
	}

	/**
	 * Language of this website as hreflang code (de-CH).
	 *
	 * @return string
	 */
	public static function own() {
		return Settings::language();
	}

	/**
	 * Other languages from the settings.
	 *
	 * @return string[] Codes like en, fr, fr-CH.
	 */
	public static function languages() {
		return self::parse( (string) Settings::get( 'hreflang_langs' ) );
	}

	/**
	 * Valid, unique language codes from a comma or line separated list.
	 *
	 * @param string $codes_list Raw list.
	 * @return string[]
	 */
	public static function parse( $codes_list ) {
		$codes = array();
		foreach ( preg_split( '/[\s,;]+/', $codes_list ) as $code ) {
			$code = str_replace( '_', '-', trim( $code ) );
			if ( preg_match( '/^([a-z]{2,3})(-[a-z]{2}|-[a-z]{4})?$/i', $code, $m ) ) {
				$code           = strtolower( $m[1] ) . ( isset( $m[2] ) ? '-' . ( 5 === strlen( $m[2] ) ? ucfirst( strtolower( substr( $m[2], 1 ) ) ) : strtoupper( substr( $m[2], 1 ) ) ) : '' );
				$codes[ $code ] = $code;
			}
		}
		unset( $codes[ self::own() ] );
		return array_values( $codes );
	}

	/**
	 * Translation addresses of a post (language => URL).
	 *
	 * @param int $post_id Post ID.
	 * @return array<string,string>
	 */
	public static function for_post( $post_id ) {
		$stored = Post_Meta::get( $post_id, 'hreflang' );
		$out    = array();
		foreach ( self::languages() as $code ) {
			if ( ! empty( $stored[ $code ] ) ) {
				$out[ $code ] = (string) $stored[ $code ];
			}
		}
		return $out;
	}

	/**
	 * Sanitises the form values.
	 *
	 * @param mixed $in Raw values (language => URL).
	 * @return array<string,string>
	 */
	public static function sanitize( $in ) {
		$out = array();
		foreach ( self::languages() as $code ) {
			$url = is_array( $in ) && isset( $in[ $code ] ) ? esc_url_raw( trim( (string) $in[ $code ] ), array( 'https', 'http' ) ) : '';
			if ( '' !== $url ) {
				$out[ $code ] = $url;
			}
		}
		return $out;
	}

	/**
	 * Prints <link rel="alternate" hreflang> for pages that have translations.
	 */
	public function print_links() {
		if ( self::handled_elsewhere() || ! self::languages() ) {
			return;
		}
		$seo = Context::current();
		if ( ! $seo['post_id'] || $seo['noindex'] || '' === $seo['url'] || ( ! is_singular() && ! is_front_page() ) ) {
			return;
		}
		$translations = self::for_post( $seo['post_id'] );
		if ( ! $translations ) {
			return;
		}
		$links = array_merge( array( self::own() => $seo['url'] ), $translations );
		$x     = (string) Settings::get( 'hreflang_default' );
		if ( isset( $links[ $x ] ) ) {
			$links['x-default'] = $links[ $x ];
		}
		foreach ( $links as $code => $url ) {
			printf( '<link rel="alternate" hreflang="%s" href="%s" />' . "\n", esc_attr( $code ), esc_url( $url ) );
		}
	}
}
