<?php
/**
 * Activation Class
 *
 * @author Iron Bound Designs
 * @since  1.0
 */

namespace ITELIC;

use ITELIC\DB\Manager;
use ITELIC\DB\Exception as DB_Exception;

/**
 * Class ITELIC_Activation
 *
 * Class that logs activations.
 *
 * @since 1.0
 */
class Activation implements API\Serializable {

	/**
	 * Represents when this site is active.
	 */
	const ACTIVE = 'active';

	/**
	 * Represents when this site is deactivated remotely via the API,
	 * or manually via the admin area.
	 */
	const DEACTIVATED = 'deactivated';

	/**
	 * Represents when this license expires because the original license
	 * has expired.
	 */
	const EXPIRED = 'expired';

	/**
	 * @var int
	 */
	private $id;

	/**
	 * @var Key
	 */
	private $key;

	/**
	 * @var string
	 */
	private $location;

	/**
	 * @var string
	 */
	private $status;

	/**
	 * @var \DateTime
	 */
	private $activation;

	/**
	 * @var \DateTime
	 */
	private $deactivation = null;

	/**
	 * Constructor.
	 *
	 * @param object $data
	 */
	public function __construct( $data ) {
		$this->init( $data );
	}

	/**
	 * Initialize this object.
	 *
	 * @param object $data
	 */
	protected function init( $data ) {
		$this->id         = $data->id;
		$this->key        = Key::with_key( $data->lkey );
		$this->location   = $data->location;
		$this->status     = $data->status;
		$this->activation = new \DateTime( $data->activation );

		if ( ! empty( $data->deactivation ) && $data->deactivation != '0000-00-00 00:00:00' ) {
			$this->deactivation = new \DateTime( $data->deactivation );
		}
	}

	/**
	 * Instantiate an activation by the activation ID
	 *
	 * @param int $id
	 *
	 * @return Activation
	 */
	public static function with_id( $id ) {
		$db   = Manager::make_query_object( 'activations' );
		$data = $db->get( $id );

		if ( empty( $id ) ) {
			return null;
		}

		return new Activation( $data );
	}

	/**
	 * Create an activation.
	 *
	 * @param Key       $key
	 * @param string    $location
	 * @param \DateTime $activation
	 * @param string    $status
	 *
	 * @return Activation
	 *
	 * @throws \LogicException|DB_Exception
	 */
	public static function create( Key $key, $location, \DateTime $activation = null, $status = '' ) {

		if ( empty( $key ) || empty( $location ) ) {
			throw new \InvalidArgumentException( __( "The license key and install location are required.", Plugin::SLUG ) );
		}

		if ( $key->get_active_count() >= $key->get_max() ) {
			throw new \LogicException( __( "This license key has reached it's maximum number of activations.", Plugin::SLUG ) );
		}

		if ( $activation === null ) {
			$activation = current_time( 'mysql' );
		} else {
			$activation = $activation->format( 'Y-m-d H:i:s' );
		}

		if ( empty( $status ) ) {
			$status = self::ACTIVE;
		}

		if ( $key->is_online_product() ) {
			$location = itelic_normalize_url( $location );
		}

		$data = array(
			'lkey'         => $key->get_key(),
			'location'     => $location,
			'activation'   => $activation,
			'deactivation' => null,
			'status'       => $status
		);

		$db = Manager::make_query_object( 'activations' );

		try {
			$id = $db->insert( $data );
		}
		catch ( DB_Exception $e ) {

			if ( $e->getCode() === 1062 ) {
				throw new DB_Exception( __( "An activation with this same location already exists.", Plugin::SLUG ), $e->getCode(), $e );
			} else {
				throw $e;
			}
		}

		$activation = self::with_id( $id );
		$activation->get_key()->log_activation( $activation );

		return $activation;
	}

	/**
	 * Deactivate this activation.
	 *
	 * @param \DateTime $date
	 */
	public function deactivate( \DateTime $date = null ) {

		if ( $date === null ) {
			$date = new \DateTime();
		}

		$this->set_deactivation( $date );
		$this->set_status( self::DEACTIVATED );
	}

