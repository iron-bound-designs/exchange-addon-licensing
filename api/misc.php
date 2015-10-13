<?php
/**
 * Miscellaneous API functions.
 *
 * @author    Iron Bound Designs
 * @since     1.0
 * @license   AGPL
 * @copyright Iron Bound Designs, 2015.
 */

/**
 * Get all Exchange products that have licensing enabled.
 *
 * @since 1.0
 *
 * @param array $args
 *
 * @return \ITELIC\Product[]
 */
function itelic_get_products_with_licensing_enabled( $args = array() ) {

	$args['meta_query'][] = array(
		'key'   => '_it_exchange_itelic_enabled',
		'value' => true
	);

	$args['show_hidden']    = true;
	$args['posts_per_page'] = - 1;

	return array_map( function ( IT_Exchange_Product $product ) {
		return itelic_get_product( $product->ID );
	}, it_exchange_Get_products( $args ) );
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