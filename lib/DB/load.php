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
use ITELIC\DB\Table\Release_Meta;
use ITELIC\DB\Table\Releases;
use ITELIC\DB\Table\Renewals;
use ITELIC\DB\Table\Updates;

Manager::register( new Keys() );
Manager::register( new Activations() );
Manager::register( new Renewals() );
Manager::register( new Releases() );
Manager::register( new Updates() );
Manager::register( new Release_Meta() );

global $wpdb;

$wpdb->itelic_releasemeta = Manager::get( 'itelic-release-meta' )->get_table_name( $wpdb );

add_filter( 'sanitize_key', function ( $sanitized, $original ) {

	if ( $original == 'itelic_release_id' ) {
		$sanitized = sanitize_key( 'release_id' );
	}

	return $sanitized;

}, 10, 2 );

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
	Manager::maybe_install_table( new Updates() );
	Manager::maybe_install_table( new Release_Meta() );
}

add_action( 'itelic_activate', __NAMESPACE__ . '\\itelic_install_tables' );
add_action( 'itelic_upgrade', __NAMESPACE__ . '\\itelic_install_tables' );
