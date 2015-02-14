<?php
/**
 * Load key types.
 *
 * @author Iron Bound Designs
 * @since  1.0
 */

/**
 * Register the 'pattern' key type.
 *
 * @since 1.0
 */
function itelic_register_key_pattern_type() {
	itelic_register_key_type( 'pattern', __( "Pattern", ITELIC::SLUG ), 'ITELIC_Key_Generator_Pattern' );
}

add_action( 'it_exchange_itelic_register_key_types', 'itelic_register_key_pattern_type' );

/**
 * Register the 'random' key type.
 *
 * @since 1.0
 */
function itelic_register_key_random_type() {
	itelic_register_key_type( 'random', __( "Random", ITELIC::SLUG ), 'ITELIC_Key_Generator_Random' );
}

add_action( 'it_exchange_itelic_register_key_types', 'itelic_register_key_random_type' );

/**
 * Fires when key types should be registered.
 *
 * @since 1.0
 */
do_action( 'it_exchange_itelic_register_key_types' );