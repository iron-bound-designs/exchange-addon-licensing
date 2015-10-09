<?php
/**
 * Test the key types service locator.
 *
 * @author Iron Bound Designs
 * @since  1.0
 */

/**
 * Class ITELIC_Test_Key_Types
 */
class ITELIC_Test_Key_Types extends ITELIC_UnitTestCase {

	public function test_get_key_type_name() {

		$unique = md5( uniqid() );

		itelic_register_key_type( $unique, 'Type Name', 'Class' );

		$this->assertEquals( 'Type Name', itelic_get_key_type_name( $unique ) );
	}

	public function test_get_key_type_class() {

		$unique = md5( uniqid() );

		itelic_register_key_type( $unique, 'Type Name', 'Class' );

		$this->assertEquals( 'Class', itelic_get_key_type_class( $unique ) );
	}

	/**
	 * @depends test_get_key_type_class
	 */
	public function test_cannot_register_key_type_more_than_once() {

		$unique = md5( uniqid() );

		$this->assertTrue( itelic_register_key_type( $unique, 'Name', 'Class_A' ) );
		$this->assertFalse( itelic_register_key_type( $unique, 'Name', 'Class_B' ) );

		$this->assertEquals( 'Class_A', itelic_get_key_type_class( $unique ) );
	}

	/**
	 * @depends test_get_key_type_class
	 */
	public function test_get_key_type_class_must_be_overridden_with_a_sub_class() {

		$unique = md5( uniqid() );

		$this->assertTrue( itelic_register_key_type( $unique, 'Name', 'Class_A' ) );

		$this->getMock( 'Class_A' );
		$this->getMock( 'Class_B' );

		add_filter( 'it_exchange_itelic_get_key_type_class', function ( $class, $slug ) use ( $unique ) {

			if ( $slug === $unique ) {
				$class = 'Class_B';
			}

			return $class;
		}, 10, 2 );

		$this->assertEquals( 'Class_A', itelic_get_key_type_class( $unique ) );
	}

	public function test_built_in_key_types_are_registered() {

		$types = itelic_get_key_types();

		$this->assertArrayHasKey( 'random', $types, 'Random key type not registered.' );
		$this->assertArrayHasKey( 'pattern', $types, 'Pattern key type not registered.' );
		$this->assertArrayHasKey( 'list', $types, 'List key type not registered.' );
	}
}