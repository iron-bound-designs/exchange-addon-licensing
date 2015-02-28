<?php
/**
 * License Renewal Class
 *
 * @author Iron Bound Designs
 * @since  1.0
 */

/**
 * Class ITELIC_Renewal
 */
class ITELIC_Renewal {

	/**
	 * @var int
	 */
	private $id;

	/**
	 * @var ITELIC_Key
	 */
	private $key;

	/**
	 * @var DateTime
	 */
	private $renewal_date;

	/**
	 * @var DateTime
	 */
	private $key_expired_date;

	/**
	 * @var IT_Exchange_Transaction
	 */
	private $transaction;

	/**
	 * Constructor.
	 *
	 * @param object $data Data from the DB
	 *
	 * @throws InvalidArgumentException
	 */
	public function __construct( $data ) {
		if ( ! is_object( $data ) ) {
			throw new InvalidArgumentException( __( "Passed data must be an object.", ITELIC::SLUG ) );
		}

		$this->init( $data );
	}

	/**
	 * Initialize this object.
	 *
	 * @param object $data
	 */
	protected function init( $data ) {
		$this->id               = $data->id;
		$this->key              = itelic_get_key( $data->lkey );
		$this->renewal_date     = new DateTime( $data->renewal_date );
		$this->key_expired_date = new DateTime( $data->key_expired_date );
		$this->transaction      = it_exchange_get_transaction( $data->transaction_id );

		if ( ! $this->transaction instanceof IT_Exchange_Transaction ) {
			throw new InvalidArgumentException( __( "Invalid transaction.", ITELIC::SLUG ) );
		}
	}

	/**
	 * Get the renewal record ID.
	 *
	 * @since 1.0
	 *
	 * @return int
	 */
	public function get_id() {
		return $this->id;
	}

	/**
	 * Get the key this record renews.
	 *
	 * @since 1.0
	 *
	 * @return string
	 */
	public function get_key() {
		return $this->key;
	}

	/**
	 * Get the date of this renewal.
	 *
	 * @since 1.0
	 *
	 * @return DateTime
	 */
	public function get_renewal_date() {
		return $this->renewal_date;
	}

	/**
	 * Get the date the key originally expired.
	 *
	 * @since 1.0
	 *
	 * @return DateTime
	 */
	public function get_key_expired_date() {
		return $this->key_expired_date;
	}

	/**
	 * Get the transaction that was used to renew this key.
	 *
	 * @since 1.0
	 *
	 * @return IT_Exchange_Transaction
	 */
	public function get_transaction() {
		return $this->transaction;
	}
}