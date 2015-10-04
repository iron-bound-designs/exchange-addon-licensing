<?php
/**
 * Releases api functions.
 *
 * @author      Iron Bound Designs
 * @since       1.0
 * @copyright   2015 (c) Iron Bound Designs.
 * @license     GPLv2
 */

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

	$query = new \ITELIC_API\Query\Releases( array(
		'product' => absint( $product_id ),
		'version' => $version
	) );

	foreach ( $query->get_results() as $release ) {
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

	$product = itelic_get_product( $args['product'] );

	if ( ! $product ) {
		return new WP_Error( 'invalid_product', __( 'Invalid Product', \ITELIC\Plugin::SLUG ) );
	}

	$file = get_post( $args['file'] );

	if ( ! $file ) {
		return new WP_Error( 'invalid_file', __( "Invalid File", \ITELIC\Plugin::SLUG ) );
	}

	$version   = $args['version'];
	$type      = $args['type'];
	$status    = $args['status'];
	$changelog = $args['changelog'];

	try {
		return \ITELIC\Release::create( $product, $file, $version, $type, $status, $changelog );
	}
	catch ( InvalidArgumentException $e ) {
		return new WP_Error( 'exception', $e->getMessage() );
	}
}