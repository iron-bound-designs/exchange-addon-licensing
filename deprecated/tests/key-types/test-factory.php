<?php
/**
 * Test the key factory.
 *
 * @author      Iron Bound Designs
 * @since       1.0
 * @copyright   2015 (c) Iron Bound Designs.
 * @license     GPLv2
 */

/**
 * Class ITELIC_Test_Key_Types_Factory
 */
class ITELIC_Test_Key_Types_Factory extends ITELIC_UnitTestCase {

	public function test_key_type_options_passed_to_generator() {

		$options = array(
			'a' => 'b'
		);

		$product = $this->getMockBuilder( '\ITELIC\Product' )->disableOriginalConstructor()->getMock();
		$product->method( 'get_feature' )->willReturnMap( array(
			array( 'licensing', array( 'field' => 'key-type' ), 'type' ),
			array( 'licensing', array( 'field' => 'type.type' ), $options )
		) );

		$customer    = $this->getMockBuilder( '\IT_Exchange_Customer' )->disableOriginalConstructor()->getMock();
		$transaction = $this->getMockBuilder( '\IT_Exchange_Transaction' )->disableOriginalConstructor()->getMock();

		$generator = $this->getMockBuilder( 'ITELIC\Key\Generator' )->getMock();
		$generator->method( 'generate' )->with(
			$options,
			$product,
			$customer,
			$transaction
		)->willReturn( 'abcd-1234' );

		WP_Mock::wpFunction( 'itelic_get_key_type_generator', array(
			'times'  => 1,
			'args'   => array( 'type' ),
			'return' => $generator
		) );

		$factory = new \ITELIC\Key\Factory( $product, $customer, $transaction );

		$this->assertEquals( 'abcd-1234', $factory->make() );
	}

	public function test_exception_thrown_if_invalid_key_type() {

		$options = array(
			'a' => 'b'
		);

		$product = $this->getMockBuilder( '\ITELIC\Product' )->disableOriginalConstructor()->getMock();
		$product->method( 'get_feature' )->willReturnMap( array(
			array( 'licensing', array( 'field' => 'key-type' ), 'type' ),
			array( 'licensing', array( 'field' => 'type.type' ), $options )
		) );

		$customer    = $this->getMockBuilder( '\IT_Exchange_Customer' )->disableOriginalConstructor()->getMock();
		$transaction = $this->getMockBuilder( '\IT_Exchange_Transaction' )->disableOriginalConstructor()->getMock();

		WP_Mock::wpFunction( 'itelic_get_key_type_generator', array(
			'times'  => 1,
			'args'   => array( 'type' ),
			'return' => null
		) );

		$factory = new \ITELIC\Key\Factory( $product, $customer, $transaction );

		$this->setExpectedException( '\UnexpectedValueException' );

		$factory->make();
	}
}