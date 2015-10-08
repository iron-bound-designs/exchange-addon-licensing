<?php
/**
 * Renewal Reminder Sender
 *
 * @author Iron Bound Designs
 * @since  1.0
 */

namespace ITELIC\Renewal\Reminder;

use IronBound\DB\Manager;
use ITELIC\Key;
use IronBound\WP_Notifications\Notification;
use IronBound\WP_Notifications\Template\Factory;
use IronBound\WP_Notifications\Template\Manager as Template_Manager;
use ITELIC\Renewal\Discount;
use ITELIC\Renewal\Reminder;

/**
 * Class Sender
 *
 * @package ITELIC\Renewal\Reminder
 */
class Sender {

	/**
	 * Constructor.
	 */
	public function __construct() {
		add_action( 'it_exchange_itelic_daily_schedule', array(
			$this,
			'on_schedule'
		) );
	}

	/**
	 * Fires on a scheduled event. Responsible for determining if and which
	 * reminders we should send.
	 *
	 * @since 1.0
	 */
	public function on_schedule() {

		// stripe calls get_current_screen during this filter
		// this isn't provided during cron
		remove_filter( 'it_exchange_get_currencies', 'it_exchange_stripe_addon_get_currency_options' );

		$notifications = $this->get_notifications();

		if ( empty( $notifications ) ) {
			return;
		}

		$queue    = \ITELIC\get_queue_processor( 'itelic-renewal-reminder' );
		$strategy = \ITELIC\get_notification_strategy();

		$queue->process( $notifications, $strategy );
	}

	/**
	 * Send the notifications.
	 *
	 * @return Notification[]
	 */
	public function get_notifications() {

		$reminders = Reminder\CPT::get_reminders();

		if ( empty( $reminders ) ) {
			return array();
		}

		$date_to_reminder = array();

		$table = Manager::get( 'itelic-keys' );
		$tn    = $table->get_table_name( $GLOBALS['wpdb'] );

		// retrieve key information and just the date value ( no time ) of when the key expires
		$sql = "SELECT *, Date(`expires`) AS EXP_DAY FROM {$tn} WHERE ";

		// START manual first record
		$sql .= $this->convert_interval_to_between( $reminders[0]->get_interval() );
		$date_to_reminder[ $this->convert_interval_to_date( $reminders[0]->get_interval() )->format( "Y-m-d" ) ] = $reminders[0];

		unset( $reminders[0] );
		// END manual first record

		foreach ( $reminders as $reminder ) {

			$interval = $reminder->get_interval();
			$date     = $this->convert_interval_to_date( $interval );

			// search for keys that expire any time during the day of the current reminder.
			$sql .= " OR " . $this->convert_datetime_to_between( $date );

			// store a reference of that entire day to the reminder object for later use.
			$date_to_reminder[ $date->format( "Y-m-d" ) ] = $reminder;
		}

		/*
		 * SQL generated is similar to:
		 * SELECT *, Date(`expires`) AS EXP_DAY FROM wp_itelic_keys WHERE
		 * (`expires` BETWEEN '2015-03-08' AND '2015-03-08 23:59:59') OR
		 * (`expires` BETWEEN '2017-03-01' AND '2017-03-01 23:59:59')
		 */
		$result = $GLOBALS['wpdb']->get_results( $sql );

		if ( empty( $result ) ) {
			return array();
		}

		$expire_to_key = array();

		foreach ( $result as $record ) {
			$expire_to_key[ $record->EXP_DAY ] = itelic_get_key_from_data( $record );
		}

		$notifications = array();
		$manager       = Factory::make( 'itelic-renewal-reminder' );

		foreach ( $expire_to_key as $expire => $key ) {
			$notifications[] = $this->make_notification( $date_to_reminder[ $expire ], $key, $manager );
		}

		return $notifications;
	}

	/**
	 * Make the notification object.
	 *
	 * @since 1.0
	 *
	 * @param Reminder         $reminder
	 * @param Key              $key
	 * @param Template_Manager $manager
	 *
	 * @return Notification
	 */
	protected function make_notification( Reminder $reminder, Key $key, Template_Manager $manager ) {

		$template     = $reminder->get_post();
		$notification = new Notification( $key->get_customer()->wp_user, $manager, $template->post_content, $template->post_title );

		$notification->add_data_source( $key );
		$notification->add_data_source( new Discount( $key ) );

		return $notification;
	}

	/**
	 * Convert an interval to a between statement.
	 *
	 * @since 1.0
	 *
	 * @param \DateInterval $interval
	 *
	 * @return string
	 */
	protected function convert_interval_to_between( \DateInterval $interval ) {
		return $this->convert_datetime_to_between( $this->convert_interval_to_date( $interval ) );
	}

	/**
	 * Convert an interval to the corresponding date in the future.
	 *
	 * @param \DateInterval $interval
	 *
	 * @return \DateTime
	 */
	protected function convert_interval_to_date( \DateInterval $interval ) {
		$now = \ITELIC\make_date_time();

		return $now->sub( $interval );
	}

	/**
	 * Convert a datetime to a between sql statement.
	 *
	 * @since 1.0
	 *
	 * @param \DateTime $date_time
	 *
	 * @return string
	 */
	protected function convert_datetime_to_between( \DateTime $date_time ) {

		$after  = $date_time->format( "Y-m-d" );
		$before = "$after 23:59:59";

		return "`expires` BETWEEN '$after' AND '$before'";
	}
}