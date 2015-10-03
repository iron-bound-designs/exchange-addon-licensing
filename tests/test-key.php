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

		$this->assertEquals( \ITELIC\make_date_time( '+1 month' ), $key->get_expires(),
			'Expiry date is incorrectly set from recurring payments interval.' );

		$this->assertEquals( \ITELIC\make_date_time( '+2 month' ), $key->extend(),
			'Expiry date is incorrectly extended.' );
	}
}