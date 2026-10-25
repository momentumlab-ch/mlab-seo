<?php
/**
 * FAQ block: questions and answers as accordion, with FAQPage schema.
 *
 * @package MomentumLab\SEO
 */

namespace MomentumLab\SEO;

defined( 'ABSPATH' ) || exit;

/**
 * Registers the block "mlab-seo/faq" and reads its items for schema.org.
 */
final class FAQ {

	/**
	 * Block name.
	 */
	const BLOCK = 'mlab-seo/faq';

	/**
	 * HTML allowed in answers.
	 */
	const ANSWER_HTML = array(
		'a'      => array(
			'href'   => true,
			'target' => true,
			'rel'    => true,
		),
		'br'     => array(),
		'strong' => array(),
		'b'      => array(),
		'em'     => array(),
		'i'      => array(),
		'ul'     => array(),
		'ol'     => array(),
		'li'     => array(),
		'p'      => array(),
	);

	/**
	 * Registers hooks.
	 */
	public function hooks() {
		add_action( 'init', array( $this, 'register' ) );
	}

	/**
	 * Registers the block with a plain script (no build step).
	 */
	public function register() {
		wp_register_script( 'mlab-seo-faq-block', MLAB_SEO_URL . 'assets/js/faq-block.js', array( 'wp-blocks', 'wp-element', 'wp-block-editor', 'wp-components', 'wp-i18n' ), Admin::asset_version( 'assets/js/faq-block.js' ), true );
		wp_set_script_translations( 'mlab-seo-faq-block', 'mlab-seo', MLAB_SEO_DIR . 'languages' );
		register_block_type(
			self::BLOCK,
			array(
				'api_version'     => 3,
				'editor_script'   => 'mlab-seo-faq-block',
				'render_callback' => array( self::class, 'render' ),
				'attributes'      => array(
					'items' => array(
						'type'    => 'array',
						'default' => array(),
					),
					'open'  => array(
						'type'    => 'boolean',
						'default' => false,
					),
				),
				'supports'        => array(
					'html'    => false,
					'anchor'  => true,
					'spacing' => array(
						'margin'  => true,
						'padding' => true,
					),
				),
			)
		);
	}

	/**
	 * Clean items from block attributes.
	 *
	 * @param array $attributes Block attributes.
	 * @return array<int,array{question:string,answer:string}>
	 */
	public static function items( $attributes ) {
		$items = array();
		foreach ( (array) ( $attributes['items'] ?? array() ) as $item ) {
			$question = trim( wp_strip_all_tags( (string) ( $item['question'] ?? '' ) ) );
			$answer   = trim( wp_kses( (string) ( $item['answer'] ?? '' ), self::ANSWER_HTML ) );
			if ( '' !== $question && '' !== $answer ) {
				$items[] = array(
					'question' => html_entity_decode( $question, ENT_QUOTES | ENT_HTML5, 'UTF-8' ),
					'answer'   => $answer,
				);
			}
		}
		return $items;
	}

	/**
	 * Front-end markup: one <details> per question, works without JavaScript.
	 *
	 * @param array $attributes Block attributes.
	 * @return string
	 */
	public static function render( $attributes ) {
		$items = self::items( $attributes );
		if ( ! $items ) {
			return '';
		}
		$html = '';
		foreach ( $items as $item ) {
			$html .= '<details class="mlab-seo-faq__item"' . ( ! empty( $attributes['open'] ) ? ' open' : '' ) . '><summary class="mlab-seo-faq__question">' . esc_html( $item['question'] ) . '</summary><div class="mlab-seo-faq__answer">' . wpautop( $item['answer'] ) . '</div></details>';
		}
		return '<div ' . get_block_wrapper_attributes( array( 'class' => 'mlab-seo-faq' ) ) . '>' . $html . '</div>';
	}

	/**
	 * All FAQ items of a post, including blocks nested in groups or columns.
	 *
	 * @param int $post_id Post ID.
	 * @return array<int,array{question:string,answer:string}>
	 */
	public static function for_post( $post_id ) {
		$content = (string) get_post_field( 'post_content', $post_id );
		if ( ! has_block( self::BLOCK, $content ) ) {
			return array();
		}
		return self::collect( parse_blocks( $content ) );
	}

	/**
	 * Walks the block tree.
	 *
	 * @param array $blocks Parsed blocks.
	 * @return array
	 */
	private static function collect( array $blocks ) {
		$items = array();
		foreach ( $blocks as $block ) {
			if ( self::BLOCK === $block['blockName'] ) {
				$items = array_merge( $items, self::items( $block['attrs'] ) );
			}
			if ( ! empty( $block['innerBlocks'] ) ) {
				$items = array_merge( $items, self::collect( $block['innerBlocks'] ) );
			}
		}
		return $items;
	}

	/**
	 * Question nodes for schema.org.
	 *
	 * @param array $items FAQ items.
	 * @return array
	 */
	public static function questions( array $items ) {
		$nodes = array();
		foreach ( $items as $item ) {
			$nodes[] = array(
				'@type'          => 'Question',
				'name'           => $item['question'],
				'acceptedAnswer' => array(
					'@type' => 'Answer',
					'text'  => $item['answer'],
				),
			);
		}
		return $nodes;
	}
}
