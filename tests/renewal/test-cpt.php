<?php
/**
 * Test the CPT.
 *
 * @author Iron Bound Designs
 * @since  1.0
 */
use ITELIC\Renewal\Reminder;
use ITELIC\Renewal\Reminder\CPT;

/**
 * Class ITELIC_Test_Renewal_CPT
 */
class ITELIC_Test_Renewal_CPT extends ITELIC_UnitTestCase {

	public function test_cpt_slug() {
		$this->assertEquals( 'it_exchange_licrenew', CPT::TYPE );
	}

	public function test_shortcode_name() {
		$this->assertEquals( 'itelic_renewal', CPT::SHORTCODE );
	}

	public function test_post_type_exists() {
		$this->assertNotNull( get_post_type_object( CPT::TYPE ) );
	}

	public function test_meta_box_saved() {

		$cpt = new CPT();

		WP_Mock::wpFunction( 'wp_verify_nonce', array(
			'times'  => 1,
			'args'   => array( 'nonce-value', 'itelic-renewal-reminders-metabox' ),
			'return' => true
		) );

		WP_Mock::wpFunction( 'update_post_meta', array(
			'times'  => 1,
			'args'   => array( 1, '_itelic_renewal_reminder_days', 3 ),
			'return' => true
		) );

		WP_Mock::wpFunction( 'update_post_meta', array(
			'times'  => 1,
			'args'   => array( 1, '_itelic_renewal_reminder_boa', Reminder::TYPE_BEFORE ),
			'return' => true
		) );

		$cpt->do_save( 1, array(
			'itelic_reminder_nonce' => 'nonce-value',
			'itelic_reminder'       => array(
				'days' => 3,
				'boa'  => Reminder::TYPE_BEFORE
			)
		) );
	}

	public function test_meta_box_save_rejects_invalid_before_or_after_setting() {

		$cpt = new CPT();

		WP_Mock::wpFunction( 'wp_verify_nonce', array(
			'times'  => 1,
			'args'   => array( 'nonce-value', 'itelic-renewal-reminders-metabox' ),
			'return' => true
		) );

		WP_Mock::wpFunction( 'update_post_meta', array(
			'times'  => 1,
			'args'   => array( 1, '_itelic_renewal_reminder_days', 3 ),
			'return' => true
		) );

		WP_Mock::wpFunction( 'update_post_meta', array(
			'times'  => 1,
			'args'   => array( 1, '_itelic_renewal_reminder_boa', 'before' ),
			'return' => true
		) );

		$cpt->do_save( 1, array(
			'itelic_reminder_nonce' => 'nonce-value',
			'itelic_reminder'       => array(
				'days' => 3,
				'boa'  => 'garbage'
			)
		) );
	}


}