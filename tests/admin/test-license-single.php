<?php
/**
 * Test the single license admin view.
 *
 * @author Iron Bound Designs
 * @since  1.0
 */
use ITELIC\Key;

/**
 * Class ITELIC_Test_Admin_License_Single
 */
class ITELIC_Test_Admin_License_Single extends ITELIC_UnitTestCase {

	public function test_update_nonce_check() {

		$controller = new \ITELIC\Admin\Licenses\Controller\Single();

		$key = $this->getMockBuilder( '\ITELIC\Key' )->disableOriginalConstructor()->getMock();
		$key->method( 'get_key' )->willReturn( 'abcd-1234' );

		WP_Mock::wpFunction( 'wp_verify_nonce', array(
			'times'  => 1,
			'args'   => array( 'nonce', 'itelic-update-key-abcd-1234' ),
			'return' => false
		) );

		$this->setExpectedException( '\InvalidArgumentException' );

		$controller->do_update( $key, 'prop', 'val', 'nonce' );
	}

	public function test_update_permissions_check() {

		$controller = new \ITELIC\Admin\Licenses\Controller\Single();

		$key = $this->getMockBuilder( '\ITELIC\Key' )->disableOriginalConstructor()->getMock();
		$key->method( 'get_key' )->willReturn( 'abcd-1234' );

		WP_Mock::wpFunction( 'wp_verify_nonce', array(
			'times'  => 1,
			'args'   => array( 'nonce', 'itelic-update-key-abcd-1234' ),
			'return' => true
		) );

		WP_Mock::wpFunction( 'current_user_can', array(
			'times'  => 1,
			'args'   => array( 'manage_options' ),
			'return' => false
		) );

		$this->setExpectedException( '\InvalidArgumentException' );

		$controller->do_update( $key, 'prop', 'val', 'nonce' );
	}

	public function test_update_invalid_prop_is_rejected() {

		$controller = new \ITELIC\Admin\Licenses\Controller\Single();

		$key = $this->getMockBuilder( '\ITELIC\Key' )->disableOriginalConstructor()->getMock();
		$key->method( 'get_key' )->willReturn( 'abcd-1234' );

		WP_Mock::wpFunction( 'wp_verify_nonce', array(
			'times'  => 1,
			'args'   => array( 'nonce', 'itelic-update-key-abcd-1234' ),
			'return' => true
		) );

		WP_Mock::wpFunction( 'current_user_can', array(
			'times'  => 1,
			'args'   => array( 'manage_options' ),
			'return' => true
		) );

		$this->setExpectedException( '\InvalidArgumentException' );

		$controller->do_update( $key, 'invalid', 'val', 'nonce' );
	}

	public function test_update_status() {

		$controller = new \ITELIC\Admin\Licenses\Controller\Single();

		$key = $this->getMockBuilder( '\ITELIC\Key' )->disableOriginalConstructor()->getMock();
		$key->method( 'get_key' )->willReturn( 'abcd-1234' );
		$key->method( 'set_status' )->with( Key::DISABLED );

		WP_Mock::wpFunction( 'wp_verify_nonce', array(
			'times'  => 1,
			'args'   => array( 'nonce', 'itelic-update-key-abcd-1234' ),
			'return' => true
		) );

		WP_Mock::wpFunction( 'current_user_can', array(
			'times'  => 1,
			'args'   => array( 'manage_options' ),
			'return' => true
		) );

		$controller->do_update( $key, 'status', Key::DISABLED, 'nonce' );
	}

	public function test_update_max() {

		$controller = new \ITELIC\Admin\Licenses\Controller\Single();

		$key = $this->getMockBuilder( '\ITELIC\Key' )->disableOriginalConstructor()->getMock();
		$key->method( 'get_key' )->willReturn( 'abcd-1234' );
		$key->method( 'set_max' )->with( 6 );

		WP_Mock::wpFunction( 'wp_verify_nonce', array(
			'times'  => 1,
			'args'   => array( 'nonce', 'itelic-update-key-abcd-1234' ),
			'return' => true
		) );

		WP_Mock::wpFunction( 'current_user_can', array(
			'times'  => 1,
			'args'   => array( 'manage_options' ),
			'return' => true
		) );

		$controller->do_update( $key, 'max', 6, 'nonce' );
	}

