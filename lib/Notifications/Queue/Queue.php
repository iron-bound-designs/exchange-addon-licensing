<?php
/**
 * Queue interface.
 *
 * @author Iron Bound Designs
 * @since  1.0
 */

namespace ITELIC\Notifications\Queue;

use ITELIC\Notifications\Notification;
use ITELIC\Notifications\Strategy\Strategy;

/**
 * Interface Queue
 * @package ITELIC\Notifications\Queue
 */
interface Queue {

	/**
	 * Process a batch of notifications.
	 *
	 * @since 1.0
	 *
	 * @param Notification[] $notifications
	 * @param Strategy       $strategy
	 *
	 * @throws \Exception
	 */
	public function process( array $notifications, Strategy $strategy );
}