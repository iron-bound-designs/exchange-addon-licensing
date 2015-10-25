<?php
/**
 * Notification class to be used when the recipient
 * is a guest customer.
 *
 * @author    Iron Bound Designs
 * @since     1.0
 * @license   AGPL
 * @copyright Iron Bound Designs, 2015.
 */

namespace ITELIC\Utils;

use IronBound\WP_Notifications\Contract;
use IronBound\WP_Notifications\Strategy\Strategy;

/**
 * Class Guest_Notification
 * @package ITELIC\Utils
 */
class Guest_Notification implements Contract {

	/**
	 * @var Contract
	 */
	private $notification;

	/**
	 * @var \IT_Exchange_Transaction
	 */
	private $transaction;

	/**
	 * @var Strategy
	 */
	private $strategy;

	/**
	 * Constructor.
	 *
	 * @since 1.0
	 *
	 * @param Contract                 $notification
	 * @param \IT_Exchange_Transaction $transaction Guest transaction.
	 */
	public function __construct( Contract $notification, \IT_Exchange_Transaction $transaction ) {
		$this->notification = $notification;
		$this->transaction  = $transaction;
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
		$this->notification->add_data_source( $source, $name );

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
		return $this->notification->was_sent();
	}

	/**
	 * Get the recipient for this notification.
	 *
	 * @since 1.0
	 *
	 * @return \WP_User
	 */
	public function get_recipient() {

		$email = it_exchange_get_transaction_customer_email( $this->transaction );

		$user = it_exchange_guest_checkout_generate_guest_user_object( $email );

		$billing  = (array) it_exchange_get_transaction_billing_address( $this->transaction );
		$shipping = (array) it_exchange_get_transaction_shipping_address( $this->transaction );

		$user->first_name = ! empty( $billing['first-name'] ) ? $billing['first-name'] : $shipping['first-name'];
		$user->last_name  = ! empty( $billing['last-name'] ) ? $billing['last-name'] : $shipping['last-name'];

		return $user;
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
	public function get_message() {
		return $this->notification->get_message();
	}

	/**
	 * Get the subject line.
	 *
	 * @since 1.0
	 *
	 * @return string
	 */
	public function get_subject() {
		return $this->notification->get_subject();
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
	public function get_tags() {
		return $this->notification->get_tags();
	}

	/**
	 * String representation of object
	 * @link  http://php.net/manual/en/serializable.serialize.php
	 * @return string the string representation of the object or null
	 * @since 5.1.0
	 */
	public function serialize() {

		$data['notification'] = serialize( $this->notification );
		$data['transaction']  = $this->transaction->ID;

		return serialize( $data );
	}

	/**
	 * Constructs the object
	 * @link  http://php.net/manual/en/serializable.unserialize.php
	 *
	 * @param string $serialized <p>
	 *                           The string representation of the object.
	 *                           </p>
	 *
	 * @return void
	 * @since 5.1.0
	 */
	public function unserialize( $serialized ) {
		$data              = unserialize( $serialized );

		$this->transaction = it_exchange_get_transaction( $data['transaction'] );
		$this->notification = unserialize( $data['notification'] );
	}
}