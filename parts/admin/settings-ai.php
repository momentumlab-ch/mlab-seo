<?php
/**
 * Settings, AI writing: provider, key status, limit, confirmation, use this month.
 *
 * @package MomentumLab\SEO
 * @var array $s   Settings.
 * @var bool  $pro Pro active.
 */

namespace MomentumLab\SEO;

defined( 'ABSPATH' ) || exit;
// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- Included inside Admin::part(), the variables are local.

?>
<p class="mlab-seo-hint"><?php esc_html_e( 'Writes drafts for SEO titles and descriptions with your own account at Claude or OpenAI. A draft changes nothing until a person approves it. Translations of approved texts also wait for approval. This is a Pro feature.', 'mlab-seo' ); ?></p>
<?php if ( ! $pro ) : ?>
	<p class="mlab-seo-hint is-note"><?php esc_html_e( 'AI writing needs Pro.', 'mlab-seo' ); ?></p>
<?php endif; ?>
<table class="form-table" role="presentation">
	<?php Fields::rows( array( 'ai_provider', 'ai_model', 'ai_limit', 'ai_consent' ) ); ?>
</table>

<h2 class="mlab-seo-section"><?php esc_html_e( 'Keys and use', 'mlab-seo' ); ?></h2>
<div class="mlab-seo-group">
	<?php foreach ( AI::providers() as $provider_key => $provider ) : ?>
		<?php $found = defined( $provider['constant'] ) && '' !== (string) constant( $provider['constant'] ); ?>
		<div class="mlab-seo-row">
			<span class="mlab-seo-dot is-<?php echo $found ? 'good' : 'ok'; ?>"></span>
			<div class="mlab-seo-row__label">
				<?php echo esc_html( $provider['label'] ); ?>
				<small>
					<?php
					/* translators: %s: name of a constant in wp-config.php. */
					echo esc_html( $found ? __( 'Key found.', 'mlab-seo' ) : sprintf( __( 'No key. Add define( \'%s\', \'...\' ); to wp-config.php.', 'mlab-seo' ), $provider['constant'] ) );
					?>
				</small>
			</div>
		</div>
	<?php endforeach; ?>
	<div class="mlab-seo-row">
		<span class="mlab-seo-dot is-<?php echo AI::enabled() ? 'good' : 'ok'; ?>"></span>
		<div class="mlab-seo-row__label">
			<?php
			/* translators: 1: characters used this month, 2: monthly limit. */
			echo esc_html( sprintf( __( 'This month: %1$s of %2$s characters.', 'mlab-seo' ), number_format_i18n( AI::used() ), number_format_i18n( AI::limit() ) ) );
			?>
			<small><?php echo esc_html( AI::enabled() ? __( 'AI writing is on.', 'mlab-seo' ) : __( 'AI writing is off until Pro, a provider, its key and the confirmation are set.', 'mlab-seo' ) ); ?></small>
		</div>
	</div>
</div>

<h2 class="mlab-seo-section"><?php esc_html_e( 'What is sent', 'mlab-seo' ); ?></h2>
<p class="mlab-seo-hint"><?php esc_html_e( 'For a draft: the website name, the language, the page title, the current SEO texts, the focus keyword and the first 6,000 characters of the page text. For a translation: only the approved SEO title and description. No user data, no form entries. Please add the provider to your privacy policy. The log keeps the time, user, number of characters and result of the last 100 requests, never the texts or the key.', 'mlab-seo' ); ?></p>
