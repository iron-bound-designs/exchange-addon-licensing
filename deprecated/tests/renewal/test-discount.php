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
 *
 * @group renewal
 */
class ITELIC_Test_Renewal_Discount extends ITELIC_UnitTestCase {

	/**
	 * Do custom initialization.
	 */
	public function setUp() {
		parent::setUp();

		$this->_toggle_global_renewal_discount( true );
	}

	/**
	 * Tear down after each test.
	 */
	public function tearDown() {
		parent::tearDown();

		$this->_toggle_global_renewal_discount( false );
	}

	/**
	 * Toggle whether renewal discounts should be enabled globally.
	 *
	 * @param bool $enable
	 */
	protected function _toggle_global_renewal_discount( $enable ) {

		$options                             = it_exchange_get_option( 'addon_itelic', true, true );
		$options['enable-renewal-discounts'] = $enable;
		it_exchange_save_option( 'addon_itelic', $options );
		it_exchange_get_option( 'addon_itelic', true, true );
	}

	public function test_is_disabled_if_globally_disabled() {

		$this->_toggle_global_renewal_discount( false );

		$mock_product = $this->getMockBuilder( '\ITELIC\Product' )->disableOriginalConstructor()->getMock();
		$mock_product->method( 'get_feature' )->with( 'licensing-discount' )->willReturn( array(
			'type'   => Discount::TYPE_FLAT,
			'amount' => '5'
		) );

		$mock_key = $this->getMockBuilder( '\ITELIC\Key' )->disableOriginalConstructor()->getMock();
		$mock_key->method( 'get_product' )->willReturn( $mock_product );

		$discount = new Discount( $mock_key );

		$this->assertTrue( $discount->is_disabled() );
	}

	public function test_is_disabled_if_disabled_for_product() {

		$mock_product = $this->getMockBuilder( '\ITELIC\Product' )->disableOriginalConstructor()->getMock();
		$mock_product->method( 'get_feature' )->with( 'licensing-discount' )->willReturn( array(
			'type'    => Discount::TYPE_FLAT,
			'amount'  => '5',
			'disable' => true
		) );

		$mock_key = $this->getMockBuilder( '\ITELIC\Key' )->disableOriginalConstructor()->getMock();
		$mock_key->method( 'get_product' )->willReturn( $mock_product );

		$discount = new Discount( $mock_key );

		$this->assertTrue( $discount->is_disabled() );
	}

	public function test_is_not_disabled_if_enabled_on_product_but_disabled_globally() {

		$this->_toggle_global_renewal_discount( false );

		$mock_product = $this->getMockBuilder( '\ITELIC\Product' )->disableOriginalConstructor()->getMock();
		$mock_product->method( 'get_feature' )->with( 'licensing-discount' )->willReturn( array(
			'type'     => Discount::TYPE_FLAT,
			'amount'   => '5',
			'override' => true
		) );

		$mock_key = $this->getMockBuilder( '\ITELIC\Key' )->disableOriginalConstructor()->getMock();
		$mock_key->method( 'get_product' )->willReturn( $mock_product );

		$discount = new Discount( $mock_key );

		$this->assertFalse( $discount->is_disabled() );
	}

	/**
	 * @depends test_is_disabled_if_globally_disabled
	 */
	public function test_discount_amount_is_0_if_disabled() {

		$this->_toggle_global_renewal_discount( false );

		$mock_product = $this->getMockBuilder( '\ITELIC\Product' )->disableOriginalConstructor()->getMock();
		$mock_product->method( 'get_feature' )->with( 'licensing-discount' )->willReturn( array(
			'type'   => Discount::TYPE_FLAT,
			'amount' => '5'
		) );

		$mock_key = $this->getMockBuilder( '\ITELIC\Key' )->disableOriginalConstructor()->getMock();
		$mock_key->method( 'get_product' )->willReturn( $mock_product );

		$discount = new Discount( $mock_key );

		$this->assertEquals( 0, $discount->get_amount() );
	}

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

	public function test_discount_is_valid_if_now_is_before_key_expiration() {

		$mock_product = $this->getMockBuilder( '\ITELIC\Product' )->disableOriginalConstructor()->getMock();
		$mock_product->method( 'get_feature' )->with( 'licensing-discount' )->willReturn( array(
			'expiry' => 30
		) );

		$mock_key = $this->getMockBuilder( '\ITELIC\Key' )->disableOriginalConstructor()->getMock();
		$mock_key->method( 'get_product' )->willReturn( $mock_product );
		$mock_key->method( 'get_expires' )->willReturn( \ITELIC\make_date_time( 'tomorrow' ) );

		$discount = new Discount( $mock_key );

		$this->assertTrue( $discount->is_discount_valid() );
	}

