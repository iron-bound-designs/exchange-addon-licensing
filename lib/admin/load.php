<?php
/**
 * Bootstrap the admin.
 *
 * @author Iron Bound Designs
 * @since  1.0
 */

/**
 * Register admin menus.
 *
 * @since 1.0
 */
function itelic_register_admin_menus() {

	add_submenu_page( 'it-exchange', __( "Licensing", ITELIC::SLUG ), __( "Licensing", ITELIC::SLUG ),
		apply_filters( 'it_exchange_admin_menu_capability', 'manage_options' ), ITELIC_Admin_Tab_Dispatch::PAGE_SLUG, array(
			new ITELIC_Admin_Tab_Dispatch(),
			'dispatch'
		) );
}

add_action( 'admin_menu', 'itelic_register_admin_menus', 85 );


/**
 * Save the per page option for the licenses list table.
 *
 * @since 1.0
 *
 * @param $status string
 * @param $option string
 * @param $value  string
 *
 * @return string|boolean
 */
function itelic_save_licenses_per_page( $status, $option, $value ) {

	if ( 'itelic_licenses_list_table_per_page' == $option ) {
		return $value;
	}

	return false;
}

add_filter( 'set-screen-option', 'itelic_save_licenses_per_page', 10, 3 );

/**
 * Load the tabs.
 */
require_once( itelic::$dir . 'lib/admin/tab/load.php' );

/**
 * Load the licenses.
 */
require_once( itelic::$dir . 'lib/admin/licenses/load.php' );