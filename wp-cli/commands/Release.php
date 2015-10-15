<?php
/**
 * Release CLI command.
 *
 * @author    Iron Bound Designs
 * @since     1.0
 * @license   AGPL
 * @copyright Iron Bound Designs, 2015.
 */

/**
 * Class ITELIC_Release_Command
 */
class ITELIC_Release_Command extends \WP_CLI\CommandWithDBObject {

	protected $obj_type = 'release';
	protected $obj_id_key = 'ID';

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
		$this->fetcher = new ITELIC_Fetcher( '\ITELIC\Release' );
		$this->faker   = \Faker\Factory::create();
	}

	/**
	 * Get an activation record.
	 *
	 * ## Options
	 *
	 * <ID>
	 * : Release ID
	 *
	 * [--show-changes]
	 * : Include the changelog.
	 *
	 * [--fields=<fields>]
	 * : Limit the output to specific object fields.
	 *
	 * [--format=<format>]
	 * : Accepted values: table, json, csv. Default: table
	 *
	 * [--raw]
	 * : Return raw values. IDs instead of human readable names.
	 *
	 * @param array $args
	 * @param array $assoc_args
	 */
	public function get( $args, $assoc_args ) {

		list( $release ) = $args;

		$object = $this->fetcher->get_check( $release );

		$fields = $this->get_fields_for_object( $object, \WP_CLI\Utils\get_flag_value( $assoc_args, 'raw', false ) );

		if ( empty( $assoc_args['fields'] ) ) {

			if ( empty( $assoc_args['show-changes'] ) ) {
				unset( $fields['changelog'] );
			}

			$assoc_args['fields'] = array_keys( $fields );
		}

		$formatter = $this->get_formatter( $assoc_args );
		$formatter->display_item( $fields );
	}

	/**
	 * Create a release.
	 *
	 * [<file>]
	 * : Read changelog from <file> Passing `-` as the filename will cause
	 * changelog to be read from STDIN
	 *
	 * --product=<product>
	 * : ID of the product being released.
	 *
	 * --version=<version>
	 * : Version number of the release being created.
	 *
	 * --file=<file>
	 * : ID of the attachment being used for the software download.
	 *
	 * --type=<type>
	 * : Type of release. Accepted values: major, minor, security, pre-release
	 *
	 * [--status=<status>]
	 * : Status of the release. Accepted values: draft, active. Default: draft
	 *
	 * [--changelog=<changelog>]
	 * : What changed in this release. Version number and date omitted. HTML
	 * allowed.
	 *
	 * [--edit]
	 * : Immediately open system's editor to write or edit changelog.
	 *
	 * [--porcelain]
	 * : Output just the new release ID.
	 *
	 * @param $args
	 * @param $assoc_args
	 */
	public function create( $args, $assoc_args ) {

		if ( ! empty( $args[0] ) ) {
			$assoc_args['changelog'] = $this->read_from_file_or_stdin( $args[0] );
		}

		if ( \WP_CLI\Utils\get_flag_value( $assoc_args, 'edit' ) ) {
			$input = \WP_CLI\Utils\get_flag_value( $assoc_args, 'post_content', '' );

			$output = \WP_CLI\Utils\launch_editor_for_input( $input, 'WP-CLI: New Release Changelog' );

			if ( $output ) {
				$assoc_args['changelog'] = $output;
			} else {
				$assoc_args['changelog'] = $input;
			}
		}

		$assoc_args = wp_parse_args( $assoc_args, array(
			'status'    => \ITELIC\Release::STATUS_DRAFT,
			'changelog' => ''
		) );

		parent::_create( $args, $assoc_args, function ( $params ) {

			$product = itelic_get_product( $params['product'] );

			if ( ! $product ) {
				WP_CLI::error( 'Invalid product ID.' );
			}

			$version = $params['version'];
			$file    = get_post( $params['file'] );

			if ( ! $file ) {
				WP_CLI::error( 'Invalid post ID for download file.' );
			}

			$type   = $params['type'];
			$status = $params['status'];

			if ( ! in_array( $status, array(
				\ITELIC\Release::STATUS_ACTIVE,
				\ITELIC\Release::STATUS_DRAFT
			) )
			) {
				WP_CLI::error( "Invalid status." );
			}

			$changelog = $params['changelog'];

			try {

				$args = array(
					'product'   => $product,
					'file'      => $file,
					'version'   => $version,
					'type'      => $type,
					'status'    => $status,
					'changelog' => $changelog
				);

				$release = itelic_create_release( $args );

				if ( is_wp_error( $release ) ) {
					return $release;
				}

				return $release->get_pk();
			}
			catch ( Exception $e ) {
				WP_CLI::error( $e->getMessage() );
			}
		} );
	}

	/**
	 * Activate a release.
	 *
	 * ## Options
	 *
	 * <ID>
	 * : Release ID to activate.
	 *
	 * @param $args
	 * @param $assoc_args
	 */
	public function activate( $args, $assoc_args ) {

		list( $ID ) = $args;

		$release = $this->fetcher->get_check( $ID );

		if ( $release->get_status() != \ITELIC\Release::STATUS_DRAFT ) {
			WP_CLI::error( "Only draft releases can be activated." );
		}

		$release->activate();

		WP_CLI::success( 'Release activated.' );
	}

	/**
	 * Pause a release.
	 *
	 * ## Options
	 *
	 * <ID>
	 * : Release ID to pause.
	 *
	 * @param $args
	 * @param $assoc_args
	 */
	public function pause( $args, $assoc_args ) {

		list( $ID ) = $args;

		$release = $this->fetcher->get_check( $ID );

		if ( $release->get_status() != \ITELIC\Release::STATUS_ACTIVE ) {
			WP_CLI::error( "Only active releases can be paused." );
		}

		$release->pause();

		WP_CLI::success( 'Release paused.' );
	}

	/**
	 * Archive a release.
	 *
	 * ## Options
	 *
	 * <ID>
	 * : Release ID to archive.
	 *
	 * @param $args
	 * @param $assoc_args
	 */
	public function archive( $args, $assoc_args ) {

		list( $ID ) = $args;

		$release = $this->fetcher->get_check( $ID );

		$active = \ITELIC\Release::STATUS_ACTIVE;
		$paused = \ITELIC\Release::STATUS_PAUSED;

		if ( ! in_array( $release->get_status(), array( $active, $paused ) ) ) {
			WP_CLI::error( "Only active or paused releases can be archived." );
		}

		$release->archive();

		WP_CLI::success( 'Release archived.' );
	}

	/**
	 * Edit a release's changelog.
	 *
	 * ## Options
	 *
	 * <ID>
	 * : Release ID's changelog to edit
	 *
	 * [<file>]
	 * : Read changelog from <file> Passing `-` as the filename will cause
	 * changelog to be read from STDIN
	 *
	 * [--changes=<changes>]
	 * : Text of changes. HTML is allowed.
	 *
	 * [--mode=<mode>]
	 * : Mode to use when editing. Accepted values: replace, append. Default:
	 * replace
	 *
	 * [--edit]
	 * : Immediately open system's editor to write or edit changelog.
	 *
	 *
	 * @param $args
	 * @param $assoc_args
	 *
	 * @subcommand edit-changelog
	 * @alias      edit-changes
	 */
	public function edit_changelog( $args, $assoc_args ) {

		list( $ID ) = $args;

		$release = $this->fetcher->get_check( $ID );

		if ( isset( $args[1] ) ) {
			$changes = $this->read_from_file_or_stdin( $args[1] );
		} else {
			$changes = \WP_CLI\Utils\get_flag_value( $assoc_args, 'changes', '' );
		}

		if ( \WP_CLI\Utils\get_flag_value( $assoc_args, 'edit' ) ) {

			if ( empty( $changes ) ) {
				$changes = $release->get_changelog();
			}

			$output = \WP_CLI\Utils\launch_editor_for_input( $changes, 'WP-CLI: Edit Release Changelog' );

			if ( $output ) {
				$changes = $output;
			}
		}

		$mode = \WP_CLI\Utils\get_flag_value( $assoc_args, 'mode', 'replace' );

		if ( ! in_array( $mode, array( 'replace', 'append' ) ) ) {
			WP_CLI::error( "Invalid value for mode" );
		}

		$release->set_changelog( $changes, $mode );

		WP_CLI::success( "Changelog updated." );
	}

	/**
	 * Show stats for this release.
	 *
	 * ## Options
	 *
	 * <ID>
	 * : Release ID
	 *
	 * [--percent-complete]
	 * : Return progress of the release as a percentage.
	 *
	 * [--updates-by-day]
	 * : Return the number of updates per-day for the first 14 days.
	 *
	 * [--previous-versions]
	 * : Return the top 5 previous versions before updating to this release
	 *
	 * [--format=<format>]
	 * : Formatting for updates and previous versions stats.
	 * Accepted values: table, json, csv. Default: table
	 *
	 * @param $args
	 * @param $assoc_args
	 */
	public function stats( $args, $assoc_args ) {

		list( $ID ) = $args;

		$release = $this->fetcher->get_check( $ID );

		if ( $release->get_status() == \ITELIC\Release::STATUS_DRAFT ) {
			WP_CLI::error( "No stats available for draft releases." );
		}

		if ( \WP_CLI\Utils\get_flag_value( $assoc_args, 'percent-complete' ) ) {
			$updated = $release->get_total_updated();
			$total   = $release->get_total_active_activations();

			if ( $total != 0 ) {
				$percent = number_format( $updated / $total * 100 ) . '%';
			} else {
				$percent = '100%';
			}

			WP_CLI::line( sprintf( "Percent Complete: %s\n", $percent ) );
		}

		if ( \WP_CLI\Utils\get_flag_value( $assoc_args, 'previous-versions' ) ) {
			$top5 = $release->get_top_5_previous_versions();

			$total_count = 0;

			foreach ( $top5 as $version ) {
				$total_count += $version->c;
			}

			$items = array();

			foreach ( $top5 as $version ) {

				$items[] = array(
					'version'    => "v{$version->v}",
					'count'      => $version->c,
					'percentage' => number_format( $version->c / $total_count * 100 ) . '%'
				);
			}

			$assoc_args['fields'] = array(
				'version',
				'count',
				'percentage'
			);

			$formatter = $this->get_formatter( $assoc_args );

			WP_CLI::line( sprintf( "Top %d versions updated from\n", count( $items ) ) );

			$formatter->display_items( $items );

			WP_CLI::line();
		}

		if ( \WP_CLI\Utils\get_flag_value( $assoc_args, 'updates-by-day' ) ) {

			$raw = $release->get_first_14_days_of_upgrades();

			$now = new \DateTime();

			$diff = $release->get_start_date()->diff( $now );

			$days = min( 14, max( $diff->days + 1, 1 ) );

			$data = array();

			$day = clone $release->get_start_date();

			$sql_df = 'Y-m-d';

			for ( $i = 0; $i < $days; $i ++ ) {

				$key = $day->format( $sql_df );

				if ( isset( $raw[ $key ] ) ) {
					$data[ $key ] = $raw[ $key ];
				} else {
					$data[ $key ] = 0;
				}

				$day = $day->add( new \DateInterval( 'P1D' ) );
			}

			$items = array();

			foreach ( $data as $date => $count ) {
				$items[] = array(
					'day'   => date( DateTime::ISO8601, strtotime( $date ) ),
					'count' => $count
				);
			}

			$assoc_args['fields'] = array(
				'day',
				'count',
			);

			$formatter = $this->get_formatter( $assoc_args );

			WP_CLI::line( sprintf( "First %d days of updates.\n", count( $data ) ) );

			$formatter->display_items( $items );

			WP_CLI::line();
		}
	}

	/**
	 * Get a list of releases.
	 *
	 * ## Options
	 *
	 * [--<field>=<value>]
	 * : Additional parameters passed to the releases query.
	 *
	 * [--show-changes]
	 * : Include the changelog.
	 *
	 * [--fields=<fields>]
	 * : Limit the output to specific object fields.
	 *
	 * [--format=<format>]
	 * : Accepted values: table, json, csv. Default: table
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
			'page'           => 1,
		) );

		$query_args['order'] = array(
			'ID' => 'DESC'
		);

		$results = itelic_get_releases( $query_args );

		$items = array();

		foreach ( $results as $item ) {
			$items[] = $this->get_fields_for_object( $item, \WP_CLI\Utils\get_flag_value( $assoc_args, 'raw', false ) );
		}

		if ( empty( $assoc_args['fields'] ) ) {
			$assoc_args['fields'] = array(
				'ID',
				'product',
				'version',
				'type',
				'status',
				'download',
				'started'
			);

			if ( ! empty( $assoc_args['show-changes'] ) ) {
				$assoc_args['fields'][] = 'changelog';
			}
		}

		$formatter = $this->get_formatter( $assoc_args );
		$formatter->display_items( $items );
	}

	/**
	 * Generate releases.
	 *
	 * @param $args
	 * @param $assoc_args
	 */
	public function generate( $args, $assoc_args ) {

		$products = itelic_get_products_with_licensing_enabled();

		$notify = \WP_CLI\Utils\make_progress_bar( 'Generating Releases', count( $products ) );

		$now = new DateTime();

		foreach ( $products as $product ) {

			$major = get_post_meta( $product->ID, '_itelic_first_release', true );
			$major = itelic_get_release( $major );

			$minor_releases = rand( 3, 9 );

			$last = $major;

			while ( $last->get_start_date() < $now ) {

				for ( $i = 0; $i < $minor_releases; $i ++ ) {

					// this exponentially spaces out minor releases
					$days = rand( $i ^ 2, ( $i + 1 ) ^ 2 );

					$start = $last->get_start_date()->add( new DateInterval( "P{$days}D" ) );

					// we don't want to create any releases in the future
					if ( $start > $now ) {
						break 2;
					}

					$last = $this->generate_minor_release( $last );
					$last->activate( $start );
				}

				// between 3 and 7 weeks after a minor release to a major release
				$days_before_major = rand( 3 * 7, 7 * 7 );

				$start = $last->get_start_date()->add( new DateInterval( "P{$days_before_major}D" ) );

				if ( $start > $now ) {
					break;
				}

				$last = $this->generate_major_release( $last );
				$last->activate( $start );
			}

			$notify->tick();
		}

		$notify->finish();
	}

	/**
	 * Generate a major release.
	 *
	 * @param \ITELIC\Release $latest
	 *
	 * @return \ITELIC\Release|null
	 */
	protected function generate_major_release( \ITELIC\Release $latest ) {

		$product = $latest->get_product();
		$type    = \ITELIC\Release::TYPE_MAJOR;

		$version = \WP_CLI\Utils\increment_version( $latest->get_version(), 'major' );

		$download = $latest->get_download();
		$name     = $download->post_name . "-$version.zip";

		$file = itelic_rename_file( $download, $name );

		$fixes  = rand( 7, 30 );
		$tweaks = rand( 6, 14 );
		$adds   = rand( 8, 20 );

		$changelog = $this->generate_changelog( $adds, $tweaks, $fixes );

		return itelic_create_release( array(
			'product'   => $product,
			'file'      => $file,
			'version'   => $version,
			'type'      => $type,
			'changelog' => $changelog
		) );
	}

	/**
	 * Generate a minor release.
	 *
	 * @param \ITELIC\Release $latest
	 *
	 * @return \ITELIC\Release|null
	 */
	protected function generate_minor_release( \ITELIC\Release $latest ) {

		$product = $latest->get_product();
		$type    = \ITELIC\Release::TYPE_MINOR;

		$version = \WP_CLI\Utils\increment_version( $latest->get_version(), 'minor' );

		$download = $latest->get_download();
		$name     = $download->post_name . "-$version.zip";

		$file = itelic_rename_file( $download, $name );

		$fixes  = rand( 3, 15 );
		$tweaks = rand( 1, 10 );
		$adds   = rand( 0, 2 );

		$changelog = $this->generate_changelog( $adds, $tweaks, $fixes );

		return itelic_create_release( array(
			'product'   => $product,
			'file'      => $file,
			'version'   => $version,
			'type'      => $type,
			'changelog' => $changelog
		) );
	}

	/**
	 * Generate a changelog.
	 *
	 * @param int $adds
	 * @param int $tweaks
	 * @param int $fixes
	 *
	 * @return string
	 */
	protected function generate_changelog( $adds, $tweaks, $fixes ) {

		$html = '<ul>';

		for ( $i = 0; $i < $adds; $i ++ ) {
			$html .= "<li>{$this->generate_changelog_entry('add')}</li>";
		}

		for ( $i = 0; $i < $tweaks; $i ++ ) {
			$html .= "<li>{$this->generate_changelog_entry('tweak')}</li>";
		}

		for ( $i = 0; $i < $fixes; $i ++ ) {
			$html .= "<li>{$this->generate_changelog_entry('fix')}</li>";
		}

		return "$html</ul>";
	}

	/**
	 * Generate a changelog entry.
	 *
	 * @param $type
	 *
	 * @return string
	 */
	protected function generate_changelog_entry( $type ) {

		$type = ucfirst( $type );

		return "$type: {$this->faker->sentence(rand(5,10))}";
	}

	/**
	 * Delete a release key.
	 *
	 * @param $args
	 * @param $assoc_args
	 *
	 * @synopsis <key>
	 */
	public function delete( $args, $assoc_args ) {

		list( $object ) = $args;

		$object = $this->fetcher->get_check( $object );

		if ( $object->get_status() != \ITELIC\Release::STATUS_DRAFT ) {
			WP_CLI::error( "Only draft releases can be deleted." );
		}

		try {
			$object->delete();
		}
		catch ( Exception $e ) {
			WP_CLI::error( $e->getMessage() );
		}

		WP_CLI::success( "Release deleted." );
	}

	/**
	 * Get data to display for a single key.
	 *
	 * @param \ITELIC\Activation|\ITELIC\Release $release
	 * @param bool                               $raw
	 *
	 * @return array
	 */
	protected function get_fields_for_object( \ITELIC\Release $release, $raw = false ) {

		if ( $release->get_start_date() ) {
			$started = $release->get_start_date()->format( DateTime::ISO8601 );
		} else {
			$started = '-';
		}

		return array(
			'ID'        => $release->get_ID(),
			'product'   => $raw ? $release->get_product()->ID : $release->get_product()->post_title,
			'version'   => $release->get_version(),
			'type'      => $release->get_type( ! $raw ),
			'status'    => $release->get_status( ! $raw ),
			'download'  => $raw ? $release->get_download()->ID : $release->get_download()->post_title,
			'started'   => $started,
			'changelog' => $release->get_changelog()
		);
	}

	/**
	 * Read post content from file or STDIN
	 *
	 * @param string $arg Supplied argument
	 *
	 * @return string
	 */
	private function read_from_file_or_stdin( $arg ) {
		if ( $arg !== '-' ) {
			$readfile = $arg;
			if ( ! file_exists( $readfile ) || ! is_file( $readfile ) ) {
				\WP_CLI::error( "Unable to read content from $readfile." );
			}
		} else {
			$readfile = 'php://stdin';
		}

		return file_get_contents( $readfile );
	}
}

/**
 * Class ITELIC_Release_Meta_Command
 */
class ITELIC_Release_Meta_Command extends \WP_CLI\CommandWithMeta {

	protected $meta_type = 'itelic_release';

	/**
	 * Check that the activation ID exists
	 *
	 * @param int
	 *
	 * @return int
	 */
	protected function check_object_id( $object_id ) {
		$fetcher    = new ITELIC_Fetcher( '\ITELIC\Release' );
		$activation = $fetcher->get_check( $object_id );

		return $activation->get_pk();
	}
}

WP_CLI::add_command( 'itelic release', 'ITELIC_Release_Command' );
WP_CLI::add_command( 'itelic release meta', 'ITELIC_Release_Meta_Command' );