<?php
/**
 * License Renewal Class
 *
 * @author Iron Bound Designs
 * @since  1.0
 */

namespace ITELIC;

use IronBound\Cache\Cache;
use IronBound\DB\Model;
use IronBound\DB\Table\Table;
use IronBound\DB\Manager;

/**
 * Class Renewal
 */
class Renewal extends Model {

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
	 * @var float
	 */
	private $revenue;

	/**
	 * Constructor.
	 *
	 * @param object $data Data from the DB
	 *
	 * @throws \InvalidArgumentException
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
		$this->id               = $data->id;
		$this->key              = itelic_get_key( $data->lkey );
		$this->renewal_date     = new \DateTime( $data->renewal_date );
		$this->key_expired_date = new \DateTime( $data->key_expired_date );
		$this->transaction      = it_exchange_get_transaction( $data->transaction_id );
		$this->revenue          = (float) $data->revenue;

		if ( ! $this->transaction instanceof \IT_Exchange_Transaction ) {
			throw new \InvalidArgumentException( __( "Invalid transaction.", Plugin::SLUG ) );
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
	public static function create( Key $key, \IT_Exchange_Transaction $transaction = null, \DateTime $expired, \DateTime $renewal = null ) {

		if ( empty( $renewal ) ) {
			$renewal = new \DateTime();
		}

		$revenue = '0.00';

		if ( $transaction ) {

			$tid = $transaction->ID;

			foreach ( it_exchange_get_transaction_products( $transaction ) as $product ) {
				if ( $product['product_id'] == $key->get_product()->ID ) {
					$revenue = $product['product_subtotal'];

					break;
				}
			}
		} else {
			$tid = 0;
		}

		$data = array(
			'lkey'             => $key->get_key(),
			'renewal_date'     => $renewal->format( "Y-m-d H:i:s" ),
			'key_expired_date' => $expired->format( "Y-m-d H:i:s" ),
			'transaction_id'   => $tid,
			'revenue'          => $revenue
		);

		$db = Manager::make_simple_query_object( 'itelic-renewals' );
		$id = $db->insert( $data );

		$renewal = self::get( $id );

		if ( $renewal ) {

			/**
			 * Fires when a renewal record is created.
			 *
			 * @since 1.0
			 *
			 * @param Renewal $renewal
			 */
			do_action( 'itelic_create_renewal', $renewal );

			Cache::add( $renewal );
		}

		return $renewal;
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
	 * Get the renewal record ID.
	 *
	 * @since 1.0
	 *
	 * @return int
	 */
	public function get_id() {
		return $this->get_pk();
	}

	/**
	 * Get the key this record renews.
	 *
	 * @since 1.0
	 *
	 * @return Key
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
	 * Get the revenue from this renewal.
	 *
	 * @since 1.0
	 *
	 * @param bool $format
	 *
	 * @return float|string
	 */
	public function get_revenue( $format = false ) {

		if ( $format ) {
			return it_exchange_format_price( $this->revenue );
		}

		return $this->revenue;
	}

	/**
	 * @return string
	 */
	public function __toString() {
		return $this->get_key() . ' – ' . $this->get_renewal_date()->format( get_option( 'date_format' ) );
	}

	/**
	 * Delete this object.
	 *
	 * @since 1.0
	 *
	 * @throws DB\Exception
	 */
	public function delete() {

		/**
		 * Fires before a renewal record is deleted.
		 *
		 * @since 1.0
		 *
		 * @param Renewal $this
		 */
		do_action( 'itelic_delete_renewal', $this );

		parent::delete();

		/**
		 * Fires after a renewal record is deleted.
		 *
		 * @since 1.0
		 *
		 * @param Renewal $this
		 */
		do_action( 'itelic_deleted_renewal', $this );
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
		return Manager::get( 'itelic-renewals' );
	}
}