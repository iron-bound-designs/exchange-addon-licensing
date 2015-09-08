<?php
/**
 * Main Plugin Functions
 *
 * @author Iron Bound Designs
 * @since  1.0
 */

namespace ITELIC;

use IronBound\WP_Notifications\Template\Listener;
use ITELIC\Key\Factory;
use ITELIC\API\Dispatch;

/**
 * Generate license keys for a transaction.
 *
 * @since 1.0
 *
 * @param \IT_Exchange_Transaction $transaction
 * @param string                   $status Default ITELIC_Key::ACTIVE
 *
 * @return bool
 */
function generate_keys_for_transaction( \IT_Exchange_Transaction $transaction, $status = '' ) {

	$result = false;

	foreach ( $transaction->get_products() as $product ) {

		if ( isset( $product['renewed_key'] ) && $product['renewed_key'] ) {
			continue; // this is a renewal purchase we shouldn't generate keys here.
		}

		if ( it_exchange_product_has_feature( $product['product_id'], 'licensing' ) ) {

			$product = it_exchange_get_product( $product['product_id'] );

			if ( generate_key_for_transaction_product( $transaction, $product, $status ) ) {
				$result = true;
			} else {
				$result = false;
			}
		}
	}

	return $result;
}

/**
 * Generate a key for a certain transaction product.
 *
 * @since 1.0
 *
 * @param \IT_Exchange_Transaction $transaction
 * @param \IT_Exchange_Product     $product
 * @param string                   $status
 *
 * @return Key
 */
function generate_key_for_transaction_product( \IT_Exchange_Transaction $transaction, \IT_Exchange_Product $product, $status = '' ) {

	$customer = it_exchange_get_transaction_customer( $transaction );

	$factory = new Factory( $product, $customer, $transaction );
	$key     = $factory->make();

	foreach ( $transaction->get_products() as $tran_product ) {

		if ( $tran_product['product_id'] == $product->ID ) {

			if ( empty( $tran_product['itemized_data'] ) ) {
				continue;
			}

			if ( is_string( $tran_product['itemized_data'] ) ) {
				$itemized = maybe_unserialize( $tran_product['itemized_data'] );
			} else {
				$itemized = $tran_product['itemized_data'];
			}

			if ( isset( $itemized['it_variant_combo_hash'] ) ) {
				$hash = $itemized['it_variant_combo_hash'];

				$max = it_exchange_get_product_feature( $product->ID, 'licensing', array(
					'field'    => 'limit',
					'for_hash' => $hash
				) );
			}

		}
	}

	if ( ! isset( $max ) ) {
		$max = it_exchange_get_product_feature( $product->ID, 'licensing', array( 'field' => 'limit' ) );
	}

	if ( ! it_exchange_product_has_feature( $product->ID, 'recurring-payments' ) ) {
		$expires = null;
	} else {

		$type  = it_exchange_get_product_feature( $product->ID, 'recurring-payments', array( 'setting' => 'interval' ) );
		$count = it_exchange_get_product_feature( $product->ID, 'recurring-payments', array( 'setting' => 'interval-count' ) );

		$interval = convert_rp_to_date_interval( $type, $count );

		$expires = new \DateTime( 'now', new \DateTimeZone( get_option( 'timezone_string' ) ) );
		$expires->add( $interval );
	}

	Key::create( $key, $transaction, $product, $customer, $max, $expires, $status );
}

/**
 * Convert a recurring payments interval to a DateInterval object.
 *
 * @since 1.0
 *
 * @param string $type
 * @param int    $count
 *
 * @return \DateInterval
 *
 * @throws \Exception if invalid interval spec.
 */
function convert_rp_to_date_interval( $type, $count ) {

	$count = absint( $count );

	switch ( $type ) {
		case 'day':
			$period_designator = 'D';
			break;
		case 'week':
			$period_designator = 'W';
			break;
		case 'month':
			$period_designator = 'M';
			break;
		case 'year':
			$period_designator = 'Y';
			break;
	}

	if ( isset( $period_designator ) ) {
		$interval_spec = $count . $period_designator;
	} else {

		/**
		 * Filters the interval spec if the period designator is unknown.
		 *
		 * @see   http://php.net/manual/en/dateinterval.construct.php#refsect1-dateinterval.construct-parameters
		 *
		 * @since 1.0
		 *
		 * @param string|null $interval_spec Conforms to PHP date interval_spec without the P prefix.
		 * @param string      $type          Raw recurring payments type.
		 * @param int         $count         Recurrence count.
		 */
		$interval_spec = apply_filters( 'itelic_convert_rp_to_date_interval_unknown_designator', null, $type, $count );
	}

	return new \DateInterval( "P$interval_spec" );
}

/**
 * Get the license key for a particular transaction product.
 *
 * @since 1.0
 *
 * @param int $transaction_id
 * @param int $product_id
 *
 * @return Key
 */
