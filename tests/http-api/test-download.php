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
}