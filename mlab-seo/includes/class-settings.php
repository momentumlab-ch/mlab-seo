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
	 * Default values.
	 *
	 * @return array
	 */
	public static function defaults() {
		return array(
			// General.
			'separator'        => '–',
			'site_name'        => '',
			'home_title'       => '',
			'home_description' => '',
			'default_image'    => 0,
			'og_locale'        => '',
			'twitter_site'     => '',
			'remove_generator' => 1,
			'attachment_pages' => 0,
			'verify_google'    => '',
			'verify_bing'      => '',
			'verify_yandex'    => '',
			'verify_pinterest' => '',
			// Publisher.
			'org_type'         => 'Organization',
			'org_name'         => '',
			'org_alt_name'     => '',
			'org_logo'         => 0,
			'org_email'        => '',
			'org_phone'        => '',
			'org_street'       => '',
			'org_zip'          => '',
			'org_city'         => '',
			'org_region'       => '',
			'org_country'      => '',
			'org_same_as'      => '',
			'org_extra'        => '',
			'org_hours'        => '',
			'org_lat'          => '',
			'org_lng'          => '',
			'org_price_range'  => '',
			// AI and crawlers.
			'ai_bots'          => array_fill_keys( array_keys( self::AI_BOTS ), 1 ),
			'llms_enabled'     => 1,
			'llms_summary'     => '',
			'llms_facts'       => '',
			'llms_full'        => 0,
			'robots_extra'     => '',
			'indexnow'         => 0,
			'indexnow_key'     => '',
			// Redirects.
			'auto_redirect'    => 1,
			// Sitemap.
			'sitemap_users'    => 0,
			'sitemap_taxonomy' => 0,
			'sitemap_types'    => array( 'page' ),
		);
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
		$d   = self::defaults();
		$out = array();

		foreach ( array( 'separator', 'site_name', 'home_title', 'og_locale', 'twitter_site', 'org_name', 'org_alt_name', 'org_phone', 'org_street', 'org_zip', 'org_city', 'org_region', 'org_country', 'org_price_range' ) as $k ) {
			$out[ $k ] = isset( $in[ $k ] ) ? sanitize_text_field( wp_unslash( $in[ $k ] ) ) : $d[ $k ];
		}
		foreach ( array( 'home_description', 'llms_summary', 'llms_facts', 'org_same_as', 'robots_extra', 'org_hours' ) as $k ) {
			$out[ $k ] = isset( $in[ $k ] ) ? sanitize_textarea_field( wp_unslash( $in[ $k ] ) ) : $d[ $k ];
		}
		foreach ( array( 'default_image', 'org_logo' ) as $k ) {
			$out[ $k ] = isset( $in[ $k ] ) ? absint( $in[ $k ] ) : 0;
		}
		foreach ( array( 'remove_generator', 'llms_enabled', 'llms_full', 'sitemap_users', 'sitemap_taxonomy', 'attachment_pages', 'indexnow' ) as $k ) {
			$out[ $k ] = empty( $in[ $k ] ) ? 0 : 1;
		}

		$out['separator']   = '' !== $out['separator'] ? mb_substr( $out['separator'], 0, 3 ) : '–';
		$out['org_email']   = isset( $in['org_email'] ) ? sanitize_email( wp_unslash( $in['org_email'] ) ) : '';
		$out['org_type']    = isset( $in['org_type'] ) && in_array( $in['org_type'], self::ORG_TYPES, true ) ? $in['org_type'] : 'Organization';
		$out['org_country'] = strtoupper( mb_substr( $out['org_country'], 0, 2 ) );
		$out['og_locale']   = preg_match( '/^[a-z]{2}_[A-Z]{2}$/', $out['og_locale'] ) ? $out['og_locale'] : '';

		// Redirect setting is saved with the redirect rules; keep it otherwise.
		$out['auto_redirect'] = array_key_exists( 'auto_redirect', $in ) ? ( empty( $in['auto_redirect'] ) ? 0 : 1 ) : (int) ( self::get( 'auto_redirect' ) ?? 1 );

		foreach ( array( 'org_lat', 'org_lng' ) as $k ) {
			$value     = isset( $in[ $k ] ) ? str_replace( ',', '.', sanitize_text_field( wp_unslash( $in[ $k ] ) ) ) : '';
			$out[ $k ] = is_numeric( $value ) && abs( (float) $value ) <= 180 ? $value : '';
		}

		foreach ( array_keys( self::VERIFY ) as $k ) {
			$out[ $k ] = isset( $in[ $k ] ) ? self::verification_code( wp_unslash( (string) $in[ $k ] ) ) : '';
		}

		// The IndexNow key is generated once and kept; it is not part of the form.
		$key                 = isset( $in['indexnow_key'] ) ? (string) $in['indexnow_key'] : (string) self::get( 'indexnow_key' );
		$out['indexnow_key'] = preg_match( '/^[a-f0-9]{32}$/', $key ) ? $key : '';
		if ( $out['indexnow'] && '' === $out['indexnow_key'] ) {
			$out['indexnow_key'] = bin2hex( random_bytes( 16 ) );
		}

		$same_as = array();
		foreach ( preg_split( '/\R/', $out['org_same_as'] ) as $url ) {
			$url = esc_url_raw( trim( $url ), array( 'https', 'http' ) );
			if ( $url ) {
				$same_as[] = $url;
			}
		}
		$out['org_same_as'] = implode( "\n", $same_as );

		$out['org_extra'] = '';
		if ( ! empty( $in['org_extra'] ) ) {
			$json = json_decode( wp_unslash( $in['org_extra'] ), true );
			if ( is_array( $json ) ) {
				$out['org_extra'] = wp_json_encode( $json, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT );
			}
		}

		$out['ai_bots'] = array();
		foreach ( array_keys( self::AI_BOTS ) as $bot ) {
			$out['ai_bots'][ $bot ] = empty( $in['ai_bots'][ $bot ] ) ? 0 : 1;
		}

		$types                = isset( $in['sitemap_types'] ) && is_array( $in['sitemap_types'] ) ? array_map( 'sanitize_key', $in['sitemap_types'] ) : array();
		$out['sitemap_types'] = array_values( array_intersect( $types, array_keys( get_post_types( array( 'public' => true ) ) ) ) );

		return $out;
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
