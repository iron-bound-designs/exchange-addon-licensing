<?php
/**
 * Main Plugin Functions
 *
 * @author    Iron Bound Designs
 * @since     1.0
 * @license   AGPL
 * @copyright Iron Bound Designs, 2015.
 */

namespace ITELIC;

use IronBound\DB\Manager;
use IronBound\DB\Table\Table;
use IronBound\WP_Notifications\Queue\Mandrill as Mandrill_Queue;
use IronBound\WP_Notifications\Strategy\Mandrill as Mandrill_Strategy;
use IronBound\WP_Notifications\Queue\Storage\Options;
use IronBound\WP_Notifications\Queue\WP_Cron;
use IronBound\WP_Notifications\Strategy\iThemes_Exchange;
use IronBound\WP_Notifications\Template\Listener;
use ITELIC\DB\Table\Activation_Meta;
use ITELIC\DB\Table\Activations;
use ITELIC\DB\Table\Keys;
use ITELIC\DB\Table\Release_Meta;
use ITELIC\DB\Table\Releases;
use ITELIC\DB\Table\Renewals;
use ITELIC\DB\Table\Updates;
use Mandrill as Mandrill_API;
use ITELIC\Key\Factory;
use ITELIC\API\Dispatch;

/**
 * Generate license keys for a transaction.
 *
 * @internal
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

		$product = itelic_get_product( $product['product_id'] );

		if ( $product->has_feature( 'licensing' ) ) {

			$customer = it_exchange_get_transaction_customer( $transaction );

			$factory = new Factory( $product, $customer, $transaction );

			if ( generate_key_for_transaction_product( $transaction, $product, $factory, $status ) ) {
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
 * @internal
 *
 * @since 1.0
 *
 * @param \IT_Exchange_Transaction $transaction
 * @param Product                  $product
 * @param Factory                  $factory
 * @param string                   $status
 * @param string                   $key
 *
 * @return Key
 */
function generate_key_for_transaction_product( \IT_Exchange_Transaction $transaction, Product $product, Factory $factory, $status = '', $key = '' ) {

	$customer = it_exchange_get_transaction_customer( $transaction );

	if ( ! $key ) {
		$key = $factory->make();
	}

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

				$max = $product->get_feature( 'licensing', array(
					'field'    => 'limit',
					'for_hash' => $hash
				) );
			}

		}
	}

	if ( ! isset( $max ) ) {
		$max = $product->get_feature( 'licensing', array( 'field' => 'limit' ) );
	}

	if ( ! $product->has_feature( 'recurring-payments' ) ) {
		$expires = null;
	} else {

		$type  = $product->get_feature( 'recurring-payments', array( 'setting' => 'interval' ) );
		$count = $product->get_feature( 'recurring-payments', array( 'setting' => 'interval-count' ) );

		$interval = convert_rp_to_date_interval( $type, $count );

		$expires = make_date_time( $transaction->post_date_gmt );
		$expires->add( $interval );
	}

	return Key::create( $key, $transaction, $product, $customer, $max, $expires, $status );
}

/**
 * Convert a recurring payments interval to a DateInterval object.
 *
 * @internal
 *
 * @since 1.0
 *
 * @param string $type
 * @param int    $count
 *
 * @return \DateInterval
 *
 * @throws \InvalidArgumentException if invalid interval spec.
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

	if ( ! $interval_spec ) {
		throw new \InvalidArgumentException( 'Invalid interval spec.' );
	}

	return new \DateInterval( "P$interval_spec" );
}

/**
 * Get the license key for a particular transaction product.
 *
 * @internal
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
================== Date Utils =================
----------------------------------------------- */

/**
 * Make a DateTime object.
 *
 * @internal
 *
 * @since 1.0
 *
 * @param string $time
 * @param bool   $gmt If false, returns object set to the timezone specified in
 *                    general settings.
 *
 * @return \DateTime
 */
function make_date_time( $time = 'now', $gmt = true ) {

	$tz   = new \DateTimeZone( 'UTC' );
	$time = new \DateTime( $time, $tz );

	if ( $gmt ) {
		return $time;
	} else {
		return convert_gmt_to_local( $time );
	}
}

/**
 * Make a local time object.
 *
 * @internal
 *
 * @param string $time
 *
 * @return \DateTime
 */
