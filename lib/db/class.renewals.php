<?php
/**
 * DB for storing renewal records.
 *
 * @author Iron Bound Designs
 * @since  1.0
 */

/**
 * Class ITELIC_DB_Renewals
 */
class ITELIC_DB_Renewals extends ITELIC_DB_Base {

	/**
	 * @var ITELIC_DB_Renewals|null
	 */
	protected static $instance = null;

	/**
	 * Constructor.
	 */
	public function __construct() {
		parent::__construct();

		$this->version     = 1.0;
		$this->primary_key = 'id';
		$this->table_name  = $this->wpdb->prefix . 'itelic_renewals';
	}

	/**
	 * Retrieve an instance of the db.
	 *
	 * @return ITELIC_DB_Activations
	 */
	public static function instance() {
		if ( self::$instance === null ) {
			self::$instance = new ITELIC_DB_Renewals();
		}

		return self::$instance;
	}

	/**
	 * Retrieve all renewals.
	 *
	 * @return object
	 */
	public static function all() {

		$db = self::instance();

		$query = $db->build_query();

		return $db->wpdb->get_results( $query );
	}

	/**
	 * Retrieve information about a renewal.
	 *
	 * @param string $key
	 *
	 * @return object
	 */
	public static function retrieve( $key ) {

		$db = self::instance();

		return $db->get( $key );
	}

	/**
	 * Find the first matching value.
	 *
	 * @param string $col Column to find a license by.
	 * @param string $val Value of that column.
	 *
	 * @return object
	 */
	public static function find( $col, $val ) {

		$db = self::instance();

		return $db->get_by( $col, $val );
	}

	/**
	 * Find many renewals by a certain column or value.
	 *
	 * @param string $col Column to find licenses by.
	 * @param string $val Value of that column.
	 *
	 * @return array
	 */
	public static function many( $col, $val ) {

		$db = self::instance();

		$query = $db->build_query( "*", array( $col => $val ) );

		return $db->wpdb->get_results( $query );
	}

	/**
	 * Search for renewals by multiple values.
	 *
	 * @param array $where
	 *
	 * @return array
	 */
	public static function search( $where ) {

		$db = self::instance();

		$query = $db->build_query( '*', $where );

		return $db->wpdb->get_results( $query );
	}

	/**
	 * Columns in the table.
	 *
	 * key => sprintf field type
	 *
	 * @since 1.0
	 *
	 * @return array
	 */
	public function get_columns() {
		return array(
			'id'               => '%d',
			'lkey'             => '%s',
			'renewal_date'     => '%s',
			'key_expired_date' => '%s',
			'transaction_id'   => '%d'
		);
	}

	/**
	 * Default column values.
	 *
	 * @since 1.0
	 *
	 * @return array
	 */
	public function get_column_defaults() {
		return array(
			'id'               => 0,
			'lkey'             => '',
			'renewal_date'     => '',
			'key_expired_date' => '',
			'transaction_id'   => 0
		);
	}

	/**
	 * Create the db table.
	 */
	public function create() {

		require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );

		$sql = "CREATE TABLE {$this->table_name} (
		id INTEGER UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
		lkey VARCHAR(255) NOT NULL,
		renewal_date DATETIME NOT NULL,
		key_expired_date DATETIME,
		transaction_id INTEGER NOT NULL,
		INDEX key_to_dates (lkey, renewal_date, key_expired_date)
		) CHARACTER SET utf8 COLLATE utf8_general_ci;";

		dbDelta( $sql );

		update_option( $this->table_name . '_db_version', $this->version );
	}

	/**
	 * Get the version of the installed table.
	 *
	 * @since 1.0
	 *
	 * @return float
	 */
	public function get_installed_version() {
		return (float) get_option( $this->table_name . '_db_version' );
	}

	/**
	 * Get version of this table.
	 *
	 * @since 1.0
	 *
	 * @return float
	 */
	public function get_version() {
		return $this->version;
	}
}