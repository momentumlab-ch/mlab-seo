<?php
/**
 * IndexNow: tells Bing, Yandex, Seznam, Naver and Yep about new and changed pages right away.
 *
 * Bing's index also feeds ChatGPT search and Copilot, so changes reach them faster.
 *
 * @package MomentumLab\SEO
 */

namespace MomentumLab\SEO;

defined( 'ABSPATH' ) || exit;

/**
 * Serves the key file and submits changed URLs once per request.
 */
final class IndexNow {

	/**
	 * Endpoint shared by all IndexNow search engines.
	 */
	const ENDPOINT = 'https://api.indexnow.org/indexnow';

	/**
	 * Option with the last submission (time and URLs).
	 */
	const LAST = 'mlab_seo_indexnow_last';

	/**
	 * URLs collected during this request (post ID => URL).
	 *
	 * @var array<int,string>
	 */
	private $queue = array();

	/**
	 * Registers hooks.
	 */
	public function hooks() {
		add_action( 'parse_request', array( $this, 'serve_key' ), 0 );
		add_action( 'transition_post_status', array( $this, 'collect' ), 10, 3 );
		add_action( 'shutdown', array( $this, 'submit' ) );
	}

	/**
	 * Whether submissions are on: enabled, key present, site public and in production.
	 *
	 * @return bool
	 */
	public static function active() {
		return Settings::get( 'indexnow' )
			&& '' !== (string) Settings::get( 'indexnow_key' )
			&& get_option( 'blog_public' )
			&& 'production' === wp_get_environment_type();
	}

	/**
	 * URL of the key file.
	 *
	 * @return string
	 */
	public static function key_url() {
		return home_url( '/' . Settings::get( 'indexnow_key' ) . '.txt' );
	}

	/**
	 * Serves /{key}.txt so the search engines can verify the site.
	 */
	public function serve_key() {
		$key = (string) Settings::get( 'indexnow_key' );
		if ( ! Settings::get( 'indexnow' ) || '' === $key ) {
			return;
		}
		$uri  = isset( $_SERVER['REQUEST_URI'] ) ? esc_url_raw( wp_unslash( $_SERVER['REQUEST_URI'] ) ) : '';
		$path = (string) wp_parse_url( $uri, PHP_URL_PATH );
		if ( (string) wp_parse_url( self::key_url(), PHP_URL_PATH ) !== $path ) {
			return;
		}
		status_header( 200 );
		header( 'Content-Type: text/plain; charset=utf-8' );
		header( 'X-Robots-Tag: noindex' );
		echo esc_html( $key );
		exit;
	}

	/**
	 * Collects the URL of a post that was published, updated or unpublished.
	 *
	 * @param string   $new_status New status.
	 * @param string   $old_status Old status.
	 * @param \WP_Post $post       Post.
	 */
	public function collect( $new_status, $old_status, $post ) {
		if ( 'publish' !== $new_status && 'publish' !== $old_status ) {
			return;
		}
		if ( wp_is_post_revision( $post ) || wp_is_post_autosave( $post ) || ! empty( $post->post_password ) ) {
			return;
		}
		if ( ! in_array( $post->post_type, Sitemap::post_types(), true ) || ! self::active() ) {
			return;
		}
		$url = (string) get_permalink( $post );
		if ( '' !== $url && ! str_contains( $url, '?' ) ) {
			$this->queue[ $post->ID ] = $url;
		}
	}

	/**
	 * Sends the collected URLs in one request at the end of the page load.
	 *
	 * The block editor saves a post twice (content, then the SEO box), so a URL
	 * submitted in the last minutes is skipped.
	 */
	public function submit() {
		if ( ! $this->queue ) {
			return;
		}
		$urls        = $this->collect_urls();
		$this->queue = array();
		if ( ! $urls ) {
			return;
		}
		$this->send( $urls );
		update_option(
			self::LAST,
			array(
				'time' => time(),
				'urls' => $urls,
			),
			false
		);
	}

	/**
	 * Posts the URL list to the IndexNow endpoint without waiting for the answer.
	 *
	 * @param string[] $urls URLs to announce.
	 * @return void
	 */
	private function send( $urls ) {
		wp_remote_post(
			self::ENDPOINT,
			array(
				'timeout'  => 3,
				'blocking' => false,
				'headers'  => array( 'Content-Type' => 'application/json; charset=utf-8' ),
				'body'     => wp_json_encode(
					array(
						'host'        => (string) wp_parse_url( home_url( '/' ), PHP_URL_HOST ),
						'key'         => (string) Settings::get( 'indexnow_key' ),
						'keyLocation' => self::key_url(),
						'urlList'     => array_values( array_unique( $urls ) ),
					)
				),
			)
		);
	}

	/**
	 * Picks the queued URLs that may be announced now and locks them.
	 *
	 * @return string[] URLs to submit.
	 */
	private function collect_urls() {
		$urls = array();
		foreach ( $this->queue as $post_id => $url ) {
			// Published pages hidden from search engines are not announced.
			if ( 'publish' === get_post_status( $post_id ) && Post_Meta::get( $post_id, 'noindex' ) ) {
				continue;
			}
			$lock = 'mlab_seo_indexnow_' . md5( $url );
			if ( ! get_transient( $lock ) ) {
				set_transient( $lock, 1, 5 * MINUTE_IN_SECONDS );
				$urls[] = $url;
			}
		}
		return $urls;
	}
}