function make_local_time( $time = 'now' ) {

	$tz_string = get_option( 'timezone_string' );

	if ( ! empty( $tz_string ) ) {
		$tz = new \DateTimeZone( $tz_string );

		return new \DateTime( $time, $tz );
	} else {
		return new \DateTime( $time );
	}
}

/**
 * Convert a GMT date to its localized equivalent.
 *
 * @internal
 *
 * @since 1.0
 *
 * @param \DateTime $time
 *
 * @return \DateTime
 */
function convert_gmt_to_local( \DateTime $time ) {

	if ( $time->getOffset() != 0 ) {
		throw new \InvalidArgumentException( "Passed DateTime object is not in GMT time." );
	}

	$tz_string = get_option( 'timezone_string' );

	if ( ! empty( $tz_string ) ) {
		$tz = new \DateTimeZone( $tz_string );

		$time->setTimezone( $tz );

		return $time;
	} else {
		$tz = new \DateTimeZone( 'UTC' );
		$time->setTimezone( $tz );

		$offset = get_option( 'gmt_offset', 0 );

		if ( $offset == 0 ) {
			return $time;
		}

		$invert = $offset < 0;

		$parts = explode( '.', $offset );
		$hours = $parts[0];

		$interval_spec = "PT{$hours}H";

		if ( isset( $parts[1] ) ) {
			$minutes_fraction = (float) "0.$parts[1]";
			$minutes          = 60 * $minutes_fraction;

			if ( $minutes ) {
				$interval_spec .= "{$minutes}M";
			}
		}

		$interval         = new \DateInterval( $interval_spec );
		$interval->invert = $invert;

		$time->add( $interval );

		return $time;
	}
}

/**
 * Convert a localized date to its GMT equivalent.
 *
 * @internal
 *
 * @since 1.0
 *
 * @param \DateTime $time
 *
 * @return \DateTime
 */

function convert_local_to_gmt( \DateTime $time ) {
	$time->setTimezone( new \DateTimeZone( 'UTC' ) );

	return $time;
}

/* --------------------------------------------
================= Notifications ===============
----------------------------------------------- */

/**
 * Get tags that are shared between managers.
 *
 * @internal
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

/**
 * Get the notifications queue processor to use.
 *
 * @internal
 *
 * @since 1.0
 *
 * @param string $batch_name
 *
 * @return \IronBound\WP_Notifications\Queue\Queue
 */
function get_queue_processor( $batch_name ) {

	if ( class_exists( 'wpMandrill' ) ) {

		\wpMandrill::getConnected();

		if ( \wpMandrill::isConnected() ) {
			$key   = \wpMandrill::getAPIKey();
			$queue = new Mandrill_Queue( new Mandrill_API( $key ) );
		}

	} elseif ( defined( 'ITELIC_Mandrill' ) && class_exists( 'Mandrill' ) ) {
		$key = ITELIC_Mandrill;

		$queue = new Mandrill_Queue( new Mandrill_API( $key ) );
	}

	if ( ! isset( $queue ) ) {
		$queue = new WP_Cron( new Options( $batch_name ) );
	}

	/**
	 * Get a queue processor.
	 *
	 * @since 1.0
	 *
	 * @param \IronBound\WP_Notifications\Queue\Queue $queue
	 * @param string                                  $batch_name
	 */

	return apply_filters( 'itelic_get_queue_processor', $queue, $batch_name );
}

/**
 * Get the notification strategy.
 *
 * @internal
 *
 * @since 1.0
 *
 * @return \IronBound\WP_Notifications\Strategy\Strategy
 */
function get_notification_strategy() {

	if ( class_exists( 'wpMandrill' ) && $c = \wpMandrill::getConnected() && \wpMandrill::isConnected() ) {
		$key = \wpMandrill::getAPIKey();

		$strategy = new Mandrill_Strategy( new Mandrill_API( $key ) );

	} elseif ( defined( 'ITELIC_Mandrill' ) && class_exists( 'Mandrill' ) ) {
		$key = ITELIC_Mandrill;

		$strategy = new Mandrill_Strategy( new Mandrill_API( $key ) );
	} else {
		$strategy = new iThemes_Exchange();
	}

	/**
	 * Get a notifications strategy.
	 *
	 * @since 1.0
	 *
	 * @param \IronBound\WP_Notifications\Strategy\Strategy $strategy
	 * @param string                                        $batch_name
	 */

	return apply_filters( 'itelic_get_queue_processor', $strategy );
}

