<?php
/**
 * Crawlers and AI: robots.txt, llms.txt, llms-full.txt and the access check.
 *
 * @package MomentumLab\SEO
 */

namespace MomentumLab\SEO;

defined( 'ABSPATH' ) || exit;

/**
 * Makes the site readable for search engines and AI assistants.
 */
final class Crawlers {

	/**
	 * Transient caching llms-full.txt.
	 */
	const FULL_CACHE = 'mlab_seo_llms_full';

	/**
	 * Registers hooks.
	 */
	public function hooks() {
		add_filter( 'robots_txt', array( $this, 'robots_txt' ), 20, 2 );
		add_action( 'parse_request', array( $this, 'serve' ), 0 );
		add_action( 'save_post', array( self::class, 'flush_cache' ) );
		add_action( 'update_option_' . Settings::OPTION, array( self::class, 'flush_cache' ) );
	}

	/**
	 * URL of llms.txt.
	 *
	 * @return string
	 */
	public static function llms_url() {
		return home_url( '/llms.txt' );
	}

	/**
	 * Clears the llms-full.txt cache.
	 */
	public static function flush_cache() {
		delete_transient( self::FULL_CACHE );
	}

	/**
	 * Builds robots.txt with explicit rules for AI crawlers.
	 *
	 * Only effective when WordPress runs at the root of its domain.
	 *
	 * @param string $output Core output.
	 * @param bool   $is_public Whether the site is public.
	 * @return string
	 */
	public function robots_txt( $output, $is_public ) {
		if ( ! $is_public ) {
			return $output;
		}
		$path = (string) wp_parse_url( home_url( '/' ), PHP_URL_PATH );
		$out  = "User-agent: *\nDisallow: {$path}wp-admin/\nAllow: {$path}wp-admin/admin-ajax.php\nDisallow: {$path}?s=\n";

		$allowed = array();
		$blocked = array();
		foreach ( (array) Settings::get( 'ai_bots' ) as $bot => $allow ) {
			if ( isset( Settings::AI_BOTS[ $bot ] ) ) {
				if ( $allow ) {
					$allowed[] = $bot;
				} else {
					$blocked[] = $bot;
				}
			}
		}
		if ( $allowed ) {
			$out .= "\n# AI crawlers: allowed\nUser-agent: " . implode( "\nUser-agent: ", $allowed ) . "\nAllow: /\n";
		}
		if ( $blocked ) {
			$out .= "\n# AI crawlers: blocked\nUser-agent: " . implode( "\nUser-agent: ", $blocked ) . "\nDisallow: /\n";
		}

		$extra = trim( (string) Settings::get( 'robots_extra' ) );
		if ( '' !== $extra ) {
			$out .= "\n" . $extra . "\n";
		}
		if ( get_option( 'blog_public' ) ) {
			$out .= "\nSitemap: " . home_url( '/wp-sitemap.xml' ) . "\n";
		}
		return $out;
	}

	/**
	 * Serves /llms.txt and /llms-full.txt.
	 */
	public function serve() {
		if ( ! Settings::get( 'llms_enabled' ) ) {
			return;
		}
		$uri  = isset( $_SERVER['REQUEST_URI'] ) ? esc_url_raw( wp_unslash( $_SERVER['REQUEST_URI'] ) ) : '';
		$path = trim( (string) wp_parse_url( $uri, PHP_URL_PATH ), '/' );
		$base = trim( (string) wp_parse_url( home_url( '/' ), PHP_URL_PATH ), '/' );
		$file = '' === $base ? $path : ( str_starts_with( $path, $base . '/' ) ? substr( $path, strlen( $base ) + 1 ) : '' );

		if ( 'llms.txt' === $file ) {
			$body = self::llms();
		} elseif ( 'llms-full.txt' === $file && Settings::get( 'llms_full' ) && License::is_pro() ) {
			$body = self::llms_full();
		} else {
			return;
		}
		status_header( 200 );
		header( 'Content-Type: text/plain; charset=utf-8' );
		header( 'X-Robots-Tag: noindex' );
		header( 'Cache-Control: public, max-age=3600' );
		echo $body; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Plain text file.
		exit;
	}

