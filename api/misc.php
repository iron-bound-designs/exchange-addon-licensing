<?php
/**
 * Miscellaneous API functions.
 *
 * @author Iron Bound Designs
 * @since  1.0
 */

/**
 * Get all Exchange products that have licensing enabled.
 *
 * @since 1.0
 *
 * @return \IT_Exchange_Product[]
 */
function itelic_get_products_with_licensing_enabled() {

	$args['meta_query'][] = array(
		'key'   => '_it_exchange_itelic_enabled',
		'value' => true
	);

	$args['show_hidden']    = true;
	$args['posts_per_page'] = - 1;

	return it_exchange_get_products( $args );
}

/**
 * Retrieve a product.
 *
 * @since 1.0
 *
 * @param int $ID
 *
 * @return \ITELIC\Product|null
 */
function itelic_get_product( $ID ) {
	return \ITELIC\Product::get( $ID );
}