<?php
/**
 * Test the deactivate endpoint.
 *
 * @author Iron Bound Designs
 * @since  1.0
 */
use ITELIC\API\Endpoint\Deactivate;

/**
 * Class ITELIC_Test_HTTP_API_Deactivate
 */
class ITELIC_Test_HTTP_API_Deactivate extends ITELIC_UnitTestCase {

	public function test_exception_thrown_if_missing_activation_id() {

		$deactivate = new Deactivate();

		$this->setExpectedException( '\ITELIC\API\Exception', \ITELIC\API\Endpoint::CODE_NO_LOCATION_ID );

		$deactivate->serve( new ArrayObject(), new ArrayObject() );
	}

	public function test_exception_thrown_if_invalid_activation_id() {

		$deactivate = new Deactivate();

		WP_Mock::wpFunction( 'itelic_get_activation', array(
			'times'  => 1,
			'args'   => array( 1 ),
			'return' => false
		) );

		$this->setExpectedException( '\ITELIC\API\Exception', \ITELIC\API\Endpoint::CODE_INVALID_LOCATION );

		$deactivate->serve( new ArrayObject(), new ArrayObject( array( 'id' => 1 ) ) );
	}

	public function test_exception_thrown_if_activation_id_does_not_match_key() {

		$deactivate = new Deactivate();

		$mock_key = $this->getMockBuilder( '\ITELIC\Key' )->disableOriginalConstructor()->getMock();
		$mock_key->method( 'get_key' )->willReturn( 'abcd-1234' );

		$mock_activation = $this->getMockBuilder( '\ITELIC\Activation' )->disableOriginalConstructor()->getMock();
		$mock_activation->method( 'get_key' )->willReturn( $mock_key );

		$wrong_key = $this->getMockBuilder( '\ITELIC\Key' )->disableOriginalConstructor()->getMock();
		$wrong_key->method( 'get_key' )->willReturn( 'efgh-5678' );

		WP_Mock::wpFunction( 'itelic_get_activation', array(
			'times'  => 1,
			'args'   => array( 1 ),
			'return' => false
		) );

		$deactivate->set_auth_license_key( $wrong_key );

		$this->setExpectedException( '\ITELIC\API\Exception', \ITELIC\API\Endpoint::CODE_INVALID_LOCATION );

		$deactivate->serve( new ArrayObject(), new ArrayObject( array( 'id' => 1 ) ) );
	}

	public function test_activation_deactivated_on_successful_request() {

		$deactivate = new Deactivate();

		$mock_key = $this->getMockBuilder( '\ITELIC\Key' )->disableOriginalConstructor()->getMock();
		$mock_key->method( 'get_key' )->willReturn( 'abcd-1234' );

		$mock_activation = $this->getMockBuilder( '\ITELIC\Activation' )->disableOriginalConstructor()->getMock();
		$mock_activation->method( 'get_key' )->willReturn( $mock_key );
		$mock_activation->expects( $this->once() )->method( 'deactivate' );

		WP_Mock::wpFunction( 'itelic_get_activation', array(
			'times'  => 1,
			'args'   => array( 1 ),
			'return' => $mock_activation
		) );

		$deactivate->set_auth_license_key( $mock_key );

		$deactivate->serve( new ArrayObject(), new ArrayObject( array( 'id' => 1 ) ) );
	}
}