	/**
	 * Posts listed in llms.txt, grouped.
	 *
	 * @return array{main:\WP_Post[],optional:\WP_Post[]}
	 */
	public static function listed_posts() {
		$types   = Sitemap::post_types();
		$privacy = (int) get_option( 'wp_page_for_privacy_policy' );
		$groups  = array(
			'main'     => array(),
			'optional' => array(),
		);
		$posts   = get_posts(
			array(
				'post_type'      => $types ? $types : array( 'page' ),
				'post_status'    => 'publish',
				'posts_per_page' => 100,
				'orderby'        => array(
					'post_type'  => 'ASC',
					'menu_order' => 'ASC',
					'date'       => 'DESC',
				),
				'has_password'   => false,
			)
		);
		$front   = (int) get_option( 'page_on_front' );
		foreach ( $posts as $post ) {
			if ( Post_Meta::get( $post->ID, 'noindex' ) ) {
				continue;
			}
			$mode = Post_Meta::get( $post->ID, 'llms' );
			if ( 'hide' === $mode ) {
				continue;
			}
			$group              = 'optional' === $mode || $privacy === $post->ID ? 'optional' : 'main';
			$groups[ $group ][] = $post;
		}
		// Front page first.
		usort(
			$groups['main'],
			function ( $a, $b ) use ( $front ) {
				return (int) ( $b->ID === $front ) - (int) ( $a->ID === $front );
			}
		);
		return $groups;
	}

	/**
	 * Builds llms.txt (Markdown, see llmstxt.org).
	 *
	 * @return string
	 */
	public static function llms() {
		$summary = trim( (string) Settings::get( 'llms_summary' ) );
		if ( '' === $summary ) {
			$summary = (string) Settings::get( 'home_description' );
		}
		if ( '' === $summary ) {
			$summary = wp_strip_all_tags( get_bloginfo( 'description' ) );
		}

		$t = '# ' . Settings::site_name() . "\n\n";
		if ( '' !== $summary ) {
			$t .= '> ' . preg_replace( '/\s*\R\s*/', ' ', $summary ) . "\n\n";
		}

		$facts = Settings::lines( 'llms_facts' );
		if ( $facts ) {
			$t .= '## ' . __( 'Key facts', 'mlab-seo' ) . "\n\n";
			foreach ( $facts as $fact ) {
				$t .= '- ' . ltrim( $fact, '-* ' ) . "\n";
			}
			$t .= "\n";
		}

		$groups = self::listed_posts();
		if ( $groups['main'] ) {
			$t .= '## ' . __( 'Pages', 'mlab-seo' ) . "\n\n";
			foreach ( $groups['main'] as $post ) {
				$t .= self::llms_line( $post );
			}
			$t .= "\n";
		}

		$t .= '## ' . __( 'Optional', 'mlab-seo' ) . "\n\n";
		foreach ( $groups['optional'] as $post ) {
			$t .= self::llms_line( $post );
		}
		if ( Settings::get( 'llms_full' ) && License::is_pro() ) {
			$t .= '- [llms-full.txt](' . home_url( '/llms-full.txt' ) . '): ' . __( 'full text of all pages in one file', 'mlab-seo' ) . "\n";
		}
		$t .= '- [' . __( 'Sitemap', 'mlab-seo' ) . '](' . home_url( '/wp-sitemap.xml' ) . '): ' . __( 'all pages as an XML sitemap', 'mlab-seo' ) . "\n";
		foreach ( Settings::lines( 'org_same_as' ) as $url ) {
			$host = (string) wp_parse_url( $url, PHP_URL_HOST );
			$t   .= '- [' . preg_replace( '/^www\./', '', $host ) . '](' . $url . ")\n";
		}

		/**
		 * Filters the llms.txt content.
		 *
		 * @param string $t Markdown.
		 */
		return (string) apply_filters( 'mlab_seo_llms_txt', $t );
	}

	/**
	 * One list line: [Title](url): description.
	 *
	 * @param \WP_Post $post Post.
	 * @return string
	 */
	private static function llms_line( $post ) {
		$seo  = Context::for_post( $post->ID );
		$name = Context::plain_title( $post );
		return '- [' . $name . '](' . $seo['url'] . ')' . ( '' !== $seo['description'] ? ': ' . $seo['description'] : '' ) . "\n";
	}

	/**
	 * Builds llms-full.txt: the text of every listed page as Markdown (Pro).
	 *
	 * @return string
	 */
	public static function llms_full() {
		$cached = get_transient( self::FULL_CACHE );
		if ( is_string( $cached ) ) {
			return $cached;
		}
		$groups = self::listed_posts();
		$t      = '# ' . Settings::site_name() . "\n\n";
		foreach ( array_merge( $groups['main'], $groups['optional'] ) as $post ) {
			$seo = Context::for_post( $post->ID );
			$t  .= "\n---\n\n# " . Context::plain_title( $post ) . "\n\n";
			$t  .= 'URL: ' . $seo['url'] . "\n";
			if ( '' !== $seo['description'] ) {
				$t .= '> ' . $seo['description'] . "\n";
			}
			$t .= "\n" . self::to_markdown( do_blocks( $post->post_content ) ) . "\n";
		}
		set_transient( self::FULL_CACHE, $t, 12 * HOUR_IN_SECONDS );
		return $t;
	}

