<?php
/**
 * Activation Class
 *
 * @author    Iron Bound Designs
 * @since     1.0
 * @license   AGPL
 * @copyright Iron Bound Designs, 2015.
 */

namespace ITELIC;

use IronBound\Cache\Cache;
use IronBound\DB\Extensions\Meta\MetaTable;
use IronBound\DB\Extensions\Meta\ModelWithMeta;
use IronBound\DB\Model;
use IronBound\DB\Table\Table;
use IronBound\DB\Manager;
use IronBound\DB\Exception as DB_Exception;
use ITELIC\Query\Updates;

/**
 * Class ITELIC_Activation
 *
 * Class that logs activations.
 *
 * @since 1.0
 *
 * @property int            $id
 * @property Key            $lkey
 * @property string         $location
 * @property string         $status
 * @property \DateTime      $activation
 * @property \DateTime|null $deactivation
 * @property Release        $release_id
 */
class Activation extends ModelWithMeta implements API\Serializable {

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
	 * Create an activation.
	 *
	 * @param Key       $key
	 * @param string    $location
	 * @param \DateTime $activation
	 * @param Release   $release
	 * @param string    $status
	 *
	 * @return Activation
	 *
	 * @throws \LogicException|DB_Exception
	 */
	public static function create( Key $key, $location, \DateTime $activation = null, Release $release = null, $status = '' ) {

		if ( empty( $key ) || empty( $location ) ) {
			throw new \InvalidArgumentException( __( "The license key and install location are required.", Plugin::SLUG ) );
		}

		if ( strlen( $location ) > 191 ) {
			throw new \LengthException( "The location field has a max length of 191 characters." );
		}

		if ( $key->get_max() && $key->get_active_count() >= $key->get_max() ) {
			throw new \OverflowException( __( "This license key has reached it's maximum number of activations.", Plugin::SLUG ) );
		}

		if ( $activation === null ) {
			$activation = make_date_time()->format( 'Y-m-d H:i:s' );
		} else {
			$activation = $activation->format( 'Y-m-d H:i:s' );
		}

		if ( empty( $status ) ) {
			$status = self::ACTIVE;
		}

		if ( $key->is_online_product() ) {
			$location = itelic_normalize_url( $location );
		}

		$data = array(
			'lkey'         => $key,
			'location'     => $location,
			'activation'   => $activation,
			'deactivation' => null,
			'status'       => $status
		);

		if ( $release ) {
			$data['release_id'] = $release;
		}

		$existing_activation = itelic_get_activation_by_location( $location, $key );

		if ( $existing_activation ) {
			throw new \InvalidArgumentException( __( "An activation with this same location already exists.", Plugin::SLUG ) );
		}

		$activation = static::_do_create( $data );

		if ( ! $release ) {

			$latest = $key->get_product()->get_latest_release_for_activation( $activation );

			if ( $latest ) {
				$activation->set_release( $latest );
			}
		}

		/**
		 * Fires when an activation record is created.
		 *
		 * @since 1.0
		 *
		 * @param Activation $activation
		 */
		do_action( 'itelic_create_activation', $activation );

		return $activation;
	}

	/**
	 * Deactivate this activation.
	 *
	 * @param \DateTime $date
	 */
	public function deactivate( \DateTime $date = null ) {

		if ( $date === null ) {
			$date = make_date_time();
		}

		$this->set_deactivation( $date );
		$this->set_status( self::DEACTIVATED );

		/**
		 * Fires when an activation record is deactivated.
		 *
		 * @since 1.0
		 *
		 * @param Activation $this
		 */
		do_action( 'itelic_deactivate_activation', $this );
	}

	/**
	 * Reactivate a previously deactivated activation.
	 *
	 * @since 1.0
	 *
	 * @param \DateTime $date When was the record reactivated.
	 *
	 * @throws \Exception If activation record isn't deactivated.
	 */
	public function reactivate( \DateTime $date = null ) {

		if ( $this->get_status() != self::DEACTIVATED ) {
			throw new \UnexpectedValueException( __( "Only deactivated activation records can be reactivated.", Plugin::SLUG ) );
		}

		if ( $this->get_key()->get_max() && $this->get_key()->get_active_count() >= $this->get_key()->get_max() ) {
			throw new \OverflowException( __( "This license key has reached it's maximum number of activations.", Plugin::SLUG ) );
		}

		if ( $date === null ) {
			$date = make_date_time();
		}

		$this->set_deactivation( null );
		$this->set_activation( $date );
		$this->set_status( self::ACTIVE );

		/**
		 * Fires when an activation record is reactivated.
		 *
		 * @since 1.0
		 *
		 * @param Activation $this
		 */
		do_action( 'itelic_reactivate_activation', $this );
	}

