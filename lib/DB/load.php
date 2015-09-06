<?php
/**
 * Load the custom DB tables.
 *
 * @author Iron Bound Designs
 * @since  1.0
 */

namespace ITELIC\DB;

use IronBound\DB\Manager;
use ITELIC\DB\Table\Keys;
use ITELIC\DB\Table\Activations;
use ITELIC\DB\Table\Releases;
use ITELIC\DB\Table\Renewals;
use ITELIC\DB\Table\Upgrades;

Manager::register( new Keys() );
Manager::register( new Activations() );
Manager::register( new Renewals() );
Manager::register( new Releases() );
Manager::register( new Upgrades() );

/**
 * Install custom DB tables.
 *
 * @since 1.0
 */
function itelic_install_tables() {

	Manager::maybe_install_table( new Keys() );        
	Manager::maybe_install_table( new Activations() );
        Manager::maybe_install_table( new Renewals() );
        Manager::maybe_install_table( new Releases() );
        Manager::maybe_install_table( new Upgrades() );
}

add_action( 'itelic_activate', __NAMESPACE__ . '\\itelic_install_tables' );
add_action( 'itelic_upgrade', __NAMESPACE__ . '\\itelic_install_tables' );