	public function test_discount_is_valid_if_now_is_after_key_expiration_and_before_discount_expiration() {

		$mock_product = $this->getMockBuilder( '\ITELIC\Product' )->disableOriginalConstructor()->getMock();
		$mock_product->method( 'get_feature' )->with( 'licensing-discount' )->willReturn( array(
			'expiry' => 30
		) );

		$mock_key = $this->getMockBuilder( '\ITELIC\Key' )->disableOriginalConstructor()->getMock();
		$mock_key->method( 'get_product' )->willReturn( $mock_product );
		$mock_key->method( 'get_expires' )->willReturn( \ITELIC\make_date_time( 'yesterday' ) );

		$discount = new Discount( $mock_key );

		$this->assertTrue( $discount->is_discount_valid() );
	}

	public function test_discount_is_valid_if_now_is_less_than_key_expiration_and_diff_is_greater_than_discount_days_validity() {

		$mock_product = $this->getMockBuilder( '\ITELIC\Product' )->disableOriginalConstructor()->getMock();
		$mock_product->method( 'get_feature' )->with( 'licensing-discount' )->willReturn( array(
			'expiry' => 30
		) );

		$mock_key = $this->getMockBuilder( '\ITELIC\Key' )->disableOriginalConstructor()->getMock();
		$mock_key->method( 'get_product' )->willReturn( $mock_product );
		$mock_key->method( 'get_expires' )->willReturn( \ITELIC\make_date_time( '+2 month' ) );

		$discount = new Discount( $mock_key );

		$this->assertTrue( $discount->is_discount_valid() );
	}

	public function test_discount_is_invalid_if_now_is_after_discount_expiration() {

		$mock_product = $this->getMockBuilder( '\ITELIC\Product' )->disableOriginalConstructor()->getMock();
		$mock_product->method( 'get_feature' )->with( 'licensing-discount' )->willReturn( array(
			'expiry' => 30
		) );

		$mock_key = $this->getMockBuilder( '\ITELIC\Key' )->disableOriginalConstructor()->getMock();
		$mock_key->method( 'get_product' )->willReturn( $mock_product );
		$mock_key->method( 'get_expires' )->willReturn( \ITELIC\make_date_time( '- 2 months' ) );

		$discount = new Discount( $mock_key );

		$this->assertFalse( $discount->is_discount_valid() );
	}

	public function test_get_amount_paid() {

		$mock_product = $this->getMockBuilder( '\ITELIC\Product' )->disableOriginalConstructor()->getMock();
		$mock_product->method( 'get_feature' )->will( $this->returnValueMap( array(
			array( 'licensing-discount', array(), array() ),
			array(
				'base-price',
				array(),
				'75.00'
			)
		) ) );
		$mock_product->ID = 1;

		$mock_txn = $this->getMockBuilder( '\IT_Exchange_Transaction' )->disableOriginalConstructor()->getMock();
		$mock_txn->method( 'get_products' )->willReturn( array(
			array(
				'product_id'       => 1,
				'product_subtotal' => '100.00'
			)
		) );

		$mock_key = $this->getMockBuilder( '\ITELIC\Key' )->disableOriginalConstructor()->getMock();
		$mock_key->method( 'get_product' )->willReturn( $mock_product );
		$mock_key->method( 'get_transaction' )->willReturn( $mock_txn );

		$discount = new Discount( $mock_key );

		$this->assertEquals( '100.00', $discount->get_amount_paid() );
	}

	public function test_get_amount_paid_if_txn_product_cannot_be_found() {

		$mock_product = $this->getMockBuilder( '\ITELIC\Product' )->disableOriginalConstructor()->getMock();
		$mock_product->method( 'get_feature' )->will( $this->returnValueMap( array(
			array( 'licensing-discount', array(), array() ),
			array(
				'base-price',
				array(),
				'75.00'
			)
		) ) );
		$mock_product->ID = 1;

		$mock_txn = $this->getMockBuilder( '\IT_Exchange_Transaction' )->disableOriginalConstructor()->getMock();
		$mock_txn->method( 'get_products' )->willReturn( array() );

		$mock_key = $this->getMockBuilder( '\ITELIC\Key' )->disableOriginalConstructor()->getMock();
		$mock_key->method( 'get_product' )->willReturn( $mock_product );
		$mock_key->method( 'get_transaction' )->willReturn( $mock_txn );

		$discount = new Discount( $mock_key );

		$this->assertEquals( '75.00', $discount->get_amount_paid() );
	}

