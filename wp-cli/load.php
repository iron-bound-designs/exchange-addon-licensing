<?php
/**
 * Load WP-CLI commands
 *
 * @author Iron Bound Designs
 * @since  1.0
 */

if ( ! defined( 'WP_CLI' ) || ! WP_CLI ) {
	return;
}

include_once __DIR__ . '/Fetcher.php';

// commands
include_once __DIR__ . '/commands/ITELIC.php';
include_once __DIR__ . '/commands/Key.php';
include_once __DIR__ . '/commands/Activation.php';
include_once __DIR__ . '/commands/Release.php';
include_once __DIR__ . '/commands/Update.php';
include_once __DIR__ . '/commands/Renewal.php';
include_once __DIR__ . '/commands/Product.php';

/**
 * Generate a number on a bell curbe.
 *
 * @link http://www.eboodevelopment.com/php-random-number-generator-with-normal-distribution-bell-curve/
 *
 * @param     $min
 * @param     $max
 * @param     $std_deviation
 * @param int $step
 *
 * @return float
 */
function itelic_purebell( $min, $max, $std_deviation, $step = 1 ) {
	$rand1           = (float) mt_rand() / (float) mt_getrandmax();
	$rand2           = (float) mt_rand() / (float) mt_getrandmax();
	$gaussian_number = sqrt( - 2 * log( $rand1 ) ) * cos( 2 * M_PI * $rand2 );
	$mean            = ( $max + $min ) / 2;
	$random_number   = ( $gaussian_number * $std_deviation ) + $mean;
	$random_number   = round( $random_number / $step ) * $step;
	if ( $random_number < $min || $random_number > $max ) {
		$random_number = itelic_purebell( $min, $max, $std_deviation );
	}

	return $random_number;
}

/**
 * Rename and copy a file.
 *
 * @since 1.0
 *
 * @param WP_Post $file
 * @param string  $name
 *
 * @return WP_Post
 */
function itelic_rename_file( WP_Post $file, $name ) {

	$zip = get_attached_file( $file->ID );

	if ( ! file_exists( $zip ) ) {
		throw new InvalidArgumentException( "Invalid file." );
	}

	$new_name = str_replace( array( ' ', '/' ), '-', strtolower( $name ) );

	$new_path = str_replace( basename( $zip ), $new_name, $zip );

	copy( $zip, $new_path );

	$file = wp_insert_attachment( array(
		'guid'           => str_replace( $zip, $new_path, $file->guid ),
		'post_mime_type' => $file->post_mime_type,
		'post_title'     => preg_replace( '/\.[^.]+$/', '', $new_name )
	), $new_path );

	require_once( ABSPATH . 'wp-admin/includes/image.php' );

	$attach_data = wp_generate_attachment_metadata( $file, $new_path );
	wp_update_attachment_metadata( $file, $attach_data );

	return get_post( $file );
}