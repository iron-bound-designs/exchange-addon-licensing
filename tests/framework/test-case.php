<?php
/**
 * Base unit test case for all test.
 *
 * @author Iron Bound Designs
 * @since  1.0
 */

/**
 * Class ITELIC_UnitTestCase
 */
abstract class ITELIC_UnitTestCase extends WP_UnitTestCase {

	/**
	 * @var ITELIC_UnitTest_Factory_For_Products
	 */
	public $product_factory;

	/**
	 * @var ITELIC_UnitTest_Factory_For_Keys
	 */
	public $key_factory;

	/**
	 * @var IT_Exchange_Admin
	 */
	public $exchange_admin;

	/**
	 * Do custom initialization.
	 */
	public function setUp() {
		parent::setUp();
		it_exchange_temporarily_load_addon( 'digital-downloads-product-type' );
		$this->product_factory = new ITELIC_UnitTest_Factory_For_Products();
		$this->key_factory     = new ITELIC_UnitTest_Factory_For_Keys( $this->factory );

		$null                 = null;
		$this->exchange_admin = new IT_Exchange_Admin( $null );

		it_exchange_save_option( 'settings_general',
			$this->exchange_admin->set_general_settings_defaults( array() ) );
		it_exchange_get_option( 'settings_general', true );
	}

	/**
	 * Simulate going to an iThemes Exchange custom page.
	 *
	 * @since 1.0
	 *
	 * @param string $exchange_page
	 */
	public function go_to_exchange_page( $exchange_page ) {

		remove_all_filters( 'it_exchange_is_page' );

		add_filter( 'it_exchange_is_page', function ( $result, $page ) use ( $exchange_page ) {
			if ( $page == $exchange_page ) {
				$result = true;
			}

			return $result;
		}, 10, 2 );
	}
}