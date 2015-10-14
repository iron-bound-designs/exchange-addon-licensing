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

global $wpdb;

$wpdb->itelic_releasemeta    = Manager::get( 'itelic-release-meta' )->get_table_name( $wpdb );
$wpdb->itelic_activationmeta = Manager::get( 'itelic-activation-meta' )->get_table_name( $wpdb );

add_filter( 'sanitize_key', function ( $sanitized, $original ) {

	if ( $original == 'itelic_release_id' ) {
		$sanitized = sanitize_key( 'release_id' );
	}

	if ( $original == 'itelic_activation_id' ) {
		$sanitized = sanitize_key( 'activation_id' );
	}

	return $sanitized;

}, 10, 2 );

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
