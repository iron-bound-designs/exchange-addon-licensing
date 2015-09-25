<?php
/**
 * Product upgrades feature.
 *
 * @author      Iron Bound Designs
 * @since       1.0
 * @copyright   2015 (c) Iron Bound Designs.
 * @license     GPLv2
 */

namespace ITELIC\Product\Feature;

use ITELIC\Plugin;
use ITELIC\Upgrades\Path_Builder;
use ITELIC\Upgrades\Upgrade_Path;

/**
 * Class Upgrades
 *
 * @package ITELIC\Product\Feature
 */
class Upgrades extends \IT_Exchange_Product_Feature_Abstract {

	/**
	 * Constructor.
	 */
	public function __construct() {
		$args = array(
			'slug'          => 'licensing-upgrades',
			'description'   => __( "Upgrade paths allow your customers to change their license type at any time.", Plugin::SLUG ),
			'metabox_title' => __( "Licensing Upgrades", Plugin::SLUG ),
			'product_types' => array( 'digital-downloads-product-type' )
		);

		parent::IT_Exchange_Product_Feature_Abstract( $args );
	}

	/**
	 * This echos the feature metabox.
	 *
	 * @since 1.0
	 *
	 * @param \WP_Post $post
	 */
	function print_metabox( $post ) {

		$product = itelic_get_product( $post );

		$data   = it_exchange_get_product_feature( isset( $post->ID ) ? $post->ID : 0, $this->slug );
		$hidden = $data['enable'] ? '' : ' hide-if-js';

		if ( empty( $data['base_path'] ) ) {
			$path = $this->create_base_upgrade_path( $product );
		} else {
			$path = Upgrade_Path::get( $data['base_path'] );
		}
		?>

		<p><?php echo $this->description; ?></p>

		<p>
			<input type="checkbox" id="itelic-upgrade-enable" name="itelic_upgrade[enable]" <?php checked( true, $data['enable'] ); ?>>
			<label for="itelic-upgrade-enable"><?php _e( "Enable upgrade paths for this product." ); ?></label>
		</p>

		<div class="itelic-upgrade-settings<?php echo esc_attr( $hidden ); ?>">

			<?php echo $this->render_upgrade_path_html( $path ); ?>

		</div>
		<?php
	}

	/**
	 * Render the HTML for a single upgrade path.
	 *
	 * @since 1.0
	 *
	 * @param Upgrade_Path $path
	 *
	 * @return string
	 */
	protected function render_upgrade_path_html( Upgrade_Path $path ) {

		ob_start();
		?>

		<div class="upgrade-path">

			<span class="upgrade-path-product-title"><?php echo $path->get_product()->post_title; ?></span>

			<span class="upgrade-path-variant-title"><?php echo $path->get_variant_title(); ?></span>

			<span class="upgrade-path-activation-limit">
				<?php echo it_exchange_get_product_feature( $path->get_product()->ID, 'licensing', array(
					'field'    => 'limit',
					'for_hash' => $path->get_upgrade_variant_hash()
				) ); ?>
			</span>
		</div>

		<?php

		return ob_get_clean();

	}

	/**
	 * Get the first variant. The one with the last activations.
	 *
	 * @param \IT_Exchange_Product $product
	 *
	 * @return Upgrade_Path
	 */
	protected function create_base_upgrade_path( \IT_Exchange_Product $product ) {

		$controller = $this->get_variants_controller( $product->ID );

		$activation_limits = it_exchange_get_product_feature( $product->ID, 'licensing', array( 'field' => 'activation_variant' ) );

		$min      = null;
		$min_hash = null;

		foreach ( $controller->post_meta as $hash => $meta ) {

			$limit = isset( $activation_limits[ $hash ] ) ? (int) $activation_limits[ $hash ] : 0;

			if ( $min === null && $limit !== 0 ) {
				$min      = $limit;
				$min_hash = $hash;
			} elseif ( $min !== null && $limit !== 0 && $limit < $min ) {
				$min      = $limit;
				$min_hash = $hash;
			}
		}

		$path_builder = new Path_Builder( $product );
		$path_builder->upgrade_to( $product, $min_hash );

		$path = $path_builder->create();

		it_exchange_update_product_feature( $product->ID, 'licensing-upgrades', array(
			'base_path' => $path->get_ID()
		) );

		return $path;
	}

