<?php
/**
 * Load the custom DB tables.
 *
 * @author    Iron Bound Designs
 * @since     1.0
 * @license   AGPL
 * @copyright Iron Bound Designs, 2015.
 */

namespace ITELIC\DB;

use IronBound\DB\Manager;
use ITELIC\DB\Table\Activation_Meta;
use ITELIC\DB\Table\Keys;
use ITELIC\DB\Table\Activations;
use ITELIC\DB\Table\Release_Meta;
use ITELIC\DB\Table\Releases;
use ITELIC\DB\Table\Renewals;
use ITELIC\DB\Table\Updates;

foreach ( \ITELIC\get_tables() as $table ) {
	Manager::register( $table );
}

/**
 * Install custom DB tables.
 *
 * @since 1.0
 */
function install_tables() {

	foreach ( \ITELIC\get_tables() as $table ) {
		Manager::maybe_install_table( $table );
	}
}

add_action( 'itelic_activate', __NAMESPACE__ . '\\install_tables' );
add_action( 'itelic_upgrade', __NAMESPACE__ . '\\install_tables' );
