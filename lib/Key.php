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
	 * @var string
	 */
	private $key;

	/**
	 * @var \IT_Exchange_Transaction
	 */
	private $transaction;

	/**
	 * @var Product
	 */
	private $product;

	/**
	 * @var \IT_Exchange_Customer
	 */
	private $customer;

	/**
	 * @var string
	 */
	private $status;

	/**
	 * @var \DateTime|null
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
	 * @throws \InvalidArgumentException If an invalid transaction, product or
	 *                                  customer.
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
		$this->key         = $data->lkey;
		$this->transaction = it_exchange_get_transaction( $data->transaction_id );
		$this->product     = itelic_get_product( $data->product );
		$this->customer    = it_exchange_get_customer( $data->customer );
		$this->status      = $data->status;
		$this->max         = $data->max;

		if ( ! empty( $data->expires ) && $data->expires != '0000-00-00 00:00:00' ) {
			$this->expires = make_date_time( $data->expires );
		}

		foreach (
			array(
				'transaction',
				'product',
				'customer'
			) as $maybe_error
		) {
			if ( ! $this->$maybe_error || is_wp_error( $this->$maybe_error ) ) {
				throw new \InvalidArgumentException( "Invalid $maybe_error" );
			}
		}
	}

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
			'transaction_id' => $transaction->ID,
			'product'        => $product->ID,
			'customer'       => $customer->id,
			'status'         => $status,
			'max'            => (int) $max,
			'expires'        => isset( $expires ) ? $expires->format( "Y-m-d H:i:s" ) : null
		);

		$db = Manager::make_simple_query_object( 'itelic-keys' );
		$db->insert( $data );

		$key = self::get( $key );

		if ( $key ) {

			/**
			 * Fires when a license key is created.
			 *
			 * @since 1.0
			 *
			 * @param Key $key
			 */
			do_action( 'itelic_create_key', $key );

			Cache::add( $key );
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

		if ( $this->get_expires() > $now && $this->get_status() != self::ACTIVE ) {
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
			$date = make_date_time( $transaction->post_date_gmt );
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

		if ( $date === null ) {
			$date = make_date_time();
		}

		if ( $this->get_status() !== self::EXPIRED ) {
			$this->set_status( self::EXPIRED );
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
		return $this->key;
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
		return $this->transaction;
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

		$this->status = $status;
		$this->update( 'status', $this->get_status() );
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

		$db = Manager::make_simple_query_object( 'itelic-activations' );

		return $db->count( array(
			'lkey'   => $this->get_key(),
			'status' => Activation::ACTIVE
		) );
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

		if ( $expires ) {
			$val = $expires->format( "Y-m-d H:i:s" );
		} else {
			$val = null;
		}

		$this->update( 'expires', $val );
	}

	/**
	 * @return int
	 */
	public function get_max() {
		return $this->max ? $this->max : '';
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
		$this->update( 'max', $this->get_max() );
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
			'expires'     => $this->get_expires() ? $this->get_expires()->format( \DateTime::ISO8601 ) : '',
			'activations' => array(
				'count'        => count( $activations ),
				'count_active' => $this->get_active_count(),
				'list'         => $activations
			)
		);

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

		$data['lkey']           = $this->get_key();
		$data['transaction_id'] = $this->get_transaction()->ID;

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
		return Manager::get( 'itelic-keys' );
	}
}