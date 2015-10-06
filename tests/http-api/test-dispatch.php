<?php
/**
 * Test the API dispatcher.
 *
 * @author Iron Bound Designs
 * @since  1.0
 */
use ITELIC\API\Contracts\Authenticatable;

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

		$exception = new \ITELIC\API\Exception( 'Error', 5 );

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
		$this->assertEquals( $exception->getCode(), $data['error']['code'] );
		$this->assertEquals( 400, $response->get_status() );
	}

	public function test_error_response_generate_if_non_api_exception_thrown() {

		$exception = new Exception( 'Error', 5 );

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
		$this->assertEquals( 0, $data['error']['code'] );
		$this->assertEquals( 500, $response->get_status() );
	}

	public function test_existing_license_key_auth_mode_rejects_request_without_key() {

		$mock_endpoint = $this->getMockBuilder( '\ITELIC\API\Contracts\Authenticatable' )->getMock();
		$mock_endpoint->expects( $this->never() )->method( 'serve' );
		$mock_endpoint->expects( $this->once() )->method( 'get_auth_mode' )->willReturn( Authenticatable::MODE_EXISTS );
		$mock_endpoint->expects( $this->once() )->method( 'get_auth_error_code' )->willReturn( \ITELIC\API\Endpoint::CODE_INVALID_KEY );

		WP_Mock::wpFunction( 'itelic_get_key', array(
			'times'  => 1,
			'args'   => array( 'abcd-1234' ),
			'return' => false
		) );

		$_SERVER['PHP_AUTH_USER'] = 'abcd-1234';

		$mock_wp_query = $this->getMockBuilder( '\WP_Query' )->disableOriginalConstructor()->getMock();
		$mock_wp_query->expects( $this->once() )->method( 'get' )->with( 'itelic_api' )->willReturn( 'mock' );

		$dispatch = new \ITELIC\API\Dispatch();
		\ITELIC\API\Dispatch::register_endpoint( $mock_endpoint, 'mock' );

		$response = $dispatch->process( $mock_wp_query );
		$data     = $response->get_data();

		$this->assertFalse( $data['success'] );
		$this->assertArrayHasKey( 'error', $data );
		$this->assertEquals( \ITELIC\API\Endpoint::CODE_INVALID_KEY, $data['error']['code'] );
	}

	/**
	 * @dataProvider existing_license_key_auth_mode_data_provider
	 */
	public function test_existing_license_key_auth_mode_returns_valid_response_with_key_of_any_status( $key ) {

		$response = new \ITELIC\API\Response();

		$mock_endpoint = $this->getMockBuilder( '\ITELIC\API\Contracts\Authenticatable' )->getMock();
		$mock_endpoint->expects( $this->once() )->method( 'serve' )->willReturn( $response );
		$mock_endpoint->expects( $this->once() )->method( 'get_auth_mode' )->willReturn( Authenticatable::MODE_EXISTS );

		WP_Mock::wpFunction( 'itelic_get_key', array(
			'times'  => 1,
			'args'   => array( 'abcd-1234' ),
			'return' => $key
		) );

		$_SERVER['PHP_AUTH_USER'] = 'abcd-1234';

		$mock_wp_query = $this->getMockBuilder( '\WP_Query' )->disableOriginalConstructor()->getMock();
		$mock_wp_query->expects( $this->once() )->method( 'get' )->with( 'itelic_api' )->willReturn( 'mock' );

		$dispatch = new \ITELIC\API\Dispatch();
		\ITELIC\API\Dispatch::register_endpoint( $mock_endpoint, 'mock' );

		$this->assertEquals( $response, $dispatch->process( $mock_wp_query ) );
	}

	public function existing_license_key_auth_mode_data_provider() {

		$mock_key = $this->getMockBuilder( '\ITELIC\Key' )->disableOriginalConstructor()->getMock();
		$mock_key->method( 'get_status' )->willReturn( \ITELIC\Key::ACTIVE );

		$keys = array(
			'Active license key' => array( $mock_key )
		);

		$mock_key->method( 'get_status' )->willReturn( \ITELIC\Key::EXPIRED );
		$keys['Expired license key'] = array( $mock_key );

		$mock_key->method( 'get_status' )->willReturn( \ITELIC\Key::DISABLED );
		$keys['Disabled license key'] = array( $mock_key );

		return $keys;
	}

	public function test_active_license_key_auth_mode_rejects_request_without_key() {

		$mock_endpoint = $this->getMockBuilder( '\ITELIC\API\Contracts\Authenticatable' )->getMock();
		$mock_endpoint->expects( $this->never() )->method( 'serve' );
		$mock_endpoint->expects( $this->once() )->method( 'get_auth_mode' )->willReturn( Authenticatable::MODE_ACTIVE );
		$mock_endpoint->expects( $this->once() )->method( 'get_auth_error_code' )->willReturn( \ITELIC\API\Endpoint::CODE_INVALID_KEY );

		WP_Mock::wpFunction( 'itelic_get_key', array(
			'times'  => 1,
			'args'   => array( 'abcd-1234' ),
			'return' => false
		) );

		$_SERVER['PHP_AUTH_USER'] = 'abcd-1234';

		$mock_wp_query = $this->getMockBuilder( '\WP_Query' )->disableOriginalConstructor()->getMock();
		$mock_wp_query->expects( $this->once() )->method( 'get' )->with( 'itelic_api' )->willReturn( 'mock' );

		$dispatch = new \ITELIC\API\Dispatch();
		\ITELIC\API\Dispatch::register_endpoint( $mock_endpoint, 'mock' );

		$response = $dispatch->process( $mock_wp_query );
		$data     = $response->get_data();

		$this->assertFalse( $data['success'] );
		$this->assertArrayHasKey( 'error', $data );
		$this->assertEquals( \ITELIC\API\Endpoint::CODE_INVALID_KEY, $data['error']['code'] );
		$this->assertArrayHasKey( 'WWW-Authenticate', $response->get_headers() );
	}

	/**
	 * @dataProvider valid_license_key_auth_mode_data_provider
	 */
	public function test_active_license_key_auth_mode_rejects_request_with_non_active_key( $key ) {

		$mock_endpoint = $this->getMockBuilder( '\ITELIC\API\Contracts\Authenticatable' )->getMock();
		$mock_endpoint->expects( $this->never() )->method( 'serve' );
		$mock_endpoint->expects( $this->exactly( 2 ) )->method( 'get_auth_mode' )->willReturn( Authenticatable::MODE_ACTIVE );
		$mock_endpoint->expects( $this->once() )->method( 'get_auth_error_code' )->willReturn( \ITELIC\API\Endpoint::CODE_INVALID_KEY );

		WP_Mock::wpFunction( 'itelic_get_key', array(
			'times'  => 1,
			'args'   => array( 'abcd-1234' ),
			'return' => $key
		) );

		$_SERVER['PHP_AUTH_USER'] = 'abcd-1234';

		$mock_wp_query = $this->getMockBuilder( '\WP_Query' )->disableOriginalConstructor()->getMock();
		$mock_wp_query->expects( $this->once() )->method( 'get' )->with( 'itelic_api' )->willReturn( 'mock' );

		$dispatch = new \ITELIC\API\Dispatch();
		\ITELIC\API\Dispatch::register_endpoint( $mock_endpoint, 'mock' );

		$response = $dispatch->process( $mock_wp_query );
		$data     = $response->get_data();

		$this->assertFalse( $data['success'] );
		$this->assertArrayHasKey( 'error', $data );
		$this->assertEquals( \ITELIC\API\Endpoint::CODE_INVALID_KEY, $data['error']['code'] );
		$this->assertArrayHasKey( 'WWW-Authenticate', $response->get_headers() );
	}

	public function valid_license_key_auth_mode_data_provider() {

		$mock_key = $this->getMockBuilder( '\ITELIC\Key' )->disableOriginalConstructor()->getMock();

		$keys = array();

		$mock_key->method( 'get_status' )->willReturn( \ITELIC\Key::EXPIRED );
		$keys['Expired license key'] = array( $mock_key );

		$mock_key->method( 'get_status' )->willReturn( \ITELIC\Key::DISABLED );
		$keys['Disabled license key'] = array( $mock_key );

		return $keys;
	}

	public function test_active_license_key_auth_mode_returns_valid_response_for_active_key() {

		$response = new \ITELIC\API\Response();

		$mock_endpoint = $this->getMockBuilder( '\ITELIC\API\Contracts\Authenticatable' )->getMock();
		$mock_endpoint->expects( $this->once() )->method( 'serve' )->willReturn( $response );
		$mock_endpoint->expects( $this->once() )->method( 'get_auth_mode' )->willReturn( Authenticatable::MODE_ACTIVE );

		$mock_key = $this->getMockBuilder( '\ITELIC\Key' )->disableOriginalConstructor()->getMock();
		$mock_key->method( 'get_status' )->willReturn( \ITELIC\Key::ACTIVE );

		WP_Mock::wpFunction( 'itelic_get_key', array(
			'times'  => 1,
			'args'   => array( 'abcd-1234' ),
			'return' => $mock_key
		) );

		$_SERVER['PHP_AUTH_USER'] = 'abcd-1234';

		$mock_wp_query = $this->getMockBuilder( '\WP_Query' )->disableOriginalConstructor()->getMock();
		$mock_wp_query->expects( $this->once() )->method( 'get' )->with( 'itelic_api' )->willReturn( 'mock' );

		$dispatch = new \ITELIC\API\Dispatch();
		\ITELIC\API\Dispatch::register_endpoint( $mock_endpoint, 'mock' );

		$this->assertEquals( $response, $dispatch->process( $mock_wp_query ) );
	}

	/**
	 * @dataProvider valid_activation_auth_mode_data_provider
	 */
	public function test_valid_activation_auth_mode_combinations( $key, $activation, $expected ) {

		$mock_endpoint = $this->getMockBuilder( '\ITELIC\API\Contracts\Authenticatable' )->getMock();

		if ( $expected ) {
			$mock_endpoint->expects( $this->once() )->method( 'serve' )->willReturn( new \ITELIC\API\Response( array(
				'success' => true
			) ) );
		} else {
			$mock_endpoint->expects( $this->never() )->method( 'serve' );
		}
		$mock_endpoint->expects( $this->atLeastOnce() )->method( 'get_auth_mode' )->willReturn( Authenticatable::MODE_VALID_ACTIVATION );

		WP_Mock::wpFunction( 'itelic_get_key', array(
			'times'  => 1,
			'args'   => array( 'abcd-1234' ),
			'return' => $key
		) );

		WP_Mock::wpFunction( 'itelic_get_activation', array(
			'times'  => 1,
			'args'   => array( '1' ),
			'return' => $activation
		) );

		$_SERVER['PHP_AUTH_USER'] = 'abcd-1234';
		$_SERVER['PHP_AUTH_PW']   = '1';

		$mock_wp_query = $this->getMockBuilder( '\WP_Query' )->disableOriginalConstructor()->getMock();
		$mock_wp_query->expects( $this->once() )->method( 'get' )->with( 'itelic_api' )->willReturn( 'mock' );

		$dispatch = new \ITELIC\API\Dispatch();
		\ITELIC\API\Dispatch::register_endpoint( $mock_endpoint, 'mock' );

		$response = $dispatch->process( $mock_wp_query );
		$data     = $response->get_data();

		$this->assertEquals( $expected, $data['success'] );

		if ( ! $expected ) {
			$this->assertArrayHasKey( 'WWW-Authenticate', $response->get_headers() );
			$this->assertArrayHasKey( 'error', $data );
		} else {
			$this->assertArrayNotHasKey( 'WWW-Authenticate', $response->get_headers() );
		}
	}

	public function valid_activation_auth_mode_data_provider() {

		$data = array();

		$keys        = array();
		$activations = array();

		$active_key = $this->getMockBuilder( '\ITELIC\Key' )->disableOriginalConstructor()->getMock();
		$active_key->method( 'get_key' )->willReturn( 'abcd-1234' );
		$active_key->method( 'get_status' )->willReturn( \ITELIC\Key::ACTIVE );
		$keys[] = $active_key;

		$disabled_key = $this->getMockBuilder( '\ITELIC\Key' )->disableOriginalConstructor()->getMock();
		$disabled_key->method( 'get_key' )->willReturn( 'abcd-1234' );
		$disabled_key->method( 'get_status' )->willReturn( \ITELIC\Key::DISABLED );
		$keys[] = $disabled_key;

		$expired_key = $this->getMockBuilder( '\ITELIC\Key' )->disableOriginalConstructor()->getMock();
		$expired_key->method( 'get_key' )->willReturn( 'abcd-1234' );
		$expired_key->method( 'get_status' )->willReturn( \ITELIC\Key::EXPIRED );
		$keys[] = $expired_key;

		$active_activation = $this->getMockBuilder( '\ITELIC\Activation' )->disableOriginalConstructor()->getMock();
		$active_activation->method( 'get_status' )->willReturn( \ITELIC\Activation::ACTIVE );
		$activations[] = $active_activation;

		$deactivated_activation = $this->getMockBuilder( '\ITELIC\Activation' )->disableOriginalConstructor()->getMock();
		$deactivated_activation->method( 'get_status' )->willReturn( \ITELIC\Activation::DEACTIVATED );
		$activations[] = $deactivated_activation;

		$expired_activation = $this->getMockBuilder( '\ITELIC\Activation' )->disableOriginalConstructor()->getMock();
		$expired_activation->method( 'get_status' )->willReturn( \ITELIC\Activation::EXPIRED );
		$activations[] = $expired_activation;

		for ( $k = 0; $k < 3; $k ++ ) {

			for ( $a = 0; $a < 3; $a ++ ) {

				$key        = $keys[ $k ];
				$activation = $activations[ $a ];

				$activation->method( 'get_key' )->willReturn( $key );

				$label = sprintf( '%s key & %s activation', $key->get_status(), $activation->get_status() );

				$success = $key->get_status() == \ITELIC\Key::ACTIVE && $activation->get_status() == \ITELIC\Activation::ACTIVE;

				$data[ $label ] = array( $key, $activation, $success );
			}
		}

		return $data;
	}

}