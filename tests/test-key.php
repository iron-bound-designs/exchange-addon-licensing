<?php
/**
 * Unit tests pertaining to license keys.
 *
 * @author Iron Bound Designs
 * @since  1.0
 */

use ITELIC\Key;

/**
 * Class ITELIC_Test_Key
 */
class ITELIC_Test_Key extends ITELIC_UnitTestCase {

	public function test_invalid_key_length_is_rejected() {

		$stub_txn      = $this->getMockBuilder( '\IT_Exchange_Transaction' )->disableOriginalConstructor()->getMock();
		$stub_prod     = $this->getMockBuilder( '\IT_Exchange_Product' )->disableOriginalConstructor()->getMock();
		$stub_customer = $this->getMockBuilder( '\IT_Exchange_Customer' )->disableOriginalConstructor()->getMock();

		$this->setExpectedException( '\InvalidArgumentException' );

		Key::create( str_repeat( '-', 129 ), $stub_txn, $stub_prod, $stub_customer, 5 );
	}

	public function test_data_to_cache() {

		/** @var Key $key */
		$key = $this->key_factory->create_and_get( array(
			'product'  => $this->product_factory->create(),
			'customer' => 1
		) );

		$data = $key->get_data_to_cache();

		$this->assertArrayHasKey( 'lkey', $data, 'lkey not cached.' );
		$this->assertArrayHasKey( 'transaction_id', $data, 'transaction_id not cached.' );
		$this->assertArrayHasKey( 'product', $data, 'product not cached.' );
		$this->assertArrayHasKey( 'customer', $data, 'customer not cached.' );
		$this->assertArrayHasKey( 'status', $data, 'status not cached.' );
		$this->assertArrayHasKey( 'max', $data, 'max not cached.' );
		$this->assertArrayHasKey( 'expires', $data, 'expires not cached.' );

	}

	public function test_extending_key_does_not_alter_expiration_date_for_lifetime_keys() {

		$product = $this->product_factory->create();

		/** @var Key $key */
		$key = $this->key_factory->create_and_get( array(
			'product'  => $product,
			'customer' => 1
		) );

		$this->assertEquals( null, $key->get_expires(), "Lifetime key's expiration date is not null." );

		$key->extend();

		$this->assertEquals( null, $key->get_expires(), "Key expiration date set." );
	}

	public function test_renewing_lifetime_key_throws_exception() {

		/** @var Key $key */
		$key = $this->key_factory->create_and_get( array(
			'product'  => $this->product_factory->create(),
			'customer' => 1
		) );

		$this->setExpectedException( 'UnexpectedValueException' );
		$key->renew();
	}

	public function test_expiring_license_updates_status() {

		/** @var Key $key */
		$key = $this->key_factory->create_and_get( array(
			'product'  => $this->product_factory->create(),
			'customer' => 1
		) );

		$key->expire();

		$this->assertEquals( Key::EXPIRED, $key->get_status(), "Status was not set to expired." );
	}

	public function test_expiring_license_updates_expiration_date() {

		/** @var Key $key */
		$key = $this->key_factory->create_and_get( array(
			'product'  => $this->product_factory->create(),
			'customer' => 1
		) );

		$key->expire();

		$this->assertEquals( \ITELIC\make_date_time(), $key->get_expires() );
	}

	public function test_extending_key_updates_expiration_date() {

		/** @var Key $key */
		$key = $this->key_factory->create_and_get( array(
			'product'  => $this->product_factory->create( array(
				'interval'       => 'month',
				'interval-count' => 1
			) ),
			'customer' => 1
		) );

		$this->assertEquals( \ITELIC\make_date_time( '+1 month' )->getTimestamp(), $key->get_expires()->getTimestamp(),
			'Expiry date is incorrectly set from recurring payments interval.', 5 );

		$this->assertEquals( \ITELIC\make_date_time( '+2 month' )->getTimestamp(), $key->extend()->getTimestamp(),
			'Expiry date is incorrectly extended.', 5 );
	}

