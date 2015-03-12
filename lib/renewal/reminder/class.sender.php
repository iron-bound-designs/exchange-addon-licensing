<?php
/**
 * Renewal Reminder Sender
 *
 * @author Iron Bound Designs
 * @since  1.0
 */

/**
 * Class ITELIC_Renewal_Reminder_Sender
 */
class ITELIC_Renewal_Reminder_Sender {

	/**
	 * Constructor.
	 */
	public function __construct() {
		add_action( 'it_exchange_itelic_daily_schedule', array( $this, 'on_schedule' ) );
	}

	/**
	 * Fires on a scheduled event. Responsible for determining if and which reminders we should send.
	 *
	 * @since 1.0
	 */
	public function on_schedule() {

		// stripe calls get_current_screen during this filter
		// this isn't provided during cron
		remove_filter( 'it_exchange_get_currencies', 'it_exchange_stripe_addon_get_currency_options' );

		$reminders = ITELIC_Renewal_Reminder_Type::get_reminders();

		if ( empty( $reminders ) ) {
			return;
		}

		$date_to_reminder = array();

		$db = ITELIC_DB_Keys::instance();
		$tn = $db->get_table_name();

		// retrieve key information and just the date value ( no time ) of when the key expires
		$sql = "SELECT *, Date(`expires`) AS EXP_DAY FROM {$tn} WHERE ";

		// START manual first record
		$sql .= $this->convert_interval_to_between( $reminders[0]->get_interval() );
		$date_to_reminder[ $this->convert_interval_to_date( $reminders[0]->get_interval() )->format( "Y-m-d" ) ] = $reminders[0];

		unset( $reminders[0] );
		// END manual first record

		foreach ( $reminders as $reminder ) {
			// search for keys that expire any time during the day of the current reminder.
			$sql .= " OR " . $this->convert_interval_to_between( $reminder->get_interval() );

			// store a reference of that entire day to the reminder object for later use.
			$date_to_reminder[ $this->convert_interval_to_date( $reminder->get_interval() )->format( "Y-m-d" ) ] = $reminder;
		}

		/*
		 * SQL generated is similar to:
		 * SELECT *, Date(`expires`) AS EXP_DAY FROM wp_itelic_keys WHERE
		 * (`expires` BETWEEN '2015-03-08' AND '2015-03-08 23:59:59') OR
		 * (`expires` BETWEEN '2017-03-01' AND '2017-03-01 23:59:59')
		 */
		$result = $GLOBALS['wpdb']->get_results( $sql );

		if ( empty( $result ) ) {
			return;
		}

		$expire_to_key = array();

		foreach ( $result as $record ) {
			$expire_to_key[ $record->EXP_DAY ] = itelic_get_key_from_data( $record );
		}

		foreach ( $expire_to_key as $expire => $key ) {
			$this->send( $date_to_reminder[ $expire ], $key );
		}
	}

	/**
	 * Send the reminder.
	 *
	 * @since 1.0
	 *
	 * @param ITELIC_Renewal_Reminder $reminder
	 * @param ITELIC_Key              $key
	 */
	protected function send( ITELIC_Renewal_Reminder $reminder, ITELIC_Key $key ) {

		$template = new ITELIC_Renewal_Reminder_Template( $reminder, $key );

		$email = new IBD_Email( $template, $key->get_customer()->wp_user );
		$email->send();
	}

	/**
	 * Convert an interval to a between statement.
	 *
	 * @since 1.0
	 *
	 * @param DateInterval $interval
	 *
	 * @return string
	 */
	protected function convert_interval_to_between( DateInterval $interval ) {
		return $this->convert_datetime_to_between( $this->convert_interval_to_date( $interval ) );
	}

	/**
	 * Convert an interval to the corresponding date in the future.
	 *
	 * @param DateInterval $interval
	 *
	 * @return DateTime
	 */
	protected function convert_interval_to_date( DateInterval $interval ) {
		$now = new DateTime();

		return $now->add( $interval );
	}

	/**
	 * Convert a datetime to a between sql statement.
	 *
	 * @since 1.0
	 *
	 * @param DateTime $date_time
	 *
	 * @return string
	 */
	protected function convert_datetime_to_between( DateTime $date_time ) {

		$after  = $date_time->format( "Y-m-d" );
		$before = "$after 23:59:59";

		return "`expires` BETWEEN '$after' AND '$before'";
	}


}