<?php
/**
 * Test the license list.
 *
 * @author      Iron Bound Designs
 * @since       1.0
 * @copyright   2015 (c) Iron Bound Designs.
 * @license     GPLv2
 */

/**
 * Class ITELIC_Test_Admin_License_List
 */
class ITELIC_Test_Admin_License_List extends ITELIC_UnitTestCase {

	public function test_extend_nonce_check() {

		$controller = new \ITELIC\Admin\Licenses\Controller\ListC();

		$key = $this->getMockBuilder( '\ITELIC\Key' )->disableOriginalConstructor()->getMock();
		$key->method( 'get_key' )->willReturn( 'abcd-1234' );

		WP_Mock::wpFunction( 'wp_verify_nonce', array(
			'times'  => 1,
			'args'   => array( 'nonce', 'itelic-extend-key-abcd-1234' ),
			'return' => false
		) );

		$this->setExpectedException( '\InvalidArgumentException' );

		$controller->do_extend( $key, 'nonce' );
	}

	public function test_extend_permissions_check() {

		$controller = new \ITELIC\Admin\Licenses\Controller\ListC();

		$key = $this->getMockBuilder( '\ITELIC\Key' )->disableOriginalConstructor()->getMock();
		$key->method( 'get_key' )->willReturn( 'abcd-1234' );

		WP_Mock::wpFunction( 'wp_verify_nonce', array(
			'times'  => 1,
			'args'   => array( 'nonce', 'itelic-extend-key-abcd-1234' ),
			'return' => true
		) );

		WP_Mock::wpFunction( 'current_user_can', array(
			'times'  => 1,
			'args'   => array( 'manage_options' ),
			'return' => false
		) );

		$this->setExpectedException( '\InvalidArgumentException' );

		$controller->do_extend( $key, 'nonce' );
	}

	public function test_extend_completed() {

		$controller = new \ITELIC\Admin\Licenses\Controller\ListC();

		$key = $this->getMockBuilder( '\ITELIC\Key' )->disableOriginalConstructor()->getMock();
		$key->method( 'get_key' )->willReturn( 'abcd-1234' );
		$key->method( 'extend' );

		WP_Mock::wpFunction( 'wp_verify_nonce', array(
			'times'  => 1,
			'args'   => array( 'nonce', 'itelic-extend-key-abcd-1234' ),
			'return' => true
		) );

		WP_Mock::wpFunction( 'current_user_can', array(
			'times'  => 1,
			'args'   => array( 'manage_options' ),
			'return' => false
		) );

		$this->setExpectedException( '\InvalidArgumentException' );

		$controller->do_extend( $key, 'nonce' );
	}

	public function test_max_invalid_direction() {

		$controller = new \ITELIC\Admin\Licenses\Controller\ListC();

		$key = $this->getMockBuilder( '\ITELIC\Key' )->disableOriginalConstructor()->getMock();

		$this->setExpectedException( '\InvalidArgumentException' );

		$controller->do_max( $key, 'garbage', 'nonce' );
	}

	public function test_max_nonce_check() {

		$controller = new \ITELIC\Admin\Licenses\Controller\ListC();

		$key = $this->getMockBuilder( '\ITELIC\Key' )->disableOriginalConstructor()->getMock();
		$key->method( 'get_key' )->willReturn( 'abcd-1234' );

		WP_Mock::wpFunction( 'wp_verify_nonce', array(
			'times'  => 1,
			'args'   => array( 'nonce', 'itelic-max-key-abcd-1234' ),
			'return' => false
		) );

		$this->setExpectedException( '\InvalidArgumentException' );

		$controller->do_max( $key, 'up', 'nonce' );
	}

	public function test_max_permissions_check() {

		$controller = new \ITELIC\Admin\Licenses\Controller\ListC();

		$key = $this->getMockBuilder( '\ITELIC\Key' )->disableOriginalConstructor()->getMock();
		$key->method( 'get_key' )->willReturn( 'abcd-1234' );

		WP_Mock::wpFunction( 'wp_verify_nonce', array(
			'times'  => 1,
			'args'   => array( 'nonce', 'itelic-max-key-abcd-1234' ),
			'return' => true
		) );

		WP_Mock::wpFunction( 'current_user_can', array(
			'times'  => 1,
			'args'   => array( 'manage_options' ),
			'return' => false
		) );

		$this->setExpectedException( '\InvalidArgumentException' );

		$controller->do_max( $key, 'up', 'nonce' );
	}

	public function test_max_completed_up() {

		$controller = new \ITELIC\Admin\Licenses\Controller\ListC();

		$key = $this->getMockBuilder( '\ITELIC\Key' )->disableOriginalConstructor()->getMock();
		$key->method( 'get_key' )->willReturn( 'abcd-1234' );
		$key->method( 'get_max' )->willReturn( 1 );
		$key->method( 'set_max' )->with( 2 );

		WP_Mock::wpFunction( 'wp_verify_nonce', array(
			'times'  => 1,
			'args'   => array( 'nonce', 'itelic-max-key-abcd-1234' ),
			'return' => true
		) );

		WP_Mock::wpFunction( 'current_user_can', array(
			'times'   => 1,
			'args'    => array( 'manage_options' ),
			'return ' => false
		) );

		$this->setExpectedException( '\InvalidArgumentException' );

		$controller->do_max( $key, 'up', 'nonce' );
	}

	public function test_max_completed_down() {

		$controller = new \ITELIC\Admin\Licenses\Controller\ListC();

		$key = $this->getMockBuilder( '\ITELIC\Key' )->disableOriginalConstructor()->getMock();
		$key->method( 'get_key' )->willReturn( 'abcd-1234' );
		$key->method( 'get_max' )->willReturn( 2 );
		$key->method( 'set_max' )->with( 1 );

		WP_Mock::wpFunction( 'wp_verify_nonce', array(
			'times'  => 1,
			'args'   => array( 'nonce', 'itelic-max-key-abcd-1234' ),
			'return' => true
		) );

		WP_Mock::wpFunction( 'current_user_can', array(
			'times'   => 1,
			'args'    => array( 'manage_options' ),
			'return ' => false
		) );

		$this->setExpectedException( '\InvalidArgumentException' );

		$controller->do_max( $key, 'down', 'nonce' );
	}

}