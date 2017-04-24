<?php
/**
 * License Renewal Class
 *
 * @author    Iron Bound Designs
 * @since     1.0
 * @license   AGPL
 * @copyright Iron Bound Designs, 2015.
 */

namespace ITELIC;

use IronBound\Cache\Cache;
use IronBound\DB\Model;
use IronBound\DB\Table\Table;
use IronBound\DB\Manager;

/**
 * Class Renewal
 *
 * @property int                      $id
 * @property Key                      $lkey
 * @property \DateTime                $renewal_date
 * @property \DateTime                $key_expired_date
 * @property \IT_Exchange_Transaction $transaction
 * @property float                    $revenue
 */
class Renewal extends Model {

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

		if ( ! $renewal ) {
			$renewal = make_date_time();
		}

		$revenue = '0.00';

		if ( $transaction ) {

			$tid = $transaction->ID;

			foreach ( $transaction->get_products() as $product ) {
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
			'renewal_date'     => $renewal,
			'key_expired_date' => $expired,
			'transaction_id'   => $tid,
			'revenue'          => $revenue
		);

		$renewal = static::_do_create( $data );

		if ( $renewal ) {

			/**
			 * Fires when a renewal record is created.
			 *
			 * @since 1.0
			 *
			 * @param Renewal $renewal
			 */
			do_action( 'itelic_create_renewal', $renewal );
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
		return $this->lkey;
	}

	/**
	 * Get the date of this renewal.
	 *
	 * @since 1.0
	 *
	 * @return \DateTime
	 */
	public function get_renewal_date() {
		return clone $this->renewal_date;
	}

	/**
	 * Get the date the key originally expired.
	 *
	 * @since 1.0
	 *
	 * @return \DateTime
	 */
	public function get_key_expired_date() {
		return clone $this->key_expired_date;
	}

	/**
	 * Get the transaction that was used to renew this key.
	 *
	 * @since 1.0
	 *
	 * @return \IT_Exchange_Transaction|null
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
		return $this->get_key() . ' – ' . convert_gmt_to_local( $this->get_renewal_date() )->format( get_option( 'date_format' ) );
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
	 * Get the table object for this model.
	 *
	 * @since 1.0
	 *
	 * @returns Table
	 */
	protected static function get_table() {
		return Manager::get( 'itelic-renewals' );
	}

	protected function _access_transaction( $raw ) {
		return it_exchange_get_transaction( $raw );
	}

	protected function _mutate_transaction( $value ) {

		if ( is_numeric( $value ) ) {
			return $value;
		}

		if ( $value instanceof \IT_Exchange_Transaction ) {
			return $value->get_ID();
		}

		if ( $value instanceof \WP_Post ) {
			return $value->ID;
		}

		return $value;
	}
}