<?php
/**
 * Renewal WP CLI command.
 *
 * @author      Iron Bound Designs
 * @since       1.0
 * @copyright   2015 (c) Iron Bound Designs.
 * @license     GPLv2
 */

/**
 * Class ITELIC_Renewal_Command
 */
class ITELIC_Renewal_Command extends \WP_CLI\CommandWithDBObject {

	protected $obj_type = 'renewal';
	protected $obj_id_key = 'id';

	/**
	 * @var ITELIC_Fetcher
	 */
	protected $fetcher;

	/**
	 * @var \Faker\Generator
	 */
	protected $faker;

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->fetcher = new ITELIC_Fetcher( '\ITELIC\Renewal' );
		$this->faker   = \Faker\Factory::create();
	}

	/**
	 * Get a renewal object.
	 *
	 * ## Options
	 *
	 * <ID>
	 * : Renewal ID
	 *
	 * [--fields=<fields>]
	 * : Return designated object fields.
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
	 * Get a list of renewals
	 *
	 * ## Options
	 *
	 * [--<field>=<value>]
	 * : Include additional query args in keys query.
	 *
	 * [--fields=<fields>]
	 * : Return designated object fields.
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
			'id' => 'DESC'
		);

		$results = itelic_get_renewals( $query_args );

		$items = array();

		foreach ( $results as $item ) {
			$items[] = $this->get_fields_for_object( $item, \WP_CLI\Utils\get_flag_value( $assoc_args, 'raw', false ) );
		}

		if ( empty( $assoc_args['fields'] ) ) {
			$assoc_args['fields'] = array(
				'id',
				'key',
				'renewal_date',
				'expired_date',
				'transaction',
				'revenue'
			);
		}

		$formatter = $this->get_formatter( $assoc_args );
		$formatter->display_items( $items );
	}

	/**
	 * Generate renewal records.
	 *
	 * ## Options
	 *
	 * <rate>
	 * : Renewal rate as a percentage. Ex: 50 or 35
	 *
	 * [--product=<product>]
	 * : Only generate renewals for a certain product.
	 *
	 * @param $args
	 * @param $assoc_args
	 */
	public function generate( $args, $assoc_args ) {

		list( $rate ) = $args;

		if ( $rate < 1 || $rate > 100 ) {
			WP_CLI::error( "Usage: 1 < <rate> <= 100" );
		}

		$query_args = array(
			'status' => \ITELIC\Key::EXPIRED
		);

		if ( ( $p = \WP_CLI\Utils\get_flag_value( $assoc_args, 'product' ) ) ) {
			$query_args['product'] = $p;
		}

		$keys = itelic_get_keys( $query_args );

		$notify = \WP_CLI\Utils\make_progress_bar( 'Generating renewals.', count( $keys ) );

		foreach ( $keys as $key ) {
			if ( rand( 0, 100 ) <= $rate ) {

				$min = $key->get_expires();
				$min->sub( new DateInterval( 'P15D' ) );

				$max = $key->get_expires();
				$max->add( new DateInterval( 'P30D' ) );

				$txn = itelic_create_renewal_transaction( array(
					'key'  => $key->get_key(),
					'date' => $this->faker->dateTimeBetween( $min, $max )->format( 'Y-m-d H:i:s' )
				) );

				if ( is_wp_error( $txn ) ) {
					WP_CLI::error( $txn );
				}

				$key->renew( $txn );
			}

			$notify->tick();
		}

		$notify->finish();
	}

	/**
	 * Delete a renewal object.
	 *
	 * ## Options
	 *
	 * <ID>
	 * : Renewal ID
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

		WP_CLI::success( "Renewal deleted." );
	}

	/**
	 * Get data to display for a single object.
	 *
	 * @param \ITELIC\Renewal $object
	 * @param bool            $raw
	 *
	 * @return array
	 */
	protected function get_fields_for_object( \ITELIC\Renewal $object, $raw = false ) {

		if ( $object->get_transaction() ) {
			$transaction = it_exchange_get_transaction_order_number( $object->get_transaction() );
		} else {
			$transaction = 'Manual';
		}

		return array(
			'id'           => $object->get_pk(),
			'key'          => $object->get_key()->get_key(),
			'renewal_date' => $object->get_renewal_date()->format( DateTime::ISO8601 ),
			'expired_date' => $object->get_key_expired_date()->format( DateTime::ISO8601 ),
			'transaction'  => $transaction,
			'revenue'      => $object->get_revenue( ! $raw )
		);
	}
}

WP_CLI::add_command( 'itelic renewal', 'ITELIC_Renewal_Command' );