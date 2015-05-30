<?php
/**
 * Load the custom DB tables.
 *
 * @author Iron Bound Designs
 * @since  1.0
 */

namespace ITELIC\DB;

use ITELIC\DB\Table\Keys;
use ITELIC\DB\Table\Activations;
use ITELIC\DB\Table\Releases;
use ITELIC\DB\Table\Renewals;
use ITELIC\DB\Table\Upgrades;

Manager::register( 'keys', new Keys() );
Manager::register( 'activations', new Activations() );
Manager::register( 'renewals', new Renewals() );
Manager::register( 'releases', new Releases() );
Manager::register( 'upgrades', new Upgrades() );

add_action( 'itelic_activate', array( 'ITELIC\DB\Manager', 'initialize_tables' ) );
add_action( 'itelic_upgrade', array( 'ITELIC\DB\Manager', 'initialize_tables' ) );