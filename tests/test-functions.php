<?php
/**
 * Test internal functions.
 *
 * @author Iron Bound Designs
 * @since  1.0
 */
use IronBound\WP_Notifications\Template\Listener;

/**
 * Class ITELIC_Test_Functions
 */
class ITELIC_Test_Functions extends ITELIC_UnitTestCase {

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

	public function test_generate_key_for_transaction_product_simple_activation_limits() {

		$product     = $this->getMockBuilder( '\ITELIC\Product' )->disableOriginalConstructor()->getMock();
		$product->ID = 1;

		$product->expects( $this->once() )->method( 'get_feature' )->with(
			'licensing',
			array( 'field' => 'limit' )
		)->willReturn( 5 );

		$product->expects( $this->once() )->method( 'has_feature' )->with(
			'recurring-payments'
		)->willReturn( false );

		$transaction     = $this->getMockBuilder( '\IT_Exchange_Transaction' )->disableOriginalConstructor()->getMock();
		$transaction->ID = 1;
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

		WP_Mock::wpFunction( 'it_exchange_get_transaction', array(
			'args'   => array( 1 ),
			'times'  => 1,
			'return' => $transaction
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

		$product->expects( $this->once() )->method( 'has_feature' )->with(
			'recurring-payments'
		)->willReturn( false );

		$transaction     = $this->getMockBuilder( '\IT_Exchange_Transaction' )->disableOriginalConstructor()->getMock();
		$transaction->ID = 1;
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

		WP_Mock::wpFunction( 'it_exchange_get_transaction', array(
			'args'   => array( 1 ),
			'times'  => 1,
			'return' => $transaction
		) );

		$key = \ITELIC\generate_key_for_transaction_product( $transaction, $product, $factory );

		$this->assertInstanceOf( '\ITELIC\Key', $key );
		$this->assertEquals( 2, $key->get_max() );
	}

	public function test_generate_key_for_transaction_product_lifetime() {

		$product     = $this->getMockBuilder( '\ITELIC\Product' )->disableOriginalConstructor()->getMock();
		$product->ID = 1;

		$product->expects( $this->once() )->method( 'get_feature' )->with(
			'licensing',
			array( 'field' => 'limit' )
		)->willReturn( 5 );

		$product->expects( $this->once() )->method( 'has_feature' )->with(
			'recurring-payments'
		)->willReturn( false );

		$transaction     = $this->getMockBuilder( '\IT_Exchange_Transaction' )->disableOriginalConstructor()->getMock();
		$transaction->ID = 1;
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

		WP_Mock::wpFunction( 'it_exchange_get_transaction', array(
			'args'   => array( 1 ),
			'times'  => 1,
			'return' => $transaction
		) );

		$key = \ITELIC\generate_key_for_transaction_product( $transaction, $product, $factory );

		$this->assertInstanceOf( '\ITELIC\Key', $key );
		$this->assertEquals( null, $key->get_expires() );
	}

	/**
	 * @depends test_convert_rp_to_date_interval
	 */
	public function test_generate_key_for_transaction_product_recurring_payments() {

		$product     = $this->getMockBuilder( '\ITELIC\Product' )->disableOriginalConstructor()->getMock();
		$product->ID = 1;

		$product->expects( $this->exactly( 3 ) )->method( 'get_feature' )->withConsecutive(
			array(
				'licensing',
				array( 'field' => 'limit' )
			),
			array(
				'recurring-payments',
				array( 'setting' => 'interval' )

			),
			array(
				'recurring-payments',
				array( 'setting' => 'interval-count' )

			)
		)->will( $this->onConsecutiveCalls( 2, 'year', 1 ) );

		$product->expects( $this->once() )->method( 'has_feature' )->with(
			'recurring-payments'
		)->willReturn( true );

		$transaction     = $this->getMockBuilder( '\IT_Exchange_Transaction' )->disableOriginalConstructor()->getMock();
		$transaction->ID = 1;
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

		WP_Mock::wpFunction( 'it_exchange_get_transaction', array(
			'args'   => array( 1 ),
			'times'  => 1,
			'return' => $transaction
		) );

		$key = \ITELIC\generate_key_for_transaction_product( $transaction, $product, $factory );

		$this->assertInstanceOf( '\ITELIC\Key', $key );
		$this->assertEquals( \ITELIC\make_date_time( '+1 year' )->getTimestamp(),
			$key->get_expires()->getTimestamp(), '', 5 );
	}

	public function test_shared_tags() {

		$listeners = \ITELIC\get_shared_tags();

		$tags = array_map( function ( Listener $listener ) {
			return $listener->get_tag();
		}, $listeners );

		$this->assertContains( 'full_customer_name', $tags,
			'full_customer_name tag is not registered.' );
		$this->assertContains( 'customer_first_name', $tags,
			'customer_first_name tag is not registered.' );
		$this->assertContains( 'customer_last_name', $tags,
			'customer_last_name tag is not registered.' );
		$this->assertContains( 'customer_email', $tags,
			'customer_email tag is not registered.' );
		$this->assertContains( 'store_name', $tags,
			'store_name tag is not registered.' );
	}

	public function test_generate_download_link() {

		$key = $this->getMockBuilder( '\ITELIC\Key' )->disableOriginalConstructor()->getMock();
		$key->method( 'get_key' )->willReturn( 'abcd-1234' );

		$activation = $this->getMockBuilder( '\ITELIC\Activation' )->disableOriginalConstructor()->getMock();
		$activation->method( 'get_pk' )->willReturn( 1 );
		$activation->method( 'get_key' )->willReturn( $key );

		$link = \ITELIC\generate_download_link( $activation );

		$this->assertEquals( 0, strpos( $link, 'http://example.org/itelic-api/download/' ),
			"Download link does not start with API Endpoint." );

		$args = array();
		parse_str( parse_url( $link, PHP_URL_QUERY ), $args );

		$this->assertArrayHasKey( 'activation', $args, 'activation query arg is missing.' );
		$this->assertArrayHasKey( 'key', $args, 'key query arg is missing.' );
		$this->assertArrayHasKey( 'expires', $args, 'expires query arg is missing.' );
		$this->assertArrayHasKey( 'token', $args, 'token query arg is missing.' );

		$this->assertEquals( 1, $args['activation'], 'activation query arg is incorrect' );
		$this->assertEquals( 'abcd-1234', $args['key'], 'key query arg is incorrect' );
		$this->assertEquals( \ITELIC\make_date_time( '+1 day' )->getTimestamp(), $args['expires'],
			'expires query arg is incorrect', 5 );
	}

	/**
	 * @depends test_generate_download_link
	 */
	public function test_download_link_args_are_valid() {

		$key = $this->getMockBuilder( '\ITELIC\Key' )->disableOriginalConstructor()->getMock();
		$key->method( 'get_key' )->willReturn( 'abcd-1234' );

		$activation = $this->getMockBuilder( '\ITELIC\Activation' )->disableOriginalConstructor()->getMock();
		$activation->method( 'get_pk' )->willReturn( 1 );
		$activation->method( 'get_key' )->willReturn( $key );

		$link = \ITELIC\generate_download_link( $activation );

		$args = array();
		parse_str( parse_url( $link, PHP_URL_QUERY ), $args );

		$this->assertTrue( \ITELIC\validate_query_args( $args ) );
	}

	/**
	 * @dataProvider missing_download_link_query_args_provider
	 */
	public function test_download_link_is_invalid_if_missing_query_args( $args, $expected ) {
		$this->assertEquals( $expected, \ITELIC\validate_query_args( $args ) );
	}

	public function missing_download_link_query_args_provider() {

		$key = $this->getMockBuilder( '\ITELIC\Key' )->disableOriginalConstructor()->getMock();
		$key->method( 'get_key' )->willReturn( 'abcd-1234' );

		$activation = $this->getMockBuilder( '\ITELIC\Activation' )->disableOriginalConstructor()->getMock();
		$activation->method( 'get_pk' )->willReturn( 1 );
		$activation->method( 'get_key' )->willReturn( $key );

		$link = \ITELIC\generate_download_link( $activation );

		$args = array();
		parse_str( parse_url( $link, PHP_URL_QUERY ), $args );

		$token   = $args['token'];
		$expires = $args['expires'];

		$test_cases = array();

		unset( $args['key'] );
		$test_cases['Link validates when missing key'] = array( $args, false );
		$args['key']                                   = 'abcd-1234';

		unset( $args['activation'] );
		$test_cases['Link validates when missing activation'] = array(
			$args,
			false
		);
		$args['activation']                                   = 1;

		unset( $args['expires'] );
		$test_cases['Link validates when missing expires'] = array(
			$args,
			false
		);
		$args['expires']                                   = $expires;

		unset( $args['token'] );
		$test_cases['Link validates when missing token'] = array(
			$args,
			false
		);
		$args['token']                                   = $token;

		return $test_cases;
	}

	public function test_download_link_is_invalid_if_past_expiration() {

		$key = $this->getMockBuilder( '\ITELIC\Key' )->disableOriginalConstructor()->getMock();
		$key->method( 'get_key' )->willReturn( 'abcd-1234' );

		$activation = $this->getMockBuilder( '\ITELIC\Activation' )->disableOriginalConstructor()->getMock();
		$activation->method( 'get_pk' )->willReturn( 1 );
		$activation->method( 'get_key' )->willReturn( $key );

		$args = \ITELIC\generate_download_query_args( $activation, \ITELIC\make_date_time( '-1 week' ) );

		$this->assertFalse( \ITELIC\validate_query_args( $args ) );
	}
}