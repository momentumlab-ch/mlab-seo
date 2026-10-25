<?php
/**
 * SEO box, advanced tab: robots, canonical, page type, llms.txt, hreflang.
 *
 * @package MomentumLab\SEO
 */

namespace MomentumLab\SEO;

defined( 'ABSPATH' ) || exit;
// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- Included inside Meta_Box::render(), the variables are local.
?>
<div class="mlab-seo__panel" data-panel="advanced" role="tabpanel" hidden>
	<p>
		<label><input type="checkbox" name="mlab_seo[noindex]" id="mlab-seo-noindex" value="1" <?php checked( Post_Meta::get( $id, 'noindex' ) ); ?> /> <?php esc_html_e( 'Hide from search engines (noindex)', 'mlab-seo' ); ?></label><br />
		<label><input type="checkbox" name="mlab_seo[nofollow]" value="1" <?php checked( Post_Meta::get( $id, 'nofollow' ) ); ?> /> <?php esc_html_e( 'Search engines should not follow the links on this page (nofollow)', 'mlab-seo' ); ?></label>
	</p>
	<fieldset class="mlab-seo__robots">
		<legend class="mlab-seo__label"><?php esc_html_e( 'More robots rules', 'mlab-seo' ); ?></legend>
		<?php
		$robots_labels = array(
			'noarchive'    => __( 'No cached copy in search engines (noarchive)', 'mlab-seo' ),
			'nosnippet'    => __( 'No text snippet in search results (nosnippet)', 'mlab-seo' ),
			'noimageindex' => __( 'Do not index the images of this page (noimageindex)', 'mlab-seo' ),
		);
		?>
		<?php foreach ( $robots_labels as $directive => $robots_label ) : ?>
			<label><input type="checkbox" name="mlab_seo[robots][]" value="<?php echo esc_attr( $directive ); ?>" <?php checked( in_array( $directive, Post_Meta::robots( $id ), true ) ); ?> /> <?php echo esc_html( $robots_label ); ?></label><br />
		<?php endforeach; ?>
	</fieldset>
	<?php if ( is_object_in_taxonomy( $post->post_type, 'category' ) ) : ?>
		<p>
			<label for="mlab-seo-primary-term" class="mlab-seo__label"><?php esc_html_e( 'Main category', 'mlab-seo' ); ?></label>
			<?php
			wp_dropdown_categories(
				array(
					'name'              => 'mlab_seo[primary_term]',
					'id'                => 'mlab-seo-primary-term',
					'selected'          => Post_Meta::get( $id, 'primary_term' ),
					'show_option_none'  => __( 'Automatic (first category)', 'mlab-seo' ),
					'option_none_value' => '0',
					'hide_empty'        => false,
					'hierarchical'      => true,
				)
			);
			?>
			<span class="description">
			<?php
			/* translators: %s: the variable %category%. */
			echo esc_html( sprintf( __( 'Shown in the breadcrumbs and used for %s. Choose one of the categories of this post.', 'mlab-seo' ), '%category%' ) );
			?>
			</span>
		</p>
	<?php endif; ?>
	<p>
		<label for="mlab-seo-breadcrumb" class="mlab-seo__label"><?php esc_html_e( 'Breadcrumb title', 'mlab-seo' ); ?></label>
		<input type="text" class="widefat" id="mlab-seo-breadcrumb" name="mlab_seo[breadcrumb]" value="<?php echo esc_attr( Post_Meta::get( $id, 'breadcrumb' ) ); ?>" placeholder="<?php echo esc_attr( Context::plain_title( $id ) ); ?>" />
	</p>
	<p>
		<label for="mlab-seo-article-type" class="mlab-seo__label"><?php esc_html_e( 'Article type (schema.org)', 'mlab-seo' ); ?></label>
		<?php
		Fields::select(
			'article-type',
			'mlab_seo[article_type]',
			Post_Meta::get( $id, 'article_type' ),
			array(
				''            => __( 'As set for the content type', 'mlab-seo' ),
				'none'        => __( 'None', 'mlab-seo' ),
				'Article'     => 'Article',
				'BlogPosting' => 'BlogPosting',
				'NewsArticle' => 'NewsArticle',
			)
		);
		?>
	</p>
	<p>
		<label for="mlab-seo-canonical" class="mlab-seo__label"><?php esc_html_e( 'Canonical URL', 'mlab-seo' ); ?></label>
		<input type="url" class="widefat" id="mlab-seo-canonical" name="mlab_seo[canonical]" value="<?php echo esc_attr( Post_Meta::get( $id, 'canonical' ) ); ?>" placeholder="<?php echo esc_attr( (string) get_permalink( $id ) ); ?>" />
		<span class="description"><?php esc_html_e( 'Only when the same content exists under another address.', 'mlab-seo' ); ?></span>
	</p>
	<p>
		<label for="mlab-seo-page-type" class="mlab-seo__label"><?php esc_html_e( 'Page type (schema.org)', 'mlab-seo' ); ?></label>
		<select id="mlab-seo-page-type" name="mlab_seo[page_type]">
			<option value=""><?php esc_html_e( 'Web page (default)', 'mlab-seo' ); ?></option>
			<?php foreach ( array_slice( Post_Meta::PAGE_TYPES, 1 ) as $page_type ) : ?>
				<option value="<?php echo esc_attr( $page_type ); ?>" <?php selected( Post_Meta::get( $id, 'page_type' ), $page_type ); ?>><?php echo esc_html( $page_type ); ?></option>
			<?php endforeach; ?>
		</select>
	</p>
	<p>
		<label for="mlab-seo-llms" class="mlab-seo__label"><?php esc_html_e( 'In llms.txt for AI assistants', 'mlab-seo' ); ?></label>
		<select id="mlab-seo-llms" name="mlab_seo[llms]">
			<option value=""><?php esc_html_e( 'List under pages', 'mlab-seo' ); ?></option>
			<option value="optional" <?php selected( Post_Meta::get( $id, 'llms' ), 'optional' ); ?>><?php esc_html_e( 'List under optional', 'mlab-seo' ); ?></option>
			<option value="hide" <?php selected( Post_Meta::get( $id, 'llms' ), 'hide' ); ?>><?php esc_html_e( 'Do not list', 'mlab-seo' ); ?></option>
		</select>
	</p>
	<?php if ( Hreflang::languages() && ! Hreflang::handled_elsewhere() ) : ?>
		<?php $translations = Hreflang::for_post( $id ); ?>
		<p class="mlab-seo__label"><?php esc_html_e( 'Translations (hreflang)', 'mlab-seo' ); ?></p>
		<p class="description"><?php esc_html_e( 'Address of this page in the other languages. Leave empty when there is no translation.', 'mlab-seo' ); ?></p>
		<?php foreach ( Hreflang::languages() as $code ) : ?>
			<p>
				<label for="mlab-seo-hreflang-<?php echo esc_attr( $code ); ?>" class="mlab-seo__label"><?php echo esc_html( $code ); ?></label>
				<input type="url" class="widefat" id="mlab-seo-hreflang-<?php echo esc_attr( $code ); ?>" name="mlab_seo_hreflang[<?php echo esc_attr( $code ); ?>]" value="<?php echo esc_attr( $translations[ $code ] ?? '' ); ?>" placeholder="https://" />
			</p>
		<?php endforeach; ?>
	<?php endif; ?>
</div>
