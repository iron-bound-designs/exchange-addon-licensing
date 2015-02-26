<?php
/**
 * API Methods for interacting with keys.
 *
 * @author Iron Bound Designs
 * @since  1.0
 */

/**
 * Get license keys.
 *
 * @since 1.0
 *
 * @param array $args
 *
 * @return ITELIC_Key[]|int
 */
function itelic_get_keys( $args = array() ) {

	$defaults = array(
		'get_row_count' => false,
		'orderby'       => 'transaction',
		'order'         => 'ASC'
	);

	$args = ITUtility::merge_defaults( $args, $defaults );

	$db = ITELIC_DB_Keys::instance();

	/**
	 * @var wpdb $wpdb
	 */
	global $wpdb;

	$used_columns = array();
	$wheres       = array();

	if ( isset( $args['transaction'] ) ) {
		$wheres['transaction_id'] = absint( $args['transaction'] );
	}

	if ( isset( $args['customer_email'] ) ) {
		$email = esc_sql( $args['customer_email'] );

		$email_search = "`customer` IN ( SELECT `ID` FROM {$wpdb->users} WHERE `user_email` LIKE '%$email%' )";
	} else {
		$email_search = '';
	}

	if ( isset( $args['customer'] ) && ! in_array( 'customer', $used_columns ) ) {
		$wheres['customer'] = absint( $args['customer'] );
	}

	if ( isset( $args['product'] ) ) {
		$wheres['product'] = absint( $args['product'] );
	}

	if ( isset( $args['key_search'] ) ) {
		$key_search = esc_sql( $args['key_search'] );

		$key_search = "`lkey` LIKE '%$key_search%'";
	} else {
		$key_search = '';
	}

	$where = $db->translate_where( $wheres );

	if ( empty( $where ) ) {
		$where .= $key_search;
	} else {
		$where .= ' AND ' . $key_search;
	}

	if ( empty( $where ) ) {
		$where .= $email_search;
	} elseif ( ! empty( $email_search ) ) {
		$where .= ' AND ' . $email_search;
	}

	$count  = isset( $args['count'] ) ? absint( $args['count'] ) : null;
	$offset = isset( $args['offset'] ) ? absint( $args['offset'] ) : null;

	if ( $args['get_row_count'] ) {
		$select = "COUNT(*) AS COUNT";

		// we ignore limit queries if we are trying to find total matching rows.
		$count  = null;
		$offset = null;
	} else {
		$select = "*";
	}

	switch ( $args['orderby'] ) {
		case 'key':
			$orderby = 'lkey';
			break;
		case 'transaction':
			$orderby = 'transaction_id';
			break;
		default:
			$orderby = $args['orderby'];
	}

	$order_by = $db->translate_order_by( array( $orderby => $args['order'] ) );

	$statement = $db->assemble_statement( $select, $where, $order_by, $count, $offset );

	$results = $wpdb->get_results( $statement );

	if ( $args['get_row_count'] ) {
		return (int) $results[0]->COUNT;
	} else {
		return array_map( 'itelic_get_key_from_data', $results );
	}
}

/**
 * Get a key.
 *
 * @since 1.0
 *
 * @param string $key
 *
 * @return ITELIC_Key
 */
function itelic_get_key( $key ) {
	return ITELIC_Key::with_key( $key );
}

/**
 * Get a key from data pulled from the DB.
 *
 * @since 1.0
 *
 * @param stdClass $data
 *
 * @return ITELIC_Key
 */
function itelic_get_key_from_data( stdClass $data ) {
	return new ITELIC_Key( $data );
}

/**
 * Get the admin edit link for a particular key.
 *
 * @since 1.0
 *
 * @param string $key
 *
 * @return string
 */
function itelic_get_admin_edit_key_link( $key ) {
	return add_query_arg( array(
		'view' => 'single',
		'key'  => (string) $key,
	), ITELIC_Admin_Tab_Dispatch::get_tab_link( 'licenses' ) );
}