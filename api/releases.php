<?php
/**
 * Releases api functions.
 *
 * @author    Iron Bound Designs
 * @since     1.0
 * @license   AGPL
 * @copyright Iron Bound Designs, 2015.
 */

/**
 * Get releases.
 *
 * @since 1.0
 *
 * @param array $args
 *
 * @return \ITELIC\Release[]
 */
function itelic_get_releases( $args = array() ) {

	$defaults = array(
		'sql_calc_found_rows' => false
	);
	$args     = wp_parse_args( $args, $defaults );

	$query = new \ITELIC\Query\Releases( $args );

	return $query->get_results();
}

/**
 * Get a release record.
 *
 * @since 1.0
 *
 * @param int $id
 *
 * @return \ITELIC\Release
 */
function itelic_get_release( $id ) {

	/**
	 * Filter the release object as retrieved from the database.
	 *
	 * @since 1.0
	 *
	 * @param \ITELIC\Release $release
	 */
	return apply_filters( 'itelic_get_release', \ITELIC\Release::get( $id ) );
}

/**
 * Get a release by its version number.
 *
 * @since 1.0
 *
 * @param int    $product_id
 * @param string $version
 *
 * @return \ITELIC\Release
 */
function itelic_get_release_by_version( $product_id, $version ) {

	$query = itelic_get_releases( array(
		'product'        => absint( $product_id ),
		'version'        => $version,
		'items_per_page' => 1
	) );

	foreach ( $query as $release ) {
		return $release;
	}

	return null;
}

/**
 * How many releases should be kept with full data until they are archived.
 *
 * @since 1.0
 *
 * @param IT_Exchange_Product $product
 *
 * @return int
 */
function itelic_keep_last_n_releases( IT_Exchange_Product $product ) {

	/**
	 * Filter how many past releases should be kept before archiving.
	 *
	 * @since 1.0
	 *
	 * @param int                 $number
	 * @param IT_Exchange_Product $product
	 */
	return apply_filters( 'itelic_keep_last_n_releases', 10, $product );
}

/**
 * Get the admin edit link for a particular release.
 *
 * @since 1.0
 *
 * @param int $release Release ID
 *
 * @return string
 */
function itelic_get_admin_edit_release_link( $release ) {
	return add_query_arg( array(
		'view' => 'single',
		'ID'  => (string) $release,
	), \ITELIC\Admin\Tab\Dispatch::get_tab_link( 'releases' ) );
}

/**
 * Create a release.
 *
 * @since 1.0
 *
 * @param array $args
 *
 * @return \ITELIC\Release|WP_Error
 */
function itelic_create_release( $args ) {

	$defaults = array(
		'product'   => '',
		'file'      => '',
		'version'   => '',
		'type'      => '',
		'status'    => '',
		'changelog' => ''
	);
	$args     = wp_parse_args( $args, $defaults );

	if ( is_numeric( $args['product'] ) ) {
		$product = itelic_get_product( $args['product'] );
	} else {
		$product = $args['product'];
	}

	if ( ! $product ) {
		return new WP_Error( 'invalid_product', __( 'Invalid Product', \ITELIC\Plugin::SLUG ) );
	}

	if ( is_numeric( $args['file'] ) ) {
		$file = get_post( $args['file'] );
	} else {
		$file = $args['file'];
	}

	if ( ! $file || get_post_type( $file ) != 'attachment' ) {
		return new WP_Error( 'invalid_file', __( "Invalid File", \ITELIC\Plugin::SLUG ) );
	}

	$version   = $args['version'];
	$type      = $args['type'];
	$status    = $args['status'];
	$changelog = $args['changelog'];

	try {
		$release = \ITELIC\Release::create( $product, $file, $version, $type, $status, $changelog );

		if ( isset( $args['security-message'] ) ) {
			$release->add_meta( 'security-message', $args['security-message'] );
		}
	}
	catch ( InvalidArgumentException $e ) {
		return new WP_Error( 'exception', $e->getMessage() );
	}
}