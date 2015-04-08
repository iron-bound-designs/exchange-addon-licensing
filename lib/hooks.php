<?php
/**
 * Main Plugin Hooks
 *
 * @author Iron Bound Designs
 * @since  1.0
 */

/**
 * When a new transaction is created, generate necessary license keys if applicable.
 *
 * @since 1.0
 *
 * @param int $transaction_id
 */
function itelic_on_add_transaction_generate_license_keys( $transaction_id ) {
	itelic_generate_keys_for_transaction( it_exchange_get_transaction( $transaction_id ) );
}

add_action( 'it_exchange_add_transaction_success', 'itelic_on_add_transaction_generate_license_keys' );

/**
 * Register our template paths
 *
 * @since 1.0
 *
 * @param array $paths existing template paths
 *
 * @return array
 */
function itelic_add_template_paths( $paths = array() ) {
	$paths[] = ITELIC::$dir . "lib/templates";

	return $paths;
}

add_filter( 'it_exchange_possible_template_paths', 'itelic_add_template_paths' );

/**
 * Enqueue scripts and styles.
 *
 * @since 1.0
 */
function itelic_scripts_and_styles() {

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
			'location_required' => __( "Location Required", ITELIC::SLUG )
		) );
	}
}

add_action( 'wp_enqueue_scripts', 'itelic_scripts_and_styles' );


/* --------------------------------------------
================== Renewals ===================
----------------------------------------------- */

/**
 * Register purchase requirements.
 *
 * @since 1.0
 */
function itelic_register_purchase_requirements() {

	$trial_properties = array(
		'priority'               => 2,
		'requirement-met'        => 'itelic_purchase_requirement_renewal_met',
		'sw-template-part'       => 'itelic-renew-product',
		'checkout-template-part' => 'itelic-renew-product',
		'notification'           => __( "You need to select a license key to renew.", ITELIC::SLUG ),
	);

	it_exchange_register_purchase_requirement( 'itelic-renew-product', $trial_properties );
}

add_action( 'init', 'itelic_register_purchase_requirements' );

/**
 * Force the renew product SW state.
 *
 * @since 1.0
 *
 * @param $valid_states array
 *
 * @return array
 */
function itelic_force_sw_valid_states( $valid_states ) {
	$valid_states[] = 'itelic-renew-product';

	return $valid_states;
}

add_filter( 'it_exchange_super_widget_valid_states', 'itelic_force_sw_valid_states' );

/**
 * Add the renew button to the super widget.
 *
 * @since 1.0
 */
function itelic_add_renew_button_to_sw() {

	$product_id = itelic_get_current_product_id();

	if ( ! $product_id ) {
		return;
	}

	if ( ! it_exchange_product_has_feature( $product_id, 'licensing' ) || ! it_exchange_product_has_feature( $product_id, 'recurring-payments' ) ) {
		return;
	}

	if ( is_user_logged_in() ) {
		$keys = itelic_get_keys( array(
			'product'  => $product_id,
			'customer' => it_exchange_get_current_customer_id()
		) );

		if ( empty( $keys ) ) {
			return;
		}
	}
	?>

	<form method="POST" class="it-exchange-sw-purchase-options it-exchange-sw-itelic-renew">
		<input type="hidden" name="it-exchange-renew-product" value="<?php echo esc_attr( $product_id ); ?>">
		<?php wp_nonce_field( 'itelic-renew-' . $product_id ); ?>
		<input type="submit" value="<?php esc_attr_e( "Renew", ITELIC::SLUG ); ?>" class="itelic-renew-button" style="width:100%;">
	</form>

<?php
}

add_action( 'it_exchange_super_widget_product_end_purchase_options_element', 'itelic_add_renew_button_to_sw' );

/**
 * Enter the renewal process when the renew button is pressed on the super widget.
 *
 * @since 1.0
 */
