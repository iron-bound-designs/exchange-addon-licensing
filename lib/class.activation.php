<?php
/**
 * Activation Class
 *
 * @author Iron Bound Designs
 * @since  1.0
 */

/**
 * Class ITELIC_Activation
 *
 * Class that logs activations.
 *
 * @since 1.0
 */
class ITELIC_Activation implements ITELIC_API_Serializable {

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
	 * @var ITELIC_Key
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
	 * @var DateTime
	 */
	private $activation;

	/**
	 * @var DateTime
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
		$this->key        = ITELIC_Key::with_key( $data->lkey );
		$this->location   = $data->location;
		$this->status     = $data->status;
		$this->activation = new DateTime( $data->activation );

		if ( ! empty( $data->deactivation ) && $data->deactivation != '0000-00-00 00:00:00' ) {
			$this->deactivation = new DateTime( $data->deactivation );
		}
	}

	/**
	 * Instantiate an activation by the activation ID
	 *
	 * @param int $id
	 *
	 * @return ITELIC_Activation
	 */
	public static function with_id( $id ) {
		$db = ITELIC_DB_Activations::retrieve( $id );

		if ( empty( $id ) ) {
			return null;
		}

		return new ITELIC_Activation( $db );
	}

	/**
	 * Create an activation.
	 *
	 * @param string   $key
	 * @param string   $location
	 * @param DateTime $activation
	 * @param string   $status
	 *
	 * @return ITELIC_Activation
	 */
	public static function create( $key, $location, DateTime $activation = null, $status = '' ) {

		if ( empty( $key ) || empty( $location ) ) {
			throw new InvalidArgumentException( __( "The license key and install location are required.", ITELIC::SLUG ) );
		}

		$key = itelic_get_key( $key );

		if ( $key->get_active_count() >= $key->get_max() ) {
			throw new LogicException( __( "This license key has reached it's maximum number of activations.", ITELIC::SLUG ) );
		}

		if ( $activation === null ) {
			$activation = current_time( 'mysql' );
		} else {
			$activation = $activation->format( 'Y-m-d H:i:s' );
		}

		if ( empty( $status ) ) {
			$status = self::ACTIVE;
		}

		$data = array(
			'lkey'         => $key->get_key(),
			'location'     => $location,
			'activation'   => $activation,
			'deactivation' => null,
			'status'       => $status
		);

		$db = ITELIC_DB_Activations::instance();
		$id = $db->insert( $data );

		$activation = self::with_id( $id );
		$activation->get_key()->log_activation( $activation );

		return $activation;
	}

	/**
	 * Deactivate this activation.
	 *
	 * @param DateTime $date
	 */
	public function deactivate( DateTime $date = null ) {

		if ( $date === null ) {
			$date = current_time( 'mysql' );
		} else {
			$date = $date->format( 'Y-m-d H:i:s' );
		}

		$update = array(
			'deactivation' => $date,
			'status'       => 'deactivated'
		);

		$db = ITELIC_DB_Activations::instance();
		$db->update( $this->get_id(), $update );

		$this->refresh();
	}

	/**
	 * Delete an activation record.
	 *
	 * @since 1.0
	 */
	public function delete() {
		ITELIC_DB_Activations::delete_by_id( $this->get_id() );
	}

	/**
	 * @return int
	 */
	public function get_id() {
		return $this->id;
	}

	/**
	 * @return ITELIC_Key
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

		switch ( $this->status ) {
			case self::ACTIVE:
				return __( "Active", ITELIC::SLUG );
			case self::DEACTIVATED:
				return __( "Deactivated", ITELIC::SLUG );
			case self::EXPIRED:
				return __( "Expired", ITELIC::SLUG );
			default:
				return __( "Unknown", ITELIC::SLUG );
		}
	}

	/**
	 * @return DateTime
	 */
	public function get_activation() {
		return $this->activation;
	}

	/**
	 * @return DateTime
	 */
	public function get_deactivation() {
		return $this->deactivation;
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
			'activation'   => $this->get_activation()->format( DateTime::ISO8601 ),
			'deactivation' => ( $d = $this->get_deactivation() ) === null ? "" : $d->format( DateTime::ISO8601 ),
			'location'     => $this->get_location(),
			'status'       => $this->get_status()
		);

		return $data;
	}

	/**
	 * Refresh this object's data.
	 *
	 * Should be called whenever a method that changes state completes.
	 */
	protected function refresh() {
		$this->init( ITELIC_DB_Activations::retrieve( $this->get_id() ) );
	}
}