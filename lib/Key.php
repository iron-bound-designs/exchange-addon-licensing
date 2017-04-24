<?php
/**
 * License Key Class
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
use ITELIC\Query\Activations;
use ITELIC\Query\Renewals;

/**
 * Class ITELIC\Key
 *
 * Class used to represent a license key.
 *
 * @since 1.0
 *
 * @property string                   $lkey
 * @property \IT_Exchange_Transaction $transaction_id
 * @property Product                  $product
 * @property \IT_Exchange_Customer    $customer
 * @property string                   $status
 * @property \DateTime|null           $expires
 * @property int                      $max
 */
class Key extends Model implements API\Serializable {

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
	 * Create a license key record.
	 *
	 * @since 1.0
	 *
	 * @param string                   $key
	 * @param \IT_Exchange_Transaction $transaction
	 * @param \IT_Exchange_Product     $product
	 * @param \IT_Exchange_Customer    $customer
	 * @param int                      $max
	 * @param \DateTime                $expires
	 * @param string                   $status
	 *
	 * @return Key
	 */
	public static function create( $key, \IT_Exchange_Transaction $transaction, \IT_Exchange_Product $product, \IT_Exchange_Customer $customer, $max, \DateTime $expires = null, $status = '' ) {

		if ( empty( $key ) ) {
			throw new \LengthException( "\$key must not be empty." );
		}

		if ( strlen( $key ) > 128 ) {
			throw new \LengthException( "The maximum key length is 128 characters." );
		}

		if ( empty( $status ) ) {
			$status = self::ACTIVE;
		}

		$now = make_date_time();

		if ( $expires && $expires < $now ) {
			$status = self::EXPIRED;
		}

		$data = array(
			'lkey'           => $key,
			'transaction_id' => $transaction,
			'product'        => $product->ID,
			'customer'       => $customer,
			'status'         => $status,
			'max'            => (int) $max,
			'expires'        => $expires
		);

		$key = static::_do_create( $data );

		if ( $key ) {

			/**
			 * Fires when a license key is created.
			 *
			 * @since 1.0
			 *
			 * @param Key $key
			 */
			do_action( 'itelic_create_key', $key );
		}

		return $key;
	}

	/**
	 * Extend the expiration date of this license,
	 * by its length. For example, if a license has an
	 * expiration date of one year after purchase,
	 * extending it will extend the expiration date by one year.
	 *
	 * @since 1.0
	 *
	 * @return \DateTime
	 */
	public function extend() {
		if ( $this->get_expires() === null ) {
			return null;
		}

		$type  = $this->get_product()->get_feature( 'recurring-payments', array( 'setting' => 'interval' ) );
		$count = $this->get_product()->get_feature( 'recurring-payments', array( 'setting' => 'interval-count' ) );

		$interval = convert_rp_to_date_interval( $type, $count );
		$expires  = $this->get_expires();

		$expires->add( $interval );
		$this->set_expires( $expires );

		$now = make_date_time();

		if ( $this->get_expires() > $now && $this->get_status() !== self::ACTIVE ) {
			$this->set_status( self::ACTIVE );
		}

		/**
		 * Fires when a license key's expiration date is extended.
		 *
		 * @since 1.0
		 *
		 * @param Key $this
		 */
		do_action( 'itelic_extend_key', $this );

		return $this->get_expires();
	}

	/**
	 * Check if a key is renewable.
	 *
	 * @since 1.0
	 *
	 * @return bool
	 */
	public function is_renewable() {

		if ( $this->get_expires() === null ) {
			return false;
		}

		return $this->product->has_feature( 'recurring-payments' );
	}

	/**
	 * Renew this license.
	 *
	 * @since 1.0
	 *
	 * @param \IT_Exchange_Transaction $transaction
	 *
	 * @return Renewal
	 */
	public function renew( \IT_Exchange_Transaction $transaction = null ) {

		if ( ! $this->is_renewable() ) {
			throw new \UnexpectedValueException( __( "You can't renew a license key that doesn't expire.", Plugin::SLUG ) );
		}

		if ( $transaction === null ) {
			$date = null;
		} else {
			$date = make_date_time( $transaction->get_date( true ) );
		}

		$record = Renewal::create( $this, $transaction, $this->get_expires(), $date );

		$this->extend();

		$now = make_date_time();

		if ( $this->get_expires() > $now ) {
			$this->set_status( self::ACTIVE );
		}

		/**
		 * Fires when a license key is renewed.
		 *
		 * @since 1.0
		 *
		 * @param Key $this
		 */
		do_action( 'itelic_renew_key', $this );

		return $record;
	}

