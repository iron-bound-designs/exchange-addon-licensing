<?php
/**
 * Load key types.
 *
 * @author Iron Bound Designs
 * @since  1.0
 */

namespace ITELIC\Key;

use ITELIC\Key\Generator\From_List;
use ITELIC\Key\Generator\Pattern;
use ITELIC\Key\Generator\Random;
use ITELIC\Plugin;

/**
 * Register the 'pattern' key type.
 *
 * @since 1.0
 */
function register_key_pattern_type() {
	itelic_register_key_type( 'pattern', __( "Pattern", Plugin::SLUG ), new Pattern() );
}

add_action( 'it_exchange_itelic_register_key_types', __NAMESPACE__ . '\\register_key_pattern_type' );

/**
 * Register the 'random' key type.
 *
 * @since 1.0
 */
function register_key_random_type() {
	itelic_register_key_type( 'random', __( "Random", Plugin::SLUG ), new Random() );
}

add_action( 'it_exchange_itelic_register_key_types', __NAMESPACE__ . '\\register_key_random_type' );

/**
 * Register the 'list' key type.
 *
 * @since 1.0
 */
function register_key_list_type() {
	itelic_register_key_type( 'list', __( "From List", Plugin::SLUG ), new From_List( new Random() ) );
}

add_action( 'it_exchange_itelic_register_key_types', __NAMESPACE__ . '\\register_key_list_type' );

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
		<br> <?php _e( "Enter '\\\\' to get the backslash character.", Plugin::SLUG ); ?>
	</p>

	<?php

}

add_action( 'it_exchange_itelic_render_key_type_pattern_settings', __NAMESPACE__ . '\\render_key_type_pattern_settings', 10, 3 );

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
		'length' => '32'
	);
	$values   = \ITUtility::merge_defaults( $values, $defaults );
	?>

	<label for="itelic-key-type-random"><?php _e( "Key Length", Plugin::SLUG ); ?></label>
	<input type="number" min="1" max="128" id="itelic-key-type-random" name="<?php echo $prefix; ?>[length]" value="<?php echo esc_attr( $values['length'] ); ?>">

	<p class="description">
		<?php _e( "Choose a key length.", Plugin::SLUG ); ?>
	</p>

	<?php

}

add_action( 'it_exchange_itelic_render_key_type_random_settings', __NAMESPACE__ . '\\render_key_type_random_settings', 10, 3 );

/**
 * Output the settings form for the 'list' key type.
 *
 * @since 1.0
 *
 * @param int    $product
 * @param string $prefix
 * @param array  $values
 */
function render_key_type_list_settings( $product, $prefix, $values = array() ) {

	$defaults = array(
		'keys' => ''
	);
	$values   = \ITUtility::merge_defaults( $values, $defaults );

	?>
	<label for="itelic-key-type-list"><?php _e( "License Keys", Plugin::SLUG ); ?></label>
	<textarea id="itelic-key-type-list" name="<?php echo $prefix; ?>[keys]"><?php echo $values['keys']; ?></textarea>
	<p class="description">
		<?php _e( "Enter in license keys, one per line. If empty, a key will be randomly generated.", Plugin::SLUG ); ?>
	</p>

	<?php
}

add_action( 'it_exchange_itelic_render_key_type_list_settings', __NAMESPACE__ . '\\render_key_type_list_settings', 10, 3 );