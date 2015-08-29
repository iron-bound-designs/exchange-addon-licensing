<?php
/**
 * Send notifications via Easy Digital Downloads
 *
 * @author      Iron Bound Designs
 * @since       1.0
 *
 * @copyright   Copyright (c) 2015, Iron Bound Designs, Inc.
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License v2 or later
 */

namespace IronBound\WP_Notifications\Strategy;

/**
 * Class EDD
 * @package IronBound\WP_Notifications\Strategy
 */
class EDD implements Strategy {

	/**
	 * Send the notification.
	 *
	 * @since 1.0
	 *
	 * @param \WP_User $recipient
	 * @param string   $message        May contain HTML. Template parts aren't replaced.
	 * @param string   $subject
	 * @param array    $template_parts Array of template parts to their values.
	 *
	 * @return bool
	 *
	 * @throws \Exception
	 */
	public function send( \WP_User $recipient, $message, $subject, array $template_parts ) {
		$message = str_replace( array_keys( $template_parts ), array_values( $template_parts ), $message );
		$subject = str_replace( array_keys( $template_parts ), array_values( $template_parts ), $subject );

		return EDD()->emails->send( $recipient->user_email, $subject, $message );
	}

	/**
	 * Get the suggested number of times a notification with this strategy can be sent per PHP request.
	 *
	 * @since 1.0
	 *
	 * @return int
	 */
	public function get_suggested_rate() {
		return 10;
	}

	/**
	 * (PHP 5 &gt;= 5.1.0)<br/>
	 * String representation of object
	 * @link http://php.net/manual/en/serializable.serialize.php
	 * @return string the string representation of the object or null
	 */
	public function serialize() {
		return serialize( array() ); // we don't have any data to save
	}

	/**
	 * (PHP 5 &gt;= 5.1.0)<br/>
	 * Constructs the object
	 * @link http://php.net/manual/en/serializable.unserialize.php
	 *
	 * @param string $serialized <p>
	 *                           The string representation of the object.
	 *                           </p>
	 *
	 * @return void
	 */
	public function unserialize( $serialized ) {
		// we don't have any data to unwrap
	}
}