	/**
	 * Reactivate a previously deactivated activation.
	 *
	 * @since 1.0
	 *
	 * @param \DateTime $date When was the record reactivated.
	 *
	 * @throws \Exception If activation record isn't deactivated.
	 */
	public function reactivate( \DateTime $date = null ) {

		if ( $this->get_status() != self::DEACTIVATED ) {
			throw new \Exception( __( "Only deactivated activation records can be reactivated.", Plugin::SLUG ) );
		}

		if ( $date === null ) {
			$date = current_time( 'mysql' );
		} else {
			$date = $date->format( 'Y-m-d H:i:s' );
		}

		$this->set_deactivation( null );
		$this->set_activation( $date );
		$this->set_status( self::ACTIVE );
	}

	/**
	 * Delete an activation record.
	 *
	 * @since 1.0
	 */
	public function delete() {
		$db = Manager::make_query_object( 'activations' );
		$db->delete( $this->get_id() );
	}

	/**
	 * @return int
	 */
	public function get_id() {
		return $this->id;
	}

	/**
	 * @return Key
	 */
	public function get_key() {
		return $this->key;
	}

	/**
	 * @return string
	 */
	public function get_location() {
		return $this->location;
	}

	/**
	 * Retrieve the status.
	 *
	 * @param bool $label If true, retrieve the label form.
	 *
	 * @return string
	 */
	public function get_status( $label = false ) {

		if ( ! $label ) {
			return $this->status;
		}

		$statuses = self::get_statuses();

		return isset( $statuses[ $this->status ] ) ? $statuses[ $this->status ] : __( "Unknown", Plugin::SLUG );
	}

	/**
	 * Set the status of this record.
	 *
	 * @param string $status
	 */
	protected function set_status( $status ) {

		if ( ! array_key_exists( $status, self::get_statuses() ) ) {
			throw new \InvalidArgumentException( "Invalid status." );
		}

		$this->status = $status;

		$this->update_value( 'status', $status );
	}

	/**
	 * Get a list of all statuses.
	 *
	 * @since 1.0
	 *
	 * @return array
	 */
	public static function get_statuses() {
		return array(
			self::ACTIVE      => __( "Active", Plugin::SLUG ),
			self::DEACTIVATED => __( "Deactivated", Plugin::SLUG ),
			self::EXPIRED     => __( "Expired", Plugin::SLUG )
		);
	}

	/**
	 * @return \DateTime
	 */
	public function get_activation() {
		return $this->activation;
	}

	/**
	 * Set the activation date.
	 *
	 * @param \DateTime $time
	 */
	protected function set_activation( \DateTime $time = null ) {

		$this->activation = $time;

		if ( $time ) {
			$val = $time->format( "Y-m-d H:i:s" );
		} else {
			$val = null;
		}

		$this->update_value( 'activation', $val );
	}

	/**
	 * @return \DateTime
	 */
	public function get_deactivation() {
		return $this->deactivation;
	}

	/**
	 * Set the deactivation date.
	 *
	 * @param \DateTime $time
	 */
	protected function set_deactivation( \DateTime $time = null ) {

		$this->deactivation = $time;

		if ( $time ) {
			$val = $time->format( "Y-m-d H:i:s" );
		} else {
			$val = null;
		}

		$this->update_value( 'deactivation', $val );
	}

	/**
	 * @return string
	 */
	public function __toString() {
		return $this->get_key() . ' – ' . $this->get_location();
	}

	/**
	 * Get data suitable for the API.
	 *
	 * @since 1.0
	 *
	 * @return array
	 */
	public function get_api_data() {
		$data = array(
			'id'           => $this->get_id(),
			'activation'   => $this->get_activation()->format( \DateTime::ISO8601 ),
			'deactivation' => ( $d = $this->get_deactivation() ) === null ? "" : $d->format( \DateTime::ISO8601 ),
			'location'     => $this->get_location(),
			'status'       => $this->get_status()
		);

		return $data;
	}

	/**
	 * Update a particular value.
	 *
	 * @since 1.0
	 *
	 * @param string $key
	 * @param mixed  $value
	 *
	 * @throws \RuntimeException|DB\Exception
	 */
	protected function update_value( $key, $value ) {

		$data = array(
			$key => $value
		);

		$db = Manager::make_query_object( 'activations' );
		$db->update( $this->get_id(), $data );
	}
}