	/**
	 * @depends test_get_amount_paid
	 */
	public function test_get_discount_price_flat() {

		$mock_product = $this->getMockBuilder( '\ITELIC\Product' )->disableOriginalConstructor()->getMock();
		$mock_product->method( 'get_feature' )->will( $this->returnValueMap( array(
			array(
				'licensing-discount',
				array(),
				array(
					'amount' => '20',
					'type'   => Discount::TYPE_FLAT
				)
			),
			array(
				'base-price',
				array(),
				'75.00'
			)
		) ) );
		$mock_product->ID = 1;

		$mock_txn = $this->getMockBuilder( '\IT_Exchange_Transaction' )->disableOriginalConstructor()->getMock();
		$mock_txn->method( 'get_products' )->willReturn( array(
			array(
				'product_id'       => 1,
				'product_subtotal' => '100.00'
			)
		) );

		$mock_key = $this->getMockBuilder( '\ITELIC\Key' )->disableOriginalConstructor()->getMock();
		$mock_key->method( 'get_product' )->willReturn( $mock_product );
		$mock_key->method( 'get_transaction' )->willReturn( $mock_txn );

		$discount = new Discount( $mock_key );

		$this->assertEquals( '80.00', $discount->get_discount_price() );
	}

	/**
	 * @depends test_get_amount_paid
	 */
	public function test_get_discount_price_percent() {

		$mock_product = $this->getMockBuilder( '\ITELIC\Product' )->disableOriginalConstructor()->getMock();
		$mock_product->method( 'get_feature' )->will( $this->returnValueMap( array(
			array(
				'licensing-discount',
				array(),
				array(
					'amount' => '30',
					'type'   => Discount::TYPE_PERCENT
				)
			),
			array(
				'base-price',
				array(),
				'75.00'
			)
		) ) );
		$mock_product->ID = 1;

		$mock_txn = $this->getMockBuilder( '\IT_Exchange_Transaction' )->disableOriginalConstructor()->getMock();
		$mock_txn->method( 'get_products' )->willReturn( array(
			array(
				'product_id'       => 1,
				'product_subtotal' => '100.00'
			)
		) );

		$mock_key = $this->getMockBuilder( '\ITELIC\Key' )->disableOriginalConstructor()->getMock();
		$mock_key->method( 'get_product' )->willReturn( $mock_product );
		$mock_key->method( 'get_transaction' )->willReturn( $mock_txn );

		$discount = new Discount( $mock_key );

		$this->assertEquals( '70.00', $discount->get_discount_price() );
	}

	/**
	 * @depends test_get_discount_price_flat
	 */
	public function test_get_discount_price_formatted() {

		$mock_product = $this->getMockBuilder( '\ITELIC\Product' )->disableOriginalConstructor()->getMock();
		$mock_product->method( 'get_feature' )->will( $this->returnValueMap( array(
			array(
				'licensing-discount',
				array(),
				array(
					'amount' => '20',
					'type'   => Discount::TYPE_FLAT
				)
			),
			array(
				'base-price',
				array(),
				'75.00'
			)
		) ) );
		$mock_product->ID = 1;

		$mock_txn = $this->getMockBuilder( '\IT_Exchange_Transaction' )->disableOriginalConstructor()->getMock();
		$mock_txn->method( 'get_products' )->willReturn( array(
			array(
				'product_id'       => 1,
				'product_subtotal' => '100.00'
			)
		) );

		$mock_key = $this->getMockBuilder( '\ITELIC\Key' )->disableOriginalConstructor()->getMock();
		$mock_key->method( 'get_product' )->willReturn( $mock_product );
		$mock_key->method( 'get_transaction' )->willReturn( $mock_txn );

		WP_Mock::wpFunction( 'it_exchange_format_price', array(
			'times'  => 1,
			'args'   => array( '80.00' ),
			'return' => function ( $price ) {
				return '$' . number_format( $price, 2 );
			}
		) );

		$discount = new Discount( $mock_key );

		$this->assertEquals( '$80.00', $discount->get_discount_price( true ) );
	}

	public function test_serialize() {

		$mock_product = $this->getMockBuilder( '\ITELIC\Product' )->disableOriginalConstructor()->getMock();
		$mock_product->method( 'get_feature' )->with( 'licensing-discount' )->willReturn( array(
			'type'   => Discount::TYPE_FLAT,
			'amount' => '5'
		) );
		$mock_product->ID = 1;

		$mock_key = $this->getMockBuilder( '\ITELIC\Key' )->disableOriginalConstructor()->getMock();
		$mock_key->method( 'get_product' )->willReturn( $mock_product );
		$mock_key->method( 'get_key' )->willReturn( 'abcd-1234' );

		WP_Mock::wpFunction( 'itelic_get_product', array(
			'times'  => 1,
			'args'   => array( 1 ),
			'return' => $mock_product
		) );

		WP_Mock::wpFunction( 'itelic_get_key', array(
			'times'  => 1,
			'args'   => array( 'abcd-1234' ),
			'return' => $mock_key
		) );

		$discount     = new Discount( $mock_key );
		$serialized   = serialize( $discount );
		$unserialized = unserialize( $serialized );

		$this->assertEquals( $discount, $unserialized );
	}
}