<?php
/**
 * Output in <head>: title, description, robots, canonical, Open Graph, X/Twitter.
 *
 * @package MomentumLab\SEO
 */

namespace MomentumLab\SEO;

defined( 'ABSPATH' ) || exit;

/**
 * Front-end head tags.
 */
final class Head {

	/**
	 * Registers hooks.
	 */
	public function hooks() {
		add_filter( 'pre_get_document_title', array( $this, 'title' ), 20 );
		add_filter( 'wp_robots', array( $this, 'robots' ) );
		add_filter( 'get_canonical_url', array( $this, 'canonical' ), 10, 2 );
		add_action( 'wp_head', array( $this, 'tags' ), 2 );
		add_action( 'init', array( $this, 'cleanup' ) );
	}

	/**
	 * Removes the WordPress version from the source code.
	 */
	public function cleanup() {
		if ( Settings::get( 'remove_generator' ) ) {
			remove_action( 'wp_head', 'wp_generator' );
		}
	}

	/**
	 * Document title.
	 *
	 * @param string $title Title from core or other plugins.
	 * @return string
	 */
	public function title( $title ) {
		$seo = Context::current();
		return '' !== $seo['title'] ? $seo['title'] : $title;
	}

	/**
	 * Robots directives (core prints the meta tag).
	 *
	 * @param array $robots Directives.
	 * @return array
	 */
	public function robots( $robots ) {
		if ( is_admin() ) {
			return $robots;
		}
		$seo = Context::current();
		if ( $seo['noindex'] ) {
			$robots['noindex'] = true;
			unset( $robots['max-image-preview'] );
		} elseif ( get_option( 'blog_public' ) ) {
			$robots['max-image-preview'] = 'large';
			$robots['max-snippet']       = '-1';
			$robots['max-video-preview'] = '-1';
		}
		if ( $seo['nofollow'] ) {
			$robots['nofollow'] = true;
		}
		foreach ( (array) ( $seo['robots'] ?? array() ) as $directive ) {
			$robots[ $directive ] = true;
		}
		if ( ! empty( $robots['nosnippet'] ) ) {
			unset( $robots['max-snippet'] );
		}
		return $robots;
	}

	/**
	 * Custom canonical URL for posts.
	 *
	 * @param string   $url  Canonical URL from core.
	 * @param \WP_Post $post Post.
	 * @return string
	 */
	public function canonical( $url, $post ) {
		$custom = Post_Meta::get( $post->ID, 'canonical' );
		return '' !== $custom ? $custom : $url;
	}

	/**
	 * Meta tags.
	 */
	public function tags() {
		$seo = Context::current();
		if ( '' !== $seo['url'] && ! is_singular() ) {
			// Core prints rel=canonical only for singular views.
			printf( '<link rel="canonical" href="%s" />' . "\n", esc_url( $seo['url'] ) );
		}

		/**
		 * Filters the meta tags before output.
		 *
		 * @param array $tags List of [attribute, name, content].
		 * @param array $seo  SEO data of the current request.
		 */
		$tags = (array) apply_filters( 'mlab_seo_meta_tags', $this->meta_tags( $seo ), $seo );

		echo "\n<!-- MomentumLab SEO " . esc_html( MLAB_SEO_VERSION ) . " -->\n";
		foreach ( $tags as $tag ) {
			printf( '<meta %s="%s" content="%s" />' . "\n", esc_attr( $tag[0] ), esc_attr( $tag[1] ), esc_attr( $tag[2] ) );
		}
		if ( Settings::get( 'llms_enabled' ) ) {
			printf( '<link rel="alternate" type="text/markdown" title="llms.txt" href="%s" />' . "\n", esc_url( Crawlers::llms_url() ) );
		}
		Schema::print_graph( $seo );
		echo "<!-- / MomentumLab SEO -->\n";
	}

	/**
	 * All meta tags as [attribute, name, content].
	 *
	 * @param array $seo SEO data.
	 * @return array
	 */
	private function meta_tags( array $seo ) {
		$tags = array();
		if ( '' !== $seo['description'] ) {
			$tags[] = array( 'name', 'description', $seo['description'] );
		}
		$title    = '' !== $seo['title'] ? $seo['title'] : wp_get_document_title();
		$og_title = '' !== ( $seo['og_title'] ?? '' ) ? $seo['og_title'] : $title;
		$og_desc  = '' !== ( $seo['og_desc'] ?? '' ) ? $seo['og_desc'] : $seo['description'];
		$img      = Context::image( $seo['image'] );

		$tags = array_merge( $tags, $this->open_graph( $seo, $og_title, $og_desc ), $this->twitter( $og_title, $og_desc, $img ) );
		$tags = array_merge( $tags, $this->image_tags( $img ) );
		if ( is_front_page() ) {
			foreach ( Settings::VERIFY as $key => $name ) {
				$code = (string) Settings::get( $key );
				if ( '' !== $code ) {
					$tags[] = array( 'name', $name, $code );
				}
			}
		}
		return $tags;
	}

	/**
	 * Open Graph tags without the image.
	 *
	 * @param array  $seo      SEO data.
	 * @param string $og_title Social title.
	 * @param string $og_desc  Social description.
	 * @return array
	 */
	private function open_graph( array $seo, $og_title, $og_desc ) {
		$tags = array(
			array( 'property', 'og:type', $seo['og_type'] ),
			array( 'property', 'og:site_name', Settings::site_name() ),
			array( 'property', 'og:locale', Settings::locale() ),
			array( 'property', 'og:title', $og_title ),
		);
		if ( '' !== $og_desc ) {
			$tags[] = array( 'property', 'og:description', $og_desc );
		}
		if ( '' !== $seo['url'] ) {
			$tags[] = array( 'property', 'og:url', $seo['url'] );
		}
		if ( 'article' === $seo['og_type'] && $seo['post_id'] ) {
			$tags[] = array( 'property', 'article:published_time', get_post_time( 'c', true, $seo['post_id'] ) );
			$tags[] = array( 'property', 'article:modified_time', get_post_modified_time( 'c', true, $seo['post_id'] ) );
		}
		return $tags;
	}

	/**
	 * X (Twitter) card tags without the image.
	 *
	 * @param string     $og_title Social title.
	 * @param string     $og_desc  Social description.
	 * @param array|null $img      Social image.
	 * @return array
	 */
	private function twitter( $og_title, $og_desc, $img ) {
		$tags = array(
			array( 'name', 'twitter:card', $img ? 'summary_large_image' : 'summary' ),
			array( 'name', 'twitter:title', $og_title ),
		);
		if ( '' !== $og_desc ) {
			$tags[] = array( 'name', 'twitter:description', $og_desc );
		}
		$handle = ltrim( (string) Settings::get( 'twitter_site' ), '@' );
		if ( '' !== $handle ) {
			$tags[] = array( 'name', 'twitter:site', '@' . $handle );
		}
		return $tags;
	}

	/**
	 * Image tags for Open Graph and X.
	 *
	 * @param array|null $img Social image.
	 * @return array
	 */
	private function image_tags( $img ) {
		if ( ! $img ) {
			return array();
		}
		$tags = array(
			array( 'property', 'og:image', $img['url'] ),
			array( 'property', 'og:image:width', (string) $img['width'] ),
			array( 'property', 'og:image:height', (string) $img['height'] ),
			array( 'name', 'twitter:image', $img['url'] ),
		);
		if ( '' !== $img['alt'] ) {
			$tags[] = array( 'property', 'og:image:alt', $img['alt'] );
			$tags[] = array( 'name', 'twitter:image:alt', $img['alt'] );
		}
		return $tags;
	}
}
