<?php
/**
 * Test the list key type.
 *
 * @author      Iron Bound Designs
 * @since       1.0
 * @copyright   2015 (c) Iron Bound Designs.
 * @license     GPLv2
 */
use ITELIC\Key\Generator\From_List;
use ITELIC\Key\Generator\Random;

/**
 * Class ITELIC_Test_Key_Types_List
 */
class ITELIC_Test_Key_Types_List extends ITELIC_UnitTestCase {

	public function test_first_key_from_list_is_used() {

		$product = $this->getMockBuilder( '\ITELIC\Product' )->disableOriginalConstructor()->getMock();
		$product->method( 'update_feature' );

		$customer = $this->getMockBuilder( '\IT_Exchange_Customer' )->disableOriginalConstructor()->getMock();
		$txn      = $this->getMockBuilder( '\IT_Exchange_Transaction' )->disableOriginalConstructor()->getMock();

		$options = array(
			'keys' => 'abcd-1234' . PHP_EOL . 'efgh-5678' . PHP_EOL . 'ijkl-9012' . PHP_EOL
		);

		$random = new From_List( new Random() );
		$key    = $random->generate( $options, $product, $customer, $txn );

		$this->assertEquals( 'abcd-1234', $key );
	}

	public function test_key_removed_from_list() {

		$product = $this->getMockBuilder( '\ITELIC\Product' )->disableOriginalConstructor()->getMock();
		$product->method( 'update_feature' )->with( 'licensing', array(
			'keys' => 'efgh-5678' . PHP_EOL . 'ijkl-9012'
		) );

		$customer = $this->getMockBuilder( '\IT_Exchange_Customer' )->disableOriginalConstructor()->getMock();
		$txn      = $this->getMockBuilder( '\IT_Exchange_Transaction' )->disableOriginalConstructor()->getMock();

		$options = array(
			'keys' => 'abcd-1234' . PHP_EOL . 'efgh-5678' . PHP_EOL . 'ijkl-9012' . PHP_EOL
		);

		$random = new From_List( new Random() );
		$key    = $random->generate( $options, $product, $customer, $txn );

		$this->assertEquals( 'abcd-1234', $key );
	}

	public function test_key_still_generated_if_no_keys_in_list() {

		$product  = $this->getMockBuilder( '\ITELIC\Product' )->disableOriginalConstructor()->getMock();
		$customer = $this->getMockBuilder( '\IT_Exchange_Customer' )->disableOriginalConstructor()->getMock();
		$txn      = $this->getMockBuilder( '\IT_Exchange_Transaction' )->disableOriginalConstructor()->getMock();

		$random = new From_List( new Random() );
		$key    = $random->generate( array(), $product, $customer, $txn );

		$this->assertEquals( 32, strlen( $key ) );
	}
}