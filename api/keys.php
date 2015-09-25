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
 * @return \ITELIC\Key[]
 */
function itelic_get_keys( $args = array() ) {

	$query = new \ITELIC_API\Query\Keys( $args );

	return $query->get_results();
}

/**
 * Get a key.
 *
 * @since 1.0
 *
 * @param string $key
 *
 * @return \ITELIC\Key
 */
function itelic_get_key( $key ) {

	/**
	 * Filters the key as it is retrieved from the database.
	 *
	 * @since 1.0
	 *
	 * @param \ITELIC\Key $key
	 */
	return apply_filters( 'itelic_get_key', \ITELIC\Key::with_key( $key ) );
}

/**
 * Get a key from data pulled from the DB.
 *
 * @since 1.0
 *
 * @param stdClass $data
 *
 * @return \ITELIC\Key
 */
function itelic_get_key_from_data( stdClass $data ) {
	return new \ITELIC\Key( $data );
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
	), \ITELIC\Admin\Tab\Dispatch::get_tab_link( 'licenses' ) );
}