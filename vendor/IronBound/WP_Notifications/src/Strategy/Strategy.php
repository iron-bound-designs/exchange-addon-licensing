<?php
/**
 * Base strategy for sending notifications.
 *
 * @author Iron Bound Designs
 * @since  1.0
 *
 * @copyright   Copyright (c) 2015, Iron Bound Designs, Inc.
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License v2 or later
 */

namespace IronBound\WP_Notifications\Strategy;

/**
 * Interface Base
 * @package IronBound\WP_Notifications\Strategy
 */
interface Strategy extends \Serializable {

	/**
	 * Send the notification.
	 *
	 * @since 1.0
	 *
	 * @param \WP_User $recipient
	 * @param string   $message        May contain HTML. Template parts haven't been replaced.
	 * @param string   $subject        Template tags haven't been replaced.
	 * @param array    $template_parts Array of template parts to their values.
	 *
	 * @return bool
	 *
	 * @throws \Exception
	 */
	public function send( \WP_User $recipient, $message, $subject, array $template_parts );

	/**
	 * Get the suggested number of times a notification with this strategy can be sent per PHP request.
	 *
	 * @since 1.0
	 *
	 * @return int
	 */
	public function get_suggested_rate();
}