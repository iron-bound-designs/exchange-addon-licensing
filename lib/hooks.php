<?php
/**
 * Main Plugin Hooks
 *
 * @author Iron Bound Designs
 * @since  1.0
 */

namespace ITELIC;

use IronBound\WP_Notifications\Queue\Manager as Queue_Manager;
use IronBound\WP_Notifications\Queue\Storage\Options;
use IronBound\WP_Notifications\Queue\WP_Cron;
use ITELIC\Purchase_Requirement\Base as Purchase_Requirement;
use ITELIC\Purchase_Requirement\Renew_Key;
use ITELIC_API\Query\Keys;
use ITELIC_API\Query\Releases;

/**
 * When a new transaction is created, generate necessary license keys if applicable.
 *
 * @since 1.0
 *
 * @param int $transaction_id
 */
function on_add_transaction_generate_license_keys( $transaction_id ) {
	generate_keys_for_transaction( it_exchange_get_transaction( $transaction_id ) );
}

add_action( 'it_exchange_add_transaction_success', 'ITELIC\on_add_transaction_generate_license_keys' );

/**
 * Register our template paths
 *
 * @since 1.0
 *
 * @param array $paths existing template paths
 *
 * @return array
 */
function add_template_paths( $paths = array() ) {
	$paths[] = Plugin::$dir . "lib/templates";

	return $paths;
}

add_filter( 'it_exchange_possible_template_paths', 'ITELIC\add_template_paths' );

/**
 * Enqueue scripts and styles.
 *
 * @since 1.0
 */
function scripts_and_styles() {

	if ( it_exchange_is_page( 'product' ) || it_exchange_in_superwidget() ) {
		wp_enqueue_script( 'itelic-super-widget' );
		wp_localize_script( 'itelic-super-widget', 'ITELIC', array(
			'ajax' => admin_url( 'admin-ajax.php' )
		) );
	}

	if ( it_exchange_is_page( 'checkout' ) ) {
		wp_enqueue_style( 'itelic-checkout' );
	}

	if ( it_exchange_is_page( 'licenses' ) ) {
		wp_enqueue_style( 'itelic-account-licenses' );
		wp_enqueue_script( 'itelic-account-licenses' );
		wp_localize_script( 'itelic-account-licenses', 'ITELIC', array(
			'ajax'              => admin_url( 'admin-ajax.php' ),
			'location_required' => __( "Location Required", Plugin::SLUG )
		) );
	}
}

add_action( 'wp_enqueue_scripts', 'ITELIC\scripts_and_styles' );

/* --------------------------------------------
================= Notifications ===============
----------------------------------------------- */

/**
 * Setup the notifications package.
 *
 * @since 1.0
 */
function setup_notifications() {

	Queue_Manager::register( 'itelic-wp-cron', new WP_Cron( new Options( 'itelic' ) ) );
}

add_action( 'init', 'ITELIC\setup_notifications' );

/* --------------------------------------------
================== Renewals ===================
----------------------------------------------- */

/**
 * Automatically set the licenses expiry status when their expiry date has passed.
 *
 * @since 1.0
 */
function auto_expire_licenses() {

	$query = new Keys( array(
		'expires' => array(
			'before' => current_time( 'mysql' )
		)
	) );

	/**
	 * @var Key[] $keys
	 */
	$keys = $query->get_results();

	foreach ( $keys as $key ) {
		$key->expire();
	}
}

add_action( 'it_exchange_itelic_daily_schedule', 'ITELIC\auto_expire_licenses' );

$purchase_req = new Renew_Key( 'itelic-renew-product', array(
	'priority'               => 2,
	'sw-template-part'       => 'itelic-renew-product',
	'checkout-template-part' => 'itelic-renew-product',
	'notification'           => __( "You need to select a license key to renew.", Plugin::SLUG ),
), function ( Purchase_Requirement $req ) {

	$product_id = get_current_product_id();
	$session    = $req->get_cache_data();

	// we are on checkout
	if ( ! $product_id ) {
		foreach ( $session as $product => $key ) {

			// so all products marked for renewal must have a key
			if ( $key === null ) {
				return false;
			}
		}

		return true;
	}

	// we are on a product page

	// if there is no record of this product in the session then the PR has been met
	if ( ! array_key_exists( "p$product_id", $session ) ) {
		return true;
	}

	return $session["p$product_id"] !== null;
} );

$purchase_req->register();

/**
 * When a renewal purchase is made, renew the renewed key.
 *
 * @since 1.0
 *
 * @param int $transaction_id
 */
