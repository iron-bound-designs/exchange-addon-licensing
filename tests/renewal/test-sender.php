<?php
/**
 * Test the renewal reminder sender.
 *
 * @author Iron Bound Designs
 * @since  1.0
 */

/**
 * Class ITELIC_Test_Renewal_Sender
 */
class ITELIC_Test_Renewal_Sender extends ITELIC_UnitTestCase {

	/**
	 * @dataProvider listeners_data_provider
	 */
	public function test_registered_listeners( $listener ) {

		$manager = \IronBound\WP_Notifications\Template\Factory::make( 'itelic-renewal-reminder' );

		$this->assertNotInstanceOf( '\IronBound\WP_Notifications\Template\Null_Listener', $manager->get_listener( $listener ) );
	}

	public function listeners_data_provider() {
		return array(
			'key'                  => array( 'key' ),
			'key expiry date'      => array( 'key_expiry_date' ),
			'days from expiration' => array( 'key_days_from_expiry' ),
			'product name'         => array( 'product_name' ),
			'transaction order no' => array( 'transaction_order_number' ),
			'discount amount'      => array( 'discount_amount' )
		);
	}
}