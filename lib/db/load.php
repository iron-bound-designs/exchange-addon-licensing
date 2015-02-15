<?php
/**
 * Load the custom DB tables.
 *
 * @author Iron Bound Designs
 * @since  1.0
 */

/**
 * Create custom DB tables on upgrade or activate.
 *
 * @since 1.0
 */
function itelic_create_tables() {

	$activations = ITELIC_DB_Activations::instance();

	if ( $activations->get_installed_version() != $activations->get_version() ) {
		$activations->create();
	}

	$keys = ITELIC_DB_Keys::instance();

	if ( $keys->get_installed_version() != $keys->get_version() ) {
		$keys->create();
	}
}

add_action( 'itelic_upgrade', 'itelic_create_tables' );
add_action( 'itelic_activate', 'itelic_create_tables' );