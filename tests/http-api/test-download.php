<?php
/**
 * Test the download endpoint.
 *
 * @author Iron Bound Designs
 * @since  1.0
 */
use ITELIC\API\Endpoint\Download;

/**
 * Class ITELIC_Test_HTTP_API_Download
 */
class ITELIC_Test_HTTP_API_Download extends ITELIC_UnitTestCase {

	public function test_error_on_invalid_query_args() {

		$download = new Download();

		$get = new ArrayObject();

		WP_Mock::wpFunction( 'ITELIC\validate_query_args', array(
			'times'  => 1,
			'args'   => array( $get ),
			'return' => false
		) );

		WP_Mock::wpFunction( 'status_header', array(
			'times' => 1,
			'args'  => array( 403 )
		) );

		$this->expectOutputString( "This download link is invalid or has expired." );

		$download->serve( $get, new ArrayObject() );
	}

	public function test_error_on_key_activation_mismatch() {

		$download = new Download();

		$get = new ArrayObject( array(
			'key'        => 'abcd-1234',
			'activation' => 1
		) );

		$mock_key = $this->getMockBuilder( '\ITELIC\Key' )->disableOriginalConstructor()->getMock();
		$mock_key->method( 'get_key' )->willReturn( 'efgh-5678' );

		$mock_activation = $this->getMockBuilder( '\ITELIC\Activation' )->disableOriginalConstructor()->getMock();
		$mock_activation->method( 'get_key' )->willReturn( $mock_key );

		WP_Mock::wpFunction( 'itelic_get_activation', array(
			'times'  => 1,
			'args'   => array( 1 ),
			'return' => $mock_activation
		) );

		WP_Mock::wpFunction( 'ITELIC\validate_query_args', array(
			'times'  => 1,
			'args'   => array( $get ),
			'return' => true
		) );

		WP_Mock::wpFunction( 'status_header', array(
			'times' => 1,
			'args'  => array( 403 )
		) );

		$this->expectOutputString( "This download link is invalid or has expired." );

		$download->serve( $get, new ArrayObject() );
	}

	public function test_update_record_created_on_successful_download() {

		$download = new Download();

		$get = new ArrayObject( array(
			'key'        => 'abcd-1234',
			'activation' => 1
		) );

		$mock_release = $this->getMockBuilder( '\ITELIC\Release' )->disableOriginalConstructor()->getMock();
		$mock_release->method( 'get_download' )->willReturn( new WP_Post( (object) array(
			'ID' => 1
		) ) );

		$mock_product = $this->getMockBuilder( '\ITELIC\Product' )->disableOriginalConstructor()->getMock();
		$mock_product->method( 'get_latest_release_for_activation' )->willReturn( $mock_release );

		$mock_key = $this->getMockBuilder( '\ITELIC\Key' )->disableOriginalConstructor()->getMock();
		$mock_key->method( 'get_key' )->willReturn( 'abcd-1234' );
		$mock_key->method( 'get_product' )->willReturn( $mock_product );

		$mock_activation = $this->getMockBuilder( '\ITELIC\Activation' )->disableOriginalConstructor()->getMock();
		$mock_activation->method( 'get_key' )->willReturn( $mock_key );

		WP_Mock::wpFunction( 'itelic_get_activation', array(
			'times'  => 1,
			'args'   => array( 1 ),
			'return' => $mock_activation
		) );

		WP_Mock::wpFunction( 'ITELIC\validate_query_args', array(
			'times'  => 1,
			'args'   => array( $get ),
			'return' => true
		) );

		WP_Mock::wpFunction( 'itelic_create_update', array(
			'times' => 1,
			'args'  => array(
				array(
					'activation' => $mock_activation,
					'release'    => $mock_release
				)
			)
		) );

		WP_Mock::wpFunction( 'wp_get_attachment_url', array(
			'times'  => 1,
			'args'   => array( 1 ),
			'return' => 'www.example.com/download/'
		) );

		WP_Mock::wpFunction( 'ITELIC\serve_download', array(
			'times' => 1,
			'args'  => array( 'www.example.com/download/' )
		) );

		$download->serve( $get, new ArrayObject() );
	}
}