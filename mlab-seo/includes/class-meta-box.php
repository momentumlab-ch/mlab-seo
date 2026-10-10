<?php
/**
 * The SEO box in the post editor: Google preview, SEO check, social image, advanced and Pro fields.
 *
 * @package MomentumLab\SEO
 */

namespace MomentumLab\SEO;

defined( 'ABSPATH' ) || exit;

/**
 * Editor meta box and list column.
 */
final class Meta_Box {

	/**
	 * Nonce action and field.
	 */
	const NONCE = 'mlab_seo_meta_box';

	/**
	 * Registers hooks.
	 */
	public function hooks() {
		add_action( 'add_meta_boxes', array( $this, 'add' ) );
		add_action( 'save_post', array( $this, 'save' ), 10, 2 );
		add_action( 'admin_enqueue_scripts', array( $this, 'assets' ) );
		foreach ( Post_Meta::post_types() as $type ) {
			add_filter( "manage_{$type}_posts_columns", array( $this, 'column' ) );
			add_action( "manage_{$type}_posts_custom_column", array( $this, 'column_value' ), 10, 2 );
		}
	}

	/**
	 * Adds the box to all supported post types.
	 */
	public function add() {
		foreach ( Post_Meta::post_types() as $type ) {
			add_meta_box( 'mlab-seo', __( 'MomentumLab SEO', 'mlab-seo' ), array( $this, 'render' ), $type, 'normal', 'high' );
		}
	}

