<?php
/**
 * Edit panel of the page list: fields left, Google and social preview, SEO check and rich results right.
 * Filled and saved by assets/js/pages.js.
 *
 * @package MomentumLab\SEO
 */

namespace MomentumLab\SEO;

defined( 'ABSPATH' ) || exit;
// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- Included inside Admin::part(), the variables are local.

$page_types = array( '' => __( 'Web page (default)', 'mlab-seo' ) ) + array_combine( array_slice( Post_Meta::PAGE_TYPES, 1 ), array_slice( Post_Meta::PAGE_TYPES, 1 ) );
?>
<div class="mlab-seo-scrim" data-close hidden></div>
<div class="mlab-seo-sheet" role="dialog" aria-modal="true" aria-labelledby="mlab-seo-sheet-title" hidden>
	<div class="mlab-seo-sheet__head">
		<button type="button" class="mlab-seo-btn is-plain" data-close><?php esc_html_e( 'Cancel', 'mlab-seo' ); ?></button>
		<h2 id="mlab-seo-sheet-title" data-show="name"></h2>
		<div class="mlab-seo-sheet__actions">
			<a class="mlab-seo-btn" data-href="edit" href="#"><?php esc_html_e( 'Open in editor', 'mlab-seo' ); ?></a>
			<button type="button" class="mlab-seo-btn is-primary" data-save><?php esc_html_e( 'Save', 'mlab-seo' ); ?></button>
		</div>
	</div>
	<form class="mlab-seo-sheet__body" novalidate>
		<div>
			<label class="mlab-seo-field">
				<span><?php esc_html_e( 'SEO title', 'mlab-seo' ); ?> <em data-count="title"></em></span>
				<input type="text" name="title" maxlength="200" />
				<span class="mlab-seo-meter" data-for="title"><i></i></span>
			</label>
			<label class="mlab-seo-field">
				<span><?php esc_html_e( 'Meta description', 'mlab-seo' ); ?> <em data-count="description"></em></span>
				<textarea name="description" rows="3" maxlength="400"></textarea>
				<span class="mlab-seo-meter" data-for="description"><i></i></span>
			</label>
			<button type="button" class="mlab-seo-btn is-plain" data-suggest><?php esc_html_e( 'Suggest from the page text', 'mlab-seo' ); ?></button>
			<label class="mlab-seo-field">
				<span><?php esc_html_e( 'Focus keyword', 'mlab-seo' ); ?></span>
				<input type="text" name="focus" maxlength="100" placeholder="<?php esc_attr_e( 'The search term this page should be found for', 'mlab-seo' ); ?>" />
			</label>

			<div class="mlab-seo-group">
				<div class="mlab-seo-row">
					<div class="mlab-seo-row__label"><label for="mlab-seo-sheet-index"><?php esc_html_e( 'Show in search engines', 'mlab-seo' ); ?></label><small><?php esc_html_e( 'Off: the page gets noindex.', 'mlab-seo' ); ?></small></div>
					<span class="mlab-seo-switch"><input type="checkbox" id="mlab-seo-sheet-index" name="index" /><span></span></span>
				</div>
				<div class="mlab-seo-row">
					<div class="mlab-seo-row__label"><label for="mlab-seo-sheet-llms"><?php esc_html_e( 'In llms.txt for AI assistants', 'mlab-seo' ); ?></label></div>
					<select id="mlab-seo-sheet-llms" name="llms">
						<option value=""><?php esc_html_e( 'List under pages', 'mlab-seo' ); ?></option>
						<option value="optional"><?php esc_html_e( 'List under optional', 'mlab-seo' ); ?></option>
						<option value="hide"><?php esc_html_e( 'Do not list', 'mlab-seo' ); ?></option>
					</select>
				</div>
				<details class="mlab-seo-row is-stack">
					<summary><?php esc_html_e( 'Advanced', 'mlab-seo' ); ?></summary>
					<label class="mlab-seo-field">
						<span><?php esc_html_e( 'Canonical URL', 'mlab-seo' ); ?></span>
						<input type="url" name="canonical" placeholder="<?php esc_attr_e( 'Only when the same content exists under another address.', 'mlab-seo' ); ?>" />
					</label>
					<label class="mlab-seo-field">
						<span><?php esc_html_e( 'Page type (schema.org)', 'mlab-seo' ); ?></span>
						<select name="page_type">
							<?php foreach ( $page_types as $value => $label ) : ?>
								<option value="<?php echo esc_attr( $value ); ?>"><?php echo esc_html( $label ); ?></option>
							<?php endforeach; ?>
						</select>
					</label>
					<label class="mlab-seo-field">
						<span><?php esc_html_e( 'Breadcrumb title', 'mlab-seo' ); ?></span>
						<input type="text" name="breadcrumb" maxlength="100" />
					</label>
				</details>
			</div>
		</div>

		<div>
			<h3 class="mlab-seo-section"><?php esc_html_e( 'Google preview', 'mlab-seo' ); ?></h3>
			<div class="mlab-seo-card mlab-seo-serp">
				<div class="mlab-seo-serp__site"><span class="mlab-seo-serp__fav" aria-hidden="true"></span><div><span data-show="site"></span><span class="mlab-seo-serp__url" data-show="url"></span></div></div>
				<div class="mlab-seo-serp__title" data-show="serpTitle"></div>
				<div class="mlab-seo-serp__desc"><span class="mlab-seo-serp__date" data-show="date"></span><span data-show="serpDesc"></span></div>
			</div>

			<h3 class="mlab-seo-section"><?php esc_html_e( 'Social media preview', 'mlab-seo' ); ?></h3>
			<div class="mlab-seo-og">
				<div class="mlab-seo-og__img" data-image></div>
				<div class="mlab-seo-og__meta">
					<span class="mlab-seo-og__host" data-show="host"></span>
					<span class="mlab-seo-og__title" data-show="ogTitle"></span>
					<span class="mlab-seo-og__desc" data-show="ogDesc"></span>
				</div>
			</div>
			<details class="mlab-seo-more">
				<summary><?php esc_html_e( 'Own text for social media', 'mlab-seo' ); ?></summary>
				<label class="mlab-seo-field">
					<span><?php esc_html_e( 'Social title', 'mlab-seo' ); ?></span>
					<input type="text" name="og_title" maxlength="200" placeholder="<?php esc_attr_e( 'Empty: the SEO title', 'mlab-seo' ); ?>" />
				</label>
				<label class="mlab-seo-field">
					<span><?php esc_html_e( 'Social description', 'mlab-seo' ); ?></span>
					<textarea name="og_desc" rows="2" maxlength="400" placeholder="<?php esc_attr_e( 'Empty: the meta description', 'mlab-seo' ); ?>"></textarea>
				</label>
				<p class="description"><?php esc_html_e( 'The social image is chosen in the editor. Without one, the featured image or the default image is used.', 'mlab-seo' ); ?></p>
			</details>

			<h3 class="mlab-seo-section"><?php esc_html_e( 'SEO check', 'mlab-seo' ); ?> <span data-show="scoreBadge"></span></h3>
			<div class="mlab-seo-card"><ul class="mlab-seo-checks" data-checks></ul></div>
		</div>
	</form>
</div>