	public function test_update_expires() {

		$controller = new \ITELIC\Admin\Licenses\Controller\Single();

		$key = $this->getMockBuilder( '\ITELIC\Key' )->disableOriginalConstructor()->getMock();
		$key->method( 'get_key' )->willReturn( 'abcd-1234' );
		$key->method( 'set_expires' )->with( \ITELIC\make_date_time( '2015-06-06', false ) );

		WP_Mock::wpFunction( 'wp_verify_nonce', array(
			'times'  => 1,
			'args'   => array( 'nonce', 'itelic-update-key-abcd-1234' ),
			'return' => true
		) );

		WP_Mock::wpFunction( 'current_user_can', array(
			'times'  => 1,
			'args'   => array( 'manage_options' ),
			'return' => true
		) );

		$controller->do_update( $key, 'expires', '2015-06-06', 'nonce' );
	}

	public function test_activation_nonce_check() {

		$controller = new \ITELIC\Admin\Licenses\Controller\Single();

		$key = $this->getMockBuilder( '\ITELIC\Key' )->disableOriginalConstructor()->getMock();
		$key->method( 'get_key' )->willReturn( 'abcd-1234' );

		WP_Mock::wpFunction( 'wp_verify_nonce', array(
			'times'  => 1,
			'args'   => array( 'nonce', 'itelic-remote-activate-key-abcd-1234' ),
			'return' => false
		) );

		$this->setExpectedException( '\InvalidArgumentException' );

		$controller->do_activation( $key, 'www.test.com', 'nonce' );
	}

	public function test_activation_permissions_check() {

		$controller = new \ITELIC\Admin\Licenses\Controller\Single();

		$key = $this->getMockBuilder( '\ITELIC\Key' )->disableOriginalConstructor()->getMock();
		$key->method( 'get_key' )->willReturn( 'abcd-1234' );

		WP_Mock::wpFunction( 'wp_verify_nonce', array(
			'times'  => 1,
			'args'   => array( 'nonce', 'itelic-remote-activate-key-abcd-1234' ),
			'return' => true
		) );

		WP_Mock::wpFunction( 'current_user_can', array(
			'times'  => 1,
			'args'   => array( 'manage_options' ),
			'return' => false
		) );

		$this->setExpectedException( '\InvalidArgumentException' );

		$controller->do_activation( $key, 'www.test.com', 'nonce' );
	}

	public function test_activation_created() {

		$controller = new \ITELIC\Admin\Licenses\Controller\Single();

		$key = $this->getMockBuilder( '\ITELIC\Key' )->disableOriginalConstructor()->getMock();
		$key->method( 'get_key' )->willReturn( 'abcd-1234' );

		WP_Mock::wpFunction( 'wp_verify_nonce', array(
			'times'  => 1,
			'args'   => array( 'nonce', 'itelic-remote-activate-key-abcd-1234' ),
			'return' => true
		) );

		WP_Mock::wpFunction( 'current_user_can', array(
			'times'  => 1,
			'args'   => array( 'manage_options' ),
			'return' => true
		) );

		WP_Mock::wpFunction( 'itelic_activate_license_key', array(
			'times'  => 1,
			'args'   => array( $key, 'www.test.com' ),
			'return' => $this->getMockBuilder( '\ITELIC\Activation' )->disableOriginalConstructor()->getMock()
		) );

		$controller->do_activation( $key, 'www.test.com', 'nonce' );
	}

	public function test_deactivation_nonce_check() {

		$controller = new \ITELIC\Admin\Licenses\Controller\Single();

		$activation = $this->getMockBuilder( '\ITELIC\Activation' )->disableOriginalConstructor()->getMock();
		$activation->method( 'get_pk' )->willReturn( 1 );

		WP_Mock::wpFunction( 'wp_verify_nonce', array(
			'times'  => 1,
			'args'   => array( 'nonce', 'itelic-remote-deactivate-1' ),
			'return' => false
		) );

		$this->setExpectedException( '\InvalidArgumentException' );

		$controller->do_deactivation( $activation, 'nonce' );
	}