	/**
	 * Convert a combo to a hash.
	 *
	 * @since 1.0
	 *
	 * @param $combo array
	 *
	 * @return null|string
	 */
	protected function combo_to_hash( $combo ) {
		if ( function_exists( 'it_exchange_variants_addon_get_selected_variants_id_hash' ) ) {

			$variants_to_hash = array();

			foreach ( $combo as $id ) {
				if ( $variant = it_exchange_variants_addon_get_variant( $id ) ) {
					$variants_to_hash[ empty( $variant->post_parent ) ? $id : $variant->post_parent ] = $id;
				}
			}

			$hash = it_exchange_variants_addon_get_selected_variants_id_hash( $variants_to_hash );
		} else {
			$hash = null;
		}

		return $hash;
	}

	/**
	 * Get the variants controller.
	 *
	 * @since 1.0
	 *
	 * @param $product_id int
	 *
	 * @return \IT_Exchange_Variants_Addon_Product_Feature_Combos|null
	 */
	protected function get_variants_controller( $product_id ) {

		if ( function_exists( 'it_exchange_variants_addon_get_product_feature_controller' ) ) {
			$controller = it_exchange_variants_addon_get_product_feature_controller( $product_id, 'base-price', array(
				'setting' => 'variants'
			) );
		} else {
			$controller = null;
		}

		return $controller;
	}

	/**
	 * This saves the value.
	 *
	 * @since 1.0
	 */
	public function save_feature_on_product_save() {

		// Abort if we don't have a product ID
		$product_id = empty( $_POST['ID'] ) ? false : $_POST['ID'];

		if ( ! $product_id ) {
			return;
		}

		$data           = $_POST['itelic_upgrade'];
		$data['enable'] = isset( $data['enable'] ) ? it_exchange_str_true( $data['enable'] ) : false;

		it_exchange_update_product_feature( $product_id, $this->slug, $data );
	}

	/**
	 * This updates the feature for a product
	 *
	 * @since 1.0
	 *
	 * @param integer $product_id the product id
	 * @param array   $new_value  the new value
	 * @param array   $options
	 *
	 * @return boolean
	 */
	public function save_feature( $product_id, $new_value, $options = array() ) {

		$prev_values = it_exchange_get_product_feature( $product_id, $this->slug );
		$values      = \ITUtility::merge_defaults( $new_value, $prev_values );

		return update_post_meta( $product_id, '_it_exchange_itelic_upgrade', $values );
	}

	/**
	 * Return the product's features
	 *
	 * @since 1.0
	 *
	 * @param mixed   $existing   the values passed in by the WP Filter API. Ignored here.
	 * @param integer $product_id the WordPress post ID
	 * @param array   $options
	 *
	 * @return string product feature
	 */
	public function get_feature( $existing, $product_id, $options = array() ) {
		$defaults = array(
			'enable'    => false,
			'base_path' => ''
		);

		$values   = get_post_meta( $product_id, '_it_exchange_itelic_upgrade', true );
		$raw_meta = \ITUtility::merge_defaults( $values, $defaults );

		if ( ! isset( $options['field'] ) ) { // if we aren't looking for a particular field
			return $raw_meta;
		}
		$field = $options['field'];
		if ( isset( $raw_meta[ $field ] ) ) { // if the field exists with that name just return it
			return $raw_meta[ $field ];
		} else if ( strpos( $field, "." ) !== false ) { // if the field name was passed using array dot notation
			$pieces  = explode( '.', $field );
			$context = $raw_meta;
			foreach ( $pieces as $piece ) {
				if ( ! is_array( $context ) || ! array_key_exists( $piece, $context ) ) {
					// error occurred
					return null;
				}
				$context = &$context[ $piece ];
			}

			return $context;
		} else {
			return null; // we didn't find the data specified
		}
	}

	/**
	 * Does the product have the feature?
	 *
	 * @since 1.0
	 *
	 * @param mixed   $result Not used by core
	 * @param integer $product_id
	 * @param array   $options
	 *
	 * @return boolean
	 */
	public function product_has_feature( $result, $product_id, $options = array() ) {
		return ! (bool) it_exchange_get_product_feature( $product_id, $this->slug, array( 'field' => 'disable' ) );
	}

	/**
	 * Does the product support this feature?
	 *
	 * This is different than if it has the feature, a product can
	 * support a feature but might not have the feature set.
	 *
	 * @since 1.0
	 *
	 * @param mixed   $result Not used by core
	 * @param integer $product_id
	 * @param array   $options
	 *
	 * @return boolean
	 */
	function product_supports_feature( $result, $product_id, $options = array() ) {
		$product_type = it_exchange_get_product_type( $product_id );

		return it_exchange_product_type_supports_feature( $product_type, $this->slug );
	}
}