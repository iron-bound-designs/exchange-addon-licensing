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
 * Register purchase requirements.
 *
 * @since 1.0
 */
function itelic_register_purchase_requirements() {

	$trial_properties = array(
		'priority'               => 2,
		'requirement-met'        => 'itelic_purchase_requirement_renew_product',
		'sw-template-part'       => 'itelic-renew-product',
		'checkout-template-part' => 'itelic-renew-product',
		'notification'           => __( "Would you like to renew this product?", ITELIC::SLUG ),
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
 * Enqueue purchase requirement scripts.
 *
 * @since 1.0
 */
function itelic_enqueue_purchase_requirement_scripts() {

	if ( it_exchange_is_page( 'product' ) || it_exchange_in_superwidget() ) {
		wp_enqueue_script( 'itelic-super-widget' );
		wp_localize_script( 'itelic-super-widget', 'ITELIC', array(
			'ajax' => admin_url( 'admin-ajax.php' )
		) );
	}

	if ( it_exchange_is_page( 'checkout' ) ) {
		wp_enqueue_script( 'itelic-checkout' );
		wp_localize_script( 'itelic-checkout', 'ITELIC', array(
			'ajax' => admin_url( 'admin-ajax.php' )
		) );
	}
}

add_action( 'wp_enqueue_scripts', 'itelic_enqueue_purchase_requirement_scripts' );

/**
 * Process the AJAX call from the renew product purchase requirement.
 *
 * @since 1.0
 */
function itelic_renew_product_purchase_requirement() {

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
		$key = $key->get_key();
	} else {
		$key = false;
	}

	itelic_set_purchase_requirement_renewal_key( $key, $product->ID );

	wp_send_json_success();
}

add_action( 'wp_ajax_itelic_renew_product_purchase_requirement', 'itelic_renew_product_purchase_requirement' );

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

	$session = itelic_get_purchase_requirement_renew_product_session();

	if ( $session['renew'] == null || $session['renew'] == false || $session['product'] !== $product['product_id'] ) {
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

	$renewal = itelic_get_purchase_requirement_renew_product_session();

	if ( $renewal['renew'] !== null && $renewal['renew'] !== false && $product['product_id'] == $renewal['product'] ) {
		$products[ $key ]['renewed_key'] = $renewal['renew'];
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
	itelic_clear_purchase_requirement_renew_product_session();

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

	$renew = itelic_get_purchase_requirement_renew_product_session();

	if ( $renew['product'] == $cart_product_id ) {
		itelic_clear_purchase_requirement_renew_product_session();
	}
}

add_action( 'it_exchange_delete_cart_product', 'itelic_remove_renewal_info_on_cart_product_removal', 10, 2 );

/**
 * Remove the renewal info when a cart is emptied.
 *
 * @since 1.0
 */
function itelic_remove_renewal_info_on_cart_empty() {
	itelic_clear_purchase_requirement_renew_product_session();
}

add_action( 'it_exchange_empty_shopping_cart', 'itelic_remove_renewal_info_on_cart_empty' );


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
add_action( 'it_exchange_content_purchases_end_product_info_loop', 'itecls_add_trial_info_to_account_purchases_tab' );

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
	$session = itelic_get_purchase_requirement_renew_product_session();

	if ( isset( $session['product'] ) && $session['product'] == $product['product_id'] ) {
		$title .= __( " – Renewal", ITELIC::SLUG );
	}

	return $title;
}

add_filter( 'it_exchange_theme_api_cart-item_title', 'iteclic_modify_cart_item_title' );