<?php
/**
 * Activation CLI command.
 *
 * @author      Iron Bound Designs
 * @since       1.0
 * @copyright   2015 (c) Iron Bound Designs.
 * @license     GPLv2
 */

/**
 * Class ITELIC_Activation_Command
 */
class ITELIC_Activation_Command extends \WP_CLI\CommandWithDBObject {

	protected $obj_type = 'activation';
	protected $obj_id_key = 'id';

	/**
	 * @var ITELIC_Fetcher
	 */
	protected $fetcher;

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->fetcher = new ITELIC_Fetcher( '\ITELIC\Activation' );
	}

	/**
	 * Get an activation record.
	 *
	 * ## Options
	 *
	 * <id>
	 * : Activation ID
	 *
	 * [--fields=<fields>]
	 * : Limit the output to specific object fields.
	 *
	 * [--format=<format>]
	 * : Accepted values: table, json, csv. Default: table
	 *
	 * @param array $args
	 * @param array $assoc_args
	 */
	public function get( $args, $assoc_args ) {

		list( $activation ) = $args;

		$object = $this->fetcher->get_check( $activation );

		$fields = $this->get_fields_for_object( $object );

		if ( empty( $assoc_args['fields'] ) ) {
			$assoc_args['fields'] = array_keys( $fields );
		}

		$formatter = $this->get_formatter( $assoc_args );
		$formatter->display_item( $fields );
	}

	/**
	 * Get an activation record by its key and location.
	 *
	 * ## Options
	 *
	 * <location>
	 * : Location where the software is installed. URLs are normalized.
	 *
	 * <key>
	 * : License key used for activating the software.
	 *
	 * [--fields=<fields>]
	 * : Limit the output to specific object fields.
	 *
	 * [--format=<format>]
	 * : Accepted values: table, json, csv. Default: table
	 *
	 * @param $args
	 * @param $assoc_args
	 *
	 * @subcommand get-by-location
	 * @alias      get-by-loc
	 */
	public function get_by_location( $args, $assoc_args ) {

		list( $location, $key ) = $args;

		$key = itelic_get_key( $key );

		if ( ! $key ) {
			WP_CLI::error( "Invalid license key." );
		}

		$activation = itelic_get_activation_by_location( $location, $key );

		if ( ! $activation ) {
			WP_CLI::error( "Activation does not exist." );
		}

		$this->get( array( $activation->get_pk() ), $assoc_args );
	}

	/**
	 * Get a list of activations for a certain key.
	 *
	 * ## Options
	 *
	 * <key>
	 * : Get activations for this license key.
	 *
	 * [--<field>=<value>]
	 * : Additional parameters passed to the activations query.
	 *
	 * [--fields=<fields>]
	 * : Limit the output to specific object fields.
	 *
	 * [--format=<format>]
	 * : Accepted values: table, json, csv. Default: table
	 *
	 * @param $args
	 * @param $assoc_args
	 *
	 * @subcommand list
	 */
	public function list_( $args, $assoc_args ) {

		list( $key ) = $args;

		$key = itelic_get_key( $key );

		if ( ! $key ) {
			WP_CLI::error( "Invalid key." );
		}

		$query_args = wp_parse_args( $assoc_args, array(
			'items_per_page' => 20,
			'page'           => 1,
			'key'            => $key->get_key()
		) );

		$query_args['order'] = array(
			'activation' => 'DESC'
		);

		$query = new \ITELIC_API\Query\Activations( $query_args );

		$results = $query->get_results();

		$items = array();

		foreach ( $results as $item ) {
			$items[] = $this->get_fields_for_object( $item );
		}

		if ( empty( $assoc_args['fields'] ) ) {
			$assoc_args['fields'] = array(
				'id',
				'location',
				'status',
				'version'
			);
		}

		$formatter = $this->get_formatter( $assoc_args );
		$formatter->display_items( $items );
	}

	/**
	 * Activate a release.
	 *
	 * ## OPTIONS
	 *
	 * <location>
	 * : Where the software is being activated. Typically a website.
	 *
	 * <key>
	 * : The key being activated.
	 *
	 * [--when=<when>]
	 * : Wen the activation occurred. Accepts strtotime compatible
	 * value.
	 *
	 * [--version=<version>]
	 * : The version of the software installed. Default: latest.
	 *
	 * [--track=<track>]
	 * : Accepted values: stable, pre-release. Default: stable
	 *
	 * [--porcelain]
	 * : Output just the new activation ID.
	 *
	 * @param $args
	 * @param $assoc_args
	 */
	public function activate( $args, $assoc_args ) {

		list( $location, $key ) = $args;

		$key = itelic_get_key( $key );

		if ( ! $key ) {
			WP_CLI::error( "Invalid key." );
		}

		if ( $key->get_status() != ITELIC\Key::ACTIVE ) {
			WP_CLI::error( sprintf( "Key has a status of '%s' not 'active'.", $key->get_status() ) );
		}

		if ( isset( $assoc_args['when'] ) ) {
			$when = new DateTime( $assoc_args['when'] );
		} else {
			$when = null;
		}

		if ( isset( $assoc_args['version'] ) ) {
			$release = itelic_get_release_by_version( $key->get_product()->ID, $assoc_args['version'] );

			if ( ! $release ) {
				WP_CLI::error( sprintf( "Invalid release ID %d.", $assoc_args['release'] ) );
			}
		} else {
			$release = null;
		}

		if ( isset( $assoc_args['track'] ) ) {
			if ( in_array( $assoc_args['track'], array(
				'stable',
				'pre-release'
			) ) ) {
				$track = $assoc_args['track'];
			} else {
				WP_CLI::error( "Invalid value '%s' for track." );
			}
		} else {
			$track = 'stable';
		}

		parent::_create( $args, $assoc_args,
			function () use ( $location, $key, $when, $release, $track ) {
				$a = itelic_activate_license_key( $key, $location, $when, $release, $track );

				if ( $a ) {
					return $a->get_pk();
				}

				return new WP_Error();
			} );
	}

	/**
	 * Deactivate an activation record.
	 *
	 * ## Options
	 *
	 * <id>
	 * : Activation ID.
	 *
	 * [--when=<when>]
	 * : When the deactivation occurred. Accepts strtotime compatible value.
	 *
	 * @param $args
	 * @param $assoc_args
	 */
	public function deactivate( $args, $assoc_args ) {

		list( $id ) = $args;

		$activation = $this->fetcher->get_check( $id );

		if ( isset( $assoc_args['when'] ) ) {
			$when = new DateTime( $assoc_args['when'] );
		} else {
			$when = null;
		}

		$activation->deactivate( $when );

		WP_CLI::success( "Activation record deactivated." );
	}

	/**
	 * Reactivate an activation record.
	 *
	 * ## Options
	 *
	 * <id>
	 * : Activation ID.
	 *
	 * [--when=<when>]
	 * : When the reactivation occurred. Accepts strtotime compatible value.
	 *
	 * @param $args
	 * @param $assoc_args
	 */
	public function reactivate( $args, $assoc_args ) {

		list( $id ) = $args;

		$activation = $this->fetcher->get_check( $id );

		if ( isset( $assoc_args['when'] ) ) {
			$when = new DateTime( $assoc_args['when'] );
		} else {
			$when = null;
		}

		try {
			$activation->reactivate( $when );
		}
		catch ( Exception $e ) {
			WP_CLI::error( $e->getMessage() );
		}

		WP_CLI::success( "Activation record reactivated." );
	}

	/**
	 * Expire an activation record.
	 *
	 * ## Options
	 *
	 * <id>
	 * : Activation ID.
	 *
	 * @param $args
	 * @param $assoc_args
	 */
	public function expire( $args, $assoc_args ) {

		list( $id ) = $args;

		$this->fetcher->get_check( $id )->expire();

		WP_CLI::success( "Activation record expired." );
	}

	/**
	 * Delete a license key.
	 *
	 * @param $args
	 * @param $assoc_args
	 *
	 * @synopsis <key>
	 */
	public function delete( $args, $assoc_args ) {

		list( $key ) = $args;

		$key = $this->fetcher->get_check( $key );

		try {
			$key->delete();
		}
		catch ( Exception $e ) {
			WP_CLI::error( $e->getMessage() );
		}

		WP_CLI::success( "Activation deleted." );
	}

	/**
	 * Get data to display for a single key.
	 *
	 * @param \ITELIC\Activation $activation
	 *
	 * @return array
	 */
	protected function get_fields_for_object( \ITELIC\Activation $activation ) {

		if ( $activation->get_deactivation() ) {
			$deactivated = $activation->get_deactivation()->format( DateTime::ISO8601 );
		} else {
			$deactivated = '-';
		}

		return array(
			'id'          => $activation->get_id(),
			'key'         => $activation->get_key()->get_key(),
			'location'    => $activation->get_location(),
			'status'      => $activation->get_status( true ),
			'activated'   => $activation->get_activation()->format( DateTime::ISO8601 ),
			'deactivated' => $deactivated,
			'version'     => $activation->get_release() ? $activation->get_release()->get_version() : 'Unknown',
			'track'       => $activation->get_meta( 'track', true ) ? $activation->get_meta( 'track', true ) : 'stable'
		);
	}
}

/**
 * Class ITELIC_Activation_Meta_Command
 */
class ITELIC_Activation_Meta_Command extends \WP_CLI\CommandWithMeta {

	protected $meta_type = 'itelic_activation';

	/**
	 * Check that the activation ID exists
	 *
	 * @param int
	 *
	 * @return int
	 */
	protected function check_object_id( $object_id ) {
		$fetcher    = new ITELIC_Fetcher( '\ITELIC\Activation' );
		$activation = $fetcher->get_check( $object_id );

		return $activation->get_pk();
	}
}

WP_CLI::add_command( 'itelic activation', 'ITELIC_Activation_Command' );
WP_CLI::add_command( 'itelic activation meta', 'ITELIC_Activation_Meta_Command' );