<?php
/**
 * Multilingual websites: the language of a page, its home address and the list of languages.
 *
 * Reads them from the multilingual plugin in use: MomentumLab Multilingual,
 * WPML, Polylang or TranslatePress. Without one, the website has one language.
 * Hreflang links and the sitemap per language come from these plugins;
 * site-wide texts are translated through wpml-config.xml (WPML and Polylang).
 *
 * @package MomentumLab\SEO
 */

namespace MomentumLab\SEO;

defined( 'ABSPATH' ) || exit;

/**
 * Language data of the active multilingual plugin.
 */
final class Multilingual {

	/**
	 * Registers hooks.
	 */
	public function hooks() {
		// Translated settings are read again once the language of the request is known.
		add_action( 'pll_language_defined', array( Settings::class, 'flush' ) );
		add_action( 'wpml_language_has_switched', array( Settings::class, 'flush' ) );
		add_filter( 'mlab_seo_context', array( $this, 'translate_url' ), 5 );
	}

	/**
	 * The multilingual plugin in use, or an empty string.
	 *
	 * @return string mlab-multilingual, wpml, polylang, translatepress or ''.
	 */
	public static function plugin() {
		if ( function_exists( 'mlab_ml' ) && count( mlab_ml()->module( 'languages' )->active() ) > 1 ) {
			return 'mlab-multilingual';
		}
		if ( defined( 'ICL_SITEPRESS_VERSION' ) ) {
			return 'wpml';
		}
		if ( function_exists( 'pll_current_language' ) ) {
			return 'polylang';
		}
		return class_exists( 'TRP_Translate_Press' ) ? 'translatepress' : '';
	}

	/**
	 * Name of the multilingual plugin in use.
	 *
	 * @return string
	 */
	public static function plugin_name() {
		$names = array(
			'mlab-multilingual' => 'MomentumLab Multilingual',
			'wpml'              => 'WPML',
			'polylang'          => 'Polylang',
			'translatepress'    => 'TranslatePress',
		);
		return $names[ self::plugin() ] ?? '';
	}

	/**
	 * Where the site-wide texts (website name, home page title …) are translated.
	 *
	 * @return string
	 */
	public static function texts_hint() {
		switch ( self::plugin() ) {
			case 'mlab-multilingual':
				return __( 'Each language gets the title and description from the SEO box of its home page.', 'mlab-seo' );
			case 'wpml':
				return __( 'Translate the website name, the home page title and description, the breadcrumb label and the llms.txt summary under WPML › String Translation.', 'mlab-seo' );
			case 'polylang':
				return __( 'Translate the website name, the home page title and description, the breadcrumb label and the llms.txt summary under Languages › Translations.', 'mlab-seo' );
			case 'translatepress':
				return __( 'Translate the titles and descriptions on the page itself with the TranslatePress editor.', 'mlab-seo' );
		}
		return '';
	}

	/**
	 * Active languages, the default language first.
	 *
	 * @return array<string,string> Language code => name. Empty without a multilingual plugin.
	 */
	public static function languages() {
		switch ( self::plugin() ) {
			case 'mlab-multilingual':
				$catalog = mlab_ml()->module( 'languages' )->catalog();
				$list    = array_intersect_key( wp_list_pluck( $catalog, 'name' ), array_flip( mlab_ml()->module( 'languages' )->active() ) );
				break;
			case 'wpml':
				$list = wp_list_pluck( (array) apply_filters( 'wpml_active_languages', null, array( 'skip_missing' => 0 ) ), 'native_name' );
				break;
			case 'polylang':
				$list = array_combine( pll_languages_list( array( 'fields' => 'slug' ) ), pll_languages_list( array( 'fields' => 'name' ) ) );
				break;
			case 'translatepress':
				$codes = (array) ( self::trp_settings()['publish-languages'] ?? array() );
				$list  = array_combine( $codes, $codes );
				break;
			default:
				return array();
		}
		$default = self::default_language();
		return isset( $list[ $default ] ) ? array( $default => $list[ $default ] ) + $list : $list;
	}

	/**
	 * Code of the default language.
	 *
	 * @return string
	 */
	public static function default_language() {
		switch ( self::plugin() ) {
			case 'mlab-multilingual':
				return mlab_ml()->module( 'languages' )->default();
			case 'wpml':
				return (string) apply_filters( 'wpml_default_language', null );
			case 'polylang':
				return (string) pll_default_language();
			case 'translatepress':
				return (string) ( self::trp_settings()['default-language'] ?? '' );
		}
		return '';
	}

