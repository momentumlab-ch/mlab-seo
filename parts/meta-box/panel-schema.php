<?php
/**
 * SEO box, event and schema tab (Pro).
 *
 * @package MomentumLab\SEO
 */

namespace MomentumLab\SEO;

defined( 'ABSPATH' ) || exit;
// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- Included inside Meta_Box::render(), the variables are local.
?>
<div class="mlab-seo__panel" data-panel="schema" role="tabpanel" hidden>
	<?php if ( ! $pro ) : ?>
		<div class="mlab-seo__locked">
			<p><strong><?php esc_html_e( 'Event and custom schema are Pro features.', 'mlab-seo' ); ?></strong></p>
			<p><?php esc_html_e( 'With Pro, events appear with date and place directly in Google results, and you can add any schema.org data to a page.', 'mlab-seo' ); ?></p>
			<p><?php echo Admin::activate_pro_link(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped in the method. ?></p>
		</div>
	<?php endif; ?>
	<fieldset class="mlab-seo__event" <?php disabled( ! $pro ); ?>>
		<legend class="mlab-seo__label"><?php esc_html_e( 'Event on this page', 'mlab-seo' ); ?></legend>
		<p class="description"><?php esc_html_e( 'Leave the name empty when the page has no event. Use the same name and URL on several pages to describe one event.', 'mlab-seo' ); ?></p>
		<?php if ( '' !== $event['name'] ) : ?>
			<?php $gaps = Post_Meta::event_gaps( $event ); ?>
			<?php if ( $gaps['required'] ) : ?>
				<div class="notice notice-error inline"><p>
					<?php
					/* translators: %s: list of missing fields. */
					echo esc_html( sprintf( __( 'Google and Bing show no event result yet. Missing: %s.', 'mlab-seo' ), implode( ', ', $gaps['required'] ) ) );
					?>
				</p></div>
			<?php elseif ( $gaps['recommended'] ) : ?>
				<div class="notice notice-warning inline"><p>
					<?php
					/* translators: %s: list of missing fields. */
					echo esc_html( sprintf( __( 'The event is complete. Recommended in addition: %s.', 'mlab-seo' ), implode( ', ', $gaps['recommended'] ) ) );
					?>
				</p></div>
			<?php endif; ?>
		<?php endif; ?>
		<div class="mlab-seo__grid">
			<?php
			Meta_Box::event_field( 'name', __( 'Name', 'mlab-seo' ), $event );
			?>
			<p><label class="mlab-seo__label" for="mlab-seo-event-type"><?php esc_html_e( 'Type', 'mlab-seo' ); ?></label>
				<select id="mlab-seo-event-type" name="mlab_seo_event[type]">
					<?php foreach ( Post_Meta::EVENT_TYPES as $event_type ) : ?>
						<option value="<?php echo esc_attr( $event_type ); ?>" <?php selected( $event['type'], $event_type ); ?>><?php echo esc_html( $event_type ); ?></option>
					<?php endforeach; ?>
				</select>
			</p>
			<?php
			Meta_Box::event_field( 'start', __( 'Start (date or date and time)', 'mlab-seo' ), $event, 'text', 'YYYY-MM-DD / YYYY-MM-DDTHH:MM' );
			Meta_Box::event_field( 'end', __( 'End', 'mlab-seo' ), $event, 'text', 'YYYY-MM-DD' );
			?>
			<p><label class="mlab-seo__label" for="mlab-seo-event-status"><?php esc_html_e( 'Status', 'mlab-seo' ); ?></label>
				<select id="mlab-seo-event-status" name="mlab_seo_event[status]">
					<?php foreach ( Post_Meta::EVENT_STATUS as $event_status ) : ?>
						<option value="<?php echo esc_attr( $event_status ); ?>" <?php selected( $event['status'], $event_status ); ?>><?php echo esc_html( $event_status ); ?></option>
					<?php endforeach; ?>
				</select>
			</p>
			<?php
			?>
			<p><label class="mlab-seo__label" for="mlab-seo-event-mode"><?php esc_html_e( 'Takes place', 'mlab-seo' ); ?></label>
				<select id="mlab-seo-event-mode" name="mlab_seo_event[mode]">
					<option value="Offline" <?php selected( $event['mode'], 'Offline' ); ?>><?php esc_html_e( 'On site', 'mlab-seo' ); ?></option>
					<option value="Online" <?php selected( $event['mode'], 'Online' ); ?>><?php esc_html_e( 'Online', 'mlab-seo' ); ?></option>
					<option value="Mixed" <?php selected( $event['mode'], 'Mixed' ); ?>><?php esc_html_e( 'On site and online', 'mlab-seo' ); ?></option>
				</select></p>
			<?php
			Meta_Box::event_field( 'online_url', __( 'Online event URL', 'mlab-seo' ), $event, 'url' );
			Meta_Box::event_field( 'performer', __( 'Performer or speaker', 'mlab-seo' ), $event );
			Meta_Box::event_field( 'sport', __( 'Sport (for sports events)', 'mlab-seo' ), $event );
			Meta_Box::event_field( 'location', __( 'Venue', 'mlab-seo' ), $event );
			Meta_Box::event_field( 'street', __( 'Street', 'mlab-seo' ), $event );
			Meta_Box::event_field( 'zip', __( 'Postcode', 'mlab-seo' ), $event );
			Meta_Box::event_field( 'city', __( 'City', 'mlab-seo' ), $event );
			Meta_Box::event_field( 'country', __( 'Country code', 'mlab-seo' ), $event, 'text', 'CH' );
			Meta_Box::event_field( 'url', __( 'Main page of the event', 'mlab-seo' ), $event, 'url', (string) get_permalink( $id ) );
			Meta_Box::event_field( 'offer_url', __( 'Tickets or registration URL', 'mlab-seo' ), $event, 'url' );
			Meta_Box::event_field( 'price', __( 'Price', 'mlab-seo' ), $event, 'text', '0' );
			Meta_Box::event_field( 'currency', __( 'Currency', 'mlab-seo' ), $event, 'text', 'CHF' );
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

		<?php $rich_type = (string) ( $rich['type'] ?? '' ); ?>
		<p class="mlab-seo__label"><label for="mlab-seo-rich-type"><?php esc_html_e( 'Other rich result on this page', 'mlab-seo' ); ?></label></p>
		<p class="description"><?php esc_html_e( 'Job posting, recipe, course or review. One type per page. Fields marked * are needed for Google and Bing.', 'mlab-seo' ); ?></p>
		<select id="mlab-seo-rich-type" name="mlab_seo_rich[type]">
			<option value=""><?php esc_html_e( 'None', 'mlab-seo' ); ?></option>
			<?php foreach ( Rich_Results::types() as $key => $label ) : ?>
				<option value="<?php echo esc_attr( $key ); ?>" <?php selected( $rich_type, $key ); ?>><?php echo esc_html( $label ); ?></option>
			<?php endforeach; ?>
		</select>
		<?php $rich_gaps = $rich ? Rich_Results::gaps( $rich ) : array(); ?>
		<?php if ( $rich_gaps ) : ?>
			<div class="notice notice-error inline"><p>
				<?php
				/* translators: %s: list of missing fields. */
				echo esc_html( sprintf( __( 'Google and Bing show no result yet. Missing: %s.', 'mlab-seo' ), implode( ', ', $rich_gaps ) ) );
				?>
			</p></div>
		<?php endif; ?>
		<p class="notice notice-warning inline mlab-seo__review-note" <?php echo 'review' === $rich_type && in_array( $rich['item_type'] ?? '', array( 'LocalBusiness', 'Organization' ), true ) ? '' : 'hidden'; ?>><?php esc_html_e( 'Google does not show stars for reviews that a business writes about itself. Use this for products, books, movies, courses and similar.', 'mlab-seo' ); ?></p>
		<?php foreach ( array_keys( Rich_Results::types() ) as $key ) : ?>
			<div class="mlab-seo__grid mlab-seo__rich" data-rich="<?php echo esc_attr( $key ); ?>" <?php echo $rich_type === $key ? '' : 'hidden'; ?>>
				<?php
				foreach ( Rich_Results::fields( $key ) as $field_key => $field ) {
					Rich_Results::input( $key, $field_key, $field, $rich );
				}
				?>
			</div>
		<?php endforeach; ?>

		<p><label class="mlab-seo__label" for="mlab-seo-schema"><?php esc_html_e( 'Additional schema.org (JSON-LD)', 'mlab-seo' ); ?></label>
			<textarea class="widefat code" rows="5" id="mlab-seo-schema" name="mlab_seo[schema]" placeholder='{ "@type": "FAQPage", "mainEntity": [] }'><?php echo esc_textarea( Post_Meta::get( $id, 'schema' ) ); ?></textarea>
			<span class="description"><?php esc_html_e( 'One node or a list of nodes. Invalid JSON is discarded on save.', 'mlab-seo' ); ?></span></p>
	</fieldset>
</div>
