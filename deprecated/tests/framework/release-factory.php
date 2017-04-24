<?php
/**
 * Release factory.
 *
 * @author Iron Bound Designs
 * @since  1.0
 */

/**
 * Class ITELIC_UnitTest_Factory_For_Releases
 */
class ITELIC_UnitTest_Factory_For_Releases extends WP_UnitTest_Factory_For_Thing {

	function create_object( $args ) {

		$activation = itelic_create_release( $args );

		if ( is_wp_error( $activation ) ) {
			return $activation;
		}

		return $activation->get_pk();
	}

	function update_object( $object, $fields ) {
		throw new UnexpectedValueException( __CLASS__ . '::update_object not implemented.' );
	}

	function get_object_by_id( $object_id ) {
		return itelic_get_release( $object_id );
	}
}