function renew_key_on_renewal_purchase( $transaction_id ) {

	$transaction = it_exchange_get_transaction( $transaction_id );

	foreach ( $transaction->get_products() as $product ) {

		if ( isset( $product['renewed_key'] ) && $product['renewed_key'] ) {
			$key = itelic_get_key( $product['renewed_key'] );

			if ( $key ) {
				$key->renew( $transaction );
			}
		}
	}
}

add_action( 'it_exchange_add_transaction_success', 'ITELIC\renew_key_on_renewal_purchase' );

/**
 * When a transaction's expiration is updated, renew the key.
 *
 * @since 1.0
 *
 * @param int    $mid
 * @param int    $object_id
 * @param string $meta_key
 * @param mixed  $_meta_value
 */
function renew_key_on_update_expirations( $mid, $object_id, $meta_key, $_meta_value ) {

	if ( false === strpos( $meta_key, '_it_exchange_transaction_subscription_expires_' ) ) {
		return;
	}

	if ( false === ( $transaction = it_exchange_get_transaction( $object_id ) ) ) {
		return;
	}

	foreach ( $transaction->get_products() as $product ) {
		// if this was a renewal purchase, then we are already going to renew they key. No need to do it twice.
		if ( isset( $product['renewed_key'] ) && $product['renewed_key'] ) {

			return;
		}
	}

	$product_id = (int) str_replace( '_it_exchange_transaction_subscription_expires_', '', $meta_key );

	$data = itelic_get_keys( array(
		'transaction'         => $object_id,
		'product'             => $product_id,
		'items_per_page'      => 1,
		'sql_calc_found_rows' => false
	) );

	if ( empty( $data ) ) {
		return;
	}

	$key = reset( $data );

	$args = array(
		'post_parent' => $key->get_transaction()->ID,
		'post_type'   => 'it_exchange_tran',
		'orderby'     => 'date',
		'order'       => 'DESC'
	);

	$child_transactions = it_exchange_get_transactions( $args );
	$child_transaction  = reset( $child_transactions );

	$key->renew( $child_transaction );
}

add_action( 'updated_post_meta', 'ITELIC\renew_key_on_update_expirations', 10, 4 );

/* --------------------------------------------
================== Releases ===================
----------------------------------------------- */

/**
 * When a new release is activated, automatically archive the old releases.
 *
 * @since 1.0
 *
 * @param Release $release
 */
function archive_old_releases_on_new_activation( Release $release ) {

	// we are paginating the number of releases we want to keep, and start on page 2 to get all that don't match
	// we don't need to calculate the number of total rows because we don't need the pagination, just the limit
	$query = new Releases( array(
		'items_per_page'      => itelic_keep_last_n_releases( $release->get_product() ),
		'page'                => 2,
		'status'              => Release::STATUS_ACTIVE,
		'sql_calc_found_rows' => false,
		'order' => array(
			'start_date' => 'DESC'
		)
	) );

	/**
	 * @var Release $release
	 */
	foreach ( $query->get_results() as $release ) {
		$release->archive();
	}
}

add_action( 'itelic_activate_release', 'ITELIC\archive_old_releases_on_new_activation' );

/* --------------------------------------------
============= Display License Key =============
----------------------------------------------- */

/**
 * Display the license key for a transaction product on the payments detail page.
 *
 * @since 1.0
 *
 * @param \WP_Post $post
 * @param array    $transaction_product
 */
function display_keys_on_transaction_detail( $post, $transaction_product ) {
	$key = get_key_for_transaction_product( $post->ID, $transaction_product['product_id'] );

	if ( $key === null ) {
		return;
	}

	$link = '<a href="' . itelic_get_admin_edit_key_link( $key->get_key() ) . '">' . $key->get_key() . '</a>';

	echo "<h4 class='product-license-key'>";
	printf( __( "License Key: %s", Plugin::SLUG ), $link );
	echo "</h4>";
}

add_action( 'it_exchange_transaction_details_begin_product_details', 'ITELIC\display_keys_on_transaction_detail', 10, 2 );

/**
 * Display renewal information on the confirmation page.
 *
 * @since 1.0
 */
function display_license_key_on_confirmation_page() {
	$transaction = $GLOBALS['it_exchange']['transaction'];
	$product     = $GLOBALS['it_exchange']['transaction_product'];

	$key = get_key_for_transaction_product( $transaction->ID, $product['product_id'] );

	if ( ! $key ) {
		return;
	}

	echo "<p>";
	printf( __( "License Key: %s", Plugin::SLUG ), $key->get_key() );
	echo "</p>";
}

add_action( 'it_exchange_content_confirmation_after_product_attibutes', 'ITELIC\display_license_key_on_confirmation_page' );
add_action( 'it_exchange_content_purchases_end_product_info_loop', 'ITELIC\display_license_key_on_confirmation_page' );

