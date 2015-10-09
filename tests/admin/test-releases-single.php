<?php
/**
 * Test the single release controller.
 *
 * @author      Iron Bound Designs
 * @since       1.0
 * @copyright   2015 (c) Iron Bound Designs.
 * @license     GPLv2
 */
use ITELIC\Admin\Releases\Controller\Single;

/**
 * Class ITELIC_Test_Admin_Releases_Single
 */
class ITELIC_Test_Admin_Releases_Single extends ITELIC_UnitTestCase {

	public function test_update_nonce_check() {

		$controller = new Single();

		$release = $this->getMockBuilder( '\ITELIC\Release' )->disableOriginalConstructor()->getMock();
		$release->method( 'get_pk' )->willReturn( 1 );

		WP_Mock::wpFunction( 'wp_verify_nonce', array(
			'times'  => 1,
			'args'   => array( 'nonce', 'itelic-update-release-1' ),
			'return' => false
		) );

		$this->setExpectedException( '\InvalidArgumentException' );

		$controller->do_update( $release, 'prop', 'val', 'nonce' );
	}

	public function test_update_permissions_check() {

		$controller = new Single();

		$release = $this->getMockBuilder( '\ITELIC\Release' )->disableOriginalConstructor()->getMock();
		$release->method( 'get_pk' )->willReturn( 1 );

		WP_Mock::wpFunction( 'wp_verify_nonce', array(
			'times'  => 1,
			'args'   => array( 'nonce', 'itelic-update-release-1' ),
			'return' => true
		) );

		WP_Mock::wpFunction( 'current_user_can', array(
			'times'  => 1,
			'args'   => array( 'manage_options' ),
			'return' => false
		) );

		$this->setExpectedException( '\InvalidArgumentException' );

		$controller->do_update( $release, 'prop', 'val', 'nonce' );
	}

	public function test_update_invalid_prop_is_rejected() {

		$controller = new Single();

		$release = $this->getMockBuilder( '\ITELIC\Release' )->disableOriginalConstructor()->getMock();
		$release->method( 'get_pk' )->willReturn( 1 );

		WP_Mock::wpFunction( 'wp_verify_nonce', array(
			'times'  => 1,
			'args'   => array( 'nonce', 'itelic-update-release-1' ),
			'return' => true
		) );

		WP_Mock::wpFunction( 'current_user_can', array(
			'times'  => 1,
			'args'   => array( 'manage_options' ),
			'return' => true
		) );

		$this->setExpectedException( '\InvalidArgumentException' );

		$controller->do_update( $release, 'invalid', 'val', 'nonce' );
	}

	public function test_update_status() {

		$controller = new Single();

		$release = $this->getMockBuilder( '\ITELIC\Release' )->disableOriginalConstructor()->getMock();
		$release->method( 'get_pk' )->willReturn( 1 );
		$release->method( 'set_status' )->with( 'new-status' );

		WP_Mock::wpFunction( 'wp_verify_nonce', array(
			'times'  => 1,
			'args'   => array( 'nonce', 'itelic-update-release-1' ),
			'return' => true
		) );

		WP_Mock::wpFunction( 'current_user_can', array(
			'times'  => 1,
			'args'   => array( 'manage_options' ),
			'return' => true
		) );

		$controller->do_update( $release, 'status', 'new-status', 'nonce' );
	}

	public function test_update_type() {

		$controller = new Single();

		$release = $this->getMockBuilder( '\ITELIC\Release' )->disableOriginalConstructor()->getMock();
		$release->method( 'get_pk' )->willReturn( 1 );
		$release->method( 'set_type' )->with( 'new-type' );

		WP_Mock::wpFunction( 'wp_verify_nonce', array(
			'times'  => 1,
			'args'   => array( 'nonce', 'itelic-update-release-1' ),
			'return' => true
		) );

		WP_Mock::wpFunction( 'current_user_can', array(
			'times'  => 1,
			'args'   => array( 'manage_options' ),
			'return' => true
		) );

		$controller->do_update( $release, 'type', 'new-type', 'nonce' );
	}

	public function test_update_version() {

		$controller = new Single();

		$release = $this->getMockBuilder( '\ITELIC\Release' )->disableOriginalConstructor()->getMock();
		$release->method( 'get_pk' )->willReturn( 1 );
		$release->method( 'set_version' )->with( 'new-version' );

		WP_Mock::wpFunction( 'wp_verify_nonce', array(
			'times'  => 1,
			'args'   => array( 'nonce', 'itelic-update-release-1' ),
			'return' => true
		) );

		WP_Mock::wpFunction( 'current_user_can', array(
			'times'  => 1,
			'args'   => array( 'manage_options' ),
			'return' => true
		) );

		$controller->do_update( $release, 'version', 'new-version', 'nonce' );
	}

	public function test_update_download() {

		$controller = new Single();

		$release = $this->getMockBuilder( '\ITELIC\Release' )->disableOriginalConstructor()->getMock();
		$release->method( 'get_pk' )->willReturn( 1 );
		$release->method( 'set_download' )->with( 1 );

		WP_Mock::wpFunction( 'wp_verify_nonce', array(
			'times'  => 1,
			'args'   => array( 'nonce', 'itelic-update-release-1' ),
			'return' => true
		) );

		WP_Mock::wpFunction( 'current_user_can', array(
			'times'  => 1,
			'args'   => array( 'manage_options' ),
			'return' => true
		) );

		$controller->do_update( $release, 'download', 1, 'nonce' );
	}

	public function test_update_changelog() {

		$controller = new Single();

		$release = $this->getMockBuilder( '\ITELIC\Release' )->disableOriginalConstructor()->getMock();
		$release->method( 'get_pk' )->willReturn( 1 );
		$release->method( 'set_changelog' )->with( 'new-changes' );

		WP_Mock::wpFunction( 'wp_verify_nonce', array(
			'times'  => 1,
			'args'   => array( 'nonce', 'itelic-update-release-1' ),
			'return' => true
		) );

		WP_Mock::wpFunction( 'current_user_can', array(
			'times'  => 1,
			'args'   => array( 'manage_options' ),
			'return' => true
		) );

		$controller->do_update( $release, 'changelog', 'new-changes', 'nonce' );
	}

	public function test_update_security_message() {

		$controller = new Single();

		$release = $this->getMockBuilder( '\ITELIC\Release' )->disableOriginalConstructor()->getMock();
		$release->method( 'get_pk' )->willReturn( 1 );
		$release->method( 'update_meta' )->with( 'security-message', 'new-message' );

		WP_Mock::wpFunction( 'wp_verify_nonce', array(
			'times'  => 1,
			'args'   => array( 'nonce', 'itelic-update-release-1' ),
			'return' => true
		) );

		WP_Mock::wpFunction( 'current_user_can', array(
			'times'  => 1,
			'args'   => array( 'manage_options' ),
			'return' => true
		) );

		$controller->do_update( $release, 'security-message', 'new-message', 'nonce' );
	}

	/**
	 * @dataProvider listeners_data_provider
	 */
	public function test_registered_listeners( $listener ) {

		$manager = \IronBound\WP_Notifications\Template\Factory::make( 'itelic-outdated-customers' );

		$this->assertNotInstanceOf( '\IronBound\WP_Notifications\Template\Null_Listener', $manager->get_listener( $listener ) );
	}

	public function listeners_data_provider() {
		return array(
			'product name' => array( 'product_name' ),
			'version'      => array( 'version' ),
			'changelog'    => array( 'changelog' ),
			'install list' => array( 'install_list' ),
		);
	}

}