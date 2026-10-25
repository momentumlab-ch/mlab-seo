<?php
/**
 * Content output: external links, missing alternative texts, RSS source link.
 *
 * @package MomentumLab\SEO
 */

namespace MomentumLab\SEO;

defined( 'ABSPATH' ) || exit;

/**
 * Filters the post content on output; nothing is changed in the database.
 */
final class Content_Filters {

	/**
	 * Registers hooks.
	 */
	public function hooks() {
		add_filter( 'the_content', array( $this, 'content' ), 99 );
		add_filter( 'wp_get_attachment_image_attributes', array( $this, 'attachment_alt' ), 10, 2 );
		add_filter( 'the_content_feed', array( $this, 'feed_source' ) );
		add_filter( 'the_excerpt_rss', array( $this, 'feed_source' ) );
	}

	/**
	 * External links and image alternative texts in the content.
	 *
	 * @param string $html Post content.
	 * @return string
	 */
	public function content( $html ) {
		$links = Settings::get( 'external_nofollow' ) || Settings::get( 'external_blank' );
		if ( is_admin() || is_feed() || ( ! $links && ! Settings::get( 'image_alt' ) ) || ! class_exists( '\WP_HTML_Tag_Processor' ) ) {
			return $html;
		}
		$tags = new \WP_HTML_Tag_Processor( $html );
		while ( $tags->next_tag() ) {
			if ( 'A' === $tags->get_tag() && $links ) {
				$this->external_link( $tags );
			} elseif ( 'IMG' === $tags->get_tag() && Settings::get( 'image_alt' ) ) {
				$this->image_alt( $tags );
			}
		}
		return $tags->get_updated_html();
	}

	/**
	 * Adds nofollow and a new tab to a link to another website.
	 *
	 * @param \WP_HTML_Tag_Processor $tags Processor at an A tag.
	 */
	private function external_link( $tags ) {
		$host = (string) wp_parse_url( (string) $tags->get_attribute( 'href' ), PHP_URL_HOST );
		if ( '' === $host || wp_parse_url( home_url(), PHP_URL_HOST ) === $host ) {
			return;
		}
		$rel = preg_split( '/\s+/', trim( (string) $tags->get_attribute( 'rel' ) ), -1, PREG_SPLIT_NO_EMPTY );
		if ( Settings::get( 'external_nofollow' ) && ! in_array( 'sponsored', $rel, true ) && ! in_array( 'ugc', $rel, true ) ) {
			$rel[] = 'nofollow';
		}
		if ( Settings::get( 'external_blank' ) && null === $tags->get_attribute( 'target' ) ) {
			$tags->set_attribute( 'target', '_blank' );
			$rel[] = 'noopener';
		}
		if ( $rel ) {
			$tags->set_attribute( 'rel', implode( ' ', array_unique( $rel ) ) );
		}
	}

	/**
	 * Fills a missing alternative text from the image title or file name.
	 * An empty alt="" is a deliberate choice for decorative images and stays.
	 *
	 * @param \WP_HTML_Tag_Processor $tags Processor at an IMG tag.
	 */
	private function image_alt( $tags ) {
		if ( null !== $tags->get_attribute( 'alt' ) ) {
			return;
		}
		$id = preg_match( '/wp-image-(\d+)/', (string) $tags->get_attribute( 'class' ), $m ) ? (int) $m[1] : 0;
		$tags->set_attribute( 'alt', self::alt_text( $id, (string) $tags->get_attribute( 'src' ) ) );
	}

	/**
	 * Alternative text for images printed by wp_get_attachment_image() without one.
	 *
	 * @param array    $attr       Image attributes.
	 * @param \WP_Post $attachment Attachment.
	 * @return array
	 */
	public function attachment_alt( $attr, $attachment ) {
		if ( is_admin() || ! Settings::get( 'image_alt' ) || '' !== trim( (string) ( $attr['alt'] ?? '' ) ) ) {
			return $attr;
		}
		$attr['alt'] = self::alt_text( $attachment->ID, (string) ( $attr['src'] ?? '' ) );
		return $attr;
	}

	/**
	 * Text from the image title, else the file name in words.
	 *
	 * @param int    $id  Attachment ID or 0.
	 * @param string $src Image URL.
	 * @return string
	 */
	private static function alt_text( $id, $src ) {
		$title = $id ? Context::plain_title( $id ) : '';
		if ( '' !== $title ) {
			return $title;
		}
		$name = pathinfo( (string) wp_parse_url( $src, PHP_URL_PATH ), PATHINFO_FILENAME );
		$name = preg_replace( '/-\d+x\d+$|-scaled$/', '', $name );
		return ucfirst( trim( (string) preg_replace( '/[-_]+/', ' ', $name ) ) );
	}

	/**
	 * Link to the original post below each post in the RSS feed.
	 *
	 * @param string $content Feed content.
	 * @return string
	 */
	public function feed_source( $content ) {
		if ( ! Settings::get( 'rss_source' ) || ! get_the_ID() ) {
			return $content;
		}
		$post = '<a href="' . esc_url( (string) get_permalink() ) . '">' . esc_html( Context::plain_title( get_the_ID() ) ) . '</a>';
		$site = '<a href="' . esc_url( home_url( '/' ) ) . '">' . esc_html( Settings::site_name() ) . '</a>';
		/* translators: 1: link to the post, 2: link to the website. */
		return $content . '<p>' . sprintf( __( 'The post %1$s appeared first on %2$s.', 'mlab-seo' ), $post, $site ) . '</p>';
	}
}