	public function test_deactivation_permissions_check() {

		$controller = new \ITELIC\Admin\Licenses\Controller\Single();

		$activation = $this->getMockBuilder( '\ITELIC\Activation' )->disableOriginalConstructor()->getMock();
		$activation->method( 'get_pk' )->willReturn( 1 );

		WP_Mock::wpFunction( 'wp_verify_nonce', array(
			'times'  => 1,
			'args'   => array( 'nonce', 'itelic-remote-deactivate-1' ),
			'return' => true
		) );

		WP_Mock::wpFunction( 'current_user_can', array(
			'times'  => 1,
			'args'   => array( 'manage_options' ),
			'return' => false
		) );

		$this->setExpectedException( '\InvalidArgumentException' );

		$controller->do_deactivation( $activation, 'nonce' );
	}

	public function test_deactivation_completed() {

		$controller = new \ITELIC\Admin\Licenses\Controller\Single();

		$activation = $this->getMockBuilder( '\ITELIC\Activation' )->disableOriginalConstructor()->getMock();
		$activation->method( 'get_pk' )->willReturn( 1 );
		$activation->method( 'deactivate' );

		WP_Mock::wpFunction( 'wp_verify_nonce', array(
			'times'  => 1,
			'args'   => array( 'nonce', 'itelic-remote-deactivate-1' ),
			'return' => true
		) );

		WP_Mock::wpFunction( 'current_user_can', array(
			'times'  => 1,
			'args'   => array( 'manage_options' ),
			'return' => true
		) );

		$controller->do_deactivation( $activation, 'nonce' );
	}

	public function test_delete_nonce_check() {

		$controller = new \ITELIC\Admin\Licenses\Controller\Single();

		$activation = $this->getMockBuilder( '\ITELIC\Activation' )->disableOriginalConstructor()->getMock();
		$activation->method( 'get_pk' )->willReturn( 1 );

		WP_Mock::wpFunction( 'wp_verify_nonce', array(
			'times'  => 1,
			'args'   => array( 'nonce', 'itelic-remote-delete-1' ),
			'return' => false
		) );

		$this->setExpectedException( '\InvalidArgumentException' );

		$controller->do_delete( $activation, 'nonce' );
	}

	public function test_delete_permissions_check() {

		$controller = new \ITELIC\Admin\Licenses\Controller\Single();

		$activation = $this->getMockBuilder( '\ITELIC\Activation' )->disableOriginalConstructor()->getMock();
		$activation->method( 'get_pk' )->willReturn( 1 );

		WP_Mock::wpFunction( 'wp_verify_nonce', array(
			'times'  => 1,
			'args'   => array( 'nonce', 'itelic-remote-delete-1' ),
			'return' => true
		) );

		WP_Mock::wpFunction( 'current_user_can', array(
			'times'  => 1,
			'args'   => array( 'manage_options' ),
			'return' => false
		) );

		$this->setExpectedException( '\InvalidArgumentException' );

		$controller->do_delete( $activation, 'nonce' );
	}

	public function test_delete_completed() {

		$controller = new \ITELIC\Admin\Licenses\Controller\Single();

		$activation = $this->getMockBuilder( '\ITELIC\Activation' )->disableOriginalConstructor()->getMock();
		$activation->method( 'get_pk' )->willReturn( 1 );
		$activation->method( 'deactivate' );

		WP_Mock::wpFunction( 'wp_verify_nonce', array(
			'times'  => 1,
			'args'   => array( 'nonce', 'itelic-remote-delete-1' ),
			'return' => true
		) );

		WP_Mock::wpFunction( 'current_user_can', array(
			'times'  => 1,
			'args'   => array( 'manage_options' ),
			'return' => true
		) );

		$controller->do_delete( $activation, 'nonce' );
	}
}