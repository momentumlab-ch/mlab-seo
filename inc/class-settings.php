<?php
/**
 * Site-wide settings: defaults, access and sanitising.
 *
 * @package MomentumLab\SEO
 */

namespace MomentumLab\SEO;

defined( 'ABSPATH' ) || exit;

/**
 * Stores all site-wide settings in one autoloaded option.
 */
final class Settings {

	/**
	 * Option name.
	 */
	const OPTION = 'mlab_seo_settings';

	/**
	 * Known AI crawlers (user agent => operator and purpose).
	 */
	const AI_BOTS = array(
		'GPTBot'             => 'OpenAI (training)',
		'OAI-SearchBot'      => 'OpenAI (ChatGPT search)',
		'ChatGPT-User'       => 'OpenAI (ChatGPT browsing)',
		'ClaudeBot'          => 'Anthropic (training)',
		'Claude-SearchBot'   => 'Anthropic (Claude search)',
		'Claude-User'        => 'Anthropic (Claude browsing)',
		'PerplexityBot'      => 'Perplexity (search)',
		'Perplexity-User'    => 'Perplexity (browsing)',
		'Google-Extended'    => 'Google (Gemini training)',
		'Applebot-Extended'  => 'Apple (Apple Intelligence training)',
		'meta-externalagent' => 'Meta (AI training)',
		'CCBot'              => 'Common Crawl (open dataset)',
	);

	/**
	 * Types (schema.org) offered for the publisher.
	 */
	const ORG_TYPES = array( 'Organization', 'SportsOrganization', 'NGO', 'Corporation', 'EducationalOrganization', 'GovernmentOrganization', 'Person', 'LocalBusiness', 'ProfessionalService', 'Store', 'Restaurant', 'Hotel', 'MedicalBusiness', 'Dentist', 'LegalService', 'FinancialService', 'RealEstateAgent', 'HealthAndBeautyBusiness', 'HomeAndConstructionBusiness', 'AutomotiveBusiness', 'SportsActivityLocation' );

	/**
	 * Publisher types that are local businesses (get opening hours, geo and price range).
	 */
	const LOCAL_TYPES = array( 'LocalBusiness', 'ProfessionalService', 'Store', 'Restaurant', 'Hotel', 'MedicalBusiness', 'Dentist', 'LegalService', 'FinancialService', 'RealEstateAgent', 'HealthAndBeautyBusiness', 'HomeAndConstructionBusiness', 'AutomotiveBusiness', 'SportsActivityLocation' );

	/**
	 * Site verification services (setting key => meta tag name).
	 */
	const VERIFY = array(
		'verify_google'    => 'google-site-verification',
		'verify_bing'      => 'msvalidate.01',
		'verify_yandex'    => 'yandex-verification',
		'verify_pinterest' => 'p:domain_verify',
	);

	/**
	 * Cached settings.
	 *
	 * @var array|null
	 */
	private static $cache = null;

	/**
	 * Default values, from inc/options.php.
	 *
	 * @return array
	 */
	public static function defaults() {
		return wp_list_pluck( mlab_seo_options(), 'default' );
	}

	/**
	 * All settings merged with the defaults.
	 *
	 * @return array
	 */
	public static function all() {
		if ( null === self::$cache ) {
			$stored      = get_option( self::OPTION, array() );
			self::$cache = array_merge( self::defaults(), is_array( $stored ) ? $stored : array() );
		}
		return self::$cache;
	}

	/**
	 * One setting.
	 *
	 * @param string $key Setting key.
	 * @return mixed
	 */
	public static function get( $key ) {
		$all = self::all();
		return $all[ $key ] ?? null;
	}

	/**
	 * Clears the cache (after saving).
	 */
	public static function flush() {
		self::$cache = null;
	}

	/**
	 * Saves settings.
	 *
	 * @param array $values Raw values.
	 */
	public static function save( array $values ) {
		update_option( self::OPTION, self::sanitize( $values ), true );
		self::flush();
	}

	/**
	 * Site name used in titles and schema.
	 *
	 * @return string
	 */
	public static function site_name() {
		$name = (string) self::get( 'site_name' );
		return '' !== $name ? $name : html_entity_decode( wp_strip_all_tags( get_bloginfo( 'name' ) ), ENT_QUOTES | ENT_HTML5, 'UTF-8' );
	}

	/**
	 * Content language as BCP 47 tag (de-CH), from the social locale or WordPress.
	 *
	 * @return string
	 */
	public static function language() {
		return str_replace( '_', '-', self::locale() );
	}

	/**
	 * Content locale as language_TERRITORY (de_CH), without variants like _informal.
	 *
	 * @return string
	 */
	public static function locale() {
		$locale = (string) self::get( 'og_locale' );
		if ( '' !== $locale ) {
			return $locale;
		}
		$parts = explode( '_', get_locale() );
		return implode( '_', array_slice( $parts, 0, 2 ) );
	}

	/**
	 * Lines of a multi-line setting, trimmed and without empties.
	 *
	 * @param string $key Setting key.
	 * @return string[]
	 */
	public static function lines( $key ) {
		return array_values( array_filter( array_map( 'trim', preg_split( '/\R/', (string) self::get( $key ) ) ) ) );
	}

