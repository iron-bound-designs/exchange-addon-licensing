<?php
/**
 * Represents Upgrade objects.
 *
 * @author Iron Bound Designs
 * @since  1.0
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
 */
class Update extends Model {

	/**
	 * @var int
	 */
	private $ID;

	/**
	 * @var Activation
	 */
	private $activation;

	/**
	 * @var Release
	 */
	private $release;

	/**
	 * @var \DateTime
	 */
	private $update_date;

	/**
	 * @var string
	 */
	private $previous_version;

	/**
	 * Constructor.
	 *
	 * @param \stdClass $data
	 */
	public function __construct( \stdClass $data ) {
		$this->init( $data );
	}

	/**
	 * Init an object.
	 *
	 * @since 1.0
	 *
	 * @param \stdClass $data
	 */
	protected function init( \stdClass $data ) {
		$this->ID               = $data->ID;
		$this->activation       = itelic_get_activation( $data->activation );
		$this->release          = itelic_get_release( $data->release_id );
		$this->update_date      = new \DateTime( $data->update_date );
		$this->previous_version = $data->previous_version;
	}

	/**
	 * Create an Upgrade record.
	 *
	 * @since 1.0
	 *
	 * @param Activation $activation
	 * @param Release    $release
	 * @param \DateTime  $upgrade_date
	 * @param string     $previous_version
	 *
	 * @return Update|null
	 * @throws DB_Exception
	 */
	public static function create( Activation $activation, Release $release, \DateTime $upgrade_date = null, $previous_version = '' ) {

		if ( $upgrade_date === null ) {
			$upgrade_date = new \DateTime();
		}

		if ( empty( $previous_version ) ) {
			$previous_version = $activation->get_version();
		}

		$data = array(
			'activation'       => $activation->get_id(),
			'release_id'       => $release->get_ID(),
			'update_date'      => $upgrade_date->format( "Y-m-d H:i:s" ),
			'previous_version' => $previous_version
		);

		$db = Manager::make_simple_query_object( 'itelic-updates' );
		$ID = $db->insert( $data );

		$update = self::get( $ID );

		if ( $update ) {

			/**
			 * Fires when an update record is created.
			 *
			 * @since 1.0
			 *
			 * @param Update $update
			 */
			do_action( 'itelic_create_update', $update );

			Cache::add( $update );
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
		return $this->update_date;
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