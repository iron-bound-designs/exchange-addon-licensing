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
	 * @var ITELIC_UnitTest_Factory_For_Activations
	 */
	public $activation_factory;

	/**
	 * @var ITELIC_UnitTest_Factory_For_Releases
	 */
	public $release_factory;

	/**
	 * @var ITELIC_UnitTest_Factory_For_Updates
	 */
	public $update_factory;

	/**
	 * @var IT_Exchange_Admin
	 */
	public $exchange_admin;

	/**
	 * Do custom initialization.
	 */
	public function setUp() {
		parent::setUp();

		it_exchange_temporarily_load_addon( 'guest-checkout' );
		it_exchange_temporarily_load_addon( 'digital-downloads-product-type' );
		it_exchange_add_feature_support_to_product_type( 'recurring-payments', 'digital-downloads-product-type' );

		$null                 = null;
		$this->exchange_admin = new IT_Exchange_Admin( $null );

		it_exchange_save_option( 'settings_general',
			$this->exchange_admin->set_general_settings_defaults( array() ) );
		it_exchange_get_option( 'settings_general', true );

		$this->product_factory    = new ITELIC_UnitTest_Factory_For_Products();
		$this->key_factory        = new ITELIC_UnitTest_Factory_For_Keys( $this->factory );
		$this->activation_factory = new ITELIC_UnitTest_Factory_For_Activations( $this->factory );
		$this->release_factory    = new ITELIC_UnitTest_Factory_For_Releases( $this->factory );
		$this->update_factory     = new ITELIC_UnitTest_Factory_For_Updates( $this->factory );

		WP_Mock::setUp();
	}

	function tearDown() {
		parent::tearDown();

		WP_Mock::tearDown();

		unset( $this->product_factory );
		unset( $this->key_factory );
		unset( $this->activation_factory );
		unset( $this->release_factory );
		unset( $this->update_factory );
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