	/**
	 * Code of the language of the current request.
	 *
	 * @return string
	 */
	public static function current() {
		switch ( self::plugin() ) {
			case 'mlab-multilingual':
				return mlab_ml_current_language();
			case 'wpml':
				$code = (string) apply_filters( 'wpml_current_language', null );
				break;
			case 'polylang':
				$code = (string) pll_current_language();
				break;
			case 'translatepress':
				$code = (string) ( $GLOBALS['TRP_LANGUAGE'] ?? '' );
				break;
			default:
				return '';
		}
		return '' !== $code && 'all' !== $code ? $code : self::default_language();
	}

	/**
	 * Home page address of a language.
	 *
	 * @param string $code Language code; empty for the current language.
	 * @return string
	 */
	public static function home_url( $code = '' ) {
		$code = '' !== $code ? $code : self::current();
		switch ( self::plugin() ) {
			case 'mlab-multilingual':
				return mlab_ml_home_url( $code );
			case 'wpml':
				return (string) apply_filters( 'wpml_permalink', home_url( '/' ), $code, true );
			case 'polylang':
				$url = pll_home_url( $code );
				return $url ? $url : home_url( '/' );
			case 'translatepress':
				return self::trp_url( home_url( '/' ), $code );
		}
		return home_url( '/' );
	}

	/**
	 * Language code of a post. Empty when unknown or when one post holds all
	 * languages (TranslatePress).
	 *
	 * @param int $post_id Post ID.
	 * @return string
	 */
	public static function post_language( $post_id ) {
		switch ( self::plugin() ) {
			case 'mlab-multilingual':
				return (string) mlab_ml_get_post_language( (int) $post_id );
			case 'wpml':
				$details = apply_filters( 'wpml_post_language_details', null, (int) $post_id );
				return is_array( $details ) ? (string) ( $details['language_code'] ?? '' ) : '';
			case 'polylang':
				return (string) pll_get_post_language( (int) $post_id );
		}
		return '';
	}

	/**
	 * Query arguments that return posts of every language, not just the current one.
	 *
	 * @return array
	 */
	public static function all_languages_args() {
		return 'polylang' === self::plugin() ? array( 'lang' => '' ) : array( 'suppress_filters' => true );
	}

	/**
	 * Posts grouped by language, in the order of the languages. One group
	 * with the key '' on websites with one language.
	 *
	 * @param \WP_Post[] $posts Posts.
	 * @return array<string,\WP_Post[]>
	 */
	public static function group_posts( array $posts ) {
		$codes = array_keys( self::languages() );
		if ( count( $codes ) < 2 ) {
			return array( '' => $posts );
		}
		$groups = array_fill_keys( $codes, array() );
		foreach ( $posts as $post ) {
			$code              = self::post_language( $post->ID );
			$code              = isset( $groups[ $code ] ) ? $code : $codes[0];
			$groups[ $code ][] = $post;
		}
		return array_filter( $groups );
	}

	/**
	 * TranslatePress keeps one post for all languages: points the canonical
	 * address of a translated page to that language.
	 *
	 * @param array $data SEO data.
	 * @return array
	 */
	public function translate_url( $data ) {
		if ( 'translatepress' !== self::plugin() || empty( $data['url'] ) || self::current() === self::default_language() ) {
			return $data;
		}
		$data['url'] = self::trp_url( $data['url'], self::current() );
		return $data;
	}

	/**
	 * TranslatePress settings.
	 *
	 * @return array
	 */
	private static function trp_settings() {
		$trp = \TRP_Translate_Press::get_trp_instance();
		return (array) $trp->get_component( 'settings' )->get_settings();
	}

	/**
	 * An address in another language (TranslatePress).
	 *
	 * @param string $url  Address in any language.
	 * @param string $code Locale of the language, e.g. en_US.
	 * @return string
	 */
	private static function trp_url( $url, $code ) {
		$converter = \TRP_Translate_Press::get_trp_instance()->get_component( 'url_converter' );
		return $converter ? (string) $converter->get_url_for_language( $code, $url, '' ) : $url;
	}
}
