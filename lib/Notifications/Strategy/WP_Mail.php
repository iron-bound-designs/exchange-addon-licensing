<?php
/**
 * Send notifications using WP Mail.
 *
 * @author Iron Bound Designs
 * @since
 */

namespace ITELIC\Notifications\Strategy;

/**
 * Class WP_Mail
 * @package ITELIC\Notifications\Strategy
 */
class WP_Mail implements Strategy {

	/**
	 * Send the notification.
	 *
	 * @since 1.0
	 *
	 * @param \IT_Exchange_Customer $customer
	 * @param string                $message        May contain HTML. Template parts aren't replaced.
	 * @param string                $subject
	 * @param array                 $template_parts Array of template parts to their values.
	 *
	 * @return bool
	 *
	 * @throws \Exception
	 */
	public function send( \IT_Exchange_Customer $customer, $message, $subject, array $template_parts ) {
		$message = str_replace( array_keys( $template_parts ), array_values( $template_parts ), $message );
		$subject = str_replace( array_keys( $template_parts ), array_values( $template_parts ), $subject );

		do_action( 'it_exchange_send_email_notification', $customer->id, $subject, $message );

		return true;
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
}