<?php
/**
 * Unit tests pertaining to license keys.
 *
 * @author Iron Bound Designs
 * @since  1.0
 */

use ITELIC\Activation;
use ITELIC\Key;

/**
 * Class ITELIC_Test_Key
 */
class ITELIC_Test_Key extends ITELIC_UnitTestCase {

	public function test_invalid_key_length_is_rejected() {

		$stub_txn      = $this->getMockBuilder( '\IT_Exchange_Transaction' )->disableOriginalConstructor()->getMock();
		$stub_prod     = $this->getMockBuilder( '\ITELIC\Product' )->disableOriginalConstructor()->getMock();
		$stub_customer = $this->getMockBuilder( '\IT_Exchange_Customer' )->disableOriginalConstructor()->getMock();

		$this->setExpectedException( '\LengthException' );

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

	public function test_expiring_license_expires_activations() {

		/** @var Key $key1 */
		$key1 = $this->key_factory->create_and_get( array(
			'product'  => $this->product_factory->create(),
			'customer' => 1
		) );

		/** @var Key $key1 */
		$key2 = $this->key_factory->create_and_get( array(
			'product'  => $this->product_factory->create(),
			'customer' => 1
		) );

		$a1 = $this->activation_factory->create( array(
			'location' => 'a.com',
			'key'      => $key1
		) );

		$a2 = $this->activation_factory->create( array(
			'location' => 'a.com',
			'key'      => $key2
		) );

		$key1->expire();

		$a1 = Activation::get( $a1 );
		$a2 = Activation::get( $a2 );

		$this->assertEquals( Activation::EXPIRED, $a1->get_status(),
			"Key::expire did not set activation records status to expired." );

		$this->assertEquals( Activation::ACTIVE, $a2->get_status(),
			"Key::expire set activation record with a different key to expired." );
	}

	/**
	 * @depends test_expiring_license_expires_activations
	 */
	public function test_expiring_license_does_not_expire_deactivated_activations() {

		/** @var Key $key */
		$key = $this->key_factory->create_and_get( array(
			'product'  => $this->product_factory->create(),
			'customer' => 1
		) );

		$activation = $this->activation_factory->create( array(
			'location'    => 'a.com',
			'key'         => $key,
			'status'      => Activation::DEACTIVATED,
			'deactivated' => \ITELIC\make_date_time()
		) );

		$key->expire();

		$activation = Activation::get( $activation );

		$this->assertEquals( Activation::DEACTIVATED, $activation->get_status() );
	}

	public function test_key_get_activations_returns_only_activations_for_current_key() {

		/** @var Key $key1 */
		$key1 = $this->key_factory->create_and_get( array(
			'product'  => $this->product_factory->create(),
			'customer' => 1
		) );

		/** @var Key $key1 */
		$key2 = $this->key_factory->create_and_get( array(
			'product'  => $this->product_factory->create(),
			'customer' => 1
		) );

		$a1 = $this->activation_factory->create_and_get( array(
			'location' => 'a.com',
			'key'      => $key1
		) );

		$this->activation_factory->create( array(
			'location' => 'a.com',
			'key'      => $key2
		) );

		$activations = array_values( $key1->get_activations() );

		$this->assertEquals( array( $a1 ), $activations );
	}

	public function test_key_get_activations_returns_only_keys_with_status() {

		/** @var Key $key */
		$key = $this->key_factory->create_and_get( array(
			'product'  => $this->product_factory->create(),
			'customer' => 1
		) );

		$this->activation_factory->create_and_get( array(
			'location' => 'a.com',
			'key'      => $key
		) );

		$a2 = $this->activation_factory->create_and_get( array(
			'location' => 'b.com',
			'key'      => $key,
			'status'   => Activation::DEACTIVATED
		) );

		$activations = array_values( $key->get_activations( Activation::DEACTIVATED ) );

		$this->assertEquals( array( $a2 ), $activations );
	}

	public function test_key_get_activations_rejects_invalid_status() {

		/** @var Key $key */
		$key = $this->key_factory->create_and_get( array(
			'product'  => $this->product_factory->create(),
			'customer' => 1
		) );

		$this->setExpectedException( '\InvalidArgumentException' );

		$key->get_activations( 'garbage' );
	}

	protected function _make_interval_key() {
		$product     = $this->getMockBuilder( '\ITELIC\Product' )->disableOriginalConstructor()->getMock();
		$product->ID = 1;
		$product->method( 'has_feature' )->with( 'licensing' )->willReturn( true );
		$product->method( 'get_feature' )->willReturnMap( array(
			array( 'base-price', array(), '50.00' ),
			array(
				'recurring-payments',
				array( 'setting' => 'interval' ),
				'month'
			),
			array(
				'recurring-payments',
				array( 'setting' => 'interval-count' ),
				1
			)
		) );

		$txn = $this->getMockBuilder( '\IT_Exchange_Transaction' )->disableOriginalConstructor()->getMock();

		WP_Mock::wpFunction( 'it_exchange_get_transaction', array(
			'times'  => 1,
			'args'   => array( 1 ),
			'return' => $txn
		) );

		WP_Mock::wpFunction( 'itelic_get_product', array(
			'times'  => 1,
			'args'   => array( 1 ),
			'return' => $product
		) );

		$key = new Key( (object) array(
			'lkey'           => 'abcd-1234',
			'transaction_id' => 1,
			'product'        => 1,
			'customer'       => 1,
			'status'         => Key::ACTIVE,
			'max'            => '',
			'expires'        => \ITELIC\make_date_time( '+1 month' )->format( 'Y-m-d H:i:s' )
		) );

		return $key;
	}

	public function test_extending_key_updates_expiration_date() {

		$key = $this->_make_interval_key();

		$expires = $key->get_expires();

		$this->assertInstanceOf( '\DateTime', $expires, 'Expires is not a DateTime object.' );

		$this->assertEquals( \ITELIC\make_date_time( '+1 month' )->getTimestamp(), $expires->getTimestamp(),
			'Expiry date is incorrectly set from recurring payments interval.', 5 );

		$this->assertEquals( \ITELIC\make_date_time( '+2 month' )->getTimestamp(), $key->extend()->getTimestamp(),
			'Expiry date is incorrectly extended.', 5 );
	}

	/**
	 * @depends test_extending_key_updates_expiration_date
	 */
	public function test_renewing_key_extends_expiration_date() {

		$key = $this->_make_interval_key();

		$key->renew();

		$expires = $key->get_expires();

		$this->assertInstanceOf( '\DateTime', $expires, 'Expires is not a DateTime object.' );

		$this->assertEquals( \ITELIC\make_date_time( '+2 month' )->getTimestamp(), $expires->getTimestamp(),
			"Key::renew did not extend the license key's expiration.", 5 );
	}

	public function test_renewing_key_updates_status_when_future_expiration_date() {

		$key = $this->_make_interval_key();

		$key->set_status( Key::EXPIRED );

		$key->renew();

		$this->assertEquals( Key::ACTIVE, $key->get_status(), "Key::renew did not update the status." );
	}

	public function test_renewing_key_does_not_update_status_when_past_expiration_date() {

		$key = $this->_make_interval_key();

		$key->set_status( Key::EXPIRED );
		$key->set_expires( \ITELIC\make_date_time( '-1 year' ) );

		$key->renew();

		$this->assertEquals( Key::EXPIRED, $key->get_status(), "Key::renew updated status, despite date in the past." );
	}

	public function test_renew_key_creates_renewal_record() {

		$key = $this->_make_interval_key();

		$renew = $key->renew();

		$this->assertInstanceOf( '\ITELIC\Renewal', $renew );
	}

	public function test_renew_key_creates_renewal_record_with_correct_expiration_date() {

		$key = $this->_make_interval_key();

		$expired = $key->get_expires();

		$renew = $key->renew();

		$this->assertEquals( $expired, $renew->get_key_expired_date() );
	}

	public function test_renew_key_with_transaction_sets_revenue() {

		$key = $this->_make_interval_key();

		$renewal_txn = $this->getMockBuilder( '\IT_Exchange_Transaction' )->disableOriginalConstructor()->getMock();
		$renewal_txn->method( 'get_products' )->willReturn( array(
			array(
				'product_id'       => $key->get_product()->ID,
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