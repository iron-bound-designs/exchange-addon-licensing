<?php
/**
 * Main Plugin Hooks
 *
 * @author    Iron Bound Designs
 * @since     1.0
 * @license   AGPL
 * @copyright Iron Bound Designs, 2015.
 */

namespace ITELIC;

use ITELIC\Purchase_Requirement\Renew_Key;

/**
 * When a new transaction is created, generate necessary license keys if
 * applicable.
 *
 * @internal
 *
 * @since 1.0
 *
 * @param int $transaction_id
 */
function on_add_transaction_generate_license_keys( $transaction_id ) {

	try {
		generate_keys_for_transaction( it_exchange_get_transaction( $transaction_id ) );
	}
	catch ( \Exception $e ) {
		it_exchange_add_message( 'error', $e->getMessage() );
	}
}

add_action( 'it_exchange_add_transaction_success', 'ITELIC\on_add_transaction_generate_license_keys' );

/**
 * Register our template paths
 *
 * @internal
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
 * @internal
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
================== Renewals ===================
----------------------------------------------- */

/**
 * Automatically set the licenses expiry status when their expiry date has
 * passed.
 *
 * @internal
 *
 * @since 1.0
 */
function auto_expire_licenses() {

	$keys = itelic_get_keys( array(
		'expires' => array(
			'before' => make_date_time()->format( 'Y-m-d H:i:s' )
		)
	) );

	$now = new \DateTime();

	foreach ( $keys as $key ) {

		if ( $key->get_expires() && $key->get_expires() < $now ) {
			$key->expire();
		}
	}
}

add_action( 'it_exchange_itelic_daily_schedule', 'ITELIC\auto_expire_licenses' );

/**
 * Register the renewal purchase requirement with Exchange.
 *
 * @internal
 *
 * @since 1.0
 */
function register_renewal_purchase_req() {

	$purchase_req = new Renew_Key();
	$purchase_req->register();
}

add_action( 'init', 'ITELIC\register_renewal_purchase_req', 0 );

/**
 * When a renewal purchase is made, renew the renewed key.
 *
 * @internal
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
				try {
					$key->renew( $transaction );
				}
				catch ( \UnexpectedValueException $e ) {
					it_exchange_add_message( 'error', $e->getMessage() );
				}
			}
		}
	}
}

add_action( 'it_exchange_add_transaction_success', 'ITELIC\renew_key_on_renewal_purchase' );

/**
 * When a transaction's expiration is updated, renew the key.
 *
 * @internal
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
 * @internal
 *
 * @since 1.0
 *
 * @param Release $release
 */
function archive_old_releases_on_new_activation( Release $release ) {

	// we are paginating the number of releases we want to keep, and start on page 2 to get all that don't match
	// we don't need to calculate the number of total rows because we don't need the pagination, just the limit
	$releases = itelic_get_releases( array(
		'items_per_page' => itelic_keep_last_n_releases( $release->get_product() ),
		'page'           => 2,
		'status'         => Release::STATUS_ACTIVE,
		'product'        => $release->get_product()->ID,
		'order'          => array(
			'start_date' => 'DESC'
		),
	) );

	foreach ( $releases as $release ) {
		$release->archive();
	}
}

add_action( 'itelic_activate_release', 'ITELIC\archive_old_releases_on_new_activation' );

/**
 * When a release is activated, set the last updated value in the readme
 * product feature.
 *
 * @internal
 *
 * @since 1.0
 *
 * @param Release $release
 */
function set_last_updated_value_in_readme_on_activate( Release $release ) {

	if ( $release->get_type() != Release::TYPE_PRERELEASE && $release->get_product()->has_feature( 'licensing-readme' ) ) {
		$release->get_product()->update_feature( 'licensing-readme', array(
			'last_updated' => $release->get_start_date()->getTimestamp()
		) );
	}
}

add_action( 'itelic_activate_release', 'ITELIC\set_last_updated_value_in_readme_on_activate' );

/**
 * When a release is paused, set the last updated value to the previous release
 * in the readme product feature.
 *
 * @internal
 *
 * @since 1.0
 *
 * @param Release      $release
 * @param Release|null $prev_release
 */
