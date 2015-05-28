<?php
/**
 * Used to send purchase notification.
 *
 * @author Iron Bound Designs
 * @since  1.0
 */

namespace ITELIC\Notifications;

use ITELIC\Notifications\Strategy\Strategy;
use ITELIC\Notifications\Template\Factory;
use ITELIC\Notifications\Template\Manager;

/**
 * Class Notification
 * @package ITELIC\Notifications
 */
class Notification implements \Serializable {

	/**
	 * @var Strategy
	 */
	protected $strategy;

	/**
	 * @var \IT_Exchange_Customer
	 */
	private $customer;

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
	 * @var Manager
	 */
	private $manager;

	/**
	 * Constructor.
	 *
	 * @since 1.0
	 *
	 * @param \IT_Exchange_Customer $customer
	 * @param Manager               $manager
	 * @param string                $message Message to be sent. Template tags are not replaced. May contain HTML.
	 * @param string                $subject
	 */
	public function __construct( \IT_Exchange_Customer $customer, Manager $manager, $message, $subject ) {
		$this->customer = $customer;
		$this->message  = $message;
		$this->manager  = $manager;
		$this->subject  = $subject;

		$this->tags = $this->generate_rendered_tags();
	}

	/**
	 * Generate the rendered forms of the tags.
	 *
	 * @since 1.0
	 *
	 * @return array
	 */
	protected function generate_rendered_tags() {

		$data_sources   = $this->data_sources;
		$data_sources[] = $this->customer;

		/**
		 * Filter the data sources used for rendering the template tags.
		 *
		 * @param array                 $data_sources
		 * @param \IT_Exchange_Customer $customer
		 */
		$data_sources = apply_filters( 'itepbo_notification_data_sources', $data_sources, $this->customer );

		$tags     = $this->manager->render_tags( $data_sources );
		$rendered = array();

		foreach ( $tags as $tag => $value ) {
			$rendered[ '{' . $tag . '}' ] = $value;
		}

		return $rendered;
	}

	/**
	 * Add a data source.
	 *
	 * @since 1.0
	 *
	 * @param \Serializable $source
	 */
	public function add_data_source( \Serializable $source ) {
		$this->data_sources[] = $source;
	}

	/**
	 * Set the send strategy.
	 *
	 * @since 1.0
	 *
	 * @param Strategy $strategy
	 */
	public function set_strategy( Strategy $strategy ) {
		$this->strategy = $strategy;
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

		return $this->strategy->send( $this->customer, $this->message, $this->subject, $this->tags );
	}

	/**
	 * Has this notification already been set.
	 *
	 * This is used by Queue processors in case of timeouts.
	 *
	 * @since 1.0
	 *
	 * @return boolean
	 */
	public function was_sent() {
		return false;
	}

	/**
	 * Get the customer for this notification.
	 *
	 * @since 1.0
	 *
	 * @return \IT_Exchange_Customer
	 */
	public function get_customer() {
		return $this->customer;
	}

	/**
	 * (PHP 5 &gt;= 5.1.0)<br/>
	 * String representation of object
	 * @link http://php.net/manual/en/serializable.serialize.php
	 * @return string the string representation of the object or null
	 *
	 * @codeCoverageIgnore
	 */
	public function serialize() {
		$data = array(
			'customer'     => $this->customer->id,
			'message'      => $this->message,
			'subject'      => $this->subject,
			'strategy'     => get_class( $this->strategy ),
			'manager'      => $this->manager->get_type(),
			'data_sources' => serialize( $this->data_sources )
		);

		return serialize( $data );
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
	public function unserialize( $serialized ) {
		$data = unserialize( $serialized );

		$this->customer     = new \IT_Exchange_Customer( $serialized['customer'] );
		$this->message      = $data['message'];
		$this->manager      = Factory::make( $data['manager'] );
		$this->tags         = $this->generate_rendered_tags();
		$this->data_sources = unserialize( $data['data_sources'] );

		$strategy_class = $data['strategy'];

		if ( $strategy_class && $strategy_class instanceof Strategy ) {
			$this->strategy = new $strategy_class();
		}
	}
}