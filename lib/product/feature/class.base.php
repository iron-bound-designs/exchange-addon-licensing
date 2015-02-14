<?php
/**
 * Base Product Feature
 *
 * @author Iron Bound Designs
 * @since  1.0
 */

/**
 * Class ITELIC_Product_Feature_Base
 *
 * Base product feature for licensing. This controls the versions of the software released,
 * as well as the ReadMe parser. If variants is not enabled, this will also present an
 * input to configure the number of times this product's license can be activated.
 *
 * @since 1.0
 */
class ITELIC_Product_Feature_Base extends IT_Exchange_Product_Feature_Abstract {

	/**
	 * Constructor.
	 */
	public function __construct() {
		$args = array(
			'slug'          => 'licensing',
			'description'   => __( "Manage product licenses.", ITELIC::SLUG ),
			'metabox_title' => __( "Licensing", ITELIC::SLUG ),
			'product_types' => array( 'digital-downloads-product-type' )
		);

		parent::IT_Exchange_Product_Feature_Abstract( $args );
	}

	/**
	 * This echos the feature metabox.
	 *
	 * @since 1.0
	 *
	 * @param WP_Post $post
	 */
	function print_metabox( $post ) {

		?>

		<p><?php echo $this->description; ?></p>

		<label for="itelic-limit"><?php _e( "Activation Limit", ITELIC::SLUG ); ?></label>
		<input type="number" name="itelic[limit]" id="itelic-limit" min="0">
		<p class="description"><?php _e( "How many times can this license be activated. Leave blank for unlimited.", ITELIC::SLUG ); ?></p>

		<label for="itelic-key-type"><?php _e( "Key Type", ITELIC::SLUG ); ?></label>
		<select id="itelic-key-type" name="itelic[key-type]">

			<option value=""><?php _e( "Select a Key Type", ITELIC::SLUG ); ?></option>

			<?php foreach ( itelic_get_key_types() as $slug => $type ): ?>
				<option value="<?php echo esc_attr( $slug ); ?>"><?php echo itelic_get_key_type_name( $slug ); ?></option>
			<?php endforeach; ?>
		</select>

	<?php
	}

	/**
	 * This saves the value.
	 *
	 * @since 1.0
	 */
	function save_feature_on_product_save() {

		// Abort if we don't have a product ID
		$product_id = empty( $_POST['ID'] ) ? false : $_POST['ID'];
		if ( ! $product_id ) {
			return;
		}


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
	function save_feature( $product_id, $new_value, $options = array() ) {
		// TODO: Implement save_feature() method.
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
	function get_feature( $existing, $product_id, $options = array() ) {
		// TODO: Implement get_feature() method.
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
	function product_has_feature( $result, $product_id, $options = array() ) {
		// TODO: Implement product_has_feature() method.
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