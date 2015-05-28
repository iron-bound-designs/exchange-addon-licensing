<?php
/**
 * Load key types.
 *
 * @author Iron Bound Designs
 * @since  1.0
 */

namespace ITELIC\Key;
use ITELIC\Plugin;

/**
 * Register the 'pattern' key type.
 *
 * @since 1.0
 */
function register_key_pattern_type() {
	itelic_register_key_type( 'pattern', __( "Pattern", Plugin::SLUG ), 'ITELIC_Key_Generator_Pattern' );
}

add_action( 'it_exchange_itelic_register_key_types', 'ITELIC\Key\register_key_pattern_type' );

/**
 * Register the 'random' key type.
 *
 * @since 1.0
 */
function register_key_random_type() {
	itelic_register_key_type( 'random', __( "Random", Plugin::SLUG ), 'ITELIC_Key_Generator_Random' );
}

add_action( 'it_exchange_itelic_register_key_types', 'ITELIC\Key\register_key_random_type' );

/**
 * Fires when key types should be registered.
 *
 * @since 1.0
 */
do_action( 'it_exchange_itelic_register_key_types' );

/**
 * Output the settings form for the 'pattern' key type.
 *
 * @since 1.0
 *
 * @param int    $product
 * @param string $prefix
 * @param array  $values
 */
function render_key_type_pattern_settings( $product, $prefix, $values = array() ) {

	$defaults = array(
		'pattern' => ''
	);
	$values   = \ITUtility::merge_defaults( $values, $defaults );
	?>

	<label for="itelic-key-type-pattern"><?php _e( "Key Pattern", Plugin::SLUG ); ?></label>
	<input type="text" id="itelic-key-type-pattern" name="<?php echo $prefix; ?>[pattern]" value="<?php echo esc_attr( $values['pattern'] ); ?>">

	<p class="description">
		<?php _e( "Setup a pattern for your license key.", Plugin::SLUG ); ?>
	</p>

	<ul>
		<li><em>X</em> – <?php _e( "A-Z Capital Letters", Plugin::SLUG ); ?></li>
		<li><em>x</em> – <?php _e( "a-z Lowercase Letters", Plugin::SLUG ); ?></li>
		<li><em>9</em> – <?php _e( "Digits 0-9", Plugin::SLUG ); ?></li>
		<li><em>#</em> – <?php _e( "Special Chars: !@#$%^&*()+=[]/", Plugin::SLUG ); ?></li>
		<li><em>?</em> – <?php _e( "Any valid character ( X, x, 9, #, ? )", Plugin::SLUG ); ?></li>
	</ul>

	<p class="description">
		<?php _e( "Preface X, X, 9, #, ? with a '\\' to get the literal character, without substitution.", Plugin::SLUG ); ?>
		<br>
		<?php _e( "Enter '\\\\' to get the backslash character.", Plugin::SLUG ); ?>
	</p>

	<?php

}

add_action( 'it_exchange_itelic_render_key_type_pattern_settings', 'ITELIC\Key\render_key_type_pattern_settings', 10, 3 );

/**
 * Output the settings form for the 'random' key type.
 *
 * @since 1.0
 *
 * @param int    $product
 * @param string $prefix
 * @param array  $values
 */
function render_key_type_random_settings( $product, $prefix, $values = array() ) {

	$defaults = array(
		'length' => ''
	);
	$values   = \ITUtility::merge_defaults( $values, $defaults );
	?>

	<label for="itelic-key-type-random"><?php _e( "Key Length", Plugin::SLUG ); ?></label>
	<input type="number" min="1" id="itelic-key-type-random" name="<?php echo $prefix; ?>[length]" value="<?php echo esc_attr( $values['length'] ); ?>">

	<p class="description">
		<?php _e( "Choose a key length.", Plugin::SLUG ); ?>
	</p>

	<?php

}

add_action( 'it_exchange_itelic_render_key_type_random_settings', 'ITELIC\Key\render_key_type_random_settings', 10, 3 );