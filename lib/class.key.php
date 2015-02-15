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
	 * @var int
	 */
	private $count;

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
		$this->count       = $data->count;
		$this->max         = $data->max;

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
	 * @param string                  $status
	 *
	 * @return ITELIC_Key
	 */
	public static function create( $key, IT_Exchange_Transaction $transaction, IT_Exchange_Product $product, IT_Exchange_Customer $customer, $max, $status = '' ) {

		if ( empty( $status ) ) {
			$status = self::ACTIVE;
		}

		$data = array(
			'lkey'           => $key,
			'transaction_id' => $transaction->ID,
			'product'        => $product->ID,
			'customer'       => $customer->id,
			'status'         => $status,
			'count'          => 0,
			'max'            => $max
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

		if ( $this->get_count() >= $this->get_max() ) {
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
		$update = array(
			'count' => $this->get_count() + 1
		);

		$db = ITELIC_DB_Keys::instance();
		$db->update( $this->get_key(), $update );

		$this->refresh();
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
	 * @return string
	 */
	public function get_status() {
		return $this->status;
	}

	/**
	 * @return int
	 */
	public function get_count() {
		return $this->count;
	}

	/**
	 * @return int
	 */
	public function get_max() {
		return $this->max;
	}

	/**
	 * Refresh this object's properties.
	 */
	protected function refresh() {
		$this->init( ITELIC_DB_Keys::retrieve( $this->get_key() ) );
	}
}