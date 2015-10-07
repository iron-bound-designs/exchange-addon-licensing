<?php
/**
 * Test the renewal discount object.
 *
 * @author      Iron Bound Designs
 * @since       1.0
 * @copyright   2015 (c) Iron Bound Designs.
 * @license     GPLv2
 */
use ITELIC\Renewal\Discount;

/**
 * Class ITELIC_Test_Renewal_Discount
 */
class ITELIC_Test_Renewal_Discount extends ITELIC_UnitTestCase {

	public function test_get_type() {

		$mock_product = $this->getMockBuilder( '\ITELIC\Product' )->disableOriginalConstructor()->getMock();
		$mock_product->method( 'get_feature' )->with( 'licensing-discount' )->willReturn( array(
			'type'   => Discount::TYPE_FLAT,
			'amount' => '5'
		) );

		$mock_key = $this->getMockBuilder( '\ITELIC\Key' )->disableOriginalConstructor()->getMock();
		$mock_key->method( 'get_product' )->willReturn( $mock_product );

		$discount = new Discount( $mock_key );

		$this->assertEquals( Discount::TYPE_FLAT, $discount->get_type() );
	}

	public function test_get_amount() {

		$mock_product = $this->getMockBuilder( '\ITELIC\Product' )->disableOriginalConstructor()->getMock();
		$mock_product->method( 'get_feature' )->with( 'licensing-discount' )->willReturn( array(
			'type'   => Discount::TYPE_FLAT,
			'amount' => '5'
		) );

		$mock_key = $this->getMockBuilder( '\ITELIC\Key' )->disableOriginalConstructor()->getMock();
		$mock_key->method( 'get_product' )->willReturn( $mock_product );

		$discount = new Discount( $mock_key );

		$this->assertEquals( 5.0, $discount->get_amount() );
	}

	public function test_get_amount_percent_formatted() {

		$mock_product = $this->getMockBuilder( '\ITELIC\Product' )->disableOriginalConstructor()->getMock();
		$mock_product->method( 'get_feature' )->with( 'licensing-discount' )->willReturn( array(
			'type'   => Discount::TYPE_PERCENT,
			'amount' => '5'
		) );

		$mock_key = $this->getMockBuilder( '\ITELIC\Key' )->disableOriginalConstructor()->getMock();
		$mock_key->method( 'get_product' )->willReturn( $mock_product );

		$discount = new Discount( $mock_key );

		$this->assertEquals( '5%', $discount->get_amount( true ) );
	}

	public function test_get_amount_flat_formatted() {

		$mock_product = $this->getMockBuilder( '\ITELIC\Product' )->disableOriginalConstructor()->getMock();
		$mock_product->method( 'get_feature' )->with( 'licensing-discount' )->willReturn( array(
			'type'   => Discount::TYPE_FLAT,
			'amount' => '5'
		) );

		$mock_key = $this->getMockBuilder( '\ITELIC\Key' )->disableOriginalConstructor()->getMock();
		$mock_key->method( 'get_product' )->willReturn( $mock_product );

		$discount = new Discount( $mock_key );

		WP_Mock::wpFunction( 'it_exchange_format_price', array(
			'times'  => 1,
			'args'   => array( 5.0 ),
			'return' => '$5.00'
		) );

		$this->assertEquals( '$5.00', $discount->get_amount( true ) );
	}

	public function test_expiry_days() {

		$mock_product = $this->getMockBuilder( '\ITELIC\Product' )->disableOriginalConstructor()->getMock();
		$mock_product->method( 'get_feature' )->with( 'licensing-discount' )->willReturn( array(
			'expiry' => 30
		) );

		$mock_key = $this->getMockBuilder( '\ITELIC\Key' )->disableOriginalConstructor()->getMock();
		$mock_key->method( 'get_product' )->willReturn( $mock_product );

		$discount = new Discount( $mock_key );

		$this->assertEquals( 30, $discount->get_expiry_days() );
	}

	public function test_discount_is_valid_if_no_discount_expiry_limit() {

		$mock_product = $this->getMockBuilder( '\ITELIC\Product' )->disableOriginalConstructor()->getMock();
		$mock_product->method( 'get_feature' )->with( 'licensing-discount' )->willReturn( array(
			'expiry' => ''
		) );

		$mock_key = $this->getMockBuilder( '\ITELIC\Key' )->disableOriginalConstructor()->getMock();
		$mock_key->method( 'get_product' )->willReturn( $mock_product );

		$discount = new Discount( $mock_key );

		$this->assertTrue( $discount->is_discount_valid() );
	}

}