<?php
/**
 * Test the single release controller.
 *
 * @author      Iron Bound Designs
 * @since       1.0
 * @copyright   2015 (c) Iron Bound Designs.
 * @license     GPLv2
 */
use ITELIC\Activation;
use ITELIC\Admin\Releases\Controller\Single;
use ITELIC\Key;
use ITELIC\Release;

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

	/**
	 * @group failing
	 */
	public function test_get_notifications() {

		$users = $this->factory->user->create_many( 4 );

		$product = $this->product_factory->create_and_get();
		$file    = $this->factory->attachment->create_object( 'file.zip', $product->ID, array(
			'post_mime_type' => 'application/zip'
		) );

		/** @var Release $r1 */
		$r1 = $this->release_factory->create_and_get( array(
			'product' => $product->ID,
			'file'    => $file,
			'version' => '1.0',
			'type'    => Release::TYPE_MAJOR
		) );

		$r1->activate( \ITELIC\make_date_time( '-2 week' ) );

		/** @var Release $r2 */
		$r2 = $this->release_factory->create_and_get( array(
			'product' => $product->ID,
			'file'    => $file,
			'version' => '1.1',
			'type'    => Release::TYPE_MAJOR
		) );

		$r2->activate( \ITELIC\make_date_time( '-1 week' ) );

		/** @var Release $r3 */
		$r3 = $this->release_factory->create_and_get( array(
			'product' => $product->ID,
			'file'    => $file,
			'version' => '1.2',
			'type'    => Release::TYPE_MAJOR
		) );

		$r3->activate();

		/** @var Key $k1 */
		$k1 = $this->key_factory->create_and_get( array(
			'product'  => $product->ID,
			'customer' => $users[0] // 1
		) );

		/** @var Activation $a1 */
		$a1 = $this->activation_factory->create_and_get( array(
			'key'        => $k1->get_pk(),
			'location'   => 'www.testa.com',
			'activation' => \ITELIC\make_date_time( '-3 weeks' ),
			'release'    => $r1
		) );

		/** @var Key $k2 */
		$k2 = $this->key_factory->create_and_get( array(
			'product'  => $product->ID,
			'customer' => $users[1] // 2
		) );

		/** @var Activation $a2 */
		$a2 = $this->activation_factory->create_and_get( array(
			'key'        => $k2->get_pk(),
			'location'   => 'www.testb.com',
			'activation' => \ITELIC\make_date_time( '-3 weeks' ),
			'release'    => $r1
		) );

		/** @var Key $k3 */
		$k3 = $this->key_factory->create_and_get( array(
			'product'  => $product->ID,
			'customer' => $users[2] // 3
		) );

		/** @var Activation $a3 */
		$a3 = $this->activation_factory->create_and_get( array(
			'key'        => $k3->get_pk(),
			'location'   => 'www.testc.com',
			'activation' => \ITELIC\make_date_time( '-3 weeks' ),
			'release'    => $r1
		) );

		/** @var Key $k4 */
		$k4 = $this->key_factory->create_and_get( array(
			'product'  => $this->product_factory->create(),
			'customer' => $users[3] // 4
		) );

		/** @var Activation $a4 */
		$a4 = $this->activation_factory->create_and_get( array(
			'key'      => $k4->get_pk(),
			'location' => 'www.testc.com',
			'release'  => $r1
		) );

		$this->update_factory->create( array(
			'activation' => $a1->get_pk(),
			'release'    => $r2
		) );

		$this->update_factory->create( array(
			'activation' => $a2->get_pk(),
			'release'    => $r3
		) );

		$controller    = new Single();
		$notifications = $controller->get_notifications( $r2, 'msg', 'subject' );

		foreach ( $notifications as $notification ) {
			
			if ( $notification->get_recipient()->ID != $users[2] ) {
				$this->fail( 'Wrong customer received notification' );
			} else {
				return;
			}
		}

		$this->fail( 'No notifications sent.' );
	}

}