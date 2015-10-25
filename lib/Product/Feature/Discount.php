<?php
/**
 * Product Feature for Discounts
 *
 * @author    Iron Bound Designs
 * @since     1.0
 * @license   AGPL
 * @copyright Iron Bound Designs, 2015.
 */

namespace ITELIC\Product\Feature;

use ITELIC\Plugin;
use ITELIC\Renewal\Discount as Renewal_Discount;

/**
 * Class Discount
 * @package ITELIC\Product\Feature
 */
class Discount extends \IT_Exchange_Product_Feature_Abstract {

	/**
	 * Constructor.
	 */
	public function __construct() {
		$args = array(
			'slug'          => 'licensing-discount',
			'description'   => __( "Manage renewal discounts.", Plugin::SLUG ),
			'metabox_title' => __( "Licensing Discount", Plugin::SLUG ),
			'product_types' => array( 'digital-downloads-product-type' )
		);

		parent::__construct( $args );
	}

	/**
	 * This echos the feature metabox.
	 *
	 * @since 1.0
	 *
	 * @param \WP_Post $post
	 */
	function print_metabox( $post ) {
		$data   = it_exchange_get_product_feature( isset( $post->ID ) ? $post->ID : 0, $this->slug, array( 'db' => false ) );
		$hidden = $data['override'] ? '' : ' hide-if-js';

		$types = array(
			Renewal_Discount::TYPE_FLAT    => __( "Flat", Plugin::SLUG ),
			Renewal_Discount::TYPE_PERCENT => __( "Percent", Plugin::SLUG )
		);
		?>

		<p><?php echo $this->description; ?></p>

		<p>
			<input type="checkbox" id="itelic-discount-disable" name="itelic_discount[disable]" <?php checked( true, $data['disable'] ); ?>>
			<label for="itelic-discount-disable"><?php _e( "Disable renewal discounts for this product." ); ?></label>
		</p>

		<p>
			<input type="checkbox" id="itelic-discount-override" name="itelic_discount[override]"
				<?php checked( true, $data['override'] ); ?> <?php disabled( $data['disable'] ) ?>>
			<label for="itelic-discount-override"><?php _e( "Override the renewal discount settings for this product." ); ?></label>
		</p>

		<div class="itelic-discount-settings<?php echo esc_attr( $hidden ); ?>">

			<label for="itelic-discount-type"><?php _e( "Discount Type", Plugin::SLUG ); ?></label>
			<select id="itelic-discount-type" name="itelic_discount[type]" <?php disabled( $data['disable'] ) ?>>
				<?php foreach ( $types as $slug => $label ): ?>
					<option value="<?php echo esc_attr( $slug ); ?>" <?php selected( $data['type'], $slug ); ?>>
						<?php echo $label; ?>
					</option>
				<?php endforeach; ?>
			</select>

			<label for="itelic-discount-amount"><?php _e( "Discount Amount", Plugin::SLUG ); ?></label>
			<input type="number" id="itelic-discount-amount" name="itelic_discount[amount]"
			       value="<?php echo esc_attr( $data['amount'] ); ?>" <?php disabled( $data['disable'] ) ?>>

			<label for="itelic-discount-expiry"><?php _e( "Valid Until", Plugin::SLUG ); ?></label>
			<input type="number" id="itelic-discount-expiry" name="itelic_discount[expiry]"
			       value="<?php echo esc_attr( $data['expiry'] ); ?>" <?php disabled( $data['disable'] ) ?>>

			<p class="description"><?php _e( "how many days after the key expires should the renewal discount be offered.", Plugin::SLUG ); ?></p>
		</div>
		<?php
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

		$data = $_POST['itelic_discount'];

		$data['disable']  = isset( $data['disable'] ) ? it_exchange_str_true( $data['disable'] ) : false;
		$data['override'] = isset( $data['override'] ) ? it_exchange_str_true( $data['override'] ) : false;

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

		$values['amount'] = (float) $values['amount'];
		$values['expiry'] = absint( $values['expiry'] );

		return update_post_meta( $product_id, '_it_exchange_itelic_renewal_discount', $values );
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
			'override' => false,
			'disable'  => false,
			'type'     => 'percent',
			'amount'   => '',
			'expiry'   => ''
		);

		$values   = get_post_meta( $product_id, '_it_exchange_itelic_renewal_discount', true );
		$raw_meta = \ITUtility::merge_defaults( $values, $defaults );

		if ( ! $raw_meta['override'] && empty( $options['db'] ) ) {
			$settings = it_exchange_get_option( 'addon_itelic', true );

			$raw_meta['type']   = $settings['renewal-discount-type'];
			$raw_meta['amount'] = $settings['renewal-discount-amount'];
			$raw_meta['expiry'] = $settings['renewal-discount-expiry'];
		}

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