<?php
/**
 * Process a queue of notifications using WP Cron.
 *
 * @author      Iron Bound Designs
 * @since       1.0
 *
 * @copyright   Copyright (c) 2015, Iron Bound Designs, Inc.
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License v2 or later
 */

namespace IronBound\WP_Notifications\Queue;

use IronBound\WP_Notifications\Contract;
use IronBound\WP_Notifications\Queue\Storage\Contract as Storage;
use IronBound\WP_Notifications\Strategy\Strategy;

/**
 * Class WP_Cron
 * @package IronBound\WP_Notifications\Queue
 */
class WP_Cron implements Queue {

	const CRON_ACTION = 'ibd-wp-notifications-cron-notification';

	/**
	 * @var Storage
	 */
	protected $storage;

	/**
	 * Constructor.
	 *
	 * @since 1.0
	 *
	 * @param Storage $storage
	 */
	public function __construct( Storage $storage ) {
		if ( ! has_action( self::CRON_ACTION, array( $this, 'cron_callback' ) ) ) {
			add_action( self::CRON_ACTION, array( $this, 'cron_callback' ) );
		}

		$this->storage = $storage;
	}

	/**
	 * Process a batch of notifications.
	 *
	 * @since 1.0
	 *
	 * @param Contract[] $notifications
	 * @param Strategy   $strategy
	 *
	 * @throws \Exception
	 */
	public function process( array $notifications, Strategy $strategy ) {

		$hash = uniqid();

		if ( ! $this->storage->store_notifications( $hash, $notifications, $strategy ) ) {
			throw new \Exception( "Unable to store notifications." );
		}

		$this->cron_callback( $hash );
	}

	/**
	 * Get the timestamp of the next event time.
	 *
	 * @since 1.0
	 *
	 * @return int
	 */
	protected static function get_next_event_time() {
		return time() + ( 2 * 60 );
	}

	/**
	 * Callback function given to WP Cron.
	 *
	 * @since 1.0
	 *
	 * @param string $hash
	 */
	public function cron_callback( $hash ) {

		$notifications = $this->storage->get_notifications( $hash );

		if ( empty( $notifications ) ) {
			return;
		}

		$strategy = $this->storage->get_notifications_strategy( $hash );

		$rate = $strategy->get_suggested_rate();

		if ( count( $notifications ) > $rate ) {
			$to_process = array_slice( $notifications, 0, $rate, true );
		} else {
			$to_process = $notifications;
		}

		/**
		 * @var Contract $notification
		 */
		foreach ( $to_process as $key => $notification ) {

			/*
			 * On the off chance that we timeout while processing the notifications,
			 * double check that this notification hasn't already been sent.
			 */
			if ( $notification->was_sent() ) {
				unset( $notifications[ $key ] );

				continue;
			}

			try {

				$notification->set_strategy( $strategy );

				if ( $notification->send() ) {
					unset( $notifications[ $key ] );
				}

			}
			catch ( \Exception $e ) {

			}
		}

		if ( empty( $notifications ) ) {
			$this->storage->clear_notifications( $hash );
		} else {
			$this->storage->store_notifications( $hash, $notifications );

			// we pass along a garbage uniquid to prevent WP Cron from denying our event since it is less than 10 minutes since the last
			wp_schedule_single_event( self::get_next_event_time(), self::CRON_ACTION, array( $hash, uniqid() ) );
		}
	}
}