	/**
	 * Expire an activation record.
	 *
	 * @since 1.0
	 */
	public function expire() {
		$this->set_status( self::EXPIRED );

		/**
		 * Fires when an activation is expired.
		 *
		 * @since 1.0
		 *
		 * @param Activation $this
		 */
		do_action( 'itelic_expire_activation', $this );
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
	 * @return int
	 */
	public function get_id() {
		return $this->get_pk();
	}

	/**
	 * @return Key
	 */
	public function get_key() {
		return $this->lkey;
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

		$statuses = self::get_statuses();

		return isset( $statuses[ $this->status ] ) ? $statuses[ $this->status ] : __( "Unknown", Plugin::SLUG );
	}

	/**
	 * Set the status of this record.
	 *
	 * @param string $status
	 */
	protected function set_status( $status ) {

		if ( ! array_key_exists( $status, self::get_statuses() ) ) {
			throw new \InvalidArgumentException( "Invalid status." );
		}

		$old_status = $this->status;
		$this->status = $status;
		$this->save();

		/**
		 * Fires when an activation's status is transitioned.
		 *
		 * @since 1.0
		 *
		 * @param Activation $this
		 * @param string     $old_status
		 * @param string     $status
		 */
		do_action( 'itelic_transition_activation_status', $this, $old_status, $status );
	}

	/**
	 * Get a list of all statuses.
	 *
	 * @since 1.0
	 *
	 * @return array
	 */
	public static function get_statuses() {
		return array(
			self::ACTIVE      => __( "Active", Plugin::SLUG ),
			self::DEACTIVATED => __( "Deactivated", Plugin::SLUG ),
			self::EXPIRED     => __( "Expired", Plugin::SLUG )
		);
	}

	/**
	 * Get the activation date.
	 *
	 * @since 1.0
	 *
	 * @return \DateTime
	 */
	public function get_activation() {
		return clone $this->activation;
	}

	/**
	 * Set the activation date.
	 *
	 * @param \DateTime $time
	 */
	protected function set_activation( \DateTime $time = null ) {
		$this->activation = $time;
		$this->save();
	}

	/**
	 * Get the deactivation date.
	 *
	 * @since 1.0
	 *
	 * @return \DateTime
	 */
	public function get_deactivation() {

		if ( $this->deactivation ) {
			return clone $this->deactivation;
		}

		return null;
	}

	/**
	 * Set the deactivation date.
	 *
	 * @param \DateTime $time
	 */
	protected function set_deactivation( \DateTime $time = null ) {
		$this->deactivation = $time;
		$this->save();
	}

	/**
	 * Get the currently installed version at this location.
	 *
	 * @since 1.0
	 *
	 * @return Release
	 */
	public function get_release() {
		return $this->release_id;
	}

	/**
	 * Set the current version installed on this location.
	 *
	 * @since 1.0
	 *
	 * @param Release $release
	 */
	public function set_release( Release $release ) {
		$this->release_id = $release;
		$this->save();
	}

	/**
	 * @return string
	 */
	public function __toString() {
		return $this->get_key() . ' – ' . $this->get_location();
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
		 * Fires before an activation record is deleted.
		 *
		 * @since 1.0
		 *
		 * @param Activation $this
		 */
		do_action( 'itelic_delete_activation', $this );

		parent::delete();

		$updates = itelic_get_updates( array(
			'activation' => $this->get_pk()
		) );

		foreach ( $updates as $update ) {
			$update->delete();
		}

		/**
		 * Fires after an activation record is deleted.
		 *
		 * @since 1.0
		 *
		 * @param Activation $this
		 */
		do_action( 'itelic_deleted_activation', $this );
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
			'activation'   => $this->get_activation()->format( \DateTime::ATOM ),
			'deactivation' => ( $d = $this->get_deactivation() ) ? $d->format( \DateTime::ATOM ) : '',
			'location'     => $this->get_location(),
			'status'       => $this->get_status(),
			'track'        => $this->get_meta( 'track', true ) ?: 'stable',
			'key'          => $this->get_key()->get_key()
		);

		/**
		 * Filter the data used in the API for showing info about an activation.
		 *
		 * @since 1.0
		 *
		 * @param array $data
		 * @param Key   $this
		 */
		$data = apply_filters( 'itelic_activation_api_data', $data, $this );

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
		return Manager::get( 'itelic-activations' );
	}

	/**
	 * @inheritDoc
	 */
	public static function get_meta_table() {
		return Manager::get( 'itelic-activation-meta' );
	}
}