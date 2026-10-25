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
			<p><a class="button button-primary" href="<?php echo esc_url( admin_url( 'admin.php?page=mlab-seo#license' ) ); ?>"><?php esc_html_e( 'Activate Pro', 'mlab-seo' ); ?></a></p>
		</div>
	<?php endif; ?>
	<fieldset class="mlab-seo__event" <?php disabled( ! $pro ); ?>>
		<legend class="mlab-seo__label"><?php esc_html_e( 'Event on this page', 'mlab-seo' ); ?></legend>
		<p class="description"><?php esc_html_e( 'Leave the name empty when the page has no event. Use the same name and URL on several pages to describe one event.', 'mlab-seo' ); ?></p>
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

		<p><label class="mlab-seo__label" for="mlab-seo-schema"><?php esc_html_e( 'Additional schema.org (JSON-LD)', 'mlab-seo' ); ?></label>
			<textarea class="widefat code" rows="5" id="mlab-seo-schema" name="mlab_seo[schema]" placeholder='{ "@type": "FAQPage", "mainEntity": [] }'><?php echo esc_textarea( Post_Meta::get( $id, 'schema' ) ); ?></textarea>
			<span class="description"><?php esc_html_e( 'One node or a list of nodes. Invalid JSON is discarded on save.', 'mlab-seo' ); ?></span></p>
	</fieldset>
</div>
