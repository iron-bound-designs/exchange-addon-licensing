<?php
/**
 * API Methods for interacting with key types.
 *
 * @author Iron Bound Designs
 * @since  1.0
 */

/**
 * Register a key type.
 *
 * @api
 *
 * @since 1.0
 *
 * @param string $slug
 * @param string $name
 * @param string $class
 *
 * @return boolean
 */
function itelic_register_key_type( $slug, $name, $class ) {
	return ITELIC_Key_Types::register( $slug, $name, $class );
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
	return ITELIC_Key_Types::all();
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

	$type = ITELIC_Key_Types::get( $key_type );

	return is_array( $type ) ? $type['name'] : false;
}

/**
 * Get a key type class.
 *
 * @api
 *
 * @since 1.0
 *
 * @param string $slug
 *
 * @return string|bool
 */
function itelic_get_payout_method_class( $slug ) {
	$key_type = ITELIC_Key_Types::get( $slug );
	$class    = is_array( $key_type ) ? $key_type['class'] : false;

	/**
	 * Filter the class that corresponds to a key type.
	 *
	 * This allows for add-ons to substitute their own subclass, if they wish,
	 * for a particular key type.
	 *
	 * The returned class name from the filter MUST be a subclass of the original class.
	 * If the returned class name is not a subclass of the original class, then the original class will be returned.
	 *
	 * @since 1.0
	 *
	 * @param string $class
	 * @param string $slug of requested class.
	 */
	$filtered = apply_filters( 'it_exchange_itelic_get_payout_method_class', $class, $slug );

	if ( $filtered != $class ) {

		try {
			$reflection_class = new ReflectionClass( $filtered );

			if ( $reflection_class->isSubclassOf( $class ) ) {
				$class = $filtered;
			}
		}
		catch ( Exception $e ) {

		}
	}

	return $class;
}