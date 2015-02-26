<?php
/**
 * Activation methods.
 *
 * @author Iron Bound Designs
 * @since  1.0
 */

/**
 * Get an activation.
 *
 * @since 1.0
 *
 * @param int $id
 *
 * @return ITELIC_Activation
 */
function itelic_get_activation( $id ) {
	return ITELIC_Activation::with_id( $id );
}

/**
 * Get an activation from data from the DB.
 *
 * @since 1.0
 *
 * @param stdClass $data
 *
 * @return ITELIC_Activation
 */
function itelic_get_activation_from_data( stdClass $data ) {
	return new ITELIC_Activation( $data );
}