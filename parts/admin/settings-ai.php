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
		<?php $source = AI::key_source( $provider_key ); ?>
		<div class="mlab-seo-row is-stack" data-ai-key="<?php echo esc_attr( $provider_key ); ?>">
			<span class="mlab-seo-dot is-<?php echo 'none' === $source ? 'ok' : 'good'; ?>"></span>
			<div class="mlab-seo-row__label">
				<?php echo esc_html( $provider['label'] ); ?>
				<small>
					<?php
					if ( 'constant' === $source ) {
						/* translators: 1: end of the key, 2: name of the constant. */
						echo esc_html( sprintf( __( 'Key %1$s from wp-config.php (%2$s). It is used before a key saved here.', 'mlab-seo' ), AI::key_hint( $provider_key ), $provider['constant'] ) );
					} elseif ( 'saved' === $source ) {
						/* translators: %s: end of the key. */
						echo esc_html( sprintf( __( 'Key %s is saved, encrypted. It is never shown in full again.', 'mlab-seo' ), AI::key_hint( $provider_key ) ) );
					} else {
						esc_html_e( 'No key yet. Paste the key of your account below.', 'mlab-seo' );
					}
					?>
				</small>
			</div>
			<?php if ( 'constant' !== $source ) : ?>
				<input type="password" autocomplete="off" spellcheck="false" data-ai-key-input="<?php echo esc_attr( $provider_key ); ?>" placeholder="<?php echo esc_attr( 'claude' === $provider_key ? 'sk-ant-…' : 'sk-…' ); ?>" aria-label="<?php echo esc_attr( $provider['label'] ); ?>" />
				<button type="button" class="mlab-seo-btn is-primary" data-ai-key-save="<?php echo esc_attr( $provider_key ); ?>"><?php esc_html_e( 'Save key', 'mlab-seo' ); ?></button>
				<?php if ( 'saved' === $source ) : ?>
					<button type="button" class="mlab-seo-btn" data-ai-key-remove="<?php echo esc_attr( $provider_key ); ?>"><?php esc_html_e( 'Remove key', 'mlab-seo' ); ?></button>
				<?php endif; ?>
			<?php endif; ?>
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
<p class="mlab-seo-hint"><?php esc_html_e( 'The key is stored encrypted with the salts of this website and only administrators can change it. For more control, define it in wp-config.php instead: MLAB_SEO_ANTHROPIC_KEY or MLAB_SEO_OPENAI_KEY.', 'mlab-seo' ); ?></p>

<h2 class="mlab-seo-section"><?php esc_html_e( 'What is sent', 'mlab-seo' ); ?></h2>
<p class="mlab-seo-hint"><?php esc_html_e( 'For a draft: the website name, the language, the page title, the current SEO texts, the focus keyword and the first 6,000 characters of the page text. For a translation: only the approved SEO title and description. No user data, no form entries. Please add the provider to your privacy policy. The log keeps the time, user, number of characters and result of the last 100 requests, never the texts or the key.', 'mlab-seo' ); ?></p>
