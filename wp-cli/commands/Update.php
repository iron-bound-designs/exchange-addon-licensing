<?php
/**
 * Keys WP CLI command.
 *
 * @author      Iron Bound Designs
 * @since       1.0
 * @copyright   2015 (c) Iron Bound Designs.
 * @license     GPLv2
 */

/**
 * Class ITELIC_Update_Command
 */
class ITELIC_Update_Command extends \WP_CLI\CommandWithDBObject {

	protected $obj_type = 'update';
	protected $obj_id_key = 'ID';

	/**
	 * @var ITELIC_Fetcher
	 */
	protected $fetcher;

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->fetcher = new ITELIC_Fetcher( '\ITELIC\Update' );
	}

	/**
	 * Get an update record.
	 *
	 * ## Options
	 *
	 * <ID>
	 * : Update record ID
	 *
	 * [--raw]
	 * : Return raw values. IDs instead of human readable names.
	 *
	 * @param array $args
	 * @param array $assoc_args
	 */
	public function get( $args, $assoc_args ) {

		list( $ID ) = $args;

		$object = $this->fetcher->get_check( $ID );

		$fields = $this->get_fields_for_object( $object, \WP_CLI\Utils\get_flag_value( $assoc_args, 'raw', false ) );

		if ( empty( $assoc_args['fields'] ) ) {
			$assoc_args['fields'] = array_keys( $fields );
		}

		$formatter = $this->get_formatter( $assoc_args );
		$formatter->display_item( $fields );
	}

	/**
	 * Get a list of updates.
	 *
	 * ## Options
	 *
	 * [--<field>=<value>]
	 * : Add additional arguments to Updates query.
	 *
	 * [--fields=<fields>]
	 * : Limit fields returned.
	 *
	 * [--raw]
	 * : Return raw values. IDs instead of human readable names.
	 *
	 * @param $args
	 * @param $assoc_args
	 *
	 * @subcommand list
	 */
	public function list_( $args, $assoc_args ) {

		$query_args = wp_parse_args( $assoc_args, array(
			'items_per_page' => 20,
			'page'           => 1
		) );

		$query_args['order'] = array(
			'update_date' => 'DESC'
		);

		$results = itelic_get_updates( $query_args );

		$items = array();

		foreach ( $results as $item ) {
			$items[] = $this->get_fields_for_object( $item, \WP_CLI\Utils\get_flag_value( $assoc_args, 'raw', false ) );
		}

		if ( empty( $assoc_args['fields'] ) ) {
			$assoc_args['fields'] = array(
				'ID',
				'activation',
				'location',
				'release',
				'update_date',
				'previous_version'
			);
		}

		$formatter = $this->get_formatter( $assoc_args );
		$formatter->display_items( $items );
	}

	/**
	 * Generate update records.
	 *
	 * ## Options
	 *
	 * <product>
	 * : Product ID to generate update records for.
	 *
	 * @param $args
	 * @param $assoc_args
	 */
	public function generate( $args, $assoc_args ) {

		list( $product ) = $args;

		$product = itelic_get_product( $product );

		if ( ! $product ) {
			WP_CLI::error( "Invalid product ID" );
		}

		$releases = itelic_get_releases( array(
			'product' => $product->ID,
			'order'   => array(
				'start_date' => 'ASC'
			)
		) );

		$notify = \WP_CLI\Utils\make_progress_bar( sprintf( "Generating Updates: %d", $product->ID ), count( $releases ) );

		foreach ( $releases as $release ) {

			switch ( $release->get_type() ) {
				case \ITELIC\Release::TYPE_MAJOR:
					$percent_updated = 75;
					break;
				case \ITELIC\Release::TYPE_MINOR:
					$percent_updated = 90;
					break;
				case \ITELIC\Release::TYPE_SECURITY:
					$percent_updated = 95;
					break;
				case \ITELIC\Release::TYPE_PRERELEASE:
					$percent_updated = 95;
					break;
				default:
					throw new InvalidArgumentException( "Invalid release type." );
			}

			$total_activations = new \ITELIC\Query\Activations( array(
				'activation'   => array(
					'before' => $release->get_start_date()->format( 'Y-m-d H:i:s' )
				),
				'product'      => $product->ID,
				'return_value' => 'count'
			) );

			$total_activations = $total_activations->get_results();

			$activations = itelic_get_activations( array(
				'activation'          => array(
					'before' => $release->get_start_date()->format( 'Y-m-d H:i:s' )
				),
				'product'             => $product->ID,
				'order'               => 'rand',
				'items_per_page'      => $total_activations * ( $percent_updated / 100 )
			) );

			foreach ( $activations as $activation ) {

				if ( $activation->get_deactivation() && $activation->get_deactivation() > $release->get_start_date() ) {
					continue;
				}

				if ( $release->get_type() == ITELIC\Release::TYPE_MAJOR ) {
					$days = rand( 0, 10 );
				} else {
					$days = rand( 0, 4 );
				}

				$upgade_date = $release->get_start_date()->add( new DateInterval( "P{$days}D" ) );

				\ITELIC\Update::create( $activation, $release, $upgade_date );
			}

			if ( $release->get_status() == \ITELIC\Release::STATUS_ARCHIVED ) {
				$release->set_status( \ITELIC\Release::STATUS_ACTIVE );
				$release->archive();
			}

			$notify->tick();
		}

		$notify->finish();
	}

	/**
	 * Delete an update record.
	 *
	 * ## Options
	 *
	 * <ID>
	 * : Update ID
	 *
	 * @param $args
	 * @param $assoc_args
	 */
	public function delete( $args, $assoc_args ) {

		list( $object ) = $args;

		$object = $this->fetcher->get_check( $object );

		try {
			$object->delete();
		}
		catch ( Exception $e ) {
			WP_CLI::error( $e->getMessage() );
		}

		WP_CLI::success( "Update deleted." );
	}

	/**
	 * Get data to display for a single object.
	 *
	 * @param \ITELIC\Update $object
	 * @param bool           $raw
	 *
	 * @return array
	 */
	protected function get_fields_for_object( \ITELIC\Update $object, $raw = false ) {
		return array(
			'ID'               => $object->get_pk(),
			'activation'       => $object->get_activation()->get_pk(),
			'location'         => $object->get_activation()->get_location(),
			'release'          => $raw ? $object->get_release()->get_pk() : $object->get_release()->get_version(),
			'update_date'      => $object->get_update_date()->format( DateTime::ISO8601 ),
			'previous_version' => $object->get_previous_version()
		);
	}
}

WP_CLI::add_command( 'itelic update', 'ITELIC_Update_Command' );