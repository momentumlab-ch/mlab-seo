<?php
/**
 * Images and videos: image sitemap, video sitemap and VideoObject schema.
 *
 * @package MomentumLab\SEO
 */

namespace MomentumLab\SEO;

defined( 'ABSPATH' ) || exit;

/**
 * Finds images and videos in posts and lists them for search engines.
 */
final class Media {

	/**
	 * Sitemap files (name => setting key).
	 */
	const SITEMAPS = array(
		'sitemap-images.xml' => 'sitemap_images',
		'sitemap-videos.xml' => 'sitemap_videos',
	);

	/**
	 * Transient prefix for the cached sitemaps.
	 */
	const CACHE = 'mlab_seo_media_';

	/**
	 * Maximum number of posts in one sitemap.
	 */
	const MAX_POSTS = 2000;

	/**
	 * Registers hooks.
	 */
	public function hooks() {
		add_action( 'parse_request', array( $this, 'serve' ), 0 );
	}

	/**
	 * URLs of the enabled media sitemaps.
	 *
	 * @return string[]
	 */
	public static function sitemap_urls() {
		$urls = array();
		foreach ( self::SITEMAPS as $file => $key ) {
			if ( Settings::get( $key ) ) {
				$urls[] = home_url( '/' . $file );
			}
		}
		return $urls;
	}

	/**
	 * Clears the cached sitemaps.
	 */
	public static function flush_cache() {
		foreach ( array_keys( self::SITEMAPS ) as $file ) {
			delete_transient( self::CACHE . md5( $file ) );
		}
	}

	/**
	 * Serves /sitemap-images.xml and /sitemap-videos.xml.
	 */
	public function serve() {
		$uri  = isset( $_SERVER['REQUEST_URI'] ) ? esc_url_raw( wp_unslash( $_SERVER['REQUEST_URI'] ) ) : '';
		$path = (string) wp_parse_url( $uri, PHP_URL_PATH );
		foreach ( self::SITEMAPS as $file => $key ) {
			if ( (string) wp_parse_url( home_url( '/' . $file ), PHP_URL_PATH ) !== $path ) {
				continue;
			}
			if ( ! Settings::get( $key ) || ! get_option( 'blog_public' ) ) {
				return;
			}
			$xml = get_transient( self::CACHE . md5( $file ) );
			if ( ! is_string( $xml ) ) {
				$xml = 'sitemap_images' === $key ? self::image_sitemap() : self::video_sitemap();
				set_transient( self::CACHE . md5( $file ), $xml, 12 * HOUR_IN_SECONDS );
			}
			status_header( 200 );
			header( 'Content-Type: application/xml; charset=utf-8' );
			header( 'X-Robots-Tag: noindex, follow' );
			echo $xml; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Built with esc_xml().
			exit;
		}
	}

	/**
	 * Indexable published posts of the sitemap content types.
	 *
	 * @return \WP_Post[]
	 */
	private static function posts() {
		$posts = get_posts(
			array(
				'post_type'      => Sitemap::post_types() ? Sitemap::post_types() : array( 'page' ),
				'post_status'    => 'publish',
				'posts_per_page' => self::MAX_POSTS,
				'has_password'   => false,
				'orderby'        => 'modified',
				'order'          => 'DESC',
			)
		);
		return array_values(
			array_filter(
				$posts,
				function ( $post ) {
					return ! Post_Meta::get( $post->ID, 'noindex' ) && '' === Post_Meta::get( $post->ID, 'canonical' );
				}
			)
		);
	}

	/**
	 * Image URLs of a post: social image, featured image and images in the content.
	 *
	 * Reads the saved content, not the rendered page, so shortcodes and
	 * dynamic blocks are not run.
	 *
	 * @param \WP_Post $post Post.
	 * @return string[]
	 */
	public static function images( $post ) {
		$urls = array();
		foreach ( array( Post_Meta::get( $post->ID, 'image' ), (int) get_post_thumbnail_id( $post ) ) as $id ) {
			$url = $id ? wp_get_attachment_image_url( $id, 'full' ) : false;
			if ( $url ) {
				$urls[] = $url;
			}
		}
		if ( preg_match_all( '#<img\s[^>]*?src=(["\'])(https?://[^"\']+)\1#i', $post->post_content, $m ) ) {
			foreach ( $m[2] as $url ) {
				$urls[] = html_entity_decode( $url, ENT_QUOTES | ENT_HTML5, 'UTF-8' );
			}
		}
		return array_slice( array_values( array_unique( $urls ) ), 0, 1000 );
	}

