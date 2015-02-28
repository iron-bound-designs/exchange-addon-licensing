<?php
/**
 * License Key Class
 *
 * @author Iron Bound Designs
 * @since  1.0
 */

/**
 * Class ITELIC_Key
 *
 * Class used to represent a license key.
 *
 * @since 1.0
 */
class ITELIC_Key {

	/**
	 * Represents when this license is active.
	 */
	const ACTIVE = 'active';

	/**
	 * Represents when this license key has expired.
	 */
	const EXPIRED = 'expired';

	/**
	 * Represents when this license key was disabled by an admin.
	 */
	const DISABLED = 'disabled';

	/**
	 * @var string
	 */
	private $key;

	/**
	 * @var IT_Exchange_Transaction
	 */
	private $transaction;

	/**
	 * @var IT_Exchange_Product
	 */
	private $product;

	/**
	 * @var IT_Exchange_Customer
	 */
	private $customer;

	/**
	 * @var string
	 */
	private $status;

	/**
	 * @var DateTime|null
	 */
	private $expires = null;

	/**
	 * @var int
	 */
	private $max;

	/**
	 * Constructor.
	 *
	 * @param object $data Data from the DB
	 *
	 * @throws InvalidArgumentException If an invalid transaction, product or customer.
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
		$this->key         = $data->lkey;
		$this->transaction = it_exchange_get_transaction( $data->transaction_id );
		$this->product     = it_exchange_get_product( $data->product );
		$this->customer    = it_exchange_get_customer( $data->customer );
		$this->status      = $data->status;
		$this->max         = $data->max;

		if ( ! empty( $data->expires ) ) {
			$this->expires = new DateTime( $data->expires );
		}

		foreach ( array( 'transaction', 'product', 'customer' ) as $maybe_error ) {
			if ( ! $this->$maybe_error || is_wp_error( $this->$maybe_error ) ) {
				throw new InvalidArgumentException( "Invalid $maybe_error" );
			}
		}
	}

	/**
	 * Retrieve a license key object by using the license key.
	 *
	 * @param string $key
	 *
	 * @return ITELIC_Key
	 */
	public static function with_key( $key ) {
		return new ITELIC_Key( ITELIC_DB_Keys::retrieve( $key ) );
	}

	/**
	 * Create a license key record.
	 *
	 * @since 1.0
	 *
	 * @param string                  $key
	 * @param IT_Exchange_Transaction $transaction
	 * @param IT_Exchange_Product     $product
	 * @param IT_Exchange_Customer    $customer
	 * @param int                     $max
	 * @param DateTime                $expires
	 * @param string                  $status
	 *
	 * @return ITELIC_Key
	 */
	public static function create( $key, IT_Exchange_Transaction $transaction, IT_Exchange_Product $product, IT_Exchange_Customer $customer, $max, DateTime $expires = null, $status = '' ) {

		if ( empty( $status ) ) {
			$status = self::ACTIVE;
		}

		$data = array(
			'lkey'           => $key,
			'transaction_id' => $transaction->ID,
			'product'        => $product->ID,
			'customer'       => $customer->id,
			'status'         => $status,
			'max'            => $max,
			'expires'        => isset( $expires ) ? $expires->format( "Y-m-d H:i:s" ) : null
		);

		$db = ITELIC_DB_Keys::instance();
		$db->insert( $data );

		return self::with_key( $key );
	}

	/**
	 * Check if this license is valid.
	 *
	 * The license is valid as long as:
	 *      The number of activations, is less than the max.
	 *      The transaction is cleared for delivery.
	 *      The subscription is not expired.
	 *
	 * @return bool
	 */
	public function is_valid() {

		if ( $this->get_active_count() >= $this->get_max() ) {
			return false;
		}

		if ( ! it_exchange_transaction_is_cleared_for_delivery( $this->get_transaction() ) ) {
			return false;
		}

		if ( $this->get_transaction()->get_transaction_meta( 'subscriber_status' ) != 'active' ) {
			return false;
		}

		return true;
	}

	/**
	 * Log an activation of this license.
	 *
	 * @param ITELIC_Activation $activation
	 */
	public function log_activation( ITELIC_Activation $activation ) {
		// nothing to do
	}

