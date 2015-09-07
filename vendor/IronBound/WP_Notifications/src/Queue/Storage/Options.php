<?php
/**
 * Store notifications using the options db.
 *
 * @author Iron Bound Designs
 * @since  1.0
 */

namespace IronBound\WP_Notifications\Queue\Storage;

use IronBound\WP_Notifications\Contract as Notification;
use IronBound\WP_Notifications\Strategy\Strategy;

/**
 * Class Options
 * @package IronBound\WP_Notifications\Queue\Storage
 */
class Options implements Contract {

	/**
	 * @var string
	 */
	private $bucket;

	/**
	 * Constructor.
	 *
	 * @since 1.0
	 *
	 * @param string $bucket
	 */
	public function __construct( $bucket ) {
		$this->bucket = $bucket;
	}

	/**
	 * Store a set of notifications.
	 *
	 * If notifications is empty, it will clear the set.
	 *
	 * @since 1.0
	 *
	 * @param string         $queue_id
	 * @param Notification[] $notifications
	 * @param Strategy       $strategy If null, previously set strategy will be used.
	 *
	 * @return bool
	 */
	public function store_notifications( $queue_id, array $notifications, Strategy $strategy = null ) {

		$all = get_option( $this->bucket, array() );

		$found = empty( $all ) ? false : true;

		if ( empty( $notifications ) ) {
			return $this->clear_notifications( $queue_id );
		}

		$all[ $queue_id ] = array(
			'notifications' => $notifications
		);

		if ( isset( $strategy ) ) {
			$all[ $queue_id ]['strategy'] = $strategy;
		}

		if ( $found ) {
			return update_option( $this->bucket, $all );
		} else {
			return add_option( $this->bucket, $all, '', 'no' );
		}
	}

	/**
	 * Get a set of notifications.
	 *
	 * @since 1.0
	 *
	 * @param string $queue_id
	 *
	 * @return Notification[]|null
	 */
	public function get_notifications( $queue_id ) {

		$all = get_option( $this->bucket, array() );

		if ( isset( $all[ $queue_id ]['notifications'] ) ) {
			return $all[ $queue_id ]['notifications'];
		} else {
			return null;
		}
	}

	/**
	 * Get the strategy for a set of notifications.
	 *
	 * @since 1.0
	 *
	 * @param string $queue_id
	 *
	 * @return Strategy|null
	 */
	public function get_notifications_strategy( $queue_id ) {

		$all = get_option( $this->bucket, array() );

		if ( isset( $all[ $queue_id ]['strategy'] ) ) {
			return $all[ $queue_id ]['strategy'];
		} else {
			return null;
		}
	}

	/**
	 * Clear a set of notifications.
	 *
	 * @since 1.0
	 *
	 * @param string $queue_id
	 *
	 * @return bool
	 */
	public function clear_notifications( $queue_id ) {

		$all = get_option( $this->bucket, array() );

		if ( ! isset( $all[ $queue_id ] ) ) {
			return false;
		}

		unset( $all[ $queue_id ] );

		if ( empty( $all ) ) {
			delete_option( $this->bucket );
		} else {
			update_option( $this->bucket, $all );
		}

		return true;
	}

	/**
	 * Clear a single notification from storage.
	 *
	 * @since 1.0
	 *
	 * @param string       $queue_id
	 * @param Notification $notification
	 *
	 * @return bool
	 */
	public function clear_notification( $queue_id, Notification $notification ) {

		$notifications = $this->get_notifications( $queue_id );

		$notification = array_search( $notification, $notifications );

		if ( false === $notification ) {
			return false;
		}

		unset( $notifications[ $notification ] );

		return $this->store_notifications( $queue_id, $notifications );
	}


}