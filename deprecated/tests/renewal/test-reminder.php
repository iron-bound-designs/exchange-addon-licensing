<?php
/**
 * Test the renewal reminder object.
 *
 * @author      Iron Bound Designs
 * @since       1.0
 * @copyright   2015 (c) Iron Bound Designs.
 * @license     GPLv2
 */

use ITELIC\Renewal\Reminder;

/**
 * Class ITELIC_Test_Renewal_Reminder
 */
class ITELIC_Test_Renewal_Reminder extends ITELIC_UnitTestCase {

	public function test_exception_thrown_if_invalid_post_type() {

		$post = new WP_Post( (object) array(
			'post_type' => 'garbage'
		) );

		$this->setExpectedException( '\InvalidArgumentException' );

		new Reminder( $post );
	}

	public function test_exception_thrown_if_invalid_before_or_after() {
		$post = new WP_Post( (object) array(
			'post_type' => Reminder\CPT::TYPE,
			'ID'        => 1
		) );

		WP_Mock::wpFunction( 'get_post_meta', array(
			'times'  => 1,
			'args'   => array( 1, '_itelic_renewal_reminder_days', true ),
			'return' => 5
		) );

		WP_Mock::wpFunction( 'get_post_meta', array(
			'times'  => 1,
			'args'   => array( 1, '_itelic_renewal_reminder_boa', true ),
			'return' => 'garbage'
		) );

		$this->setExpectedException( '\InvalidArgumentException' );

		new Reminder( $post );
	}

	public function test_interval_before() {

		$post = new WP_Post( (object) array(
			'post_type' => Reminder\CPT::TYPE,
			'ID'        => 1
		) );

		WP_Mock::wpFunction( 'get_post_meta', array(
			'times'  => 1,
			'args'   => array( 1, '_itelic_renewal_reminder_days', true ),
			'return' => 5
		) );

		WP_Mock::wpFunction( 'get_post_meta', array(
			'times'  => 1,
			'args'   => array( 1, '_itelic_renewal_reminder_boa', true ),
			'return' => Reminder::TYPE_BEFORE
		) );

		$reminder = new Reminder( $post );

		$expects = new DateInterval( 'P5D' );

		$this->assertEquals( $expects, $reminder->get_interval() );
	}

	public function test_interval_after() {

		$post = new WP_Post( (object) array(
			'post_type' => Reminder\CPT::TYPE,
			'ID'        => 1
		) );

		WP_Mock::wpFunction( 'get_post_meta', array(
			'times'  => 1,
			'args'   => array( 1, '_itelic_renewal_reminder_days', true ),
			'return' => 5
		) );

		WP_Mock::wpFunction( 'get_post_meta', array(
			'times'  => 1,
			'args'   => array( 1, '_itelic_renewal_reminder_boa', true ),
			'return' => Reminder::TYPE_AFTER
		) );

		$reminder = new Reminder( $post );

		$expects         = new DateInterval( 'P5D' );
		$expects->invert = true;

		$this->assertEquals( $expects, $reminder->get_interval() );
	}
}