function itelic_enter_renewal_process_sw() {

	if ( ! isset( $_GET['sw-product'] ) || ! isset( $_GET['_wpnonce'] ) ) {
		return;
	}

	$product = $_GET['sw-product'];

	if ( ! wp_verify_nonce( $_GET['_wpnonce'], "itelic-renew-$product" ) ) {
		it_exchange_add_message( 'error', __( "Something went wrong. Please refresh and try again.", ITELIC::SLUG ) );

		return;
	}

	it_exchange_add_product_to_shopping_cart( $product, 1 );

	$keys = itelic_get_keys( array(
		'product'  => $product,
		'customer' => it_exchange_get_current_customer_id()
	) );

	if ( count( $keys ) == 1 ) {
		$key = reset( $keys );
	} else {
		$key = null;
	}

	itelic_update_purchase_requirement_renewal_product( it_exchange_get_product( $product ), $key );

	die( 1 );
}

add_action( 'it_exchange_processing_super_widget_ajax_renew_key', 'itelic_enter_renewal_process_sw' );


/**
 * Process the AJAX call from the renew product purchase requirement.
 *
 * @since 1.0
 */
function itelic_process_purchase_requirement_renewal_ajax() {

	if ( ! isset( $_POST['nonce'] ) || ! isset( $_POST['key'] ) || ! isset( $_POST['product'] ) || ! isset( $_POST['renew'] ) ) {
		wp_send_json_error( array(
			'message' => __( "Invalid request format.", ITELIC::SLUG )
		) );
	}

	$nonce   = $_POST['nonce'];
	$key     = itelic_get_key( $_POST['key'] );
	$product = it_exchange_get_product( $_POST['product'] );
	$renew   = (bool) $_POST['renew'];

	if ( ! wp_verify_nonce( $nonce, 'itelic_renew_product_sw' ) ) {
		wp_send_json_error( array(
			'message' => __( "Sorry, this request has expired. Please refresh and try again.", ITELIC::SLUG )
		) );
	}

	if ( $key->get_product()->ID != $product->ID || it_exchange_get_current_customer_id() != $key->get_customer()->wp_user->ID ) {
		wp_send_json_error( array(
			'message' => __( "Invalid license key selected.", ITELIC::SLUG )
		) );
	}

	if ( $renew === true ) {
		itelic_update_purchase_requirement_renewal_product( $product, $key );
	} else {
		itelic_remove_purchase_requirement_renewal_product( $product );
	}

	wp_send_json_success();
}

add_action( 'wp_ajax_itelic_renew_product_purchase_requirement', 'itelic_process_purchase_requirement_renewal_ajax' );

/**
 * Process the renewal purchase requirement from the checkout screen.
 *
 * @since 1.0
 */
function itelic_process_purchase_requirement_renewal_checkout() {

	if ( ! isset( $_POST['itelic_renew_keys_checkout'] ) || ! isset( $_POST['_wpnonce'] ) || empty( $_POST['itelic_key'] ) ) {
		return;
	}

	if ( ! wp_verify_nonce( $_POST['_wpnonce'], 'itelic-renew-keys-checkout' ) ) {
		it_exchange_add_message( 'error', __( "Sorry this request has expired. Please refresh and try again.", ITELIC::SLUG ) );

		return;
	}

	$session = itelic_get_purchase_requirement_renewal_session();
	$keys    = $_POST['itelic_key'];

	foreach ( $session as $product => $key ) {

		if ( $key === null ) {
			if ( isset( $keys[ $product ] ) ) {
				$session[ $product ] = $keys[ $product ];
			}
		}
	}

	itelic_update_purchase_requirement_renewal_session( $session );
}

add_action( 'init', 'itelic_process_purchase_requirement_renewal_checkout' );

/**
 * Apply the renewal discount.
 *
 * @since 1.0
 *
 * @param string|float $db_base_price
 * @param array        $product
 * @param bool         $format
 *
 * @return string|float
 */
