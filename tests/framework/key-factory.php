<?php
/**
 * Key factory.
 *
 * @author      Iron Bound Designs
 * @since       1.0
 * @copyright   2015 (c) Iron Bound Designs.
 * @license     GPLv2
 */

/**
 * Class ITELIC_UnitTest_Factory_For_Keys
 */
class ITELIC_UnitTest_Factory_For_Keys extends WP_UnitTest_Factory_For_Thing {

	function create_object( $args ) {
		$key = itelic_create_key( $args );

		if ( is_wp_error( $key ) ) {
			return $key;
		}

		return $key->get_key();
	}

	function update_object( $object, $fields ) {
		throw new UnexpectedValueException( "ITELIC_Key_Factory::update_object not implemented." );
	}

	function get_object_by_id( $object_id ) {
		return itelic_get_key( $object_id );
	}
}