	/**
	 * Scripts and styles on the edit screen.
	 *
	 * @param string $hook Admin page hook.
	 */
	public function assets( $hook ) {
		$screen = get_current_screen();
		if ( ! in_array( $hook, array( 'post.php', 'post-new.php' ), true ) || ! $screen || ! in_array( $screen->post_type, Post_Meta::post_types(), true ) ) {
			return;
		}
		wp_enqueue_media();
		wp_enqueue_style( 'mlab-seo-admin', MLAB_SEO_URL . 'assets/css/admin.css', array(), Admin::asset_version( 'assets/css/admin.css' ) );
		wp_enqueue_script( 'mlab-seo-editor', MLAB_SEO_URL . 'assets/js/editor.js', array( 'jquery' ), Admin::asset_version( 'assets/js/editor.js' ), true );
		wp_localize_script(
			'mlab-seo-editor',
			'mlabSeoEditor',
			array(
				'separator' => Settings::get( 'separator' ),
				'siteName'  => Settings::site_name(),
				'homeUrl'   => home_url( '/' ),
				'i18n'      => array(
					'good'           => __( 'Good', 'mlab-seo' ),
					'improve'        => __( 'Can be improved', 'mlab-seo' ),
					'problem'        => __( 'Problem', 'mlab-seo' ),
					'chooseImage'    => __( 'Choose social image', 'mlab-seo' ),
					'useImage'       => __( 'Use this image', 'mlab-seo' ),
					/* translators: %d: number of characters. */
					'chars'          => __( '%d characters', 'mlab-seo' ),
					/* translators: %d: width in pixels. */
					'pixels'         => __( 'about %d px of 580 px', 'mlab-seo' ),
					'titleLength'    => __( 'The title fits into the Google result (up to about 580 px).', 'mlab-seo' ),
					'titleLong'      => __( 'The title is too long and will be cut off in Google.', 'mlab-seo' ),
					'titleShort'     => __( 'The title is very short. Use the space for a clear description of the page.', 'mlab-seo' ),
					'descGood'       => __( 'The meta description has a good length (120 to 160 characters).', 'mlab-seo' ),
					'descEmpty'      => __( 'No meta description: Google picks a text itself. Write one that invites a click.', 'mlab-seo' ),
					'descShort'      => __( 'The meta description is short. Aim for 120 to 160 characters.', 'mlab-seo' ),
					'descLong'       => __( 'The meta description is longer than 160 characters and will be cut off.', 'mlab-seo' ),
					'noFocus'        => __( 'Set a focus keyword to see the keyword checks.', 'mlab-seo' ),
					'focusTitle'     => __( 'The focus keyword is in the SEO title.', 'mlab-seo' ),
					'focusTitleNo'   => __( 'Put the focus keyword into the SEO title, ideally near the start.', 'mlab-seo' ),
					'focusDesc'      => __( 'The focus keyword is in the meta description.', 'mlab-seo' ),
					'focusDescNo'    => __( 'Use the focus keyword in the meta description.', 'mlab-seo' ),
					'focusIntro'     => __( 'The focus keyword appears in the first paragraph.', 'mlab-seo' ),
					'focusIntroNo'   => __( 'Mention the focus keyword in the first paragraph.', 'mlab-seo' ),
					'focusUrl'       => __( 'The focus keyword is in the URL.', 'mlab-seo' ),
					'focusUrlNo'     => __( 'The URL does not contain the focus keyword.', 'mlab-seo' ),
					'focusHeading'   => __( 'The focus keyword appears in a subheading.', 'mlab-seo' ),
					'focusHeadingNo' => __( 'Use the focus keyword in at least one subheading (H2 or H3).', 'mlab-seo' ),
					/* translators: %s: keyword density in percent. */
					'density'        => __( 'Keyword density %s %% is fine.', 'mlab-seo' ),
					/* translators: %s: keyword density in percent. */
					'densityLow'     => __( 'Keyword density %s %% is low. Use the keyword a few more times.', 'mlab-seo' ),
					/* translators: %s: keyword density in percent. */
					'densityHigh'    => __( 'Keyword density %s %% is high. This can look like keyword stuffing.', 'mlab-seo' ),
					/* translators: %d: number of words. */
					'words'          => __( 'The text has %d words.', 'mlab-seo' ),
					/* translators: %d: number of words. */
					'wordsLow'       => __( 'The text has only %d words. Pages with at least 300 words rank better.', 'mlab-seo' ),
					'headings'       => __( 'The text is structured with subheadings.', 'mlab-seo' ),
					'headingsNo'     => __( 'Add subheadings (H2) so readers and search engines can scan the page.', 'mlab-seo' ),
					'h1'             => __( 'The text contains its own H1. The page title already is the H1, use H2 instead.', 'mlab-seo' ),
					'alt'            => __( 'All images have an alternative text.', 'mlab-seo' ),
					/* translators: %d: number of images. */
					'altNo'          => __( '%d image(s) without alternative text. Search engines and screen readers need it.', 'mlab-seo' ),
					'internal'       => __( 'The page links to other pages of the website.', 'mlab-seo' ),
					'internalNo'     => __( 'Add at least one link to another page of the website.', 'mlab-seo' ),
					'external'       => __( 'The page links to external sources.', 'mlab-seo' ),
					'externalNo'     => __( 'No external links. A link to a trusted source can help.', 'mlab-seo' ),
					'noindex'        => __( 'This page is hidden from search engines (noindex).', 'mlab-seo' ),
					'image'          => __( 'There is an image for social media and search previews.', 'mlab-seo' ),
					'imageNo'        => __( 'No image for social media. Set a social image or a featured image.', 'mlab-seo' ),
					'sentences'      => __( 'Most sentences are short and easy to read.', 'mlab-seo' ),
					/* translators: %s: share of long sentences in percent. */
					'sentencesLong'  => __( '%s %% of the sentences have more than 20 words. Shorter sentences are easier to read.', 'mlab-seo' ),
					/* translators: %d: number of paragraphs. */
					'paragraphsLong' => __( '%d paragraph(s) with more than 150 words. Split long paragraphs.', 'mlab-seo' ),
					'removeSub'      => __( 'Remove', 'mlab-seo' ),
				),
			)
		);
	}