/* --------------------------------------------
=============== Count Functions ===============
----------------------------------------------- */

/**
 * Count the number of keys belonging to each status.
 *
 * @internal
 *
 * @since 1.0
 *
 * @param string $status
 *
 * @return array
 */
function count_keys( $status = '' ) {

	/** @var $wpdb \wpdb */
	global $wpdb;

	$tn = Manager::get( 'itelic-keys' )->get_table_name( $wpdb );

	$raw = $wpdb->get_results(
		"SELECT COUNT(1) AS C, k.status AS S FROM $tn k GROUP BY k.status"
	);

	$counts = array();

	foreach ( $raw as $result ) {
		$counts[ $result->S ] = $result->C;
	}

	$counts = wp_parse_args( $counts, array(
		Key::ACTIVE   => 0,
		Key::DISABLED => 0,
		Key::EXPIRED  => 0
	) );

	if ( $status ) {
		return $counts[ $status ];
	}

	return $counts;
}

/**
 * Count the number of releases belonging to each status.
 *
 * @internal
 *
 * @since 1.0
 *
 * @param string $status
 *
 * @return array
 */
function count_releases( $status = '' ) {

	/** @var $wpdb \wpdb */
	global $wpdb;

	$tn = Manager::get( 'itelic-releases' )->get_table_name( $wpdb );

	$raw = $wpdb->get_results(
		"SELECT COUNT(1) AS C, r.status AS S FROM $tn r GROUP BY r.status"
	);

	$counts = array();

	foreach ( $raw as $result ) {
		$counts[ $result->S ] = $result->C;
	}

	$counts = wp_parse_args( $counts, array(
		Release::STATUS_ACTIVE   => 0,
		Release::STATUS_ARCHIVED => 0,
		Release::STATUS_DRAFT    => 0,
		Release::STATUS_PAUSED   => 0,
	) );

	if ( $status ) {
		return $counts[ $status ];
	}

	return $counts;
}

/* --------------------------------------------
============ Purchase Requirements ============
----------------------------------------------- */

/**
 * Exchange isn't very consistent in getting access to the current product.
 *
 * This function tries to abstract that away and provide all the possible means
 * of getting the product.
 *
 * @internal
 *
 * @since 1.0
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
 * Generate a download link for an activation record.
 *
 * @internal
 *
 * @since 1.0
 *
 * @param Activation $activation
 *
 * @return string
 */
function generate_download_link( Activation $activation ) {

	$now     = make_date_time();
	$expires = $now->add( new \DateInterval( "P1D" ) );

	$args = generate_download_query_args( $activation, $expires );

	$download_ep = Dispatch::get_url( 'download' );

	$link = add_query_arg( $args, $download_ep );

	/**
	 * Filters the download link for an activation.
	 *
	 * Download links are specific to activations, not products.
	 * This allows for pausing releases, and pre-release tracks.
	 *
	 * If you are going to override the download link, you need
	 * to be able dynamically serve the download based on the current
	 * state of the releases, and the activation track.
	 *
	 * @since 1.0
	 *
	 * @param string     $link
	 * @param Activation $activation
	 */
	$link = apply_filters( 'itelic_generate_download_link', $link, $activation );

	return $link;
}

/**
 * Generates query args to be appended to the download URL.
 *
 * @internal
 *
 * @since 1.0
 *
 * @param Activation $activation
 * @param \DateTime  $expires
 *
 * @return array
 */
function generate_download_query_args( Activation $activation, \DateTime $expires ) {

	$args = array(
		'activation' => (int) $activation->get_pk(),
		'key'        => $activation->get_key()->get_key(),
		'expires'    => (int) $expires->getTimestamp()
	);

	$token = hash_hmac( 'md5', serialize( $args ), wp_salt() );

	$args['token'] = $token;

	/**
	 * Filters the query args generated for downloading a software update.
	 *
	 * @since 1.0
	 *
	 * @param array      $args        Query args.
	 * @param Activation $activation  Activation record download is delivered to.
	 * @param \DateTime  $expires     Expiration date for arguments. Link MUST function until time given.
	 */
	$args = apply_filters( 'itelic_generate_download_query_args', $args, $activation, $expires );

	return $args;
}

/**
 * Validate a download link.
 *
 * @internal
 *
 * @since 1.0
 *
 * @param array $query_args
 *
 * @return bool
 */
