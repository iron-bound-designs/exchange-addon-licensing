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
	 * @param array $args
	 * @param array $assoc_args
	 */
	public function get( $args, $assoc_args ) {

		list( $ID ) = $args;

		$object = $this->fetcher->get_check( $ID );

		$fields = $this->get_fields_for_object( $object );

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

		$query = new \ITELIC_API\Query\Updates( $query_args );

		$results = $query->get_results();

		$items = array();

		foreach ( $results as $item ) {
			$items[] = $this->get_fields_for_object( $item );
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
	 *
	 * @return array
	 */
	protected function get_fields_for_object( \ITELIC\Update $object ) {
		return array(
			'ID'               => $object->get_pk(),
			'activation'       => $object->get_activation()->get_pk(),
			'location'         => $object->get_activation()->get_location(),
			'release'          => $object->get_release()->get_pk(),
			'update_date'      => $object->get_update_date()->format( DateTime::ISO8601 ),
			'previous_version' => $object->get_previous_version()
		);
	}
}

WP_CLI::add_command( 'itelic update', 'ITELIC_Update_Command' );