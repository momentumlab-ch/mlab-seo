<?php
/**
 * Connection to an AI provider (Claude or OpenAI) with the customer's own key.
 *
 * Nothing is sent without a key in wp-config.php, the written consent of an administrator and a monthly limit.
 * The class only talks to the provider. What happens with the answer is decided in AI_Suggestions.
 *
 * @package MomentumLab\SEO
 */

namespace MomentumLab\SEO;

defined( 'ABSPATH' ) || exit;

/**
 * Provider settings, limit, log and the HTTP request.
 */
final class AI {

	/**
	 * Option with the characters sent per month (UTC): array( 'Y-m' => int ).
	 */
	const USAGE = 'mlab_seo_ai_usage';

	/**
	 * Option with the last requests: time, provider, kind, characters and result. No texts.
	 */
	const LOG = 'mlab_seo_ai_log';

	/**
	 * Default models. Both are settings, because model names change. Not checked against the provider's current list.
	 */
	const MODELS = array(
		'claude' => 'claude-haiku-5-5',
		'openai' => 'gpt-4o-mini',
	);

	/**
	 * Providers with label and the constant in wp-config.php that holds the key.
	 *
	 * @return array<string,array{label:string,constant:string}>
	 */
	public static function providers() {
		return array(
			'claude' => array(
				'label'    => 'Claude (Anthropic)',
				'constant' => 'MLAB_SEO_ANTHROPIC_KEY',
			),
			'openai' => array(
				'label'    => 'GPT (OpenAI)',
				'constant' => 'MLAB_SEO_OPENAI_KEY',
			),
		);
	}

	/**
	 * Chosen provider, or an empty string when off.
	 *
	 * @return string
	 */
	public static function provider() {
		$provider = (string) Settings::get( 'ai_provider' );
		return isset( self::providers()[ $provider ] ) ? $provider : '';
	}

	/**
	 * Key of the chosen provider from wp-config.php.
	 *
	 * @return string
	 */
	private static function key() {
		$constant = self::providers()[ self::provider() ]['constant'] ?? '';
		return '' !== $constant && defined( $constant ) ? (string) constant( $constant ) : '';
	}

	/**
	 * Whether the key of the chosen provider exists.
	 *
	 * @return bool
	 */
	public static function has_key() {
		return '' !== self::key();
	}

	/**
	 * Model name: the setting, or the default of the provider.
	 *
	 * @return string
	 */
	public static function model() {
		$model = trim( (string) Settings::get( 'ai_model' ) );
		return '' !== $model ? $model : ( self::MODELS[ self::provider() ] ?? '' );
	}

	/**
	 * Whether AI writing can be used: Pro, a provider, its key and the confirmation.
	 *
	 * @return bool
	 */
	public static function enabled() {
		return License::is_pro() && '' !== self::provider() && self::has_key() && (bool) Settings::get( 'ai_consent' );
	}

	/**
	 * Monthly limit in characters sent to the provider.
	 *
	 * @return int
	 */
	public static function limit() {
		return max( 0, (int) Settings::get( 'ai_limit' ) );
	}

	/**
	 * Characters sent this month.
	 *
	 * @return int
	 */
	public static function used() {
		$usage = get_option( self::USAGE, array() );
		return (int) ( is_array( $usage ) ? ( $usage[ gmdate( 'Y-m' ) ] ?? 0 ) : 0 );
	}

	/**
	 * Sends one request and returns the answer text, or an error with a message that says what to do.
	 *
	 * @param string $kind   write or translate, for the log.
	 * @param string $system Instructions.
	 * @param string $user   Data.
	 * @return string|\WP_Error
	 */
	public static function ask( $kind, $system, $user ) {
		if ( ! self::enabled() ) {
			return new \WP_Error( 'mlab_seo_ai_off', __( 'AI writing is not set up. An administrator can do this under Settings › AI writing.', 'mlab-seo' ), array( 'status' => 403 ) );
		}
		$chars = strlen( $system ) + strlen( $user );
		if ( self::used() + $chars > self::limit() ) {
			return new \WP_Error( 'mlab_seo_ai_limit', __( 'The monthly limit for AI writing is reached. An administrator can raise it under Settings › AI writing.', 'mlab-seo' ), array( 'status' => 429 ) );
		}
		self::count( $chars );
		$response = wp_remote_post( ...self::request( $system, $user ) );
		$result   = self::answer( $response );
		self::log( $kind, $chars, is_wp_error( $result ) ? $result->get_error_code() : 'ok' );
		return $result;
	}