function validate_query_args( $query_args ) {

	$valid = true;

	if ( ! isset( $query_args['key'] ) || ! isset( $query_args['expires'] ) ||
	     ! isset( $query_args['token'] ) || ! isset( $query_args['activation'] )
	) {
		$valid = false;
	}

	if ( $valid ) {

		$args = array(
			'activation' => (int) $query_args['activation'],
			'key'        => $query_args['key'],
			'expires'    => (int) $query_args['expires']
		);

		$token = hash_hmac( 'md5', serialize( $args ), wp_salt() );

		if ( ! hash_equals( $token, $query_args['token'] ) ) {
			$valid = false;
		} else {
			$now     = make_date_time();
			$expires = make_date_time( "@{$args['expires']}" );

			$valid = $now < $expires;
		}
	}


	/**
	 * Filter whether the download link query args are valid.
	 *
	 * @since 1.0
	 *
	 * @param bool  $valid
	 * @param array $query_args
	 */
	$valid = apply_filters( 'itelic_validate_download_link_query_args', $valid, $query_args );

	return $valid;
}

/**
 * Serve a download.
 *
 * Essentially a clone of it_exchange_serve_product_download(), but works for
 * arbitrary URLs.
 *
 * @internal
 *
 * @since 1.0
 *
 * @param string $url
 */
function serve_download( $url ) {

	/**
	 * Fires prior to a download before being served.
	 *
	 * @since 1.0
	 *
	 * @param string $url
	 */
	do_action( 'itelic_serve_download', $url );

	// Attempt to grab file
	if ( $response = wp_remote_head( str_replace( ' ', '%20', $url ) ) ) {
		if ( ! is_wp_error( $response ) ) {
			$valid_response_codes = array(
				200,
				301,
				302,
			);

			if ( in_array( wp_remote_retrieve_response_code( $response ), (array) $valid_response_codes ) ) {

				// Get Resource Headers
				$headers = wp_remote_retrieve_headers( $response );

				// White list of headers to pass from original resource
				$passthru_headers = array(
					'accept-ranges',
					'content-length',
					'content-type',
				);

				// Set Headers for download from original resource
				foreach ( (array) $passthru_headers as $header ) {
					if ( isset( $headers[ $header ] ) ) {
						header( esc_attr( $header ) . ': ' . esc_attr( $headers[ $header ] ) );
					}
				}

				// Set headers to force download
				header( 'Content-Description: File Transfer' );
				header( 'Content-Disposition: attachment; filename=' . basename( parse_url( $url, PHP_URL_PATH ) ) );
				header( 'Content-Transfer-Encoding: binary' );
				header( 'Expires: 0' );
				header( 'Cache-Control: must-revalidate' );
				header( 'Pragma: public' );

				// Clear buffer
				flush();
				ob_end_clean();

				// Deliver the file: readfile, curl, redirect
				if ( ini_get( 'allow_url_fopen' ) ) {
					// Use readfile if allow_url_fopen is on
					readfile( str_replace( ' ', '%20', $url ) );
				} else if ( is_callable( 'curl_init' ) ) {
					// Use cURL if allow_url_fopen is off and curl is available
					$ch = curl_init( str_replace( ' ', '%20', $url ) );
					curl_exec( $ch );
					curl_close( $ch );
				} else {
					// Just redirect to the file becuase their host <strike>sucks</strike> doesn't support allow_url_fopen or curl.
					wp_redirect( str_replace( ' ', '%20', $url ) );
				}
				die();

			}
			die( __( 'Download Error: Invalid response: ', 'it-l10n-ithemes-exchange' ) . wp_remote_retrieve_response_code( $response ) );
		} else {
			die( __( 'Download Error:', 'it-l10n-ithemes-exchange' ) . ' ' . $response->get_error_message() );
		}
	}
}

/**
 * Get page rewrites for it_exchange_register_page
 *
 * @internal
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

/* --------------------------------------------
==================== Database =================
----------------------------------------------- */

/**
 * Get all custom tables used.
 *
 * @internal
 *
 * @since 1.0
 *
 * @return Table[]
 */
function get_tables() {
	return array(
		new Keys(),
		new Activations(),
		new Renewals(),
		new Releases(),
		new Updates(),
		new Release_Meta(),
		new Activation_Meta()
	);
}