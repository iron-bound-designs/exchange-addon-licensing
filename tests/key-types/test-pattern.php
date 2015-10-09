<?php
/**
 * Test the pattern key type.
 *
 * @author      Iron Bound Designs
 * @since       1.0
 * @copyright   2015 (c) Iron Bound Designs.
 * @license     GPLv2
 */
use ITELIC\Key\Generator\Pattern;

/**
 * Class ITELIC_Test_Key_Types_Pattern
 */
class ITELIC_Test_Key_Types_Pattern extends ITELIC_UnitTestCase {

	public function test_exception_thrown_if_no_pattern() {

		$product  = $this->getMockBuilder( '\ITELIC\Product' )->disableOriginalConstructor()->getMock();
		$customer = $this->getMockBuilder( '\IT_Exchange_Customer' )->disableOriginalConstructor()->getMock();
		$txn      = $this->getMockBuilder( '\IT_Exchange_Transaction' )->disableOriginalConstructor()->getMock();

		$this->setExpectedException( '\InvalidArgumentException' );

		$pattern = new Pattern();
		$pattern->generate( array(), $product, $customer, $txn );
	}

	public function test_literal() {

		$product  = $this->getMockBuilder( '\ITELIC\Product' )->disableOriginalConstructor()->getMock();
		$customer = $this->getMockBuilder( '\IT_Exchange_Customer' )->disableOriginalConstructor()->getMock();
		$txn      = $this->getMockBuilder( '\IT_Exchange_Transaction' )->disableOriginalConstructor()->getMock();

		$options = array(
			'pattern' => 'X\?9'
		);

		$pattern = new Pattern();
		$key     = $pattern->generate( $options, $product, $customer, $txn );

		$this->assertEquals( '?', substr( $key, 1, 1 ) );
	}
}