function itelic_apply_renewal_discount( $db_base_price, $product, $format ) {

	if ( ! it_exchange_product_has_feature( $product['product_id'], 'licensing' ) ) {
		return $db_base_price;
	}

	$session = itelic_get_purchase_requirement_renewal_session();

	if ( ! isset( $session[ $product['product_id'] ] ) || $session[ $product['product_id'] ] === null ) {
		return $db_base_price;
	}

	$discount = new ITELIC_Renewal_Discount( it_exchange_get_product( $product['product_id'] ) );

	return $discount->get_discount_price( $format );
}

add_filter( 'it_exchange_get_cart_product_base_price', 'itelic_apply_renewal_discount', 10, 3 );

/**
 * Save our renewal info with the transaction object.
 *
 * @since 1.0
 *
 * @param $products array
 * @param $key      string
 * @param $product  array
 *
 * @return object
 */
function itelic_save_renewal_info_to_transaction_object( $products, $key, $product ) {

	$session = itelic_get_purchase_requirement_renewal_session();

	foreach ( $session as $renewed_product => $license_key ) {
		if ( $renewed_product == $product['product_id'] ) {
			$products[ $key ]['renewed_key'] = $license_key;
		}
	}

	return $products;
}

add_filter( 'it_exchange_generate_transaction_object_products', 'itelic_save_renewal_info_to_transaction_object', 10, 3 );

/**
 * Clear our renewal info data
 * after the transaction object has been generated
 *
 * @since 1.0
 *
 * @param $transaction_object object
 *
 * @return object
 */
function itelic_clear_renewal_session_on_purchase( $transaction_object ) {
	itelic_clear_purchase_requirement_renewal_session();

	return $transaction_object;
}

add_filter( 'it_exchange_transaction_object', 'itelic_clear_renewal_session_on_purchase' );


/**
 * Remove the renewal info, when a key is removed from the cart.
 *
 * @since 1.0
 *
 * @param int   $cart_product_id
 * @param array $products
 */
function itelic_remove_renewal_info_on_cart_product_removal( $cart_product_id, $products ) {

	$renew = itelic_get_purchase_requirement_renewal_session();

	if ( $renew['product'] == $cart_product_id ) {
		itelic_clear_purchase_requirement_renewal_session();
	}
}

add_action( 'it_exchange_delete_cart_product', 'itelic_remove_renewal_info_on_cart_product_removal', 10, 2 );

/**
 * Remove the renewal info when a cart is emptied.
 *
 * @since 1.0
 */
function itelic_remove_renewal_info_on_cart_empty() {
	itelic_clear_purchase_requirement_renewal_session();
}

add_action( 'it_exchange_empty_shopping_cart', 'itelic_remove_renewal_info_on_cart_empty' );

/**
 * When a renewal purchase is made, renew the renewed key.
 *
 * @since 1.0
 *
 * @param int $transaction_id
 */
function itelic_renew_key_on_renewal_purchase( $transaction_id ) {

	$transaction = it_exchange_get_transaction( $transaction_id );

	foreach ( $transaction->get_products() as $product ) {

		if ( isset( $product['renewed_key'] ) && $product['renewed_key'] ) {
			$key = ITELIC_Key::with_key( $product['renewed_key'] );

			if ( $key ) {
				$key->renew( $transaction );
			}
		}
	}
}

