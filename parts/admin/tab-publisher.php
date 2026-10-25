<?php
/**
 * Publisher tab: who is behind the website.
 *
 * @package MomentumLab\SEO
 */

namespace MomentumLab\SEO;

defined( 'ABSPATH' ) || exit;
// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- Included inside Admin, the variables are local.

?>
<p><?php esc_html_e( 'Who is behind the website. Google uses this for the knowledge panel, AI assistants to answer questions about you.', 'mlab-seo' ); ?></p>
<table class="form-table" role="presentation">
	<?php Fields::rows( array( 'org_type', 'org_name', 'org_alt_name', 'org_logo', 'org_email', 'org_phone', 'org_street', 'org_zip', 'org_city', 'org_region', 'org_country', 'org_hours', 'org_lat', 'org_lng', 'org_price_range', 'org_same_as', 'org_extra' ) ); ?>
</table>