	/**
	 * Small HTML to Markdown conversion for readable plain text.
	 *
	 * @param string $html HTML.
	 * @return string
	 */
	public static function to_markdown( $html ) {
		$html = self::remove_noise( strip_shortcodes( $html ) );
		$html = preg_replace( '#<(script|style|form|svg|noscript)\b.*?</\1>#is', '', $html );
		$html = preg_replace( '#<br\s*/?>#i', ' ', $html );
		$html = preg_replace( '#</(span|small|time|div|p|li|td|th|h[1-6])>#i', '$0 ', $html );
		$html = preg_replace_callback(
			'#<h([1-6])[^>]*>(.*?)</h\1>#is',
			function ( $m ) {
				$text = trim( preg_replace( '/\s+/', ' ', wp_strip_all_tags( preg_replace( '#<[^>]+>#', ' ', $m[2] ) ) ) );
				return "\n\n" . str_repeat( '#', min( 6, (int) $m[1] + 1 ) ) . ' ' . $text . "\n\n";
			},
			$html
		);
		$html = preg_replace_callback(
			'#<a\s[^>]*href=("|\')(.*?)\1[^>]*>(.*?)</a>#is',
			function ( $m ) {
				$text = trim( wp_strip_all_tags( $m[3] ) );
				return '' === $text ? '' : '[' . $text . '](' . html_entity_decode( $m[2] ) . ')';
			},
			$html
		);
		$html = preg_replace( '#<li[^>]*>#i', "\n- ", $html );
		$html = preg_replace( '#<(td|th)[^>]*>#i', ' | ', $html );
		$html = preg_replace( '#<(/p|/div|/tr|/ul|/ol|/figure|/blockquote|/section)\s*>#i', "\n", $html );
		$text = html_entity_decode( wp_strip_all_tags( $html ), ENT_QUOTES | ENT_HTML5, 'UTF-8' );
		$text = preg_replace( '/[ \t]+/', ' ', $text );
		$text = preg_replace( '/ *\n */', "\n", $text );
		return trim( preg_replace( "/\n{3,}/", "\n\n", $text ) );
	}

	/**
	 * Removes live widgets (timers), hidden and decorative elements before the text export.
	 *
	 * @param string $html HTML.
	 * @return string
	 */
	private static function remove_noise( $html ) {
		if ( '' === trim( $html ) || ! class_exists( '\DOMDocument' ) ) {
			return $html;
		}
		$doc      = new \DOMDocument();
		$previous = libxml_use_internal_errors( true );
		$doc->loadHTML( '<?xml encoding="utf-8"?><div id="mlab-seo-root">' . $html . '</div>', LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD );
		libxml_clear_errors();
		libxml_use_internal_errors( $previous );

		$xpath = new \DOMXPath( $doc );
		$nodes = $xpath->query( '//*[@role="timer" or @aria-hidden="true" or @hidden or self::script or self::style or self::form or self::svg or self::noscript or self::template]' );
		foreach ( iterator_to_array( $nodes ) as $node ) {
			$node->parentNode->removeChild( $node ); // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
		}
		$root = $doc->getElementById( 'mlab-seo-root' );
		if ( ! $root ) {
			return $html;
		}
		$out = '';
		foreach ( $root->childNodes as $child ) { // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
			$out .= $doc->saveHTML( $child );
		}
		return $out;
	}

	/**
	 * Requests the home page with the user agents of major crawlers.
	 *
	 * Shows whether a firewall or bot protection on the server blocks them.
	 *
	 * @return array<string,int|string> User agent => HTTP status or error.
	 */
	public static function access_check() {
		$agents  = array(
			'Googlebot'     => 'Mozilla/5.0 (compatible; Googlebot/2.1; +http://www.google.com/bot.html)',
			'Bingbot'       => 'Mozilla/5.0 (compatible; bingbot/2.0; +http://www.bing.com/bingbot.htm)',
			'GPTBot'        => 'Mozilla/5.0 AppleWebKit/537.36 (KHTML, like Gecko; compatible; GPTBot/1.2; +https://openai.com/gptbot)',
			'OAI-SearchBot' => 'Mozilla/5.0 AppleWebKit/537.36 (KHTML, like Gecko); compatible; OAI-SearchBot/1.0; +https://openai.com/searchbot',
			'ClaudeBot'     => 'Mozilla/5.0 AppleWebKit/537.36 (KHTML, like Gecko; compatible; ClaudeBot/1.0; +claudebot@anthropic.com)',
			'PerplexityBot' => 'Mozilla/5.0 AppleWebKit/537.36 (KHTML, like Gecko; compatible; PerplexityBot/1.0; +https://perplexity.ai/perplexitybot)',
		);
		$results = array();
		foreach ( $agents as $name => $agent ) {
			$response         = wp_remote_get(
				home_url( '/' ),
				array(
					'timeout'     => 8,
					'redirection' => 2,
					'user-agent'  => $agent,
				)
			);
			$results[ $name ] = is_wp_error( $response ) ? $response->get_error_message() : (int) wp_remote_retrieve_response_code( $response );
		}
		return $results;
	}
}