	/**
	 * URL and arguments of the request for the chosen provider.
	 *
	 * @param string $system Instructions.
	 * @param string $user   Data.
	 * @return array{0:string,1:array}
	 */
	private static function request( $system, $user ) {
		$model = self::model();
		if ( 'claude' === self::provider() ) {
			$body = array(
				'model'      => $model,
				'max_tokens' => 2000,
				'system'     => $system,
				'messages'   => array(
					array(
						'role'    => 'user',
						'content' => $user,
					),
				),
			);
			if ( preg_match( '/^claude-(haiku|sonnet|opus|fable|mythos)-5/', $model ) ) {
				$body['output_config'] = array( 'effort' => 'low' ); // Short answers: little thinking is needed.
			}
			return array(
				'https://api.anthropic.com/v1/messages',
				array(
					'timeout' => 60,
					'headers' => array(
						'x-api-key'         => self::key(),
						'anthropic-version' => '2023-06-01',
						'content-type'      => 'application/json',
					),
					'body'    => wp_json_encode( $body ),
				),
			);
		}
		return array(
			'https://api.openai.com/v1/chat/completions',
			array(
				'timeout' => 60,
				'headers' => array(
					'Authorization' => 'Bearer ' . self::key(),
					'content-type'  => 'application/json',
				),
				'body'    => wp_json_encode(
					array(
						'model'           => $model,
						'response_format' => array( 'type' => 'json_object' ),
						'messages'        => array(
							array(
								'role'    => 'system',
								'content' => $system,
							),
							array(
								'role'    => 'user',
								'content' => $user,
							),
						),
					)
				),
			),
		);
	}

	/**
	 * Answer text of a response, or an error.
	 *
	 * @param array|\WP_Error $response HTTP response.
	 * @return string|\WP_Error
	 */
	private static function answer( $response ) {
		if ( is_wp_error( $response ) ) {
			return new \WP_Error( 'mlab_seo_ai_network', __( 'The AI provider could not be reached. Please try again in a moment.', 'mlab-seo' ), array( 'status' => 502 ) );
		}
		$code = (int) wp_remote_retrieve_response_code( $response );
		$data = json_decode( wp_remote_retrieve_body( $response ), true );
		if ( $code >= 400 || ! is_array( $data ) ) {
			return self::http_error( $code );
		}
		if ( 'claude' === self::provider() ) {
			if ( 'refusal' === ( $data['stop_reason'] ?? '' ) ) {
				return new \WP_Error( 'mlab_seo_ai_refused', __( 'The AI provider declined this page. Write the texts yourself or try another page.', 'mlab-seo' ), array( 'status' => 422 ) );
			}
			$texts = array_column( array_filter( (array) ( $data['content'] ?? array() ), fn ( $block ) => 'text' === ( $block['type'] ?? '' ) ), 'text' );
			$text  = implode( "\n", $texts );
		} else {
			$text = (string) ( $data['choices'][0]['message']['content'] ?? '' );
		}
		return '' !== trim( $text ) ? $text : new \WP_Error( 'mlab_seo_ai_empty', __( 'The AI provider returned no text. Please try again.', 'mlab-seo' ), array( 'status' => 502 ) );
	}

	/**
	 * Error for an HTTP status, with a message that says what to do.
	 *
	 * @param int $code HTTP status.
	 * @return \WP_Error
	 */
	private static function http_error( $code ) {
		if ( 401 === $code || 403 === $code ) {
			$message = __( 'The AI provider did not accept the key. An administrator should check the key in wp-config.php.', 'mlab-seo' );
		} elseif ( 429 === $code ) {
			$message = __( 'The AI provider asks to slow down. Please try again in a minute.', 'mlab-seo' );
		} elseif ( 404 === $code || 400 === $code ) {
			$message = __( 'The AI provider did not accept the request. Check the model name under Settings › AI writing.', 'mlab-seo' );
		} else {
			$message = __( 'The AI provider has a problem right now. Please try again later.', 'mlab-seo' );
		}
		return new \WP_Error( 'mlab_seo_ai_http_' . $code, $message, array( 'status' => 502 ) );
	}

	/**
	 * Adds characters to the count of this month.
	 *
	 * @param int $chars Characters.
	 */
	private static function count( $chars ) {
		update_option( self::USAGE, array( gmdate( 'Y-m' ) => self::used() + $chars ), false );
	}

	/**
	 * Keeps the last 100 requests: when, who, what kind, how many characters, result. No texts and no keys.
	 *
	 * @param string $kind   write or translate.
	 * @param int    $chars  Characters sent.
	 * @param string $result ok or error code.
	 */
	private static function log( $kind, $chars, $result ) {
		$log   = (array) get_option( self::LOG, array() );
		$log[] = array(
			'time'     => time(),
			'user'     => get_current_user_id(),
			'provider' => self::provider(),
			'kind'     => $kind,
			'chars'    => $chars,
			'result'   => $result,
		);
		update_option( self::LOG, array_slice( $log, -100 ), false );
	}

	/**
	 * Extracts the JSON object from an answer, also when it comes in a code block.
	 *
	 * @param string $text Answer text.
	 * @return array
	 */
	public static function json( $text ) {
		$start = strpos( $text, '{' );
		$end   = strrpos( $text, '}' );
		$data  = false !== $start && false !== $end ? json_decode( substr( $text, $start, $end - $start + 1 ), true ) : null;
		return is_array( $data ) ? $data : array();
	}
}
