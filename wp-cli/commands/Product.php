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

	protected $obj_type = 'post';
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
	 * [--<field>=<field>]
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