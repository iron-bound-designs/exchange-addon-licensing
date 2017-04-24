<?php
/**
 * Notification contract.
 *
 * @author Iron Bound Designs
 * @since  1.0
 */
namespace IronBound\WP_Notifications;

use IronBound\WP_Notifications\Strategy\Strategy;


/**
 * Interface Contract
 * @package IronBound\WP_Notifications
 */
interface Contract extends \Serializable {
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
	public function add_data_source( \Serializable $source, $name = '' );

	/**
	 * Set the send strategy.
	 *
	 * @since 1.0
	 *
	 * @param Strategy $strategy
	 *
	 * @return self
	 */
	public function set_strategy( Strategy $strategy );

	/**
	 * Send the notification.
	 *
	 * @since 1.0
	 *
	 * @return bool
	 *
	 * @throws \Exception
	 */
	public function send();

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
	public function was_sent();

	/**
	 * Get the recipient for this notification.
	 *
	 * @since 1.0
	 *
	 * @return \WP_User
	 */
	public function get_recipient();

	/**
	 * Get the message content.
	 *
	 * This may contain HTML.
	 *
	 * @since 1.0
	 *
	 * @return string
	 */
	public function get_message();

	/**
	 * Get the subject line.
	 *
	 * @since 1.0
	 *
	 * @return string
	 */
	public function get_subject();

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
	public function get_tags();
}