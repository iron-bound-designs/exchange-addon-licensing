<?php
/**
 * Test the random key type.
 *
 * @author      Iron Bound Designs
 * @since       1.0
 * @copyright   2015 (c) Iron Bound Designs.
 * @license     GPLv2
 */
use ITELIC\Key\Generator\Random;

/**
 * Class ITELIC_Test_Key_Types_Random
 */
class ITELIC_Test_Key_Types_Random extends ITELIC_UnitTestCase {

	public function test_default_length_is_32() {

		$product  = $this->getMockBuilder( '\ITELIC\Product' )->disableOriginalConstructor()->getMock();
		$customer = $this->getMockBuilder( '\IT_Exchange_Customer' )->disableOriginalConstructor()->getMock();
		$txn      = $this->getMockBuilder( '\IT_Exchange_Transaction' )->disableOriginalConstructor()->getMock();

		$random = new Random();
		$key    = $random->generate( array(), $product, $customer, $txn );

		$this->assertEquals( 32, strlen( $key ) );
	}

	public function test_exception_thrown_if_invalid_length() {

		$product  = $this->getMockBuilder( '\ITELIC\Product' )->disableOriginalConstructor()->getMock();
		$customer = $this->getMockBuilder( '\IT_Exchange_Customer' )->disableOriginalConstructor()->getMock();
		$txn      = $this->getMockBuilder( '\IT_Exchange_Transaction' )->disableOriginalConstructor()->getMock();

		$random = new Random();

		$this->setExpectedException( '\InvalidArgumentException' );
		$random->generate( array( 'length' => - 1 ), $product, $customer, $txn );
	}

	public function test_passed_length_is_used() {

		$product  = $this->getMockBuilder( '\ITELIC\Product' )->disableOriginalConstructor()->getMock();
		$customer = $this->getMockBuilder( '\IT_Exchange_Customer' )->disableOriginalConstructor()->getMock();
		$txn      = $this->getMockBuilder( '\IT_Exchange_Transaction' )->disableOriginalConstructor()->getMock();

		$random = new Random();
		$key    = $random->generate( array( 'length' => 16 ), $product, $customer, $txn );

		$this->assertEquals( 16, strlen( $key ) );
	}
}