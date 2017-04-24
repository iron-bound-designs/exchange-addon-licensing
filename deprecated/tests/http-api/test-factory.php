<?php
/**
 * Test the API dispatch factory.
 *
 * @author    Iron Bound Designs
 * @since     1.0
 * @license   AGPL
 * @copyright Iron Bound Designs, 2015.
 */

/**
 * Class ITELIC_Test_HTTP_API_Factory
 */
class ITELIC_Test_HTTP_API_Factory extends ITELIC_UnitTestCase {

	/**
	 * @dataProvider _default_endpoints_data_provider
	 */
	public function test_default_endpoints( $action, $endpoint ) {

		$factory  = new \ITELIC\API\Factory();
		$dispatch = $factory->make();

		$endpoints = $dispatch->get_endpoints();

		$this->assertArrayHasKey( $action, $endpoints );
		$this->assertInstanceOf( $endpoint, $endpoints[ $action ] );
	}

	public function _default_endpoints_data_provider() {
		return array(
			array( 'activate', '\ITELIC\API\Endpoint\Activate' ),
			array( 'changelog', '\ITELIC\API\Endpoint\Changelog' ),
			array( 'deactivate', '\ITELIC\API\Endpoint\Deactivate' ),
			array( 'download', '\ITELIC\API\Endpoint\Download' ),
			array( 'info', '\ITELIC\API\Endpoint\Info' ),
			array( 'product', '\ITELIC\API\Endpoint\Product' ),
			array( 'version', '\ITELIC\API\Endpoint\Version' )
		);
	}
}