/* --------------------------------------------
============= Display Renewal Info ============
----------------------------------------------- */

/**
 * Display renewal information on the confirmation page.
 *
 * @since 1.0
 */
function display_renewal_on_confirmation_page() {
	$product = $GLOBALS['it_exchange']['transaction_product'];

	if ( ! isset( $product['renewed_key'] ) || ! $product['renewed_key'] ) {
		return;
	}

	echo "<p>";
	printf( __( "Renewed Key: %s", Plugin::SLUG ), $product['renewed_key'] );
	echo "</p>";
}

add_action( 'it_exchange_content_confirmation_after_product_attibutes', 'ITELIC\display_renewal_on_confirmation_page' );
add_action( 'it_exchange_content_purchases_end_product_info_loop', 'ITELIC\display_renewal_on_confirmation_page' );

/**
 * Add the renewal info to the payments screen.
 *
 * @since 1.0
 *
 * @param \IT_Exchange_Transaction $transaction
 * @param array                    $product
 */
function add_renewal_info_to_payments_screen( $transaction, $product ) {

	if ( ! isset( $product['renewed_key'] ) || ! $product['renewed_key'] ) {
		return;
	}

	?>
	<div class="key-renewal">
		<strong><?php printf( __( "Renewal – %s" ), $product['renewed_key'] ); ?></strong>
	</div>

	<?php

}

add_action( 'it_exchange_transaction_details_begin_product_details', 'ITELIC\add_renewal_info_to_payments_screen', 10, 2 );

/**
 * Add renewal info to the cart description for a product.
 *
 * @since 1.0
 *
 * @param string $description
 * @param array  $product
 *
 * @return string
 */
function add_renewal_info_to_cart_description_for_product( $description, $product ) {

	if ( isset( $product['renewed_key'] ) && $product['renewed_key'] ) {
		$description .= " " . __( "Renewal", Plugin::SLUG );
	}

	return $description;
}

add_filter( 'it_exchange_get_cart_description_for_product', 'ITELIC\add_renewal_info_to_cart_description_for_product', 10, 2 );

/**
 * Add trial info to the product title transaction feature.
 *
 * @since 1.0
 *
 * @param string $value
 * @param array  $product
 * @param string $feature
 *
 * @return string
 */
function add_renewal_info_to_product_title_transaction_feature( $value, $product, $feature ) {

	if ( isset( $_GET['post'] ) && it_exchange_get_transaction( $_GET['post'] ) ) {
		return $value;
	}

	if ( it_exchange_is_page() ) {
		return $value;
	}

	if ( $feature != 'product_name' ) {
		return $value;
	}

	if ( isset( $product['renewed_key'] ) && $product['renewed_key'] ) {
		$product = itelic_get_product( $product['product_id'] );

		if ( $product ) {
			$value .= __( " – Renewal", Plugin::SLUG );
		}
	}

	return $value;
}

add_filter( 'it_exchange_get_transaction_product_feature', 'ITELIC\add_renewal_info_to_product_title_transaction_feature', 10, 3 );


/* --------------------------------------------
============== Confirmation Email =============
----------------------------------------------- */

/**
 * Register custom email notification shortcodes.
 *
 * @since 1.0
 *
 * @param array $shortcodes
 *
 * @return array
 */
function register_email_notification_shortcodes( $shortcodes ) {

	$shortcodes['license_keys'] = 'itelic_render_license_keys_email_notification_shortcode';

	return $shortcodes;
}

add_filter( 'it_exchange_email_notification_shortcode_functions', 'ITELIC\register_email_notification_shortcodes' );

/**
 * Render the license keys email notification shortcode tag.
 *
 * @since 1.0
 *
 * @param \IT_Exchange_Email_Notifications $email_notifications
 *
 * @return string
 */
function render_license_keys_email_notification_shortcode( \IT_Exchange_Email_Notifications $email_notifications ) {

	$transaction = it_exchange_get_transaction( $email_notifications->transaction_id );

	$out = '';

	foreach ( $transaction->get_products() as $product ) {
		$product = itelic_get_product( $product['product_id'] );
		$key     = get_key_for_transaction_product( $transaction->ID, $product->ID );

		if ( $key ) {
			$out .= "<li>" . $product->post_title . ": " . $key->get_key() . "</li>";
		}
	}

	if ( $out ) {
		$out = "<h4>" . __( "License Keys", Plugin::SLUG ) . "<h4/>" . "<ul>$out</ul>";
	}

	return $out;
}

/**
 * Display our custom email notification shortcodes on the settings page.
 *
 * @since 1.0
 */
