<?php
/**
 * SEO fields in Quick Edit of the post lists. The script fills them from the SEO column.
 *
 * @package MomentumLab\SEO
 */

namespace MomentumLab\SEO;

defined( 'ABSPATH' ) || exit;
?>
<fieldset class="inline-edit-col-left mlab-seo-quick"><div class="inline-edit-col">
	<span class="title inline-edit-categories-label"><?php esc_html_e( 'MomentumLab SEO', 'mlab-seo' ); ?></span>
	<label><span class="title"><?php esc_html_e( 'SEO title', 'mlab-seo' ); ?></span>
		<span class="input-text-wrap"><input type="text" name="mlab_seo_quick[title]" data-field="title" /></span></label>
	<label><span class="title"><?php esc_html_e( 'Meta description', 'mlab-seo' ); ?></span>
		<span class="input-text-wrap"><textarea name="mlab_seo_quick[description]" data-field="description" rows="2"></textarea></span></label>
	<label><span class="title"><?php esc_html_e( 'Focus keyword', 'mlab-seo' ); ?></span>
		<span class="input-text-wrap"><input type="text" name="mlab_seo_quick[focus]" data-field="focus" /></span></label>
	<label class="alignleft"><input type="checkbox" name="mlab_seo_quick[noindex]" value="1" data-field="noindex" />
		<span class="checkbox-title"><?php esc_html_e( 'Hide from search engines (noindex)', 'mlab-seo' ); ?></span></label>
</div></fieldset>