	/**
	 * Expire this license and all active activation records.
	 *
	 * @since 1.0
	 *
	 * @param \DateTime $date
	 */
	public function expire( \DateTime $date = null ) {

		if ( $this->status !== self::EXPIRED ) {
			$this->status = self::EXPIRED;
		}

		if ( $date === null ) {
			$date = make_date_time();
		}

		$this->set_expires( $date );

		foreach ( $this->get_activations( Activation::ACTIVE ) as $activation ) {
			$activation->expire();
		}

		/**
		 * Fires when a license key is expired.
		 *
		 * @since 1.0
		 *
		 * @param Key $this
		 */
		do_action( 'itelic_expire_license', $this );
	}

	/**
	 * Get all activations of this license key.
	 *
	 * @since 1.0
	 *
	 * @param string $status
	 *
	 * @return Activation[]
	 */
	public function get_activations( $status = '' ) {

		$args = array(
			'key' => $this->get_key()
		);

		if ( $status ) {

			if ( ! array_key_exists( $status, Activation::get_statuses() ) ) {
				throw new \InvalidArgumentException( "Invalid status" );
			}

			$args['status'] = $status;
		}

		return itelic_get_activations( $args );
	}

	/**
	 * Get the unique pk for this record.
	 *
	 * @since 1.0
	 *
	 * @return mixed (generally int, but not necessarily).
	 */
	public function get_pk() {
		return $this->lkey;
	}

	/**
	 * @return string
	 */
	public function get_key() {
		return $this->get_pk();
	}

	/**
	 * @return \IT_Exchange_Transaction
	 */
	public function get_transaction() {
		return $this->transaction_id;
	}

	/**
	 * @return Product
	 */
	public function get_product() {
		return $this->product;
	}

	/**
	 * @return \IT_Exchange_Customer
	 */
	public function get_customer() {
		return $this->customer ?: $this->transaction_id->get_customer();
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

		$stauses = self::get_statuses();

		return isset( $stauses[ $this->status ] ) ? $stauses[ $this->status ] : __( "Unknown", Plugin::SLUG );
	}

	/**
	 * Set the status of this key.
	 *
	 * @since 1.0
	 *
	 * @param string $status
	 */
	public function set_status( $status ) {
		if ( ! array_key_exists( $status, self::get_statuses() ) ) {
			throw new \InvalidArgumentException( __( "Invalid value for key status.", Plugin::SLUG ) );
		}

		$old_status = $this->status;

		if ( $old_status === self::ACTIVE && $status === self::EXPIRED ) {
			$this->expire();
		}

		$this->status = $status;
		$this->save();

		/**
		 * Fires when a key's status is transitioned.
		 *
		 * @since 1.0
		 *
		 * @param Key    $this
		 * @param string $old_status
		 * @param string $status
		 */
		do_action( 'itelic_transition_key_status', $this, $old_status, $status );
	}

	/**
	 * Get the list of statuses.
	 *
	 * @since 1.0
	 *
	 * @return array
	 */
	public static function get_statuses() {
		return array(
			self::ACTIVE   => __( "Active", Plugin::SLUG ),
			self::DISABLED => __( "Disabled", Plugin::SLUG ),
			self::EXPIRED  => __( "Expired", Plugin::SLUG )
		);
	}

	/**
	 * @return int
	 */
	public function get_active_count() {

		$count = wp_cache_get( $this->get_key(), 'itelic-key-active-count' );

		if ( $count === false ) {

			$db = Manager::make_simple_query_object( 'itelic-activations' );

			$count = $db->count( array(
				'lkey'   => $this->get_key(),
				'status' => Activation::ACTIVE
			) );

			wp_cache_set( $this->get_key(), $count, 'itelic-key-active-count' );
		}

		return $count;
	}

