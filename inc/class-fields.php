<?php
/**
 * Form rows of the settings screen, built from the definitions in inc/options.php.
 *
 * @package MomentumLab\SEO
 */

namespace MomentumLab\SEO;

defined( 'ABSPATH' ) || exit;

/**
 * Prints table rows for settings by key.
 */
final class Fields {

	/**
	 * Prints the rows of several settings.
	 *
	 * @param string[] $keys Setting keys.
	 */
	public static function rows( array $keys ) {
		foreach ( $keys as $key ) {
			self::row( $key );
		}
	}

	/**
	 * Prints one row with label, input and help text.
	 *
	 * @param string $key   Setting key.
	 * @param array  $extra Overrides for the definition, for example a help text with a value.
	 */
	public static function row( $key, array $extra = array() ) {
		$option = array_merge( mlab_seo_options()[ $key ], $extra );
		$label  = esc_html( $option['label'] ?? '' );
		if ( in_array( $option['type'], array( 'text', 'email', 'tel', 'textarea', 'code', 'select' ), true ) ) {
			$label = '<label for="mlab-seo-' . esc_attr( $key ) . '">' . $label . '</label>';
		}
		echo '<tr><th scope="row">' . $label . ( ! empty( $option['pro'] ) ? ' <span class="mlab-seo__pro">Pro</span>' : '' ) . '</th><td>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped above.
		self::input( $key, $option );
		if ( ! empty( $option['help'] ) ) {
			echo '<p class="description">' . esc_html( $option['help'] ) . '</p>';
		}
		echo '</td></tr>';
	}

	/**
	 * Prints the input of a setting.
	 *
	 * @param string $key    Setting key.
	 * @param array  $option Definition.
	 */
	private static function input( $key, array $option ) {
		$value = Settings::get( $key );
		$name  = 'mlab_seo[' . $key . ']';
		switch ( $option['type'] ) {
			case 'checkbox':
				printf( '<label><input type="checkbox" name="%s" value="1" %s %s /> %s</label>', esc_attr( $name ), checked( (bool) $value, true, false ), disabled( ! empty( $option['disabled'] ), true, false ), esc_html( $option['text'] ?? '' ) );
				return;
			case 'image':
				self::image( $name, (int) $value );
				return;
			case 'select':
				self::select( $key, $name, (string) $value, $option['choices'] );
				return;
			case 'textarea':
			case 'code':
				printf( '<textarea class="large-text %s" rows="%d" id="mlab-seo-%s" name="%s">%s</textarea>', 'code' === $option['type'] ? 'code' : '', (int) ( $option['rows'] ?? 3 ), esc_attr( $key ), esc_attr( $name ), esc_textarea( (string) $value ) );
				return;
		}
		printf( '<input type="%s" class="regular-text" id="mlab-seo-%s" name="%s" value="%s" placeholder="%s" />', esc_attr( $option['type'] ), esc_attr( $key ), esc_attr( $name ), esc_attr( (string) $value ), esc_attr( $option['placeholder'] ?? '' ) );
	}

	/**
	 * Prints a select box.
	 *
	 * @param string $key     Setting key.
	 * @param string $name    Field name.
	 * @param string $value   Current value.
	 * @param array  $choices Value => label.
	 */
	public static function select( $key, $name, $value, array $choices ) {
		printf( '<select id="mlab-seo-%s" name="%s">', esc_attr( $key ), esc_attr( $name ) );
		foreach ( $choices as $choice => $label ) {
			printf( '<option value="%s" %s>%s</option>', esc_attr( (string) $choice ), selected( $value, (string) $choice, false ), esc_html( $label ) );
		}
		echo '</select>';
	}

	/**
	 * Prints an image picker.
	 *
	 * @param string $name  Field name.
	 * @param int    $value Attachment ID.
	 */
	public static function image( $name, $value ) {
		$img = Context::image( $value, 'medium' );
		?>
		<div class="mlab-seo-picker">
			<div class="mlab-seo-picker__preview"><?php echo $img ? '<img src="' . esc_url( $img['url'] ) . '" alt="" />' : ''; ?></div>
			<input type="hidden" name="<?php echo esc_attr( $name ); ?>" value="<?php echo esc_attr( $value ? (string) $value : '' ); ?>" />
			<button type="button" class="button mlab-seo-picker__pick"><?php esc_html_e( 'Choose image', 'mlab-seo' ); ?></button>
			<button type="button" class="button-link mlab-seo-picker__remove" <?php echo $img ? '' : 'hidden'; ?>><?php esc_html_e( 'Remove image', 'mlab-seo' ); ?></button>
		</div>
		<?php
	}
}
