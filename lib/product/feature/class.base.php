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

		add_action( 'wp_ajax_itelic_get_key_type_settings', array( $this, 'get_key_type_settings' ) );
	}

	/**
	 * This echos the feature metabox.
	 *
	 * @since 1.0
	 *
	 * @param WP_Post $post
	 */
	function print_metabox( $post ) {

		wp_enqueue_script( 'itelic-add-edit-product' );
		wp_localize_script( 'itelic-add-edit-product', 'ITELIC', array(
			'ajax'    => admin_url( 'admin-ajax.php' ),
			'product' => isset( $post->ID ) ? $post->ID : 0
		) );

		$downloads = it_exchange_get_product_feature( isset( $post->ID ) ? $post->ID : 0, 'downloads' );
		$data      = it_exchange_get_product_feature( isset( $post->ID ) ? $post->ID : 0, $this->slug );
		?>

		<p><?php echo $this->description; ?></p>

		<label for="itelic-limit"><?php _e( "Activation Limit", ITELIC::SLUG ); ?></label>
		<input type="number" name="itelic[limit]" id="itelic-limit" min="0" value="<?php echo esc_attr( $data['limit'] ); ?>">

		<p class="description"><?php _e( "How many times can this license be activated. Leave blank for unlimited.", ITELIC::SLUG ); ?></p>

		<label for="itelic-key-type"><?php _e( "Key Type", ITELIC::SLUG ); ?></label>
		<select id="itelic-key-type" name="itelic[key-type]">

			<option value=""><?php _e( "Select a Key Type", ITELIC::SLUG ); ?></option>

			<?php foreach ( itelic_get_key_types() as $slug => $type ): ?>
				<option value="<?php echo esc_attr( $slug ); ?>" <?php selected( $data['key-type'], $slug ); ?>>
					<?php echo itelic_get_key_type_name( $slug ); ?>
				</option>
			<?php endforeach; ?>
		</select>

		<p class="description"><?php _e( "How should license keys be generated for this product.", ITELIC::SLUG ); ?></p>

		<div id="itelic-key-type-settings">

		</div>

		<label for="itelic-update-file"><?php _e( "Update File", ITELIC::SLUG ); ?></label>
		<select id="itelic-update-file" name="itelic[update-file]">
			<?php foreach ( $downloads as $download ): ?>
				<option value="<?php echo esc_attr( $download['id'] ); ?>"><?php echo $download['name']; ?></option>
			<?php endforeach; ?>
		</select>

		<p class="description"><?php _e( "Select a file to be used for automatic updates.", ITELIC::SLUG ); ?></p>

	<?php
	}

	/**
	 * Get the settings form for a certain key type.
	 *
	 * @since 1.0
	 */
	public function get_key_type_settings() {
		$type    = $_POST['type'];
		$product = $_POST['product'];

		$prefix = "itelic[type][$type]";

		$values = it_exchange_get_product_feature( $product, $this->slug, array( 'field' => "type.$type" ) );

		/**
		 * Fires when the settings form for a key type should be shown.
		 *
		 * @since 1.0
		 *
		 * @param int    $product
		 * @param string $prefix
		 */
		do_action( "it_exchange_itelic_render_key_type_{$type}_settings", $product, $prefix );

		die();
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
		$defaults = array(
			'limit'       => '',
			'key-type'    => '',
			'update-file' => ''
		);

		$values   = get_post_meta( $product_id, '_it_exchange_itelic_feature', true );
		$raw_meta = ITUtility::merge_defaults( $values, $defaults );

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