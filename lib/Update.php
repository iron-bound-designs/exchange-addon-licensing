<?php
/**
 * Represents Upgrade objects.
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
use IronBound\DB\Exception as DB_Exception;

/**
 * Class Upgrade
 *
 * @package ITELIC
 *
 * @property int        $ID
 * @property Activation $activation
 * @property Release    $release
 * @property \DateTime  $update_date
 * @property string     $previous_version
 */
class Update extends Model {

	/**
	 * Create an Upgrade record.
	 *
	 * @since 1.0
	 *
	 * @param Activation $activation
	 * @param Release    $release
	 * @param \DateTime  $update_date
	 * @param string     $previous_version
	 *
	 * @return Update|null
	 * @throws DB_Exception
	 */
	public static function create( Activation $activation, Release $release, \DateTime $update_date = null, $previous_version = '' ) {

		if ( $update_date === null ) {
			$update_date = make_date_time();
		}

		if ( empty( $previous_version ) && $activation->get_release() ) {
			$previous_version = $activation->get_release()->get_version();
		}

		$data = array(
			'activation'       => $activation,
			'release_id'       => $release,
			'update_date'      => $update_date,
			'previous_version' => $previous_version
		);

		$update = static::_do_create( $data );

		if ( $update ) {

			$activation->set_release( $release );

			/**
			 * Fires when an update record is created.
			 *
			 * @since 1.0
			 *
			 * @param Update $update
			 */
			do_action( 'itelic_create_update', $update );
		}

		return $update;
	}

	/**
	 * Get the unique pk for this record.
	 *
	 * @since 1.0
	 *
	 * @return mixed (generally int, but not necessarily).
	 */
	public function get_pk() {
		return $this->ID;
	}

	/**
	 * Get the ID of this record.
	 *
	 * @since 1.0
	 *
	 * @return int
	 */
	public function get_ID() {
		return $this->get_pk();
	}

	/**
	 * Get the corresponding activation record.
	 *
	 * @since 1.0
	 *
	 * @return Activation
	 */
	public function get_activation() {
		return $this->activation;
	}

	/**
	 * Get the release object.
	 *
	 * @since 1.0
	 *
	 * @return Release
	 */
	public function get_release() {
		return $this->release;
	}

	/**
	 * Get the date when this upgrade took place.
	 *
	 * @since 1.0
	 *
	 * @return \DateTime
	 */
	public function get_update_date() {
		return clone $this->update_date;
	}

	/**
	 * Get the key that was upgraded.
	 *
	 * @since 1.0
	 *
	 * @return Key
	 */
	public function get_key() {
		return $this->get_activation()->get_key();
	}

	/**
	 * Get the corresponding customer for this upgrade.
	 *
	 * @since 1.0
	 *
	 * @return \IT_Exchange_Customer
	 */
	public function get_customer() {
		return $this->get_activation()->get_key()->get_customer();
	}

	/**
	 * Get the previous version the customer upgrade from.
	 *
	 * @since 1.0
	 *
	 * @return string
	 */
	public function get_previous_version() {
		return $this->previous_version;
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
		 * Fires before an update record is deleted.
		 *
		 * @since 1.0
		 *
		 * @param Update $this
		 */
		do_action( 'itelic_delete_update', $this );

		parent::delete();

		/**
		 * Fires after an update record is deleted.
		 *
		 * @since 1.0
		 *
		 * @param Update $this
		 */
		do_action( 'itelic_deleted_update', $this );
	}

	/**
	 * Get the table object for this model.
	 *
	 * @since 1.0
	 *
	 * @return Table
	 */
	protected static function get_table() {
		return Manager::get( 'itelic-updates' );
	}
}