<?php
/**
 * Test the activate endpoint.
 *
 * @author Iron Bound Designs
 * @since  1.0
 */

/**
 * Class ITELIC_Test_HTTP_API_Activate
 */
class ITELIC_Test_HTTP_API_Activate extends ITELIC_UnitTestCase {

	public function test_exception_thrown_if_missing_location() {

		$activate = new \ITELIC\API\Endpoint\Activate();

		$this->setExpectedException( '\ITELIC\API\Exception', \ITELIC\API\Endpoint::CODE_NO_LOCATION );

		$activate->serve( new ArrayObject(), new ArrayObject() );
	}

	public function test_exception_thrown_if_location_length_to_long() {

		$activate = new \ITELIC\API\Endpoint\Activate();

		$key = $this->getMockBuilder( '\ITELIC\Key' )->disableOriginalConstructor()->getMock();
		$activate->set_auth_license_key( $key );

		$this->setExpectedException( '\ITELIC\API\Exception', \ITELIC\API\Endpoint::CODE_INVALID_LOCATION );

		$activate->serve( new ArrayObject(), new ArrayObject( array( 'location' => str_repeat( '-', 192 ) ) ) );
	}

	public function test_exception_thrown_if_max_activations_reached() {

		$activate = new \ITELIC\API\Endpoint\Activate();

		$key = $this->getMockBuilder( '\ITELIC\Key' )->disableOriginalConstructor()->getMock();
		$key->method( 'get_max' )->willReturn( 1 );
		$key->method( 'get_active_count' )->willReturn( 1 );
		$activate->set_auth_license_key( $key );

		$this->setExpectedException( '\ITELIC\API\Exception', ITELIC\API\Endpoint::CODE_MAX_ACTIVATIONS );

		$activate->serve( new ArrayObject(), new ArrayObject( array( 'location' => 'www.test.com' ) ) );
	}

	public function test_location_is_properly_saved() {

		$activate = new \ITELIC\API\Endpoint\Activate();

		$key = $this->key_factory->create_and_get( array(
			'product'  => $this->product_factory->create(),
			'customer' => 1
		) );

		$activate->set_auth_license_key( $key );

		$location = 'www.test.com';

		$response = $activate->serve( new ArrayObject(), new ArrayObject( array( 'location' => $location ) ) );

		$data = $response->get_data();

		$this->assertTrue( $data['success'] );
		$this->assertEquals( itelic_normalize_url( $location ), $data['body']->get_location() );
	}

	public function test_default_track_is_stable() {

		$activate = new \ITELIC\API\Endpoint\Activate();

		$key = $this->key_factory->create_and_get( array(
			'product'  => $this->product_factory->create(),
			'customer' => 1
		) );

		$activate->set_auth_license_key( $key );

		$response = $activate->serve( new ArrayObject(), new ArrayObject( array( 'location' => 'www.test.com' ) ) );
		$data     = $response->get_data();

		$this->assertTrue( $data['success'] );
		$this->assertEquals( 'stable', $data['body']->get_meta( 'track', true ) );

		$response = $activate->serve( new ArrayObject(), new ArrayObject( array(
			'location' => 'www.test2.com',
			'track'    => 'garbage'
		) ) );
		$data     = $response->get_data();

		$this->assertTrue( $data['success'] );
		$this->assertEquals( 'stable', $data['body']->get_meta( 'track', true ) );
	}

	public function test_track_is_properly_saved() {

		$activate = new \ITELIC\API\Endpoint\Activate();

		$key = $this->key_factory->create_and_get( array(
			'product'  => $this->product_factory->create(),
			'customer' => 1
		) );

		$activate->set_auth_license_key( $key );

		$response = $activate->serve( new ArrayObject(), new ArrayObject( array(
			'location' => 'www.test.com',
			'track'    => 'pre-release'
		) ) );

		$data = $response->get_data();

		$this->assertTrue( $data['success'] );
		$this->assertEquals( 'pre-release', $data['body']->get_meta( 'track', true ) );
	}

	public function test_activation_record_is_reactivated_if_dupe_location_and_previous_record_exists_and_is_deactivated() {

		$activate = new \ITELIC\API\Endpoint\Activate();

		$key = $this->key_factory->create_and_get( array(
			'product'  => $this->product_factory->create(),
			'customer' => 1
		) );

		$ID = $this->activation_factory->create( array(
			'key'      => $key,
			'location' => 'www.test.com',
			'statis'   => \ITELIC\Activation::DEACTIVATED
		) );

		$activate->set_auth_license_key( $key );

		$response = $activate->serve( new ArrayObject(), new ArrayObject( array( 'location' => 'www.test.com' ) ) );
		$data     = $response->get_data();

		$this->assertEquals( $ID, $data['body']->get_pk() );
		$this->assertEquals( \ITELIC\Activation::ACTIVE, $data['body']->get_status() );
	}

	public function test_activation_record_track_is_changed_if_dupe_location() {

		$activate = new \ITELIC\API\Endpoint\Activate();

		$key = $this->key_factory->create_and_get( array(
			'product'  => $this->product_factory->create(),
			'customer' => 1
		) );

		$ID = $this->activation_factory->create( array(
			'key'      => $key,
			'location' => 'www.test.com'
		) );

		$activate->set_auth_license_key( $key );

		$response = $activate->serve( new ArrayObject(), new ArrayObject( array(
			'location' => 'www.test.com',
			'track'    => 'pre-release'
		) ) );

		$data = $response->get_data();

		$this->assertEquals( $ID, $data['body']->get_pk() );
		$this->assertEquals( 'pre-release', $data['body']->get_meta( 'track', true ) );
	}

	public function test_release_is_set_if_version_argument_passed() {
		$activate = new \ITELIC\API\Endpoint\Activate();

		$key = $this->key_factory->create_and_get( array(
			'product'  => $this->product_factory->create(),
			'customer' => 1
		) );

		$activate->set_auth_license_key( $key );

		$mock_release = $this->getMockBuilder( '\ITELIC\Release' )->disableOriginalConstructor()->getMock();
		$mock_release->method( 'get_pk' )->willReturn( 1 );

		WP_Mock::wpFunction( 'itelic_get_release_by_version', array(
			'times'  => 1,
			'args'   => array( $key->get_product()->ID, '1.1' ),
			'return' => $mock_release
		) );

		WP_Mock::wpFunction( 'itelic_get_release', array(
			'times'  => 1,
			'args'   => 1,
			'return' => $mock_release
		) );

		$response = $activate->serve( new ArrayObject(), new ArrayObject( array(
			'location' => 'www.test.com',
			'version'  => '1.1'
		) ) );

		$data = $response->get_data();

		$this->assertEquals( 1, $data['body']->get_release()->get_pk() );
	}
}