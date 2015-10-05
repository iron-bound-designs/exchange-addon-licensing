<?php
/**
 * Test internal functions.
 *
 * @author Iron Bound Designs
 * @since  1.0
 */

/**
 * Class ITELIC_Test_Functions
 */
class ITELIC_Test_Functions extends ITELIC_UnitTestCase {

	public function test_generate_key_for_transaction_product_simple_activation_limits() {

		$product     = $this->getMockBuilder( '\ITELIC\Product' )->disableOriginalConstructor()->getMock();
		$product->ID = 1;

		$product->expects( $this->once() )->method( 'get_feature' )->with(
			'licensing',
			array( 'field' => 'limit' )
		)->willReturn( 5 );

		$transaction = $this->getMockBuilder( '\IT_Exchange_Transaction' )->disableOriginalConstructor()->getMock();
		$transaction->method( 'get_products' )->willReturn( array(
			array(
				'product_id' => 1
			)
		) );

		$customer     = $this->getMockBuilder( '\IT_Exchange_Customer' )->disableOriginalConstructor()->getMock();
		$customer->id = 1;

		$factory = $this->getMockBuilder( '\ITELIC\Key\Factory' )->disableOriginalConstructor()->getMock();
		$factory->method( 'make' )->willReturn( 'abcd-1234' );

		WP_Mock::wpFunction( 'it_exchange_get_transaction_customer', array(
			'args'   => array( $transaction ),
			'times'  => 1,
			'return' => $customer
		) );

		WP_Mock::wpFunction( 'itelic_get_product', array(
			'args'   => array( 1 ),
			'times'  => 1,
			'return' => $product
		) );

		$key = \ITELIC\generate_key_for_transaction_product( $transaction, $product, $factory );

		$this->assertInstanceOf( '\ITELIC\Key', $key );
		$this->assertEquals( 5, $key->get_max() );
	}

	public function test_generate_key_for_transaction_product_variant_activation_limits() {

		$product     = $this->getMockBuilder( '\ITELIC\Product' )->disableOriginalConstructor()->getMock();
		$product->ID = 1;

		$product->expects( $this->once() )->method( 'get_feature' )->with(
			'licensing',
			array(
				'field'    => 'limit',
				'for_hash' => 'var-hash'
			)
		)->willReturn( 2 );

		$transaction = $this->getMockBuilder( '\IT_Exchange_Transaction' )->disableOriginalConstructor()->getMock();
		$transaction->method( 'get_products' )->willReturn( array(
			array(
				'product_id'    => 1,
				'itemized_data' => serialize( array(
					'it_variant_combo_hash' => 'var-hash'
				) )
			)
		) );

		$customer     = $this->getMockBuilder( '\IT_Exchange_Customer' )->disableOriginalConstructor()->getMock();
		$customer->id = 1;

		$factory = $this->getMockBuilder( '\ITELIC\Key\Factory' )->disableOriginalConstructor()->getMock();
		$factory->method( 'make' )->willReturn( 'abcd-1234' );

		WP_Mock::wpFunction( 'it_exchange_get_transaction_customer', array(
			'args'   => array( $transaction ),
			'times'  => 1,
			'return' => $customer
		) );

		WP_Mock::wpFunction( 'itelic_get_product', array(
			'args'   => array( 1 ),
			'times'  => 1,
			'return' => $product
		) );

		$key = \ITELIC\generate_key_for_transaction_product( $transaction, $product, $factory );

		$this->assertInstanceOf( '\ITELIC\Key', $key );
		$this->assertEquals( 2, $key->get_max() );
	}

	/**
	 * @dataProvider rp_date_interval_provider
	 */
	public function test_convert_rp_to_date_interval( $type, $count, $expected ) {
		$this->assertEquals( $expected, \ITELIC\convert_rp_to_date_interval( $type, $count ) );
	}

	public function rp_date_interval_provider() {
		return array(
			array( 'day', '1', new DateInterval( 'P1D' ) ),
			array( 'day', '3', new DateInterval( 'P3D' ) ),
			array( 'week', '1', new DateInterval( 'P1W' ) ),
			array( 'week', '4', new DateInterval( 'P4W' ) ),
			array( 'month', '1', new DateInterval( 'P1M' ) ),
			array( 'month', '12', new DateInterval( 'P12M' ) ),
			array( 'year', '1', new DateInterval( 'P1Y' ) ),
			array( 'year', '10', new DateInterval( 'P10Y' ) ),
		);
	}
}