function set_last_updated_value_in_readme_on_pause( Release $release, Release $prev_release = null ) {

	if ( $prev_release && $release->get_product()->has_feature( 'licensing-readme' ) ) {
		$release->get_product()->update_feature( 'licensing-readme', array(
			'last_updated' => $prev_release->get_start_date()->getTimestamp()
		) );
	}
}

add_action( 'itelic_pause_release', '\ITELIC\set_last_updated_value_in_readme_on_pause', 10, 2 );

/* --------------------------------------------
==================== Cache ====================
----------------------------------------------- */

/**
 * Clear a key's active count cache.
 *
 * @since 1.0
 *
 * @param Activation $activation
 */
function clear_key_active_count_and_total_activation_count_cache( Activation $activation ) {
	
	wp_cache_delete( $activation->get_key()->get_key(), 'itelic-key-active-count' );

	$releases = itelic_get_releases( array(
		'product' => $activation->get_key()->get_product()->ID,
		'status'  => array( Release::STATUS_ACTIVE, Release::STATUS_PAUSED )
	) );

	foreach ( $releases as $release ) {
		wp_cache_delete( $release->get_pk(), 'itelic-release-active-activations' );
	}
}

add_action( 'itelic_create_activation', 'ITELIC\clear_key_active_count_and_total_activation_count_cache' );
add_action( 'itelic_deactivate_activation', 'ITELIC\clear_key_active_count_and_total_activation_count_cache' );
add_action( 'itelic_reactivate_activation', 'ITELIC\clear_key_active_count_and_total_activation_count_cache' );
add_action( 'itelic_delete_activation', 'ITELIC\clear_key_active_count_and_total_activation_count_cache' );

/**
 * Clear our cache of the key status counts.
 *
 * @since 1.0
 *
 * @see   \ITELIC\count_keys()
 */
function clear_key_status_count_cache() {

	wp_cache_delete( 'itelic-key-counts' );
}

add_action( 'itelic_create_key', 'ITELIC\clear_key_status_count_cache' );
add_action( 'itelic_delete_key', 'ITELIC\clear_key_status_count_cache' );
add_action( 'itelic_transition_key_status', 'ITELIC\clear_key_status_count_cache' );

/**
 * Clear our cache of the release status counts.
 *
 * @since 1.0
 *
 * @see   \ITELIC\count_releases()
 */
function clear_release_status_count_cache() {

	wp_cache_delete( 'itelic-release-counts' );
}

add_action( 'itelic_create_release', 'ITELIC\clear_release_status_count_cache' );
add_action( 'itelic_delete_release', 'ITELIC\clear_release_status_count_cache' );
add_action( 'itelic_transition_release_status', 'ITELIC\clear_release_status_count_cache' );


/* --------------------------------------------
=============== Exchange Hooks ================
----------------------------------------------- */

/**
 * When a transaction is refunded, disable the key.
 *
 * @internal
 *
 * @since 1.0
 *
 * @param \IT_Exchange_Transaction $transaction
 * @param float                    $amount
 */
function disable_key_on_refund( $transaction, $amount ) {

	if ( ! $transaction ) {
		return;
	}

	if ( $transaction->get_total() != 0 ) {
		return;
	}

	$keys = itelic_get_keys( array(
		'transaction' => $transaction->ID
	) );

	foreach ( $keys as $key ) {
		$key->set_status( Key::DISABLED );
	}
}

add_action( 'it_exchange_add_refund_to_transaction', '\ITELIC\disable_key_on_refund', 10, 2 );

/**
 * Disable a key when a transaction's status changes.
 *
 * @internal
 *
 * @since 1.0
 *
 * @param \IT_Exchange_Transaction $txn
 * @param string                   $old_status
 * @param bool                     $old_status_cleared
 */
function disable_key_on_transaction_status_change( $txn, $old_status, $old_status_cleared ) {

	$txn = it_exchange_get_transaction( $txn );

	if ( $old_status_cleared && ! it_exchange_transaction_is_cleared_for_delivery( $txn ) ) {

		$keys = itelic_get_keys( array(
			'transaction' => $txn->ID
		) );

		foreach ( $keys as $key ) {
			$key->set_status( Key::DISABLED );
		}
	}
}

add_action( 'it_exchange_update_transaction_status', '\ITELIC\disable_key_on_transaction_status_change', 10, 3 );

/**
 * Delete license keys when the generating transaction is deleted.
 *
 * @since 1.0
 *
 * @param int $post_id
 */
