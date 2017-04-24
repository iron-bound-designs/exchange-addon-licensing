<?php
/**
 * Activation Meta Table
 *
 * @author    Iron Bound Designs
 * @since     1.0
 * @license   AGPL
 * @copyright Iron Bound Designs, 2015.
 */

namespace ITELIC\DB\Table;

use IronBound\DB\Extensions\Meta\BaseMetaTable;
use IronBound\DB\Table\Table;

/**
 * Class Activation_Meta
 *
 * @package ITELIC\DB\Table
 */
class Activation_Meta extends BaseMetaTable {

	/**
	 * @inheritDoc
	 */
	public function __construct( Table $table ) {
		parent::__construct( $table, array(
			'slug'              => 'itelic-activation-meta',
			'primary_id_column' => 'activation_id'
		) );
	}

	/**
	 * Retrieve the name of the database table.
	 *
	 * @since 1.0
	 *
	 * @param \wpdb $wpdb
	 *
	 * @return string
	 */
	public function get_table_name( \wpdb $wpdb ) {
		return $wpdb->prefix . 'itelic_activationmeta';
	}

	/**
	 * Retrieve the version number of the current table schema as written.
	 *
	 * The version should be incremented by 1 for each change.
	 *
	 * @since 1.0
	 *
	 * @return int
	 */
	public function get_version() {
		return 1;
	}
}