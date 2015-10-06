<?php
/**
 * Test the API dispatcher.
 *
 * @author Iron Bound Designs
 * @since  1.0
 */

/**
 * Class ITELIC_Test_HTTP_API
 */
class ITELIC_Test_HTTP_API extends ITELIC_UnitTestCase {

	public function test_unknown_response_generates_404() {

		$dispatch = new \ITELIC\API\Dispatch();

		$mock_wp_query = $this->getMockBuilder( '\WP_Query' )->disableOriginalConstructor()->getMock();
		$mock_wp_query->expects( $this->once() )->method( 'get' )->with( 'itelic_api' )->willReturn( 'garbage' );

		$response = $dispatch->process( $mock_wp_query );
		$data     = $response->get_data();

		$this->assertEquals( 404, $response->get_status() );
		$this->assertFalse( $data['success'] );
		$this->assertArrayHasKey( 'error', $data );
		$this->assertEquals( 404, $data['error']['code'] );
	}

}