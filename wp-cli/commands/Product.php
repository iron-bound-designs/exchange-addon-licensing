<?php
/**
 * CLI Product Command
 *
 * @author      Iron Bound Designs
 * @since       1.0
 * @copyright   2015 (c) Iron Bound Designs.
 * @license     GPLv2
 */

/**
 * Class ITELIC_Product_Command
 */
class ITELIC_Product_Command extends \WP_CLI\CommandWithDBObject {

	protected $obj_type = 'product';
	protected $obj_fields = array(
		'ID',
		'post_title',
		'base-price',
		'version',
		'key-type',
		'limit'
	);

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->fetcher = new ITELIC_Product_Fetcher();
	}

	/**
	 * Get a product by ID.
	 *
	 * ## Options
	 *
	 * <ID>
	 * : Product ID
	 *
	 * [--fields=<fields>]
	 * : Return designated option fields.
	 *
	 * [--format=<format>]
	 * : Accepted values: table, json, csv. Default: table
	 *
	 * @param $args
	 * @param $assoc_args
	 */
	public function get( $args, $assoc_args ) {

		list( $ID ) = $args;

		$product = $this->fetcher->get_check( $ID );

		$formatter = $this->get_formatter( $assoc_args );
		$formatter->display_item( $this->get_fields_for_object( $product ) );
	}

	/**
	 * Get a product's activation limits.
	 *
	 * ## Options
	 *
	 * <ID>
	 * : Product ID
	 *
	 * [--format=<format>]
	 * : Accepted values: table, json, csv. Default: table
	 *
	 * @param $args
	 * @param $assoc_args
	 *
	 * @subcommand get-limits
	 */
	public function get_limits( $args, $assoc_args ) {

		list( $ID ) = $args;

		$product = $this->fetcher->get_check( $ID );

		$items = $this->get_variant_limit_for_product( $product );

		$assoc_args['fields'] = array( 'combo', 'limit', 'hash' );

		$formatter = $this->get_formatter( $assoc_args );
		$formatter->display_items( $items );
	}

	/**
	 * Get a product's key type settings.
	 *
	 * ## Options
	 *
	 * <ID>
	 * : Product ID
	 *
	 * [--format=<format>]
	 * : Accepted values: table, json, csv. Default: table
	 *
	 * @param $args
	 * @param $assoc_args
	 *
	 * @subcommand get-key-type
	 */
	public function get_key_type( $args, $assoc_args ) {

		list( $ID ) = $args;

		$this->fetcher->get_check( $ID );

		$type = it_exchange_get_product_feature( $ID, 'licensing', array( 'field' => 'key-type' ) );
		$item = it_exchange_get_product_feature( $ID, 'licensing', array( 'field' => "type.$type" ) );

		if ( ! is_array( $item ) ) {
			$item = array();
		}

		$item = array( 'type' => $type ) + $item;

		$assoc_args['fields'] = array_keys( $item );

		$formatter = $this->get_formatter( $assoc_args );
		$formatter->display_item( $item );
	}

	/**
	 * Update a product.
	 *
	 * ## Options
	 *
	 * <ID>
	 * : Product ID
	 *
	 * --<field>=<value>
	 * : One or more fields to update.
	 * Accepted: online-software, update-file, variants-enabled, base-price,
	 * limit, key-type
	 *
	 * @param $args
	 * @param $assoc_args
	 */
	public function update( $args, $assoc_args ) {

		list( $ID ) = $args;

		$this->fetcher->get_check( $ID );

		$white_list = array(
			'online-software',
			'update-file',
			'variants-enabled',
			'base-price',
			'limit',
			'key-type'
		);

		foreach ( $assoc_args as $arg => $val ) {
			if ( ! in_array( $arg, $white_list ) ) {
				WP_CLI::error( sprintf( "Invalid update param '%s'", $arg ) );
			}
		}

		$key_types = itelic_get_key_types();

		if ( isset( $assoc_args['key-type'] ) && ! isset( $key_types[ $assoc_args['key-type'] ] ) ) {
			WP_CLI::error( sprintf( "Invalid key type '%s'", $assoc_args['key-type'] ) );
		}

		parent::_update( $args, $assoc_args, function ( $params ) use ( $ID ) {

			if ( isset( $params['variants-enabled'] ) ) {
				$params['enabled_variant_activations'] = $params['variants-enabled'];
			}

			if ( isset( $params['base-price'] ) ) {
				it_exchange_update_product_feature( $ID, 'base-price', $params['base-price'] );
			}

			return it_exchange_update_product_feature( $ID, 'licensing', $params );
		} );
	}

	/**
	 * Update a product's activation limits.
	 *
	 * ## Options
	 *
	 * <ID>
	 * : Product ID
	 *
	 * --<field>=<value>
	 * : One or more hashes to update.
	 *
	 * @param $args
	 * @param $assoc_args
	 *
	 * @subcommand update-limits
	 */
	public function update_limits( $args, $assoc_args ) {

		list( $ID ) = $args;

		$this->fetcher->get_check( $ID );

		$limits = it_exchange_get_product_feature( $ID, 'licensing', array(
			'field' => 'activation_variant'
		) );

		if ( empty( $limits ) ) {
			WP_CLI::error( "No variant activation limits exist." );
		}

		foreach ( $assoc_args as $hash => $val ) {

			if ( ! isset( $limits[ $hash ] ) ) {
				WP_CLI::error( sprintf( "Invalid hash '%s'.", $hash ) );
			}

			$val = (int) $val;

			if ( empty( $val ) ) {
				$val = '';
			}

			$limits[ $hash ] = $val;
		}

		it_exchange_update_product_feature( $ID, 'licensing', array(
			'activation_variant' => $limits
		) );

		WP_CLI::success( "Activation limits updated." );
	}

	/**
	 * Get a product's key type settings.
	 *
	 * ## Options
	 *
	 * <ID>
	 * : Product ID
	 *
	 * --<field>=<value>
	 * : One or more fields to update.
	 *
	 * @param $args
	 * @param $assoc_args
	 *
	 * @subcommand update-key-type
	 */
	public function update_key_type( $args, $assoc_args ) {

		list( $ID ) = $args;

		$this->fetcher->get_check( $ID );

		$type = it_exchange_get_product_feature( $ID, 'licensing', array( 'field' => 'key-type' ) );

		$current = it_exchange_get_product_feature( $ID, 'licensing', array( 'field' => "type.$type" ) );

		if ( ! is_array( $current ) ) {
			$current = array();
		}

		$new = wp_parse_args( $assoc_args, $current );

		it_exchange_update_product_feature( $ID, 'licensing', $new, array( 'key-type' => $type ) );

		WP_CLI::success( 'Key type settings updated.' );
	}

	/**
	 * Get all products with licensing enabled.
	 *
	 * ## Options
	 *
	 * [--<field>=<value>]
	 * : Pass additional parameters to products query.
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

		$products = itelic_get_products_with_licensing_enabled( $assoc_args );

		$items = array();

		foreach ( $products as $product ) {
			$items[] = $this->get_fields_for_object( $product );
		}

		$formatter = $this->get_formatter( $assoc_args );
		$formatter->display_items( $items );
	}

	/**
	 * Create a product with licensing enabled.
	 *
	 * ## Options
	 *
	 * <title>
	 * : Product Title
	 *
	 * <price>
	 * : Product price. Formatted as a float.
	 *
	 * --file=<file>
	 * : Attachment ID used for download
	 *
	 * --limit=<limit>
	 * : Activation limit. Variants are not supported. Pass 0 for unlimited.
	 *
	 * [--key-type=<key-type>]
	 * : Key type. Default: random.
	 *
	 * [--online-software=<online-software>]
	 * : Enable online software tools like remote deactivation. Default: true
	 *
	 * [--version=<version>]
	 * : Initial version. Default: 1.0
	 *
	 * [--description=<description>]
	 * : Short product description. 3-5 sentences max.
	 *
	 * @param $args
	 * @param $assoc_args
	 */
	public function create( $args, $assoc_args ) {

		list( $title, $price ) = $args;

		$fn = array( $this, 'create_product' );

		parent::_create( $args, $assoc_args, function ( $params ) use ( $title, $price, $fn ) {
			return call_user_func( $fn, $title, $price, $params );
		} );
	}

	/**
	 * Generate products.
	 *
	 * ## Options
	 *
	 * [--file=<file>]
	 * : Specify which files to used. By default pulls random zips from DB.
	 *
	 * [--count=<count>]
	 * : How many products to generate. Default: 15
	 *
	 * @param $args
	 * @param $assoc_args
	 */
	public function generate( $args, $assoc_args ) {

		$count = \WP_CLI\Utils\get_flag_value( $assoc_args, 'count', 15 );

		if ( ! \WP_CLI\Utils\get_flag_value( $assoc_args, 'file' ) ) {

			/**
			 * @var \wpdb $wpdb
			 */
			global $wpdb;

			$results = $wpdb->get_results( $wpdb->prepare(
				"SELECT ID FROM $wpdb->posts WHERE post_type = 'attachment'
				 AND post_mime_type = 'application/zip' LIMIT %d",
				$count ) );

			$files = array();

			foreach ( $results as $result ) {
				$files[] = $result->ID;
			}
		} else {

			if ( get_post_type( $assoc_args['file'] ) != 'attachment' ) {
				WP_CLI::error( "Invalid file post type." );
			}

			$files = array( $assoc_args['file'] );
		}

		if ( empty( $files ) ) {
			WP_CLI::error( 'No files exist.' );
		}

		$notify = \WP_CLI\Utils\make_progress_bar( 'Generating products.', $count );

		$faker = \Faker\Factory::create();

		$results = array();

		$limits = array( '-', '2', '2', '5', '5', '10' );

		for ( $i = 0; $i < $count; $i ++ ) {

			$title = $faker->catchPhrase . ' software';

			$price = itelic_purebell( 44, 199, 45 );
			$price = floatval( intval( $price ) );

			$index = array_rand( $files );
			$file  = get_post( $files[ $index ] );
			$zip   = get_attached_file( $file->ID );

			if ( ! file_exists( $zip ) ) {
				unset( $files[ $index ] );

				if ( empty( $files ) ) {
					WP_CLI::error( 'No files exist.' );
				}
			}

			$new_name = str_replace( array(' ', '/'), '-', strtolower( $title ) );
			$new_name .= '-1.0.zip';

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

			$params = array(
				'description' => $faker->realText(),
				'limit'       => $limits[ array_rand( $limits ) ],
				'file'        => $file
			);

			$recurring = array(
				array(
					'interval'  => 'month',
					'count'     => 1,
					'frequency' => 10
				),
				array(
					'interval'  => 'month',
					'count'     => 6,
					'frequency' => 20
				),
				array(
					'interval'  => 'none',
					'frequency' => 30
				),
				array(
					'interval'  => 'year',
					'count'     => 1,
					'frequency' => 100
				),
			);

			$rand = rand( 0, 100 );

			foreach ( $recurring as $option ) {
				if ( $rand <= $option['frequency'] ) {

					if ( $option['interval'] != 'none' ) {
						$params['interval']       = $option['interval'];
						$params['interval-count'] = $option['count'];
					}

					break;
				}
			}

			$results[] = $this->create_product( $title, $price, $params );

			$notify->tick();
		}

		$notify->finish();

		foreach ( $results as $result ) {
			if ( is_wp_error( $result ) ) {
				WP_CLI::error( $result, false );
			}
		}
	}

	/**
	 * Create a product.
	 *
	 * @param string $title
	 * @param float  $price
	 * @param array  $params
	 *
	 * @return WP_Error|int
	 */
	protected function create_product( $title, $price, $params ) {

		$file = get_post( $params['file'] );

		if ( get_post_type( $file ) != 'attachment' ) {
			return new WP_Error( 'invalid_file', "Invalid file. Post type is not attachment." );
		}

		$limit = (int) $params['limit'];

		if ( empty( $limit ) ) {
			$limit = '';
		}

		$key_type        = \WP_CLI\Utils\get_flag_value( $params, 'key-type', 'random' );
		$online_software = \WP_CLI\Utils\get_flag_value( $params, 'online-software', 'true' );
		$version         = \WP_CLI\Utils\get_flag_value( $params, 'version', '1.0' );
		$description     = \WP_CLI\Utils\get_flag_value( $params, 'description', '' );

		$product = it_exchange_add_product( array(
			'type'          => 'digital-downloads-product-type',
			'title'         => $title,
			'base-price'    => $price,
			'description'   => $description,
			'show_in_store' => true
		) );

		if ( ! $product ) {
			return new WP_Error( 'product_error', 'Product not created.' );
		}

		$faker    = \Faker\Factory::create();
		$new_date = $faker->dateTimeBetween( '-2 years', '-1 months' )->format( 'Y-m-d H:i:s' );

		wp_update_post( array(
			'ID'            => $product,
			'post_date'     => $new_date,
			'post_date_gmt' => get_gmt_from_date( $new_date )
		) );

		$download_data = array(
			'product_id' => $product,
			'source'     => wp_get_attachment_url( $file->ID ),
			'name'       => $file->post_title
		);

		it_exchange_update_product_feature( $product, 'downloads', $download_data );

		$downloads   = it_exchange_get_product_feature( $product, 'downloads' );
		$download_id = key( $downloads );

		$feature_data = array(
			'enabled'         => true,
			'online-software' => $online_software == 'true' ? true : false,
			'limit'           => $limit,
			'key-type'        => $key_type,
			'update-file'     => $download_id,
			'version'         => $version
		);

		it_exchange_update_product_feature( $product, 'licensing', $feature_data );

		if ( isset( $params['interval'] ) ) {
			it_exchange_update_product_feature( $product, 'recurring-payments', 'on' );
			it_exchange_update_product_feature( $product, 'recurring-payments', $params['interval'], array(
				'setting' => 'interval'
			) );
			it_exchange_update_product_feature( $product, 'recurring-payments', $params['interval-count'], array(
				'setting' => 'interval-count'
			) );
		}

		$product = it_exchange_get_product( $product );

		$type      = \ITELIC\Release::TYPE_MAJOR;
		$status    = \ITELIC\Release::STATUS_PAUSED;
		$changelog = '<ul><li>' . __( "Initial release.", \ITELIC\Plugin::SLUG ) . '</li></ul>';

		try {
			$release = \ITELIC\Release::create( $product, $file, $version, $type, $status, $changelog );

			$when = new DateTime( $product->post_date );
			$release->activate( $when );
		}
		catch ( Exception $e ) {
			return new WP_Error( 'release_exception', $e->getMessage() );
		}

		update_post_meta( $product->ID, '_itelic_first_release', $release->get_pk() );

		return $product->ID;
	}

	/**
	 * Get fields for object.
	 *
	 * @param \ITELIC\Product $product
	 *
	 * @return array
	 */
	protected function get_fields_for_object( \ITELIC\Product $product ) {

		$data = get_object_vars( $product );

		$data['base-price'] = it_exchange_get_product_feature( $product->ID, 'base-price' );

		$base = it_exchange_get_product_feature( $product->ID, 'licensing' );

		$data['online-software'] = $base['online-software'] ? 'yes' : 'no';

		$data['version']     = $base['version'];
		$data['key-type']    = $base['key-type'];
		$data['update-file'] = ( $p = get_post( $base['update-file'] ) ) ? $p->post_title : '-';

		$data['variants-enabled'] = $base['enabled_variant_activations'] ? 'yes' : 'no';

		if ( $base['enabled_variant_activations'] ) {
			$data['limit'] = 'variant';
		} else {
			$data['limit'] = $base['limit'] ? $base['limit'] : 'Unlimited';
		}

		$data['changelog'] = $product->get_changelog( 10 );

		return $data;
	}

	/**
	 * Get variant limit value for a product.
	 *
	 * @param \ITELIC\Product $product
	 *
	 * @return array|string
	 */
	protected function get_variant_limit_for_product( \ITELIC\Product $product ) {

		if ( ! function_exists( 'it_exchange_variants_addon_get_product_feature_controller' ) ) {
			return 'disabled';
		}

		$c = it_exchange_variants_addon_get_product_feature_controller( $product->ID, 'base-price', array(
			'setting' => 'variants'
		) );

		$hashes = it_exchange_get_product_feature( $product->ID, 'licensing', array(
				'field' => 'activation_variant'
			)
		);

		$return = array();

		foreach ( $c->post_meta as $hash => $variant ) {

			$limit = empty( $hashes[ $hash ] ) ? 'Unlimited' : $hashes[ $hash ];

			$return[] = array(
				'combo' => $variant['combos_title'],
				'limit' => $limit,
				'hash'  => $hash
			);
		}

		return $return;
	}

}

class ITELIC_Product_Fetcher extends WP_CLI\Fetchers\Base {

	/**
	 * @var string $msg Error message to use when invalid data is provided
	 */
	protected $msg = "Could not find the product with ID %d.";

	/**
	 * Get a post object by ID
	 *
	 * @param int $arg
	 *
	 * @return \ITELIC\Product|false
	 */
	public function get( $arg ) {
		$product = itelic_get_product( $arg );

		if ( ! $product ) {
			return false;
		}

		if ( ! it_exchange_product_has_feature( $product->ID, 'licensing' ) ) {
			return false;
		}

		return $product;
	}
}

WP_CLI::add_command( 'itelic product', 'ITELIC_Product_Command' );