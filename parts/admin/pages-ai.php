<?php
/**
 * Pages, AI suggestions: the approval list. Check, approve, reject, and translate after approval.
 *
 * @package MomentumLab\SEO
 * @var bool $pro Pro active.
 */

namespace MomentumLab\SEO;

defined( 'ABSPATH' ) || exit;
// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- Included inside Admin::part(), the variables are local.

$queue       = AI_Suggestions::pending();
$can_translate = count( Multilingual::languages() ) > 1;
?>
<ol class="mlab-seo-flow">
	<li><strong><?php esc_html_e( 'Draft', 'mlab-seo' ); ?></strong> <?php esc_html_e( 'The AI writes up to 3 variants. Nothing changes on the website.', 'mlab-seo' ); ?></li>
	<li><strong><?php esc_html_e( 'Check', 'mlab-seo' ); ?></strong> <?php esc_html_e( 'Compare with the current texts and choose a variant.', 'mlab-seo' ); ?></li>
	<li><strong><?php esc_html_e( 'Approve', 'mlab-seo' ); ?></strong> <?php esc_html_e( 'The variant becomes the SEO title and description. Or reject it.', 'mlab-seo' ); ?></li>
	<li><strong><?php esc_html_e( 'Translate', 'mlab-seo' ); ?></strong> <?php esc_html_e( 'Translations are drafts as well and appear here for approval.', 'mlab-seo' ); ?></li>
</ol>
<?php if ( ! $queue ) : ?>
	<p><span class="mlab-seo-badge is-good"><?php esc_html_e( 'Good', 'mlab-seo' ); ?></span> <?php esc_html_e( 'No drafts are waiting. Open a page and press «Write with AI» in the SEO box.', 'mlab-seo' ); ?></p>
<?php endif; ?>
<?php foreach ( $queue as $queue_id ) : ?>
	<?php
	$draft = AI_Suggestions::get( $queue_id );
	if ( ! $draft || ! current_user_can( 'edit_post', $queue_id ) ) {
		continue;
	}
	$is_translation = 'translate' === $draft['kind'];
	?>
	<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="mlab-seo-card mlab-seo-draft">
		<input type="hidden" name="action" value="mlab_seo_ai_decide" />
		<input type="hidden" name="post_id" value="<?php echo (int) $queue_id; ?>" />
		<?php wp_nonce_field( 'mlab_seo_ai_decide' ); ?>
		<h3>
			<a href="<?php echo esc_url( (string) get_edit_post_link( $queue_id ) ); ?>"><?php echo esc_html( Context::plain_title( $queue_id ) ); ?></a>
			<span class="mlab-seo-tag"><?php echo esc_html( $is_translation ? sprintf( /* translators: %s: language code. */ __( 'Translation (%s)', 'mlab-seo' ), $draft['lang'] ) : __( 'New text', 'mlab-seo' ) ); ?></span>
		</h3>
		<?php if ( $is_translation && ! empty( $draft['from'] ) ) : ?>
			<p class="mlab-seo-hint">
				<?php
				/* translators: %s: title of the original page. */
				echo esc_html( sprintf( __( 'Translated from the approved texts of «%s».', 'mlab-seo' ), Context::plain_title( (int) $draft['from'] ) ) );
				?>
			</p>
		<?php endif; ?>
		<p class="mlab-seo-hint">
			<?php esc_html_e( 'Current:', 'mlab-seo' ); ?>
			<?php echo esc_html( '' !== Post_Meta::get( $queue_id, 'title' ) ? Post_Meta::get( $queue_id, 'title' ) : __( '(no SEO title)', 'mlab-seo' ) ); ?>
			<br /><?php echo esc_html( '' !== Post_Meta::get( $queue_id, 'description' ) ? Post_Meta::get( $queue_id, 'description' ) : __( '(no description)', 'mlab-seo' ) ); ?>
		</p>
		<?php foreach ( $draft['variants'] as $variant_index => $variant ) : ?>
			<label class="mlab-seo-variant">
				<input type="radio" name="variant" value="<?php echo (int) $variant_index; ?>" <?php checked( 0, $variant_index ); ?> />
				<span>
					<strong><?php echo esc_html( $variant['title'] ); ?></strong>
					<small><?php echo esc_html( sprintf( /* translators: %d: number of characters. */ __( '%d characters', 'mlab-seo' ), mb_strlen( $variant['title'] ) ) ); ?></small><br />
					<?php echo esc_html( $variant['description'] ); ?>
					<small><?php echo esc_html( sprintf( /* translators: %d: number of characters. */ __( '%d characters', 'mlab-seo' ), mb_strlen( $variant['description'] ) ) ); ?></small>
				</span>
			</label>
		<?php endforeach; ?>
		<p class="mlab-seo-steps">
			<button type="submit" name="decision" value="approve" class="mlab-seo-btn is-primary"><?php esc_html_e( 'Approve', 'mlab-seo' ); ?></button>
			<?php if ( ! $is_translation && $can_translate ) : ?>
				<button type="submit" name="decision" value="approve_translate" class="mlab-seo-btn"><?php esc_html_e( 'Approve and create translation drafts', 'mlab-seo' ); ?></button>
			<?php endif; ?>
			<button type="submit" name="decision" value="reject" class="mlab-seo-btn"><?php esc_html_e( 'Reject', 'mlab-seo' ); ?></button>
		</p>
	</form>
<?php endforeach; ?>