add_action( 'it_exchange_add_transaction_success', 'itelic_renew_key_on_renewal_purchase' );

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
function itelic_renew_key_on_update_expirations( $mid, $object_id, $meta_key, $_meta_value ) {

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

	$data = ITELIC_DB_Keys::search( array(
		'transaction_id' => $object_id,
		'product'        => $product_id
	) );

	if ( empty( $data ) ) {
		return;
	}

	$key = itelic_get_key_from_data( $data[0] );

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

add_action( 'updated_post_meta', 'itelic_renew_key_on_update_expirations', 10, 4 );

/**
 * Listen for the auto renewal URL.
 *
 * @since 1.0
 */
function itelic_listen_for_auto_renewal_url() {

	if ( ! it_exchange_is_page( 'product' ) ) {
		return;
	}

	if ( ! isset( $_GET['renew_key'] ) ) {
		return;
	}

	if ( ! is_user_logged_in() ) {
		return;
	}

	$key = itelic_get_key( $_GET['renew_key'] );

	it_exchange_empty_shopping_cart();
	it_exchange_add_product_to_shopping_cart( $key->get_product()->ID );
	itelic_update_purchase_requirement_renewal_product( $key->get_product(), $key );
}

add_action( 'template_redirect', 'itelic_listen_for_auto_renewal_url', 0 );

/**
 * On login, look for the renewal key query param.
 *
 * @since 1.0
 *
 * @param string  $login
 * @param WP_User $user
 */
function itelic_add_renewal_key_to_session_on_login( $login, $user ) {

	if ( ! isset( $_GET['renew_key'] ) ) {
		return;
	}

	$key = itelic_get_key( $_GET['renew_key'] );

	it_exchange_empty_shopping_cart();
	it_exchange_add_product_to_shopping_cart( $key->get_product()->ID );
	itelic_update_purchase_requirement_renewal_product( $key->get_product(), $key );

	if ( ! it_exchange_is_page( 'product' ) || ! is_page( $key->get_product()->ID ) ) {
		wp_redirect( get_permalink( $key->get_product()->ID ) );
		exit;
	}
}

add_action( 'wp_login', 'itelic_add_renewal_key_to_session_on_login', 10, 2 );


/* --------------------------------------------
============= Display License Key =============
----------------------------------------------- */

/**
 * Display the license key for a transaction product on the payments detail page.
 *
 * @since 1.0
 *
 * @param WP_Post $post
 * @param array   $transaction_product
 */
function itelic_display_keys_on_transaction_detail( $post, $transaction_product ) {
	$key = itelic_get_key_for_transaction_product( $post->ID, $transaction_product['product_id'] );

	if ( $key === null ) {
		return;
	}

	echo "<h4 class='product-license-key'>";
	printf( __( "License Key: %s", ITELIC::SLUG ), $key->get_key() );
	echo "</h4>";
}

add_action( 'it_exchange_transaction_details_begin_product_details', 'itelic_display_keys_on_transaction_detail', 10, 2 );

/**
 * Display renewal information on the confirmation page.
 *
 * @since 1.0
 */
function itelic_display_license_key_on_confirmation_page() {
	$transaction = $GLOBALS['it_exchange']['transaction'];
	$product     = $GLOBALS['it_exchange']['transaction_product'];

	$key = itelic_get_key_for_transaction_product( $transaction->ID, $product['product_id'] );

	if ( ! $key ) {
		return;
	}

	echo "<p>";
	printf( __( "License Key: %s", ITELIC::SLUG ), $key->get_key() );
	echo "</p>";
}

add_action( 'it_exchange_content_confirmation_after_product_attibutes', 'itelic_display_license_key_on_confirmation_page' );
add_action( 'it_exchange_content_purchases_end_product_info_loop', 'itelic_display_license_key_on_confirmation_page' );

/* --------------------------------------------
============= Display Renewal Info ============
----------------------------------------------- */

/**
 * Display renewal information on the confirmation page.
 *
 * @since 1.0
 */
function itelic_display_renewal_on_confirmation_page() {
	$product = $GLOBALS['it_exchange']['transaction_product'];

	if ( ! isset( $product['renewed_key'] ) || ! $product['renewed_key'] ) {
		return;
	}

	echo "<p>";
	printf( __( "Renewed Key: %s", ITELIC::SLUG ), $product['renewed_key'] );
	echo "</p>";
}

add_action( 'it_exchange_content_confirmation_after_product_attibutes', 'itelic_display_renewal_on_confirmation_page' );
add_action( 'it_exchange_content_purchases_end_product_info_loop', 'itelic_display_renewal_on_confirmation_page' );

/**
 * Add the renewal info to the payments screen.
 *
 * @since 1.0
 *
 * @param IT_Exchange_Transaction $transaction
 * @param array                   $product
 */
function itelic_add_renewal_info_to_payments_screen( $transaction, $product ) {

	if ( ! isset( $product['renewed_key'] ) || ! $product['renewed_key'] ) {
		return;
	}

	?>
	<div class="key-renewal">
		<strong><?php printf( __( "Renewal – %s" ), $product['renewed_key'] ); ?></strong>
	</div>

<?php

}

add_action( 'it_exchange_transaction_details_begin_product_details', 'itelic_add_renewal_info_to_payments_screen', 10, 2 );

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
function itelic_add_renewal_info_to_cart_description_for_product( $description, $product ) {

	if ( isset( $product['renewed_key'] ) && $product['renewed_key'] ) {
		$description .= " " . __( "Renewal", ITELIC::SLUG );
	}

	return $description;
}

add_filter( 'it_exchange_get_cart_description_for_product', 'itelic_add_renewal_info_to_cart_description_for_product', 10, 2 );

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
function itelic_add_renewal_info_to_product_title_transaction_feature( $value, $product, $feature ) {

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
		$product = it_exchange_get_product( $product['product_id'] );

		if ( $product ) {
			$value .= __( " – Renewal", ITELIC::SLUG );
		}
	}

	return $value;
}