	/**
	 * Renders the box.
	 *
	 * @param \WP_Post $post Post.
	 */
	public function render( $post ) {
		$id       = $post->ID;
		$title    = Post_Meta::get( $id, 'title' );
		$desc     = Post_Meta::get( $id, 'description' );
		$focus    = Post_Meta::get( $id, 'focus' );
		$image    = Post_Meta::get( $id, 'image' );
		$fallback = Context::for_post( $id );
		$img      = Context::image( $image, 'medium' );
		$pro      = License::is_pro();
		$event    = array_merge(
			array(
				'type'        => 'Event',
				'name'        => '',
				'description' => '',
				'start'       => '',
				'end'         => '',
				'status'      => 'EventScheduled',
				'sport'       => '',
				'location'    => '',
				'street'      => '',
				'zip'         => '',
				'city'        => '',
				'country'     => '',
				'url'         => '',
				'offer_url'   => '',
				'price'       => '',
				'currency'    => '',
				'sub'         => array(),
			),
			Post_Meta::get( $id, 'event' )
		);

		wp_nonce_field( self::NONCE, self::NONCE );
		?>
		<div class="mlab-seo" data-front="<?php echo esc_attr( (int) ( 'page' === get_option( 'show_on_front' ) && (int) get_option( 'page_on_front' ) === $id ) ); ?>">
			<div class="mlab-seo__tabs" role="tablist">
				<button type="button" role="tab" class="mlab-seo__tab is-active" aria-selected="true" data-tab="seo"><?php esc_html_e( 'Google', 'mlab-seo' ); ?> <span class="mlab-seo__score" aria-hidden="true"></span></button>
				<button type="button" role="tab" class="mlab-seo__tab" aria-selected="false" data-tab="social"><?php esc_html_e( 'Social media', 'mlab-seo' ); ?></button>
				<button type="button" role="tab" class="mlab-seo__tab" aria-selected="false" data-tab="advanced"><?php esc_html_e( 'Advanced', 'mlab-seo' ); ?></button>
				<button type="button" role="tab" class="mlab-seo__tab" aria-selected="false" data-tab="schema"><?php esc_html_e( 'Event & schema', 'mlab-seo' ); ?> <span class="mlab-seo__pro">Pro</span></button>
			</div>

			<div class="mlab-seo__panel is-active" data-panel="seo" role="tabpanel">
				<p class="mlab-seo__label"><?php esc_html_e( 'Google preview', 'mlab-seo' ); ?></p>
				<div class="mlab-seo__serp" aria-live="polite">
					<div class="mlab-seo__serp-site"><span class="mlab-seo__serp-name"><?php echo esc_html( Settings::site_name() ); ?></span><span class="mlab-seo__serp-url"><?php echo esc_html( $fallback['url'] ); ?></span></div>
					<div class="mlab-seo__serp-title"></div>
					<div class="mlab-seo__serp-desc"></div>
				</div>

				<p>
					<label for="mlab-seo-title" class="mlab-seo__label"><?php esc_html_e( 'SEO title', 'mlab-seo' ); ?></label>
					<input type="text" class="widefat" id="mlab-seo-title" name="mlab_seo[title]" value="<?php echo esc_attr( $title ); ?>" placeholder="<?php echo esc_attr( '' === $title ? $fallback['title'] : '' ); ?>" />
					<span class="mlab-seo__meter" data-for="title"><span></span></span>
					<span class="mlab-seo__hint" data-hint="title"></span>
				</p>
				<p>
					<label for="mlab-seo-description" class="mlab-seo__label"><?php esc_html_e( 'Meta description', 'mlab-seo' ); ?></label>
					<textarea class="widefat" rows="3" id="mlab-seo-description" name="mlab_seo[description]" placeholder="<?php echo esc_attr( '' === $desc ? $fallback['description'] : '' ); ?>"><?php echo esc_textarea( $desc ); ?></textarea>
					<span class="mlab-seo__meter" data-for="description"><span></span></span>
					<span class="mlab-seo__hint" data-hint="description"></span>
				</p>
				<p>
					<label for="mlab-seo-focus" class="mlab-seo__label"><?php esc_html_e( 'Focus keyword', 'mlab-seo' ); ?></label>
					<input type="text" class="widefat" id="mlab-seo-focus" name="mlab_seo[focus]" value="<?php echo esc_attr( $focus ); ?>" placeholder="<?php esc_attr_e( 'The search term this page should be found for', 'mlab-seo' ); ?>" />
				</p>
				<p class="mlab-seo__label"><?php esc_html_e( 'SEO check', 'mlab-seo' ); ?></p>
				<ul class="mlab-seo__checks"></ul>
				<input type="hidden" name="mlab_seo[score]" id="mlab-seo-score" value="<?php echo esc_attr( (string) Post_Meta::get( $id, 'score' ) ); ?>" />
			</div>

			<div class="mlab-seo__panel" data-panel="social" role="tabpanel" hidden>
				<p class="description"><?php esc_html_e( 'Shown when the page is shared on Facebook, LinkedIn, WhatsApp, X and in messengers. Without an own image the featured image or the default image from the settings is used.', 'mlab-seo' ); ?></p>
				<p>
					<label for="mlab-seo-og-title" class="mlab-seo__label"><?php esc_html_e( 'Social title', 'mlab-seo' ); ?></label>
					<input type="text" class="widefat" id="mlab-seo-og-title" name="mlab_seo[og_title]" value="<?php echo esc_attr( Post_Meta::get( $id, 'og_title' ) ); ?>" placeholder="<?php esc_attr_e( 'Empty: the SEO title', 'mlab-seo' ); ?>" />
				</p>
				<p>
					<label for="mlab-seo-og-desc" class="mlab-seo__label"><?php esc_html_e( 'Social description', 'mlab-seo' ); ?></label>
					<textarea class="widefat" rows="2" id="mlab-seo-og-desc" name="mlab_seo[og_desc]" placeholder="<?php esc_attr_e( 'Empty: the meta description', 'mlab-seo' ); ?>"><?php echo esc_textarea( Post_Meta::get( $id, 'og_desc' ) ); ?></textarea>
				</p>
				<div class="mlab-seo__image">
					<div class="mlab-seo__image-preview"><?php echo $img ? '<img src="' . esc_url( $img['url'] ) . '" alt="" />' : ''; ?></div>
					<input type="hidden" name="mlab_seo[image]" id="mlab-seo-image" value="<?php echo esc_attr( (string) $image ); ?>" />
					<button type="button" class="button mlab-seo__image-pick"><?php esc_html_e( 'Choose image', 'mlab-seo' ); ?></button>
					<button type="button" class="button-link mlab-seo__image-remove" <?php echo $image ? '' : 'hidden'; ?>><?php esc_html_e( 'Remove image', 'mlab-seo' ); ?></button>
					<p class="description"><?php esc_html_e( 'Recommended: 1200 × 630 px.', 'mlab-seo' ); ?></p>
				</div>
			</div>

			<div class="mlab-seo__panel" data-panel="advanced" role="tabpanel" hidden>
				<p>
					<label><input type="checkbox" name="mlab_seo[noindex]" id="mlab-seo-noindex" value="1" <?php checked( Post_Meta::get( $id, 'noindex' ) ); ?> /> <?php esc_html_e( 'Hide from search engines (noindex)', 'mlab-seo' ); ?></label><br />
					<label><input type="checkbox" name="mlab_seo[nofollow]" value="1" <?php checked( Post_Meta::get( $id, 'nofollow' ) ); ?> /> <?php esc_html_e( 'Search engines should not follow the links on this page (nofollow)', 'mlab-seo' ); ?></label>
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
						<?php foreach ( array_slice( Post_Meta::PAGE_TYPES, 1 ) as $type ) : ?>
							<option value="<?php echo esc_attr( $type ); ?>" <?php selected( Post_Meta::get( $id, 'page_type' ), $type ); ?>><?php echo esc_html( $type ); ?></option>
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
			</div>

			<div class="mlab-seo__panel" data-panel="schema" role="tabpanel" hidden>
				<?php if ( ! $pro ) : ?>
					<div class="mlab-seo__locked">
						<p><strong><?php esc_html_e( 'Event and custom schema are Pro features.', 'mlab-seo' ); ?></strong></p>
						<p><?php esc_html_e( 'With Pro, events appear with date and place directly in Google results, and you can add any schema.org data to a page.', 'mlab-seo' ); ?></p>
						<p><a class="button button-primary" href="<?php echo esc_url( admin_url( 'admin.php?page=mlab-seo#license' ) ); ?>"><?php esc_html_e( 'Activate Pro', 'mlab-seo' ); ?></a></p>
					</div>
				<?php endif; ?>
				<fieldset class="mlab-seo__event" <?php disabled( ! $pro ); ?>>
					<legend class="mlab-seo__label"><?php esc_html_e( 'Event on this page', 'mlab-seo' ); ?></legend>
					<p class="description"><?php esc_html_e( 'Leave the name empty when the page has no event. Use the same name and URL on several pages to describe one event.', 'mlab-seo' ); ?></p>
					<div class="mlab-seo__grid">
						<?php
						$this->event_field( 'name', __( 'Name', 'mlab-seo' ), $event );
						?>
						<p><label class="mlab-seo__label" for="mlab-seo-event-type"><?php esc_html_e( 'Type', 'mlab-seo' ); ?></label>
							<select id="mlab-seo-event-type" name="mlab_seo_event[type]">
								<?php foreach ( Post_Meta::EVENT_TYPES as $type ) : ?>
									<option value="<?php echo esc_attr( $type ); ?>" <?php selected( $event['type'], $type ); ?>><?php echo esc_html( $type ); ?></option>
								<?php endforeach; ?>
							</select>
						</p>
						<?php
						$this->event_field( 'start', __( 'Start (date or date and time)', 'mlab-seo' ), $event, 'text', 'YYYY-MM-DD / YYYY-MM-DDTHH:MM' );
						$this->event_field( 'end', __( 'End', 'mlab-seo' ), $event, 'text', 'YYYY-MM-DD' );
						?>
						<p><label class="mlab-seo__label" for="mlab-seo-event-status"><?php esc_html_e( 'Status', 'mlab-seo' ); ?></label>
							<select id="mlab-seo-event-status" name="mlab_seo_event[status]">
								<?php foreach ( Post_Meta::EVENT_STATUS as $status ) : ?>
									<option value="<?php echo esc_attr( $status ); ?>" <?php selected( $event['status'], $status ); ?>><?php echo esc_html( $status ); ?></option>
								<?php endforeach; ?>
							</select>
						</p>
						<?php
						$this->event_field( 'sport', __( 'Sport (for sports events)', 'mlab-seo' ), $event );
						$this->event_field( 'location', __( 'Venue', 'mlab-seo' ), $event );
						$this->event_field( 'street', __( 'Street', 'mlab-seo' ), $event );
						$this->event_field( 'zip', __( 'Postcode', 'mlab-seo' ), $event );
						$this->event_field( 'city', __( 'City', 'mlab-seo' ), $event );
						$this->event_field( 'country', __( 'Country code', 'mlab-seo' ), $event, 'text', 'CH' );
						$this->event_field( 'url', __( 'Main page of the event', 'mlab-seo' ), $event, 'url', (string) get_permalink( $id ) );
						$this->event_field( 'offer_url', __( 'Tickets or registration URL', 'mlab-seo' ), $event, 'url' );
						$this->event_field( 'price', __( 'Price', 'mlab-seo' ), $event, 'text', '0' );
						$this->event_field( 'currency', __( 'Currency', 'mlab-seo' ), $event, 'text', 'CHF' );
						?>
					</div>
					<p><label class="mlab-seo__label" for="mlab-seo-event-description"><?php esc_html_e( 'Description', 'mlab-seo' ); ?></label>
						<textarea class="widefat" rows="2" id="mlab-seo-event-description" name="mlab_seo_event[description]"><?php echo esc_textarea( $event['description'] ); ?></textarea></p>

					<p class="mlab-seo__label"><?php esc_html_e( 'Sub-events (races, concerts, sessions)', 'mlab-seo' ); ?></p>
					<table class="mlab-seo__subs widefat striped">
						<thead><tr><th><?php esc_html_e( 'Name', 'mlab-seo' ); ?></th><th><?php esc_html_e( 'Start', 'mlab-seo' ); ?></th><th><?php esc_html_e( 'Venue', 'mlab-seo' ); ?></th><th><span class="screen-reader-text"><?php esc_html_e( 'Remove', 'mlab-seo' ); ?></span></th></tr></thead>
						<tbody>
							<?php foreach ( array_merge( $event['sub'], array( array() ) ) as $i => $sub ) : ?>
								<tr>
									<td><input type="text" class="widefat" name="mlab_seo_event[sub][<?php echo (int) $i; ?>][name]" value="<?php echo esc_attr( $sub['name'] ?? '' ); ?>" aria-label="<?php esc_attr_e( 'Name', 'mlab-seo' ); ?>" /></td>
									<td><input type="text" class="widefat" name="mlab_seo_event[sub][<?php echo (int) $i; ?>][start]" value="<?php echo esc_attr( $sub['start'] ?? '' ); ?>" placeholder="YYYY-MM-DDTHH:MM" aria-label="<?php esc_attr_e( 'Start', 'mlab-seo' ); ?>" /></td>
									<td><input type="text" class="widefat" name="mlab_seo_event[sub][<?php echo (int) $i; ?>][location]" value="<?php echo esc_attr( $sub['location'] ?? '' ); ?>" aria-label="<?php esc_attr_e( 'Venue', 'mlab-seo' ); ?>" /></td>
									<td><button type="button" class="button-link mlab-seo__sub-remove" aria-label="<?php esc_attr_e( 'Remove', 'mlab-seo' ); ?>">×</button></td>
								</tr>
							<?php endforeach; ?>
						</tbody>
					</table>
					<p><button type="button" class="button mlab-seo__sub-add"><?php esc_html_e( 'Add sub-event', 'mlab-seo' ); ?></button></p>

					<p><label class="mlab-seo__label" for="mlab-seo-schema"><?php esc_html_e( 'Additional schema.org (JSON-LD)', 'mlab-seo' ); ?></label>
						<textarea class="widefat code" rows="5" id="mlab-seo-schema" name="mlab_seo[schema]" placeholder='{ "@type": "FAQPage", "mainEntity": [] }'><?php echo esc_textarea( Post_Meta::get( $id, 'schema' ) ); ?></textarea>
						<span class="description"><?php esc_html_e( 'One node or a list of nodes. Invalid JSON is discarded on save.', 'mlab-seo' ); ?></span></p>
				</fieldset>
			</div>
		</div>
		<?php
	}

