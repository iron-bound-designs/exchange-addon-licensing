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

/**
 * Class Upgrade
 * @package ITELIC
 */
class Upgrade extends Model {

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
	private $upgrade_date;

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
		$this->ID           = $data->ID;
		$this->activation   = itelic_get_activation( $data->activation );
		$this->release      = new Release( $data->release_id );
		$this->upgrade_date = new \DateTime( $data->upgrade_date );
	}

	/**
	 * Create an Upgrade record.
	 *
	 * @since 1.0
	 *
	 * @param Activation $activation
	 * @param Release    $release
	 * @param \DateTime  $upgrade_date
	 *
	 * @return Upgrade|null
	 * @throws DB\Exception
	 */
	public static function create( Activation $activation, Release $release, \DateTime $upgrade_date = null ) {

		if ( $upgrade_date === null ) {
			$upgrade_date = new \DateTime();
		}

		$data = array(
			'activation'   => $activation->get_id(),
			'release_id'   => $release->get_ID(),
			'upgrade_date' => $upgrade_date->format( "Y-m-d H:i:s" )
		);

		$db = Manager::make_simple_query_object( 'itelic-upgrades' );
		$ID = $db->insert( $data );

		$upgrade = self::get( $ID );

		if ( $upgrade ) {
			Cache::add( $upgrade );
		}

		return $upgrade;
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
	public function get_upgrade_date() {
		return $this->upgrade_date;
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
	 * Get the table object for this model.
	 *
	 * @since 1.0
	 *
	 * @return Table
	 */
	protected static function get_table() {
		return Manager::get( 'itelic-upgrades' );
	}
}