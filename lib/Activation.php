<?php
/**
 * Activation Class
 *
 * @author Iron Bound Designs
 * @since  1.0
 */

namespace ITELIC;

use IronBound\Cache\Cache;
use IronBound\DB\Model;
use IronBound\DB\Table\Table;
use IronBound\DB\Manager;
use IronBound\DB\Exception as DB_Exception;

/**
 * Class ITELIC_Activation
 *
 * Class that logs activations.
 *
 * @since 1.0
 */
class Activation extends Model implements API\Serializable {

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
	 * @var string
	 */
	private $version;

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
	 * @param \stdClass $data
	 */
	protected function init( \stdClass $data ) {
		$this->id         = $data->id;
		$this->key        = Key::with_key( $data->lkey );
		$this->location   = $data->location;
		$this->status     = $data->status;
		$this->activation = new \DateTime( $data->activation );

		if ( ! empty( $data->deactivation ) && $data->deactivation != '0000-00-00 00:00:00' ) {
			$this->deactivation = new \DateTime( $data->deactivation );
		}

		$this->version = trim( $data->version );
	}

	/**
	 * Instantiate an activation by the activation ID
	 *
	 * @param int $id
	 *
	 * @return Activation
	 */
	public static function with_id( $id ) {
		return self::get( $id );
	}

	/**
	 * Create an activation.
	 *
	 * @param Key       $key
	 * @param string    $location
	 * @param \DateTime $activation
	 * @param string    $status
	 * @param string    $version
	 *
	 * @return Activation
	 *
	 * @throws \LogicException|DB_Exception
	 */
	public static function create( Key $key, $location, \DateTime $activation = null, $status = '', $version = '' ) {

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

		if ( ! $version ) {
			$version = it_exchange_get_product_feature( $key->get_product()->ID, 'licensing', array( 'field' => 'version' ) );
		} else {
			$version = sanitize_text_field( $version );
		}

		$data = array(
			'lkey'         => $key->get_key(),
			'location'     => $location,
			'activation'   => $activation,
			'deactivation' => null,
			'status'       => $status,
			'version'      => $version
		);

		$db = Manager::make_simple_query_object( 'itelic-activations' );

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

		if ( ! $id ) {
			return null;
		}

		$activation = self::with_id( $id );
		$activation->get_key()->log_activation( $activation );

		Cache::add( $activation );

		/**
		 * Fires when an activation record is created.
		 *
		 * @since 1.0
		 *
		 * @param Activation $activation
		 */
		do_action( 'itelic_create_activation', $activation );

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
	 * Expire an activation record.
	 *
	 * @since 1.0
	 */
	public function expire() {
		$this->set_status( self::EXPIRED );

		/**
		 * Fires when an activation is expired.
		 *
		 * @since 1.0
		 *
		 * @param Activation $this
		 */
		do_action( 'itelic_expire_activation', $this );
	}

	/**
	 * Get the unique pk for this record.
	 *
	 * @since 1.0
	 *
	 * @return mixed (generally int, but not necessarily).
	 */
	public function get_pk() {
		return $this->id;
	}

	/**
	 * @return int
	 */
	public function get_id() {
		return $this->get_pk();
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

		$this->update( 'status', $status );
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

		$this->update( 'activation', $val );
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

		$this->update( 'deactivation', $val );
	}

	/**
	 * Get the currently installed version at this location.
	 *
	 * @since 1.0
	 *
	 * @return string
	 */
	public function get_version() {
		return $this->version;
	}

	/**
	 * Set the current version installed on this location.
	 *
	 * @since 1.0
	 *
	 * @param string $version
	 */
	public function set_version( $version ) {

		$this->version = sanitize_text_field( $version );

		$this->update( 'version', $this->version );
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
	 * Get the data we'd like to cache.
	 *
	 * This is a bit magical. It iterates through all of the table columns,
	 * and checks if a getter for that method exists. If so, it pulls in that
	 * value. Otherwise, it will pull in the default value. If you'd like to
	 * customize this you should override this function in your child model
	 * class.
	 *
	 * @since 1.0
	 *
	 * @return array
	 */
	public function get_data_to_cache() {
		$data = parent::get_data_to_cache();

		unset( $data['key'] );
		$data['lkey'] = $this->get_key();

		return $data;
	}

	/**
	 * Get the table object for this model.
	 *
	 * @since 1.0
	 *
	 * @returns Table
	 */
	protected static function get_table() {
		return Manager::get( 'itelic-activations' );
	}

}