	/**
	 * Sanitises a full settings array.
	 *
	 * @param array $in Raw values.
	 * @return array
	 */
	public static function sanitize( array $in ) {
		$out = array();
		foreach ( mlab_seo_options() as $key => $option ) {
			$out[ $key ] = self::sanitize_value( $option, $in[ $key ] ?? null );
		}
		$out = array_merge( $out, self::sanitize_special( $in, $out ) );
		$out = array_merge( $out, self::sanitize_publisher( $in, $out ) );

		$out['ai_bots'] = array();
		foreach ( array_keys( self::AI_BOTS ) as $bot ) {
			$out['ai_bots'][ $bot ] = empty( $in['ai_bots'][ $bot ] ) ? 0 : 1;
		}

		$types                = isset( $in['sitemap_types'] ) && is_array( $in['sitemap_types'] ) ? array_map( 'sanitize_key', $in['sitemap_types'] ) : array();
		$out['sitemap_types'] = array_values( array_intersect( $types, array_keys( get_post_types( array( 'public' => true ) ) ) ) );
		$out['titles']        = Titles::sanitize( $in['titles'] ?? array() );

		return $out;
	}

	/**
	 * Sanitises one value by the type of its option. A missing value gets the default,
	 * a missing checkbox or image is off.
	 *
	 * @param array $option Option definition.
	 * @param mixed $raw    Raw value or null.
	 * @return mixed
	 */
	private static function sanitize_value( array $option, $raw ) {
		switch ( $option['type'] ) {
			case 'checkbox':
				return empty( $raw ) ? 0 : 1;
			case 'image':
				return absint( $raw );
		}
		if ( null === $raw ) {
			return $option['default'];
		}
		switch ( $option['type'] ) {
			case 'textarea':
			case 'code':
				return sanitize_textarea_field( wp_unslash( (string) $raw ) );
			case 'email':
				return sanitize_email( wp_unslash( (string) $raw ) );
			case 'select':
				return isset( $option['choices'][ $raw ] ) ? $raw : $option['default'];
			case 'text':
			case 'tel':
				return sanitize_text_field( wp_unslash( (string) $raw ) );
		}
		return $option['default'];
	}

	/**
	 * Settings with own rules: separators, locale, languages, verification, IndexNow key.
	 *
	 * @param array $in  Raw values.
	 * @param array $out Values sanitised by type.
	 * @return array
	 */
	private static function sanitize_special( array $in, array $out ) {
		$special                     = array(
			'separator'      => '' !== $out['separator'] ? mb_substr( $out['separator'], 0, 3 ) : '–',
			'breadcrumb_sep' => '' !== $out['breadcrumb_sep'] ? mb_substr( $out['breadcrumb_sep'], 0, 3 ) : '›',
			'og_locale'      => preg_match( '/^[a-z]{2}_[A-Z]{2}$/', $out['og_locale'] ) ? $out['og_locale'] : '',
			'hreflang_langs' => isset( $in['hreflang_langs'] ) ? implode( ', ', Hreflang::parse( $out['hreflang_langs'] ) ) : '',
			// Redirect setting is saved with the redirect rules; keep it otherwise.
			'auto_redirect'  => array_key_exists( 'auto_redirect', $in ) ? $out['auto_redirect'] : (int) ( self::get( 'auto_redirect' ) ?? 1 ),
		);
		$languages                   = array_merge( array( Hreflang::own() ), Hreflang::parse( $special['hreflang_langs'] ) );
		$special['hreflang_default'] = in_array( $out['hreflang_default'], $languages, true ) ? $out['hreflang_default'] : '';

		foreach ( array_keys( self::VERIFY ) as $k ) {
			$special[ $k ] = isset( $in[ $k ] ) ? self::verification_code( wp_unslash( (string) $in[ $k ] ) ) : '';
		}

		// The IndexNow key is generated once and kept; it is not part of the form.
		$key                     = isset( $in['indexnow_key'] ) ? (string) $in['indexnow_key'] : (string) self::get( 'indexnow_key' );
		$special['indexnow_key'] = preg_match( '/^[a-f0-9]{32}$/', $key ) ? $key : '';
		if ( $out['indexnow'] && '' === $special['indexnow_key'] ) {
			$special['indexnow_key'] = bin2hex( random_bytes( 16 ) );
		}
		return $special;
	}

	/**
	 * Publisher settings with own rules: type, country, map position, profiles, extra JSON.
	 *
	 * @param array $in  Raw values.
	 * @param array $out Values sanitised by type.
	 * @return array
	 */
	private static function sanitize_publisher( array $in, array $out ) {
		$special = array(
			'org_type'    => in_array( $out['org_type'], self::ORG_TYPES, true ) ? $out['org_type'] : 'Organization',
			'org_country' => strtoupper( mb_substr( $out['org_country'], 0, 2 ) ),
			'org_same_as' => implode( "\n", array_filter( array_map( array( self::class, 'profile_url' ), preg_split( '/\R/', $out['org_same_as'] ) ) ) ),
			'org_extra'   => '',
		);
		foreach ( array( 'org_lat', 'org_lng' ) as $k ) {
			$value         = str_replace( ',', '.', $out[ $k ] );
			$special[ $k ] = is_numeric( $value ) && abs( (float) $value ) <= 180 ? $value : '';
		}
		$json = empty( $in['org_extra'] ) ? null : json_decode( wp_unslash( $in['org_extra'] ), true );
		if ( is_array( $json ) ) {
			$special['org_extra'] = wp_json_encode( $json, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT );
		}
		return $special;
	}

	/**
	 * A profile URL, or an empty string when it is not a web address.
	 *
	 * @param string $url Raw URL.
	 * @return string
	 */
	private static function profile_url( $url ) {
		return esc_url_raw( trim( $url ), array( 'https', 'http' ) );
	}

	/**
	 * Verification code from a code or a pasted meta tag.
	 *
	 * @param string $raw Raw input.
	 * @return string
	 */
	private static function verification_code( $raw ) {
		if ( preg_match( '/content=["\']([^"\']+)["\']/i', $raw, $m ) ) {
			$raw = $m[1];
		}
		return preg_replace( '/[^A-Za-z0-9_\-=.+\/]/', '', trim( $raw ) );
	}
}