	/**
	 * Extend the expiration date of this license,
	 * by its length. For example, if a license has an
	 * expiration date of one year after purchase,
	 * extending it will extend the expiration date by one year.
	 *
	 * @since 1.0
	 *
	 * @return DateTime
	 */
	public function extend() {
		if ( $this->get_expires() === null ) {
			return null;
		}

		$type  = it_exchange_get_product_feature( $this->get_product()->ID, 'recurring-payments', array( 'setting' => 'interval' ) );
		$count = it_exchange_get_product_feature( $this->get_product()->ID, 'recurring-payments', array( 'setting' => 'interval-count' ) );

		$interval = itelic_convert_rp_to_date_interval( $type, $count );
		$expires  = $this->get_expires();

		$expires->add( $interval );
		$this->set_expires( $expires );

		return $this->get_expires();
	}

	/**
	 * Renew this license.
	 *
	 * @since 1.0
	 */
	public function renew() {

		$args = array(
			'post_parent' => $this->get_transaction()->ID,
			'post_type'   => 'it_exchange_tran',
			'orderby'     => 'date',
			'order'       => 'DESC'
		);

		$transactions = it_exchange_get_transactions( $args );
		$transaction  = reset( $transactions );

		ITELIC_Renewal::create( $this, $transaction, $this->get_expires(), new DateTime( $transaction->post_date ) );

		$this->extend();
	}

	/**
	 * Get all activations of this license key.
	 *
	 * @since 1.0
	 *
	 * @return ITELIC_Activation[]
	 */
	public function get_activations() {
		$activations = ITELIC_DB_Activations::many( 'lkey', $this->get_key() );

		if ( ! is_array( $activations ) || empty( $activations ) ) {
			return array();
		}

		return array_map( 'itelic_get_activation_from_data', $activations );
	}

	/**
	 * @return string
	 */
	public function get_key() {
		return $this->key;
	}

	/**
	 * @return IT_Exchange_Transaction
	 */
	public function get_transaction() {
		return $this->transaction;
	}

	/**
	 * @return IT_Exchange_Product
	 */
	public function get_product() {
		return $this->product;
	}

	/**
	 * @return IT_Exchange_Customer
	 */
	public function get_customer() {
		return $this->customer;
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

		return self::get_status_label( $this->status );
	}

	/**
	 * Set the status of this key.
	 *
	 * @since 1.0
	 *
	 * @param string $status
	 */
	public function set_status( $status ) {
		if ( ! in_array( $status, array( self::ACTIVE, self::EXPIRED, self::DISABLED ) ) ) {
			throw new InvalidArgumentException( __( "Invalid value for key status.", ITELIC::SLUG ) );
		}

		$db = ITELIC_DB_Keys::instance();
		$db->update( $this->get_key(), array( 'status' => $status ) );

		$this->refresh();
	}

	/**
	 * Get a status label.
	 *
	 * @since 1.0
	 *
	 * @param string $status
	 *
	 * @return string
	 */
	public static function get_status_label( $status ) {

		switch ( $status ) {
			case self::ACTIVE:
				return __( "Active", ITELIC::SLUG );
			case self::DISABLED:
				return __( "Disabled", ITELIC::SLUG );
			case self::EXPIRED:
				return __( "Expired", ITELIC::SLUG );
			default:
				return __( "Unknown", ITELIC::SLUG );
		}
	}

	/**
	 * @return int
	 */
	public function get_active_count() {

		$db = ITELIC_DB_Activations::instance();

		return $db->count( array( 'lkey' => $this->get_key(), 'status' => ITELIC_Activation::ACTIVE ) );
	}

	/**
	 * @return DateTime|null
	 */
	public function get_expires() {
		return $this->expires;
	}

	/**
	 * Set the expiry date.
	 *
	 * @since 1.0
	 *
	 * @param DateTime $expires . Set null for forever.
	 */
	public function set_expires( DateTime $expires = null ) {
		$db = ITELIC_DB_Keys::instance();

		if ( $expires instanceof DateTime ) {
			$val = $expires->format( "Y-m-d H:i:s" );
		} else {
			$val = null;
		}

		$db->update( $this->get_key(), array( 'expires' => $val ) );

		$this->refresh();
	}

	/**
	 * @return int
	 */
	public function get_max() {
		return $this->max;
	}

	/**
	 * Set the maximum number of activations.
	 *
	 * @since 1.0
	 *
	 * @param int $max
	 */
	public function set_max( $max ) {
		$db  = ITELIC_DB_Keys::instance();
		$res = $db->update( $this->get_key(), array( 'max' => absint( $max ) ) );

		error_log( "key: {$this->get_key()} max: $max res: $res" );

		$this->refresh();
	}

	/**
	 * Refresh this object's properties.
	 */
	protected function refresh() {
		$this->init( ITELIC_DB_Keys::retrieve( $this->get_key() ) );
	}
}