function get_key_for_transaction_product( $transaction_id, $product_id ) {
	$data = itelic_get_keys( array(
		'transaction'         => absint( $transaction_id ),
		'product'             => absint( $product_id ),
		'items_per_page'      => 1,
		'sql_calc_found_rows' => false
	) );

	if ( empty( $data ) ) {
		return null;
	}

	return reset( $data );
}

/* --------------------------------------------
================= Notifications ===============
----------------------------------------------- */

/**
 * Get tags that are shared between managers.
 *
 * @since 1.0
 *
 * @return Listener[]
 */
function get_shared_tags() {
	return array(
		new Listener( 'full_customer_name', function ( \WP_User $to ) {
			return $to->first_name . " " . $to->last_name;
		} ),
		new Listener( 'customer_first_name', function ( \WP_User $to ) {
			return $to->first_name;
		} ),
		new Listener( 'customer_last_name', function ( \WP_User $to ) {
			return $to->last_name;
		} ),
		new Listener( 'customer_email', function ( \WP_User $to ) {
			return $to->user_email;
		} ),
		new Listener( 'store_name', function () {
			$settings = it_exchange_get_option( 'settings_general' );

			return $settings['company-name'];
		} )
	);
}

/* --------------------------------------------
============ Purchase Requirements ============
----------------------------------------------- */
/**
 * Exchange isn't very consistent in getting access to the current product.
 *
 * This function tries to abstract that away and provide all the possible means of getting the product.
 *
 * @return int
 */
function get_current_product_id() {
	if ( isset( $GLOBALS['it_exchange']['product'] ) ) {
		$id = $GLOBALS['it_exchange']['product']->ID;
	} elseif ( isset( $GLOBALS['post'] ) ) {
		$id = $GLOBALS['post']->ID;
	} else {
		return 0;
	}

	if ( get_post( $id )->post_type != 'it_exchange_prod' ) {
		return 0;
	}

	return $id;
}


/**
 * Generate a download link.
 *
 * @since 1.0
 *
 * @param Key                  $key
 * @param \IT_Exchange_Product $product
 *
 * @return string
 */
function generate_download_link( Key $key, \IT_Exchange_Product $product ) {

	$now     = new \DateTime( 'now', new \DateTimeZone( get_option( 'timezone_string' ) ) );
	$expires = $now->add( new \DateInterval( "P1D" ) );

	$args            = generate_download_query_args( $key, $expires );
	$args['product'] = $product->ID;

	$download_ep = Dispatch::get_url( 'download' );

	return add_query_arg( $args, $download_ep );
}

/**
 * Generates query args to be appended to the download URL.
 *
 * @since 1.0
 *
 * @param Key       $key
 * @param \DateTime $expires
 *
 * @return array
 */
function generate_download_query_args( Key $key, \DateTime $expires ) {

	$args = array(
		'key'     => $key->get_key(),
		'expires' => (int) $expires->getTimestamp()
	);

	$salt = wp_salt();

	$token = md5( serialize( $args ) . $salt );

	$args['token'] = $token;

	return $args;
}

/**
 * Validate a download link.
 *
 * @since 1.0
 *
 * @param array $query_args
 *
 * @return bool
 */
function validate_query_args( $query_args ) {

	if ( ! isset( $query_args['key'] ) || ! isset( $query_args['expires'] ) || ! isset( $query_args['token'] ) ) {
		return false;
	}

	$args = array(
		'key'     => $query_args['key'],
		'expires' => (int) $query_args['expires']
	);

	$salt = wp_salt();

	$token = md5( serialize( $args ) . $salt );


	if ( ! hash_equals( $token, $query_args['token'] ) ) {
		return false;
	}

	$now     = new \DateTime( 'now', new \DateTimeZone( get_option( 'timezone_string' ) ) );
	$expires = new \DateTime( "@{$args['expires']}", new \DateTimeZone( get_option( 'timezone_string' ) ) );

	return $now < $expires;
}

/**
 * Get page rewrites for it_exchange_register_page
 *
 * @since 1.0
 *
 * @param string $page
 *
 * @return array
 */
function page_rewrites( $page ) {
	$slug         = it_exchange_get_page_slug( $page );
	$account_slug = it_exchange_get_page_slug( 'account' );

	// If we're using WP as acount page type, add the WP slug to rewrites and return.
	if ( 'wordpress' == it_exchange_get_page_type( 'account' ) ) {
		$account      = get_post( it_exchange_get_page_wpid( 'account' ) );
		$account_slug = $account->post_name;
	}

	$rewrites = array(
		$account_slug . '/([^/]+)/' . $slug . '$' => 'index.php?' . $account_slug . '=$matches[1]&' . $slug . '=1',
		$account_slug . '/' . $slug . '$'         => 'index.php?' . $account_slug . '=1&' . $slug . '=1',
	);

	return $rewrites;
}