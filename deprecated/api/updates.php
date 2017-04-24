<?php
/**
 * Updates API functions.
 *
 * @author    Iron Bound Designs
 * @since     1.0
 * @license   AGPL
 * @copyright Iron Bound Designs, 2015.
 */

/**
 * Get updates.
 *
 * @api
 *
 * @since 1.0
 *
 * @param array $args
 *
 * @return \ITELIC\Update[]
 */
function itelic_get_updates( $args = array() ) {

	$defaults = array(
		'sql_calc_found_rows' => false
	);
	$args     = wp_parse_args( $args, $defaults );

	$query = new \ITELIC\Query\Updates( $args );

	return $query->get_results();
}

/**
 * Get an update record.
 *
 * @api
 *
 * @since 1.0
 *
 * @param int $id
 *
 * @return \ITELIC\Update
 */
function itelic_get_update( $id ) {

	$update = \ITELIC\Update::get( $id );

	/**
	 * Filter the update object as retrieved from the database.
	 *
	 * @since 1.0
	 *
	 * @param \ITELIC\Update $update
	 */
	$filtered = apply_filters( 'itelic_get_update', $update );

	if ( $filtered instanceof \ITELIC\Update ) {
		$update = $filtered;
	}

	return $update;
}

/**
 * Create an update record.
 *
 * @api
 *
 * @since 1.0
 *
 * @param array $args
 *
 * @return \ITELIC\Update|WP_Error
 */
function itelic_create_update( $args ) {

	$defaults = array(
		'activation'       => '',
		'release'          => '',
		'update_date'      => '',
		'previous_version' => ''
	);

	$args = ITUtility::merge_defaults( $args, $defaults );

	$activation = is_numeric( $args['activation'] ) ? itelic_get_activation( $args['activation'] ) : $args['activation'];

	if ( ! $activation ) {
		return new WP_Error( 'invalid_activation', __( "Invalid activation record.", \ITELIC\Plugin::SLUG ) );
	}

	$release = is_numeric( $args['release'] ) ? itelic_get_release( $args['release'] ) : $args['release'];

	if ( ! $release ) {
		return new WP_Error( 'invalid_release', __( "Invalid release object.", \ITELIC\Plugin::SLUG ) );
	}

	if ( ! empty( $args['update_date'] ) ) {
		$update_date = is_string( $args['update_date'] ) ? \ITELIC\make_date_time( $args['update_date'] ) : $args['update_date'];

		if ( ! $update_date instanceof DateTime ) {
			return new WP_Error( "invalid_update_date", __( "Invalid update date.", \ITELIC\Plugin::SLUG ) );
		}

	} else {
		$update_date = null;
	}

	return \ITELIC\Update::create( $activation, $release, $update_date, $args['previous_version'] );
}