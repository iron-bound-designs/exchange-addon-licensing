<?php
/**
 * Update factory.
 *
 * @author      Iron Bound Designs
 * @since       1.0
 * @copyright   2015 (c) Iron Bound Designs.
 * @license     GPLv2
 */

/**
 * Class ITELIC_UnitTest_Factory_For_Updates
 */
class ITELIC_UnitTest_Factory_For_Updates extends WP_UnitTest_Factory_For_Thing {

	function create_object( $args ) {
		$update = itelic_create_update( $args );

		if ( is_wp_error( $update ) ) {
			return $update;
		}

		return $update->get_pk();
	}

	function update_object( $object, $fields ) {
		throw new UnexpectedValueException( __CLASS__ . '::update_object not implemented.' );
	}

	function get_object_by_id( $object_id ) {
		return itelic_get_update( $object_id );
	}
}