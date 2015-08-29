<?php
/**
 * Used to send purchase notification.
 *
 * @author      Iron Bound Designs
 * @since       1.0
 *
 * @copyright   Copyright (c) 2015, Iron Bound Designs, Inc.
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License v2 or later
 */

namespace IronBound\WP_Notifications;

use IronBound\WP_Notifications\Strategy\Strategy;
use IronBound\WP_Notifications\Template\Factory;
use IronBound\WP_Notifications\Template\Manager;

/**
 * Class Notification
 * @package IronBound\WP_Notifications
 */
class Notification implements Contract {

	/**
	 * @var Strategy
	 */
	private $strategy;

	/**
	 * @var \WP_User
	 */
	private $recipient;

	/**
	 * @var string
	 */
	private $message;

	/**
	 * @var string
	 */
	private $subject;

	/**
	 * @var array
	 */
	private $data_sources = array();

	/**
	 * @var array
	 */
	private $tags = array();

	/**
	 * @var bool
	 */
	private $regenerate = true;

	/**
	 * @var Manager
	 */
	private $manager;

	/**
	 * Constructor.
	 *
	 * @since 1.0
	 *
	 * @param \WP_User $recipient
	 * @param Manager  $manager
	 * @param string   $message Message to be sent with template tags. May contain HTML.
	 * @param string   $subject No limit is enforced, but should be short and concise. Template tags allowed.
	 */
	public function __construct( \WP_User $recipient, Manager $manager, $message, $subject ) {
		$this->recipient = $recipient;
		$this->message   = $message;
		$this->manager   = $manager;
		$this->subject   = $subject;
	}

	/**
	 * Generate the rendered forms of the tags.
	 *
	 * @since 1.0
	 *
	 * @return array
	 */
	final protected function generate_rendered_tags() {

		$data_sources   = $this->data_sources;
		$data_sources[] = $this->recipient;

		$tags     = $this->manager->render_tags( $data_sources );
		$rendered = array();

		foreach ( $tags as $tag => $value ) {
			$rendered[ '{' . $tag . '}' ] = $value;
		}

		$this->regenerate = false;

		return $rendered;
	}

	/**
	 * Mark the notification's template tags as needing regeneration.
	 *
	 * @since 1.0
	 */
	final protected function regenerate() {
		$this->regenerate = true;
	}

	/**
	 * Add a data source.
	 *
	 * @since 1.0
	 *
	 * @param \Serializable $source
	 * @param string        $name If passed, listeners specifying that function
	 *                            argument name will receive this data source.
	 *
	 * @return self
	 */
	public function add_data_source( \Serializable $source, $name = '' ) {

		if ( $name ) {
			$this->data_sources[ $name ] = $source;
		} else {
			$this->data_sources[] = $source;
		}

		$this->regenerate();

		return $this;
	}

	/**
	 * Set the send strategy.
	 *
	 * @since 1.0
	 *
	 * @param Strategy $strategy
	 *
	 * @return self
	 */
	public function set_strategy( Strategy $strategy ) {
		$this->strategy = $strategy;

		return $this;
	}

	/**
	 * Send the notification.
	 *
	 * @since 1.0
	 *
	 * @return bool
	 *
	 * @throws \Exception
	 */
	public function send() {

		if ( is_null( $this->strategy ) ) {
			throw new \LogicException( "No strategy has been set." );
		}

		return $this->strategy->send( $this->get_recipient(), $this->get_message(), $this->get_subject(), $this->get_tags() );
	}

	/**
	 * Has this notification already been set.
	 *
	 * This is used by Queue processors in case of timeouts. If you have someway of determining,
	 * that a recipient was notified, you should implement this method.
	 *
	 * @since 1.0
	 *
	 * @return boolean
	 */
	public function was_sent() {
		return false;
	}

	/**
	 * Get the recipient for this notification.
	 *
	 * @since 1.0
	 *
	 * @return \WP_User
	 */
	final public function get_recipient() {
		return $this->recipient;
	}

	/**
	 * Get the message content.
	 *
	 * This may contain HTML.
	 *
	 * @since 1.0
	 *
	 * @return string
	 */
	final public function get_message() {
		return $this->message;
	}

	/**
	 * Get the subject line.
	 *
	 * @since 1.0
	 *
	 * @return string
	 */
	final public function get_subject() {
		return $this->subject;
	}

	/**
	 * Get the tags to be replaced.
	 *
	 * This is the already rendered form so an array of:
	 *
	 *  {first_name} => "John"
	 *
	 * @since 1.0
	 *
	 * @return array
	 */
	final public function get_tags() {

		if ( $this->regenerate ) {
			$this->tags = $this->generate_rendered_tags();
		}

		return $this->tags;
	}

	/**
	 * (PHP 5 &gt;= 5.1.0)<br/>
	 * String representation of object
	 * @link http://php.net/manual/en/serializable.serialize.php
	 * @return string the string representation of the object or null
	 *
	 * @codeCoverageIgnore
	 */
	final public function serialize() {
		return serialize( $this->get_data_to_serialize() );
	}

	/**
	 * Get the data to serialize.
	 *
	 * Child classes should override this method, and add their own data.
	 *
	 * This can be exploited to override the base class's data - don't.
	 *
	 * @since 1.0
	 *
	 * @return array
	 */
	protected function get_data_to_serialize() {
		return array(
			'recipient'    => $this->recipient->ID,
			'message'      => $this->message,
			'subject'      => $this->subject,
			'strategy'     => serialize( $this->strategy ),
			'manager'      => $this->manager->get_type(),
			'data_sources' => serialize( $this->data_sources )
		);
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
	 *
	 * @codeCoverageIgnore
	 */
	final public function unserialize( $serialized ) {
		$data = unserialize( $serialized );
		$this->do_unserialize( $data );
	}

	/**
	 * Do the actual unserialization.
	 *
	 * Assign the data to class properties.
	 *
	 * @since 1.0
	 *
	 * @param array $data
	 */
	protected function do_unserialize( array $data ) {
		$this->recipient = get_user_by( 'id', $data['recipient'] );
		$this->message   = $data['message'];
		$this->subject   = $data['subject'];
		$this->manager   = Factory::make( $data['manager'] );
		$this->strategy  = unserialize( $data['strategy'] );

		$this->data_sources = unserialize( $data['data_sources'] );
		$this->tags         = $this->generate_rendered_tags();
	}
}