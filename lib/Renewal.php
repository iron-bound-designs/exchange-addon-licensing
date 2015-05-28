<?php
/**
 * License Renewal Class
 *
 * @author Iron Bound Designs
 * @since  1.0
 */

namespace ITELIC;
use ITELIC\DB\Manager;

/**
 * Class Renewal
 */
class Renewal {

	/**
	 * @var int
	 */
	private $id;

	/**
	 * @var Key
	 */
	private $key;

	/**
	 * @var \DateTime
	 */
	private $renewal_date;

	/**
	 * @var \DateTime
	 */
	private $key_expired_date;

	/**
	 * @var \IT_Exchange_Transaction
	 */
	private $transaction;

	/**
	 * Constructor.
	 *
	 * @param object $data Data from the DB
	 *
	 * @throws \InvalidArgumentException
	 */
	public function __construct( $data ) {
		if ( ! is_object( $data ) ) {
			throw new \InvalidArgumentException( __( "Passed data must be an object.", Plugin::SLUG ) );
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
		$this->renewal_date     = new \DateTime( $data->renewal_date );
		$this->key_expired_date = new \DateTime( $data->key_expired_date );
		$this->transaction      = it_exchange_get_transaction( $data->transaction_id );

		if ( ! $this->transaction instanceof \IT_Exchange_Transaction ) {
			throw new \InvalidArgumentException( __( "Invalid transaction.", Plugin::SLUG ) );
		}
	}

	/**
	 * Get a renewal record from it's ID.
	 *
	 * @since 1.0
	 *
	 * @param int $id
	 *
	 * @return Renewal
	 */
	public static function from_id( $id ) {

		$db   = Manager::make_query_object( 'renewals' );
		$data = $db->get( $id );

		if ( $data ) {
			return new Renewal( $data );
		} else {
			return null;
		}
	}

	/**
	 * Create a renewal record.
	 *
	 * @since 1.0
	 *
	 * @param Key                      $key
	 * @param \IT_Exchange_Transaction $transaction
	 * @param \DateTime                $expired
	 * @param \DateTime                $renewal
	 *
	 * @return Renewal
	 */
	public static function create( Key $key, \IT_Exchange_Transaction $transaction, \DateTime $expired, \DateTime $renewal = null ) {

		if ( empty( $renewal ) ) {
			$renewal = new \DateTime();
		}

		$data = array(
			'lkey'             => $key->get_key(),
			'renewal_date'     => $renewal->format( "Y-m-d H:i:s" ),
			'key_expired_date' => $expired->format( "Y-m-d H:i:s" ),
			'transaction_id'   => $transaction->ID
		);

		$db = Manager::make_query_object( 'renewals' );
		$id = $db->insert( $data );

		return self::from_id( $id );
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
	 * @return \DateTime
	 */
	public function get_renewal_date() {
		return $this->renewal_date;
	}

	/**
	 * Get the date the key originally expired.
	 *
	 * @since 1.0
	 *
	 * @return \DateTime
	 */
	public function get_key_expired_date() {
		return $this->key_expired_date;
	}

	/**
	 * Get the transaction that was used to renew this key.
	 *
	 * @since 1.0
	 *
	 * @return \IT_Exchange_Transaction
	 */
	public function get_transaction() {
		return $this->transaction;
	}

	/**
	 * @return string
	 */
	public function __toString() {
		return $this->get_key() . ' – ' . $this->get_renewal_date()->format( get_option( 'date_format' ) );
	}
}