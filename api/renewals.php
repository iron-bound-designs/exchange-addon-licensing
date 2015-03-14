<?php
/**
 * API Renewal Functions
 *
 * @author Iron Bound Designs
 * @since  1.0
 */

/**
 * Get a renewal record.
 *
 * @since 1.0
 *
 * @param int $id
 *
 * @return ITELIC_Renewal
 */
function itelic_get_renewal_record( $id ) {
	return ITELIC_Renewal::from_id( $id );
}

/**
 * Generate an automatic renewal URL.
 *
 * @since 1.0
 *
 * @param ITELIC_Key $key
 *
 * @return string
 */
function itelic_generate_auto_renewal_url( ITELIC_Key $key ) {

	$product_link = get_permalink( $key->get_product()->ID );

	$args = array(
		'renew_key' => $key->get_key()
	);

	return add_query_arg( $args, $product_link );
}