	/**
	 * Get the expiration date.
	 *
	 * @since 1.0
	 *
	 * @return \DateTime|null Returns null if lifetime.
	 */
	public function get_expires() {

		if ( $this->expires ) {
			return clone $this->expires;
		}

		return null;
	}

	/**
	 * Set the expiry date.
	 *
	 * @since 1.0
	 *
	 * @param \DateTime $expires . Set null for forever.
	 */
	public function set_expires( \DateTime $expires = null ) {
		$this->expires = $expires;
		$this->save();
	}

	/**
	 * @return int
	 */
	public function get_max() {
		return $this->max ?: '';
	}

	/**
	 * Set the maximum number of activations.
	 *
	 * @since 1.0
	 *
	 * @param int $max
	 */
	public function set_max( $max ) {
		$this->max = (int) $max;
		$this->save();
	}

	/**
	 * Is this an online product, IE are activations tied to URLs.
	 *
	 * @since 1.0
	 *
	 * @return bool
	 */
	public function is_online_product() {
		return (bool) $this->get_product()->get_feature( 'licensing', array( 'field' => 'online-software' ) );
	}

	/**
	 * @return string
	 */
	public function __toString() {
		return $this->get_key();
	}

	/**
	 * Get data suitable for the API.
	 *
	 * @since 1.0
	 *
	 * @return array
	 */
	public function get_api_data() {

		$activations = $this->get_activations();

		$data = array(
			'transaction' => $this->get_transaction()->ID,
			'product'     => $this->get_product()->ID,
			'customer'    => $this->get_customer()->wp_user->ID,
			'status'      => $this->get_status(),
			'max'         => $this->get_max(),
			'expires'     => $this->get_expires() ? $this->get_expires()->format( \DateTime::ATOM ) : '',
			'activations' => array(
				'count'        => count( $activations ),
				'count_active' => $this->get_active_count(),
				'list'         => $activations
			)
		);

		/**
		 * Filter the data used in the API for showing info about a license key.
		 *
		 * @since 1.0
		 *
		 * @param array $data
		 * @param Key   $this
		 */
		$data = apply_filters( 'itelic_key_api_data', $data, $this );

		return $data;
	}

	/**
	 * Delete the license key.
	 */
	public function delete() {

		/**
		 * Fires before a key is deleted.
		 *
		 * @since 1.0
		 *
		 * @param Activation $this
		 */
		do_action( 'itelic_delete_key', $this );

		$activations = itelic_get_activations( array(
			'key' => $this->get_key()
		) );

		foreach ( $activations as $activation ) {
			$activation->delete();
		}

		$renewals = itelic_get_renewals( array(
			'key' => $this->get_key()
		) );

		foreach ( $renewals as $renewal ) {
			$renewal->delete();
		}

		parent::delete();

		/**
		 * Fires after a key is deleted.
		 *
		 * @since 1.0
		 *
		 * @param Activation $this
		 */
		do_action( 'itelic_deleted_key', $this );
	}

	/**
	 * Get the table object for this model.
	 *
	 * @since 1.0
	 *
	 * @returns Table
	 */
	protected static function get_table() {
		return Manager::get( 'itelic-keys' );
	}

	protected function _access_product( $raw ) {
		return itelic_get_product( $raw );
	}

	protected function _mutate_product( $value ) {
		if ( is_numeric( $value ) ) {
			return $value;
		}

		if ( $value instanceof \IT_Exchange_Product ) {
			return $value->get_ID();
		}

		return $value;
	}

	protected function _access_transaction_id( $raw ) {
		return it_exchange_get_transaction( $raw );
	}

	protected function _mutate_transaction_id( $value ) {
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

	protected function _access_customer( $raw ) {
		return it_exchange_get_customer( $raw );
	}

	protected function _mutate_customer( $value ) {
		if ( is_numeric( $value ) ) {
			return $value;
		}

		if ( $value instanceof \IT_Exchange_Customer ) {
			return $value->get_ID();
		}

		if ( $value instanceof \WP_User ) {
			return $value->ID;
		}

		return $value;
	}
}