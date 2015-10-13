<?php
/**
 * API Methods for interacting with key types.
 *
 * @author    Iron Bound Designs
 * @since     1.0
 * @license   AGPL
 * @copyright Iron Bound Designs, 2015.
 */

use ITELIC\Key\Generator;

/**
 * Register a key type.
 *
 * @api
 *
 * @since 1.0
 *
 * @param string    $slug
 * @param string    $name
 * @param Generator $generator
 *
 * @return boolean
 */
function itelic_register_key_type( $slug, $name, Generator $generator ) {
	return \ITELIC\Key\Types::register( $slug, $name, $generator );
}

/**
 * Get all registered key types.
 *
 * @api
 *
 * @since 1.0
 *
 * @return array
 */
function itelic_get_key_types() {
	return \ITELIC\Key\Types::all();
}

/**
 * Get the name of a key type.
 *
 * @api
 *
 * @since 1.0
 *
 * @param string $key_type The slug of the key type.
 *
 * @return string|bool
 */
function itelic_get_key_type_name( $key_type ) {

	$type = \ITELIC\Key\Types::get( $key_type );

	return is_array( $type ) ? $type['name'] : false;
}

/**
 * Get a key type generator.
 *
 * @api
 *
 * @since 1.0
 *
 * @param string $slug
 *
 * @return Generator|Null
 */
function itelic_get_key_type_generator( $slug ) {
	$key_type  = \ITELIC\Key\Types::get( $slug );
	$generator = is_array( $key_type ) ? $key_type['generator'] : false;

	/**
	 * Filter the generator that corresponds to a key type.
	 *
	 * This allows for add-ons to substitute their own subclass, if they wish,
	 * for a particular key type.
	 *
	 * The returned generator from the filter MUST be a subclass of the original class.
	 * If the returned generator is not a subclass of the original class, then the original class will be returned.
	 *
	 * @since 1.0
	 *
	 * @param Generator $generator
	 * @param string    $slug of requested class.
	 */
	$filtered = apply_filters( 'itelic_get_key_type_generator', $generator, $slug );

	if ( is_subclass_of( $filtered, get_class( $generator ) ) ) {
		$generator = $filtered;
	}

	return $generator;
}