<?php
/**
 * SEO score of the current page in the admin bar, on the website and in the editor.
 *
 * @package MomentumLab\SEO
 */

namespace MomentumLab\SEO;

defined( 'ABSPATH' ) || exit;

/**
 * Adds one entry with the score and shortcuts for the page being viewed or edited.
 */
final class Admin_Bar {

	/**
	 * Registers hooks.
	 */
	public function hooks() {
		add_action( 'admin_bar_menu', array( $this, 'add' ), 90 );
	}

	/**
	 * Adds the entry for the current post.
	 *
	 * @param \WP_Admin_Bar $bar Admin bar.
	 */
	public function add( $bar ) {
		if ( ! Settings::get( 'admin_bar' ) ) {
			return;
		}
		$id = self::current_post_id();
		if ( ! $id || ! current_user_can( 'edit_post', $id ) || ! in_array( get_post_type( $id ), Post_Meta::post_types(), true ) ) {
			return;
		}
		$score = (int) Post_Meta::get( $id, 'score' );
		$state = $score ? Dashboard::state( $score ) : 'none';
		$hidden = Post_Meta::get( $id, 'noindex' );
		$label  = $hidden ? __( 'noindex', 'mlab-seo' ) : ( $score ? (string) $score : '–' );
		$dot    = array(
			'good' => '#34c759',
			'ok'   => '#ff9f0a',
			'bad'  => '#ff3b30',
			'none' => '#8c8f94',
		);
		$bar->add_node(
			array(
				'id'    => 'mlab-seo',
				'title' => '<span style="display:inline-block;width:9px;height:9px;border-radius:50%;margin-right:6px;background:' . esc_attr( $dot[ $state ] ?? $dot['none'] ) . '"></span>' . esc_html( sprintf( /* translators: %s: SEO score or noindex. */ __( 'SEO: %s', 'mlab-seo' ), $label ) ),
				'href'  => Admin::url( 'mlab-seo-pages', '', array( 'edit' => $id ) ),
			)
		);
		$links = array(
			'mlab-seo-schema' => array( __( 'Structured data', 'mlab-seo' ), Admin::url( 'mlab-seo-search', 'schema', array( 'schema_post' => $id ) ) ),
			'mlab-seo-test'   => array( __( 'Test rich results', 'mlab-seo' ), 'https://search.google.com/test/rich-results?url=' . rawurlencode( (string) get_permalink( $id ) ) ),
		);
		foreach ( $links as $key => $link ) {
			$bar->add_node(
				array(
					'id'     => $key,
					'parent' => 'mlab-seo',
					'title'  => esc_html( $link[0] ),
					'href'   => $link[1],
					'meta'   => array( 'target' => 'mlab-seo-test' === $key ? '_blank' : '_self' ),
				)
			);
		}
	}

	/**
	 * Post being viewed on the website or edited in the admin.
	 *
	 * @return int
	 */
	private static function current_post_id() {
		if ( ! is_admin() ) {
			return is_singular() ? (int) get_queried_object_id() : 0;
		}
		$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
		return $screen && 'post' === $screen->base ? (int) get_the_ID() : 0;
	}
}