add_filter( 'it_exchange_get_transaction_product_feature', 'itelic_add_renewal_info_to_product_title_transaction_feature', 10, 3 );

/**
 * Modify the cart item title to specify that it is a renewal.
 *
 * @since 1.0
 *
 * @param string $title
 *
 * @return string
 */
function iteclic_modify_cart_item_title( $title ) {
	$product = $GLOBALS['it_exchange']['cart-item'];
	$session = itelic_get_purchase_requirement_renewal_session();

	if ( isset( $session['product'] ) && $session['product'] == $product['product_id'] ) {
		$title .= __( " – Renewal", ITELIC::SLUG );
	}

	return $title;
}

add_filter( 'it_exchange_theme_api_cart-item_title', 'iteclic_modify_cart_item_title' );

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
function itelic_register_email_notification_shortcodes( $shortcodes ) {

	$shortcodes['license_keys'] = 'itelic_render_license_keys_email_notification_shortcode';

	return $shortcodes;
}

add_filter( 'it_exchange_email_notification_shortcode_functions', 'itelic_register_email_notification_shortcodes' );

/**
 * Render the license keys email notification shortcode tag.
 *
 * @since 1.0
 *
 * @param IT_Exchange_Email_Notifications $email_notifications
 *
 * @return string
 */
function itelic_render_license_keys_email_notification_shortcode( IT_Exchange_Email_Notifications $email_notifications ) {

	$transaction = it_exchange_get_transaction( $email_notifications->transaction_id );

	$out = '';

	foreach ( $transaction->get_products() as $product ) {
		$product = it_exchange_get_product( $product['product_id'] );
		$key     = itelic_get_key_for_transaction_product( $transaction->ID, $product->ID );

		if ( $key ) {
			$out .= "<li>" . $product->post_title . ": " . $key->get_key() . "</li>";
		}
	}

	if ( $out ) {
		$out = "<h4>" . __( "License Keys", ITELIC::SLUG ) . "<h4/>" . "<ul>$out</ul>";
	}

	return $out;
}

/**
 * Display our custom email notification shortcodes on the settings page.
 *
 * @since 1.0
 */
function itelic_display_email_notification_shortcodes() {
	echo "<li>license_keys - " . __( "Display product license keys, if any.", ITELIC::SLUG ) . "</li>";
}

add_action( 'it_exchange_email_template_tags_list', 'itelic_display_email_notification_shortcodes' );

/* --------------------------------------------
================ Licenses Page ================
----------------------------------------------- */

/**
 * Register the account/classes page.
 *
 * @since 1.0
 */
