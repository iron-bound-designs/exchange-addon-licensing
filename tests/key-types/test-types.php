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

		$generator = $this->getMock( '\ITELIC\Key\Generator' );

		itelic_register_key_type( $unique, 'Type Name', $generator );

		$this->assertEquals( 'Type Name', itelic_get_key_type_name( $unique ) );
	}

	public function test_get_key_type_generator() {

		$unique = md5( uniqid() );

		$generator = $this->getMock( '\ITELIC\Key\Generator' );

		itelic_register_key_type( $unique, 'Type Name', $generator );

		$this->assertSame( $generator, itelic_get_key_type_generator( $unique ) );
	}

	/**
	 * @depends test_get_key_type_generator
	 */
	public function test_cannot_register_key_type_more_than_once() {

		$unique = md5( uniqid() );

		$g1 = $this->getMock( '\ITELIC\Key\Generator' );
		$g2 = $this->getMock( '\ITELIC\Key\Generator' );

		$this->assertTrue( itelic_register_key_type( $unique, 'Name', $g1 ) );
		$this->assertFalse( itelic_register_key_type( $unique, 'Name', $g2 ) );

		$this->assertSame( $g1, itelic_get_key_type_generator( $unique ) );
	}

	/**
	 * @depends test_get_key_type_generator
	 */
	public function test_get_key_type_class_must_be_overridden_with_a_sub_class() {

		$unique = md5( uniqid() );

		$g1 = $this->getMock( '\ITELIC\Key\Generator' );
		$g2 = $this->getMock( '\ITELIC\Key\Generator' );

		$this->assertTrue( itelic_register_key_type( $unique, 'Name', $g1 ) );

		add_filter( 'itelic_get_key_type_generator', function ( $generator, $slug ) use ( $unique, $g2 ) {

			if ( $slug === $unique ) {
				$generator = $g2;
			}

			return $generator;
		}, 10, 2 );

		$this->assertSame( $g1, itelic_get_key_type_generator( $unique ) );
	}

	public function test_built_in_key_types_are_registered() {

		$types = itelic_get_key_types();

		$this->assertArrayHasKey( 'random', $types, 'Random key type not registered.' );
		$this->assertArrayHasKey( 'pattern', $types, 'Pattern key type not registered.' );
		$this->assertArrayHasKey( 'list', $types, 'List key type not registered.' );
	}
}