function delete_keys_when_transaction_is_deleted( $post_id ) {

	if ( get_post_type( $post_id ) != 'it_exchange_tran' ) {
		return;
	}

	$keys = itelic_get_keys( array(
		'transaction' => $post_id
	) );

	foreach ( $keys as $key ) {
		$key->delete();
	}
}

add_action( 'before_delete_post', '\ITELIC\delete_keys_when_transaction_is_deleted' );

/**
 * Delete license keys when the product is deleted.
 *
 * @since 1.0
 *
 * @param int $post_id
 */
function delete_keys_and_releases_when_product_is_deleted( $post_id ) {

	if ( get_post_type( $post_id ) != 'it_exchange_prod' ) {
		return;
	}

	$keys = itelic_get_keys( array(
		'product' => $post_id
	) );

	foreach ( $keys as $key ) {
		$key->delete();
	}

	$releases = itelic_get_releases( array(
		'product' => $post_id
	) );

	foreach ( $releases as $release ) {
		$release->delete();
	}
}

add_action( 'before_delete_post', '\ITELIC\delete_keys_and_releases_when_product_is_deleted' );

/**
 * Delete a customer's license keys when the customer is deleted.
 *
 * @since 1.0
 *
 * @param int $id
 */
function delete_keys_when_customer_deleted( $id ) {

	foreach ( itelic_get_keys( array( 'customer' => $id ) ) as $key ) {
		$key->delete();
	}
}

add_action( 'delete_user', 'ITELIC\delete_keys_when_customer_deleted' );

/* --------------------------------------------
============= Display License Key =============
----------------------------------------------- */

/**
 * Display the license key for a transaction product on the payments detail
 * page.
 *
 * @internal
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
 * @internal
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
 * @internal
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
 * @internal
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
 * @internal
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
 * @internal
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

/**
 * On the downloads page, exclude transactions that are renewing a key.
 *
 * @since 1.0
 *
 * @param \IT_Exchange_Transaction[] $transactions
 * @param \IT_Exchange_Customer      $customer
 *
 * @return \IT_Exchange_Transaction[]
 */
function exclude_renewals_from_downloads_page( $transactions, $customer ) {

	if ( it_exchange_is_page( 'downloads' ) ) {
		$transactions = array_filter( $transactions, function ( \IT_Exchange_Transaction $transaction ) {

			foreach ( $transaction->get_products() as $product ) {
				if ( ! empty( $product['renewed_key'] ) ) {
					return false;
				}
			}

			return true;
		} );
	}

	return $transactions;
}

add_filter( 'it_exchange_get_customer_transactions', 'ITELIC\exclude_renewals_from_downloads_page', 10, 2 );

/* --------------------------------------------
============== Confirmation Email =============
----------------------------------------------- */

/**
 * Register custom email notification shortcodes.
 *
 * @internal
 *
 * @since 1.0
 *
 * @param array $shortcodes
 *
 * @return array
 */
function register_email_notification_shortcodes( $shortcodes ) {

	$shortcodes['license_keys'] = 'ITELIC\render_license_keys_email_notification_shortcode';

	return $shortcodes;
}

add_filter( 'it_exchange_email_notification_shortcode_functions', 'ITELIC\register_email_notification_shortcodes' );

/**
 * Render the license keys email notification shortcode tag.
 *
 * @internal
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
 * @internal
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
 * @internal
 *
 * @since 1.0
 */
function register_account_licenses_page() {

	// Profile
	$options = array(
		'slug'            => 'licenses',
		'name'            => __( 'Licenses', Plugin::SLUG ),
		'rewrite - rules' => array( 128, 'ITELIC\page_rewrites' ),
		'url'             => 'it_exchange_get_core_page_urls',
		'settings - name' => __( 'Licenses Page', Plugin::SLUG ),
		'tip'             => __( 'A list of a customer\'s licenses.', Plugin::SLUG ),
		'type'            => 'exchange',
		'menu'            => true,
		'optional'        => true,
	);

	it_exchange_register_page( 'licenses', $options );
}

add_action( 'init', 'ITELIC\register_account_licenses_page' );

/**
 * Protect licenses page, and register as a profile page.
 *
 * @internal
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
 * @internal
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
 * @internal
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