function itelic_register_account_licenses_page() {

	// Profile
	$options = array(
		'slug'          => 'licenses',
		'name'          => __( 'Licenses', ITELIC::SLUG ),
		'rewrite-rules' => array( 128, 'itelic_page_rewrites' ),
		'url'           => 'it_exchange_get_core_page_urls',
		'settings-name' => __( 'Licenses Page', ITELIC::SLUG ),
		'tip'           => __( 'A list of a customer\'s licenses.', ITELIC::SLUG ),
		'type'          => 'exchange',
		'menu'          => true,
		'optional'      => true,
	);

	it_exchange_register_page( 'licenses', $options );
}

add_action( 'init', 'itelic_register_account_licenses_page' );

/**
 * Protect licenses page, and register as a profile page.
 *
 * @since 1.0
 *
 * @param array $pages
 *
 * @return array
 */
function itelic_register_protect_licenses_page( $pages ) {

	$pages[] = 'licenses';

	return $pages;
}

add_filter( 'it_exchange_profile_pages', 'itelic_register_protect_licenses_page' );
add_filter( 'it_exchange_pages_to_protect', 'itelic_register_protect_licenses_page' );
add_filter( 'it_exchange_account_based_pages', 'itelic_register_protect_licenses_page' );
add_filter( 'it_exchange_customer_menu_pages', 'itelic_register_protect_licenses_page' );

/**
 * AJAX handler for deactivating a location.
 *
 * @since 1.0
 */
function itelic_account_licenses_deactivate_location() {

	if ( ! isset( $_POST['id'] ) || ! isset( $_POST['nonce'] ) ) {
		wp_send_json_error( array(
			'message' => __( "Invalid request format.", ITELIC::SLUG )
		) );
	}

	$id    = absint( $_POST['id'] );
	$nonce = $_POST['nonce'];

	if ( ! wp_verify_nonce( $nonce, "itelic-deactivate-$id" ) ) {
		wp_send_json_error( array(
			'message' => __( "Request expired. Please refresh and try again.", ITELIC::SLUG )
		) );
	}

	try {
		$record = ITELIC_Activation::with_id( $id );
	}
	catch ( Exception $e ) {
		wp_send_json_error( array(
			'message' => __( "Invalid install location.", ITELIC::SLUG )
		) );

		die();
	}

	if ( ! current_user_can( 'edit_user', $record->get_key()->get_customer()->wp_user->ID ) ) {
		wp_send_json_error( array(
			'message' => __( "You don't have permission to do this.", ITELIC::SLUG )
		) );
	}

	$record->deactivate();

	wp_send_json_success();
}

add_action( 'wp_ajax_itelic_account_licenses_deactivate_location', 'itelic_account_licenses_deactivate_location' );

/**
 * AJAX handler for remote activating a location.
 *
 * @since 1.0
 */
function itelic_account_licenses_activate() {

	if ( ! isset( $_POST['location'] ) || ! isset( $_POST['nonce'] ) || ! isset( $_POST['key'] ) ) {
		wp_send_json_error( array(
			'message' => __( "Invalid request format.", ITELIC::SLUG )
		) );
	}

	$location = sanitize_text_field( $_POST['location'] );
	$key      = $_POST['key'];
	$nonce    = $_POST['nonce'];

	if ( ! wp_verify_nonce( $nonce, "itelic-remote-activate-$key" ) ) {
		wp_send_json_error( array(
			'message' => __( "Request expired. Please refresh and try again.", ITELIC::SLUG )
		) );
	}

	try {
		$key = ITELIC_Key::with_key( $key );
	}
	catch ( Exception $e ) {
		wp_send_json_error( array(
			'message' => __( "Invalid license key.", ITELIC::SLUG )
		) );

		die();
	}

	if ( ! current_user_can( 'edit_user', $key->get_customer()->wp_user->ID ) ) {
		wp_send_json_error( array(
			'message' => __( "You don't have permission to do this.", ITELIC::SLUG )
		) );
	}

	try {
		telic_activate_license_key( $key, $location );
	}
	catch ( Exception $e ) {
		wp_send_json_error( array(
			'message' => $e->getMessage()
		) );
	}

	wp_send_json_success();
}

add_action( 'wp_ajax_itelic_account_licenses_activate', 'itelic_account_licenses_activate' );