function display_email_notification_shortcodes() {
	echo "<li>license_keys - " . __( "Display product license keys, if any.", Plugin::SLUG ) . "</li>";
}

add_action( 'it_exchange_email_template_tags_list', 'ITELIC\display_email_notification_shortcodes' );

/* --------------------------------------------
================ Licenses Page ================
----------------------------------------------- */

/**
 * Register the account/classes page.
 *
 * @since 1.0
 */
function register_account_licenses_page() {

	// Profile
	$options = array(
		'slug'          => 'licenses',
		'name'          => __( 'Licenses', Plugin::SLUG ),
		'rewrite-rules' => array( 128, 'ITELIC\page_rewrites' ),
		'url'           => 'it_exchange_get_core_page_urls',
		'settings-name' => __( 'Licenses Page', Plugin::SLUG ),
		'tip'           => __( 'A list of a customer\'s licenses.', Plugin::SLUG ),
		'type'          => 'exchange',
		'menu'          => true,
		'optional'      => true,
	);

	it_exchange_register_page( 'licenses', $options );
}

add_action( 'init', 'ITELIC\register_account_licenses_page' );

/**
 * Protect licenses page, and register as a profile page.
 *
 * @since 1.0
 *
 * @param array $pages
 *
 * @return array
 */
function register_protect_licenses_page( $pages ) {

	$pages[] = 'licenses';

	return $pages;
}

add_filter( 'it_exchange_profile_pages', 'ITELIC\register_protect_licenses_page' );
add_filter( 'it_exchange_pages_to_protect', 'ITELIC\register_protect_licenses_page' );
add_filter( 'it_exchange_account_based_pages', 'ITELIC\register_protect_licenses_page' );
add_filter( 'it_exchange_customer_menu_pages', 'ITELIC\register_protect_licenses_page' );

/**
 * AJAX handler for deactivating a location.
 *
 * @since 1.0
 */
function account_licenses_deactivate_location() {

	if ( ! isset( $_POST['id'] ) || ! isset( $_POST['nonce'] ) ) {
		wp_send_json_error( array(
			'message' => __( "Invalid request format.", Plugin::SLUG )
		) );
	}

	$id    = absint( $_POST['id'] );
	$nonce = $_POST['nonce'];

	if ( ! wp_verify_nonce( $nonce, "itelic-deactivate-$id" ) ) {
		wp_send_json_error( array(
			'message' => __( "Request expired. Please refresh and try again.", Plugin::SLUG )
		) );
	}

	try {
		$record = itelic_get_activation( $id );
	}
	catch ( \Exception $e ) {
		wp_send_json_error( array(
			'message' => __( "Invalid install location.", Plugin::SLUG )
		) );

		die();
	}

	if ( ! current_user_can( 'edit_user', $record->get_key()->get_customer()->wp_user->ID ) ) {
		wp_send_json_error( array(
			'message' => __( "You don't have permission to do this.", Plugin::SLUG )
		) );
	}

	$record->deactivate();

	wp_send_json_success();
}

add_action( 'wp_ajax_itelic_account_licenses_deactivate_location', 'ITELIC\account_licenses_deactivate_location' );

/**
 * AJAX handler for remote activating a location.
 *
 * @since 1.0
 */
function account_licenses_activate() {

	if ( ! isset( $_POST['location'] ) || ! isset( $_POST['nonce'] ) || ! isset( $_POST['key'] ) ) {
		wp_send_json_error( array(
			'message' => __( "Invalid request format.", Plugin::SLUG )
		) );
	}

	$location = sanitize_text_field( $_POST['location'] );
	$key      = $_POST['key'];
	$nonce    = $_POST['nonce'];

	if ( ! wp_verify_nonce( $nonce, "itelic-remote-activate-$key" ) ) {
		wp_send_json_error( array(
			'message' => __( "Request expired. Please refresh and try again.", Plugin::SLUG )
		) );
	}

	try {
		$key = itelic_get_key( $key );
	}
	catch ( \Exception $e ) {
		wp_send_json_error( array(
			'message' => __( "Invalid license key.", Plugin::SLUG )
		) );

		die();
	}

	if ( ! current_user_can( 'edit_user', $key->get_customer()->wp_user->ID ) ) {
		wp_send_json_error( array(
			'message' => __( "You don't have permission to do this.", Plugin::SLUG )
		) );
	}

	try {
		itelic_activate_license_key( $key, $location );
	}
	catch ( \Exception $e ) {
		wp_send_json_error( array(
			'message' => $e->getMessage()
		) );
	}

	wp_send_json_success();
}

add_action( 'wp_ajax_itelic_account_licenses_activate', 'ITELIC\account_licenses_activate' );