	/**
	 * One event input.
	 *
	 * @param string $key         Field key.
	 * @param string $label       Label.
	 * @param array  $event       Event data.
	 * @param string $type        Input type.
	 * @param string $placeholder Placeholder.
	 */
	private function event_field( $key, $label, $event, $type = 'text', $placeholder = '' ) {
		printf(
			'<p><label class="mlab-seo__label" for="mlab-seo-event-%1$s">%2$s</label><input type="%3$s" class="widefat" id="mlab-seo-event-%1$s" name="mlab_seo_event[%1$s]" value="%4$s" placeholder="%5$s" /></p>',
			esc_attr( $key ),
			esc_html( $label ),
			esc_attr( $type ),
			esc_attr( (string) $event[ $key ] ),
			esc_attr( $placeholder )
		);
	}

	/**
	 * Saves the box.
	 *
	 * @param int      $post_id Post ID.
	 * @param \WP_Post $post    Post.
	 */
	public function save( $post_id, $post ) {
		if ( ! isset( $_POST[ self::NONCE ] ) || ! wp_verify_nonce( sanitize_key( wp_unslash( $_POST[ self::NONCE ] ) ), self::NONCE ) ) {
			return;
		}
		if ( wp_is_post_autosave( $post_id ) || wp_is_post_revision( $post_id ) || ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}
		if ( ! in_array( $post->post_type, Post_Meta::post_types(), true ) ) {
			return;
		}

		// Values are sanitised per field in Post_Meta::sanitize_field().
		$in = isset( $_POST['mlab_seo'] ) && is_array( $_POST['mlab_seo'] ) ? wp_unslash( $_POST['mlab_seo'] ) : array(); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		foreach ( array_keys( Post_Meta::FIELDS ) as $field ) {
			if ( 'schema' === $field && ! License::is_pro() ) {
				continue;
			}
			$value = Post_Meta::sanitize_field( $field, $in[ $field ] ?? '' );
			if ( '' === $value || 0 === $value ) {
				delete_post_meta( $post_id, Post_Meta::PREFIX . $field );
			} else {
				update_post_meta( $post_id, Post_Meta::PREFIX . $field, $value );
			}
		}

		if ( License::is_pro() ) {
			$event = Post_Meta::sanitize_event( isset( $_POST['mlab_seo_event'] ) ? wp_unslash( $_POST['mlab_seo_event'] ) : array() ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Sanitised in sanitize_event().
			if ( $event ) {
				update_post_meta( $post_id, Post_Meta::PREFIX . 'event', $event );
			} else {
				delete_post_meta( $post_id, Post_Meta::PREFIX . 'event' );
			}
		}
		Crawlers::flush_cache();
	}

	/**
	 * Adds the SEO column.
	 *
	 * @param array $columns Columns.
	 * @return array
	 */
	public function column( $columns ) {
		$columns['mlab_seo'] = __( 'SEO', 'mlab-seo' );
		return $columns;
	}

	/**
	 * Prints the SEO column.
	 *
	 * @param string $column  Column.
	 * @param int    $post_id Post ID.
	 */
	public function column_value( $column, $post_id ) {
		if ( 'mlab_seo' !== $column ) {
			return;
		}
		if ( Post_Meta::get( $post_id, 'noindex' ) ) {
			echo '<span class="mlab-seo-badge is-off">' . esc_html__( 'noindex', 'mlab-seo' ) . '</span>';
			return;
		}
		$score = Post_Meta::get( $post_id, 'score' );
		if ( ! $score ) {
			echo '<span aria-hidden="true">–</span><span class="screen-reader-text">' . esc_html__( 'Not checked yet', 'mlab-seo' ) . '</span>';
			return;
		}
		$class = $score >= 80 ? 'is-good' : ( $score >= 50 ? 'is-ok' : 'is-bad' );
		printf( '<span class="mlab-seo-badge %s">%d</span>', esc_attr( $class ), (int) $score );
	}
}
