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

	public function test_dispatch_calls_endpoint_serve_method_and_returns_given_response() {

		$response = new \ITELIC\API\Response();

		$mock_endpoint = $this->getMockBuilder( '\ITELIC\API\Contracts\Endpoint' )->getMock();
		$mock_endpoint->expects( $this->once() )->method( 'serve' )->willReturn( $response );

		$mock_wp_query = $this->getMockBuilder( '\WP_Query' )->disableOriginalConstructor()->getMock();
		$mock_wp_query->expects( $this->once() )->method( 'get' )->with( 'itelic_api' )->willReturn( 'mock' );

		$dispatch = new \ITELIC\API\Dispatch();
		\ITELIC\API\Dispatch::register_endpoint( $mock_endpoint, 'mock' );

		$this->assertEquals( $response, $dispatch->process( $mock_wp_query ) );
	}

	public function test_error_response_generated_if_api_exception_thrown() {

		$exception = new API\Exception( 'Error', 5 );

		$mock_endpoint = $this->getMockBuilder( '\ITELIC\API\Contracts\Endpoint' )->getMock();
		$mock_endpoint->expects( $this->once() )->method( 'serve' )->will( $this->throwException( $exception ) );

		$mock_wp_query = $this->getMockBuilder( '\WP_Query' )->disableOriginalConstructor()->getMock();
		$mock_wp_query->expects( $this->once() )->method( 'get' )->with( 'itelic_api' )->willReturn( 'mock' );

		$dispatch = new \ITELIC\API\Dispatch();
		\ITELIC\API\Dispatch::register_endpoint( $mock_endpoint, 'mock' );

		$response = $dispatch->process( $mock_wp_query );
		$data     = $response->get_data();

		$this->assertFalse( $data['success'] );
		$this->assertArrayHasKey( 'error', $data );
		$this->assertArrayHasKey( 'message', $data['error'] );
		$this->assertArrayHasKey( 'code', $data['error'] );
		$this->assertEquals( $exception->getMessage(), $data['error']['message'] );
		$this->assertEquals( $exception->getCode(), $data['message']['code'] );
	}


}