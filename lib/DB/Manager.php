<?php
/**
 * DB Query Manager.
 *
 * @author Iron Bound Designs
 * @since  1.0
 */

namespace ITELIC\DB;

use ITELIC\DB\Query\Query;
use ITELIC\DB\Table\Base;

/**
 * Class Manager
 * @package ITELIC\DB
 */
final class Manager {

	/**
	 * @var Base[]
	 */
	private static $tables = array();

	/**
	 * Register a db table.
	 *
	 * @since 1.0
	 *
	 * @param string $slug  Table name.
	 * @param Base   $class Table object.
	 */
	public static function register( $slug, $class ) {
		self::$tables[ $slug ] = $class;
	}

	/**
	 * Retrieve a db table object.
	 *
	 * @since 1.0
	 *
	 * @param string $slug
	 *
	 * @return Base|null
	 */
	public static function get( $slug ) {

		if ( isset( self::$tables[ $slug ] ) ) {
			return self::$tables[ $slug ];
		} else {
			return null;
		}
	}

	/**
	 * Make a query object for the selected db table.
	 *
	 * @since 1.0
	 *
	 * @param string $slug Table name.
	 *
	 * @return Query|null
	 */
	public static function make_query_object( $slug ) {

		$table = self::get( $slug );

		if ( $table ) {
			return new Query( $GLOBALS['wpdb'], $table );
		} else {
			return null;
		}
	}

	/**
	 * Initialize tables.
	 *
	 * Check the currently installed version, and, if different, run the upgrade routine.
	 *
	 * @since 1.0
	 */
	public static function initialize_tables() {

		require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );

		foreach ( self::$tables as $table ) {
			$installed = (int) get_option( $table->get_table_name( $GLOBALS['wpdb'] ) . '_version', 0 );

			if ( $installed < $table->get_version() ) {
				dbDelta( $table->get_creation_sql( $GLOBALS['wpdb'] ) );

				update_option( $table->get_table_name( $GLOBALS['wpdb'] ) . '_version', $table->get_version() );
			}
		}
	}
}