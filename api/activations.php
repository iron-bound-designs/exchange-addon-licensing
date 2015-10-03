<?php
/**
 * Activation methods.
 *
 * @author Iron Bound Designs
 * @since  1.0
 */

/**
 * Get an activation.
 *
 * @since 1.0
 *
 * @param int $id
 *
 * @return \ITELIC\Activation
 */
function itelic_get_activation( $id ) {

	/**
	 * Filters the activation as it is retrieved from the database.
	 *
	 * @since 1.0
	 *
	 * @param \ITELIC\Activation $activation
	 */
	return apply_filters( 'itelic_get_activation', \ITELIC\Activation::get( $id ) );
}

/**
 * Get an activation record by its location
 *
 * @since 1.0
 *
 * @param string      $location
 * @param \ITELIC\Key $key
 *
 * @return \ITELIC\Activation|null
 */
function itelic_get_activation_by_location( $location, \ITELIC\Key $key ) {

	$query = new \ITELIC_API\Query\Activations( array(
		'location' => itelic_normalize_url( $location ),
		'key'      => $key->get_key()
	) );

	$activations = $query->get_results();

	if ( empty( $activations ) ) {
		return null;
	}

	return ( reset( $activations ) );
}

/**
 * Check if a URL is a dev site.
 *
 * @since 1.0
 *
 * @param string $url
 *
 * @return bool
 */
function itelic_is_url_dev_site( $url ) {

	$is_dev = false;

	$white_listed = array(
		'localhost',
		'127.0.0.1',
		'192.168.50.4'
	);

	/**
	 * Filter the set of white listed domains and IPs.
	 *
	 * @since 1.0
	 *
	 * @param array $white_listed Set of white listed domains, or IPs
	 */
	$white_listed = apply_filters( 'itelic_is_url_dev_site_white_listed_urls', $white_listed );

	if ( in_array( $url, $white_listed, true ) ) {
		$is_dev = true;
	}

	$tlds = array( 'local' );

	/**
	 * Filter the white list of TLDs.
	 *
	 * @since 1.0
	 *
	 * @param array $tlds Set of top-level domains, without prefacing period.
	 */
	$tlds = apply_filters( 'itelic_is_url_dev_site_tlds', $tlds );

	foreach ( $tlds as $tld ) {
		if ( strpos( $url, ".$tld" ) !== false ) {
			$is_dev = true;

			break;
		}
	}

	/**
	 * Filter whether the passed URL belongs to a dev site.
	 *
	 * @since 1.0
	 */
	$is_dev = apply_filters( 'itelic_is_url_dev_site', $is_dev, $url );

	return $is_dev;
}

/**
 * Normalize URLs so they are all saved the same way.
 *
 * If passed an IP address, just saves the IP address, doesn't normalize.
 *
 * @since 1.0
 *
 * @param string $url
 *
 * @return string
 */
function itelic_normalize_url( $url ) {

	if ( filter_var( $url, FILTER_VALIDATE_IP ) !== false ) {
		return $url;
	}

	$normalizer = new URL\Normalizer( $url );

	return trailingslashit( esc_url_raw( $normalizer->normalize() ) );
}

/**
 * Activate a license key.
 *
 * @since 1.0
 *
 * @param \ITELIC\Key     $key
 * @param string          $location
 * @param DateTime        $date
 * @param \ITELIC\Release $release
 * @param string          $track Either stable or pre-release
 *
 * @return \ITELIC\Activation
 *
 * @throws LogicException|\IronBound\DB\Exception
 */
function itelic_activate_license_key( \ITELIC\Key $key, $location, DateTime $date = null, \ITELIC\Release $release = null, $track = 'stable' ) {

	$record = \ITELIC\Activation::create( $key, $location, $date, $release );
	$record->add_meta( 'track', $track );

	return $record;
}

/**
 * Create an activation record.
 *
 * @since 1.0
 *
 * @param array $args
 *
 * @return \ITELIC\Activation|WP_Error
 */
function itelic_create_activation( $args ) {

	$defaults = array(
		'key'        => '',
		'location'   => '',
		'activation' => '',
		'release'    => '',
		'status'     => '',
		'track'      => 'stable'
	);

	$args = ITUtility::merge_defaults( $args, $defaults );

	$key = is_string( $args['key'] ) ? itelic_get_key( $args['key'] ) : $args['key'];

	if ( ! $key ) {
		return new WP_Error( 'invalid_key', __( "Invalid Key", \ITELIC\Plugin::SLUG ) );
	}

	$location = $args['location'];

	if ( ! empty( $args['activation'] ) ) {

		if ( is_string( $args['activation'] ) ) {
			$activation = \ITELIC\make_date_time( $args['activation'] );
		} else {
			$activation = $args['activation'];
		}

		if ( ! $activation instanceof DateTime ) {
			return new WP_Error( 'invalid_activation', __( "Invalid activation date.", \ITELIC\Plugin::SLUG ) );
		}
	} else {
		$activation = null;
	}

	if ( ! empty( $args['release'] ) ) {

		if ( is_string( $args['release'] ) ) {
			$release = itelic_get_release( $args['release'] );
		} else {
			$release = $args['release'];
		}

		if ( ! $activation instanceof \ITELIC\Release ) {
			return new WP_Error( 'invalid_release', __( "Invalid release.", \ITELIC\Plugin::SLUG ) );
		}
	} else {
		$release = null;
	}

	$status = $args['status'];

	try {
		$activation = \ITELIC\Activation::create( $key, $location, $activation, $release, $status );
		$activation->update_meta( $args['track'] );
	}
	catch ( Exception $e ) {
		return new WP_Error( $e->getCode(), $e->getMessage() );
	}

	return $activation;
}