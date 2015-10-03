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

		$this->assertEquals( $key->get_expires(), null, "Lifetime key's expiration date is not null." );

		$key->extend();

		$this->assertEquals( $key->get_expires(), null, "Key expiration date set." );
	}
}