<?php
/**
 * Send notifications using Mandrill.
 *
 * @author      Iron Bound Designs
 * @since       1.0
 *
 * @copyright   Copyright (c) 2015, Iron Bound Designs, Inc.
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License v2 or later
 */

namespace IronBound\WP_Notifications\Strategy;

/**
 * Class Mandrill
 * @package IronBound\WP_Notifications\Strategy
 */
class Mandrill implements Strategy {

	/**
	 * @var \Mandrill
	 */
	private $mandrill;

	/**
	 * @var array
	 */
	private $defaults = array();

	/**
	 * Constructor.
	 *
	 * @since 1.0
	 *
	 * @param \Mandrill $mandrill
	 * @param array     $defaults
	 */
	public function __construct( \Mandrill $mandrill, array $defaults = array() ) {
		$this->mandrill = $mandrill;
		$this->defaults = $defaults;
	}

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

		$to = array(
			'email' => $recipient->user_email,
			'name'  => "{$recipient->first_name} {$recipient->last_name}",
			'type'  => 'to'
		);

		$args = array(
			'html'       => $message,
			'subject'    => $subject,
			'from_name'  => get_option( 'blogname' ),
			'from_email' => get_option( 'admin_email' ),
			'to'         => array( $to ),
			'auto_text'  => true,
		);

		$args = wp_parse_args( $args, $this->defaults );

		$this->mandrill->messages->send( $args );
	}

	/**
	 * Get the suggested number of times a notification with this strategy can be sent per PHP request.
	 *
	 * If you want to send multiple mandrill notifications at once, you should use the Mandrill queue.
	 *
	 * @since 1.0
	 *
	 * @return int
	 */
	public function get_suggested_rate() {
		return 3;
	}

	/**
	 * (PHP 5 &gt;= 5.1.0)<br/>
	 * String representation of object
	 * @link http://php.net/manual/en/serializable.serialize.php
	 * @return string the string representation of the object or null
	 */
	public function serialize() {
		return serialize( array(
			'mandrill' => $this->mandrill->apikey
		) );
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

		$data           = unserialize( $serialized );
		$this->mandrill = new \Mandrill( $data['mandrill'] );
	}
}