	/**
	 * Videos of a post: YouTube and Vimeo embeds and uploaded videos.
	 *
	 * Videos without a thumbnail are left out; for Vimeo and uploads the
	 * poster, the social or featured image or the default image is used.
	 *
	 * @param \WP_Post $post Post.
	 * @return array<int,array{embed:string,content:string,thumbnail:string}>
	 */
	public static function videos( $post ) {
		$html     = $post->post_content;
		$fallback = '';
		foreach ( array( Post_Meta::get( $post->ID, 'image' ), (int) get_post_thumbnail_id( $post ), (int) Settings::get( 'default_image' ) ) as $id ) {
			$url = $id ? wp_get_attachment_image_url( $id, 'full' ) : false;
			if ( $url ) {
				$fallback = $url;
				break;
			}
		}

		$videos = array();
		if ( preg_match_all( '#(?:youtube(?:-nocookie)?\.com/(?:watch\?v=|embed/|shorts/|live/)|youtu\.be/)([A-Za-z0-9_-]{11})#', $html, $m ) ) {
			foreach ( array_unique( $m[1] ) as $id ) {
				$videos[ 'yt' . $id ] = array(
					'embed'     => 'https://www.youtube.com/embed/' . $id,
					'content'   => '',
					'thumbnail' => 'https://i.ytimg.com/vi/' . $id . '/hqdefault.jpg',
				);
			}
		}
		if ( preg_match_all( '#vimeo\.com/(?:video/)?(\d{6,})#', $html, $m ) ) {
			foreach ( array_unique( $m[1] ) as $id ) {
				$videos[ 'vi' . $id ] = array(
					'embed'     => 'https://player.vimeo.com/video/' . $id,
					'content'   => '',
					'thumbnail' => $fallback,
				);
			}
		}
		if ( preg_match_all( '#<video\b([^>]*)>(.*?)</video>#is', $html, $m, PREG_SET_ORDER ) ) {
			foreach ( $m as $video ) {
				if ( ! preg_match( '#\ssrc=(["\'])(https?://[^"\']+)\1#i', $video[1] . ' ' . $video[2], $src ) ) {
					continue;
				}
				$poster                   = preg_match( '#\sposter=(["\'])(https?://[^"\']+)\1#i', $video[1], $p ) ? $p[2] : $fallback;
				$videos[ 'up' . $src[2] ] = array(
					'embed'     => '',
					'content'   => html_entity_decode( $src[2], ENT_QUOTES | ENT_HTML5, 'UTF-8' ),
					'thumbnail' => html_entity_decode( $poster, ENT_QUOTES | ENT_HTML5, 'UTF-8' ),
				);
			}
		}
		return array_values(
			array_filter(
				$videos,
				function ( $video ) {
					return '' !== $video['thumbnail'];
				}
			)
		);
	}

	/**
	 * VideoObject nodes for the videos of a post.
	 *
	 * @param int   $post_id Post ID.
	 * @param array $seo     SEO data of the page.
	 * @return array
	 */
	public static function video_schema( $post_id, $seo ) {
		$post   = get_post( $post_id );
		$videos = $post ? self::videos( $post ) : array();
		$nodes  = array();
		$url    = '' !== $seo['url'] ? $seo['url'] : (string) get_permalink( $post_id );
		foreach ( $videos as $i => $video ) {
			$name    = Context::plain_title( $post ) . ( count( $videos ) > 1 ? ' (' . ( $i + 1 ) . ')' : '' );
			$nodes[] = array_filter(
				array(
					'@type'        => 'VideoObject',
					'@id'          => $url . '#video-' . ( $i + 1 ),
					'name'         => $name,
					'description'  => '' !== $seo['description'] ? $seo['description'] : $name,
					'thumbnailUrl' => $video['thumbnail'],
					'uploadDate'   => get_post_time( 'c', true, $post_id ),
					'embedUrl'     => $video['embed'],
					'contentUrl'   => $video['content'],
					'isPartOf'     => array( '@id' => $url . '#webpage' ),
				)
			);
		}
		return $nodes;
	}

	/**
	 * Image sitemap (Google image extension).
	 *
	 * @return string
	 */
	public static function image_sitemap() {
		$xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n" . '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" xmlns:image="http://www.google.com/schemas/sitemap-image/1.1">' . "\n";
		foreach ( self::posts() as $post ) {
			$images = self::images( $post );
			if ( ! $images ) {
				continue;
			}
			$xml .= "<url>\n<loc>" . esc_xml( Context::for_post( $post->ID )['url'] ) . "</loc>\n";
			foreach ( $images as $image ) {
				$xml .= '<image:image><image:loc>' . esc_xml( $image ) . "</image:loc></image:image>\n";
			}
			$xml .= "</url>\n";
		}
		return $xml . "</urlset>\n";
	}

	/**
	 * Video sitemap (Google video extension).
	 *
	 * @return string
	 */
	public static function video_sitemap() {
		$xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n" . '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" xmlns:video="http://www.google.com/schemas/sitemap-video/1.1">' . "\n";
		foreach ( self::posts() as $post ) {
			$videos = self::videos( $post );
			if ( ! $videos ) {
				continue;
			}
			$seo  = Context::for_post( $post->ID );
			$xml .= "<url>\n<loc>" . esc_xml( $seo['url'] ) . "</loc>\n";
			foreach ( $videos as $i => $video ) {
				$title = Context::plain_title( $post ) . ( count( $videos ) > 1 ? ' (' . ( $i + 1 ) . ')' : '' );
				$xml  .= '<video:video>'
					. '<video:thumbnail_loc>' . esc_xml( $video['thumbnail'] ) . '</video:thumbnail_loc>'
					. '<video:title>' . esc_xml( $title ) . '</video:title>'
					. '<video:description>' . esc_xml( mb_substr( '' !== $seo['description'] ? $seo['description'] : $title, 0, 2048 ) ) . '</video:description>'
					. ( '' !== $video['content'] ? '<video:content_loc>' . esc_xml( $video['content'] ) . '</video:content_loc>' : '' )
					. ( '' !== $video['embed'] ? '<video:player_loc>' . esc_xml( $video['embed'] ) . '</video:player_loc>' : '' )
					. '<video:publication_date>' . esc_xml( get_post_time( 'c', true, $post ) ) . '</video:publication_date>'
					. "</video:video>\n";
			}
			$xml .= "</url>\n";
		}
		return $xml . "</urlset>\n";
	}
}
