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
class ITELIC_Activation {

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

		if ( ! empty( $data->deactivation ) ) {
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
		return new ITELIC_Activation( ITELIC_DB_Activations::retrieve( $id ) );
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
	public static function create( $key, $location, $activation = null, $status = '' ) {

		if ( $activation === null ) {
			$activation = current_time( 'mysql' );
		} else {
			$activation = $activation->format( 'Y-m-d H:i:s' );
		}

		if ( empty( $status ) ) {
			$status = self::ACTIVE;
		}

		$data = array(
			'lkey'       => $key,
			'location'   => $location,
			'activation' => $activation,
			'status'     => $status
		);

		$db = ITELIC_DB_Activations::instance();
		$id = $db->insert( $data );

		return self::with_id( $id );
	}

	/**
	 * Deactivate this activation.
	 *
	 * @param DateTime $date
	 */
	public function deactivate( $date = null ) {

		if ( $date == null ) {
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
	 * Refresh this object's data.
	 *
	 * Should be called whenever a method that changes state completes.
	 */
	protected function refresh() {
		$this->init( ITELIC_DB_Activations::retrieve( $this->get_id() ) );
	}
}