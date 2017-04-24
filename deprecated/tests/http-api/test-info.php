<?php
/**
 * Test the key info endpoint.
 *
 * @author      Iron Bound Designs
 * @since       1.0
 * @copyright   2015 (c) Iron Bound Designs.
 * @license     GPLv2
 */

/**
 * Class ITELIC_Test_HTTP_API_Info
 */
class ITELIC_Test_HTTP_API_Info extends ITELIC_UnitTestCase {

	public function test_given_keys_info_is_returned() {

		$mock_key = $this->getMockBuilder( '\ITELIC\Key' )->disableOriginalConstructor()->getMock();

		$info = new \ITELIC\API\Endpoint\Info();
		$info->set_auth_license_key( $mock_key );

		$response = $info->serve( new ArrayObject(), new ArrayObject() );
		$data     = $response->get_data();

		$this->assertTrue( $data['success'] );
		$this->assertEquals( $mock_key, $data['body'] );
	}
}