	/**
	 * @depends test_extending_key_updates_expiration_date
	 */
	public function test_renewing_key_extends_expiration_date() {

		/** @var Key $key */
		$key = $this->key_factory->create_and_get( array(
			'product'  => $this->product_factory->create( array(
				'interval'       => 'month',
				'interval-count' => 1
			) ),
			'customer' => 1
		) );

		$key->renew();

		$this->assertEquals( \ITELIC\make_date_time( '+2 month' )->getTimestamp(), $key->get_expires()->getTimestamp(),
			"Key::renew did not extend the license key's expiration.", 5 );
	}

	public function test_renewing_key_updates_status_when_future_expiration_date() {

		/** @var Key $key */
		$key = $this->key_factory->create_and_get( array(
			'product'  => $this->product_factory->create( array(
				'interval'       => 'month',
				'interval-count' => 1
			) ),
			'customer' => 1
		) );

		$key->set_status( Key::EXPIRED );

		$key->renew();

		$this->assertEquals( Key::ACTIVE, $key->get_status(), "Key::renew did not update the status." );
	}

	public function test_renewing_key_does_not_update_status_when_past_expiration_date() {

		/** @var Key $key */
		$key = $this->key_factory->create_and_get( array(
			'product'  => $this->product_factory->create( array(
				'interval'       => 'month',
				'interval-count' => 1
			) ),
			'customer' => 1
		) );

		$key->set_status( Key::EXPIRED );
		$key->set_expires( \ITELIC\make_date_time( '-1 year' ) );

		$key->renew();

		$this->assertEquals( Key::EXPIRED, $key->get_status(), "Key::renew updated status, despite date in the past." );
	}

	public function test_renew_key_creates_renewal_record() {

		/** @var Key $key */
		$key = $this->key_factory->create_and_get( array(
			'product'  => $this->product_factory->create( array(
				'interval'       => 'month',
				'interval-count' => 1
			) ),
			'customer' => 1
		) );

		$renew = $key->renew();

		$this->assertInstanceOf( '\ITELIC\Renewal', $renew );
	}

	public function test_renew_key_creates_renewal_record_with_correct_expiration_date() {

		/** @var Key $key */
		$key = $this->key_factory->create_and_get( array(
			'product'  => $this->product_factory->create( array(
				'interval'       => 'month',
				'interval-count' => 1
			) ),
			'customer' => 1
		) );

		$expired = $key->get_expires();

		$renew = $key->renew();

		$this->assertEquals( $expired, $renew->get_key_expired_date() );
	}

	public function test_renew_key_with_transaction_sets_revenue() {

		$stub_txn = $this->getMockBuilder( '\IT_Exchange_Transaction' )->getMock();

		$product = $this->product_factory->create_and_get( array(
			'interval'       => 'month',
			'interval-count' => 1
		) );

		$stub_customer     = $this->getMockBuilder( '\IT_Exchange_Customer' )->disableOriginalConstructor()->getMock();
		$stub_customer->id = 1;

		$key = Key::create( 'key', $stub_txn, $product, $stub_customer, 5, \ITELIC\make_date_time( '+1 year' ) );

		$renewal_txn = $this->getMockBuilder( '\IT_Exchange_Transaction' )->getMock();
		$renewal_txn->method( 'get_products' )->willReturn( array(
			array(
				'product_id'       => $product->ID,
				'product_subtotal' => '29.95'
			)
		) );

		$renewal = $key->renew( $renewal_txn );

		$this->assertEquals( '29.95', $renewal->get_revenue() );
	}

	public function test_statuses_exist() {

		$statuses = Key::get_statuses();

		$this->assertArrayHasKey( 'active', $statuses, 'Active status does not exist.' );
		$this->assertArrayHasKey( 'disabled', $statuses, 'Disable status does not exist.' );
		$this->assertArrayHasKey( 'expired', $statuses, 'Expired status does not exist.' );
	}

	public function test_set_status_rejects_invalid_status() {

		/** @var Key $key */
		$key = $this->key_factory->create_and_get( array(
			'product'  => $this->product_factory->create(),
			'customer' => 1
		) );

		$key->set_status( 'disabled' );
		$this->assertEquals( 'disabled', $key->get_status(), 'Valid status option was rejected.' );

		$this->setExpectedException( '\InvalidArgumentException' );

		$key->set_status( 'garbage' );
	}
}