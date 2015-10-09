<?php
/**
 * Product Feature for Parsing README's
 *
 * @author Iron Bound Designs
 * @since  1.0
 */

namespace ITELIC\Product\Feature;
use ITELIC\Plugin;

/**
 * Class Readme
 * @package ITELIC\Product\Feature
 */
class Readme extends \IT_Exchange_Product_Feature_Abstract {

	/**
	 * Constructor.
	 */
	public function __construct() {
		$args = array(
			'slug'          => 'licensing-readme',
			'description'   => __( "Manage this WordPress Product's README.", Plugin::SLUG ),
			'metabox_title' => __( "Licensing README", Plugin::SLUG ),
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
		$data   = it_exchange_get_product_feature( isset( $post->ID ) ? $post->ID : 0, $this->slug );
		$hidden = $data['enable'] ? '' : ' hide-if-js';
		?>

		<p><?php echo $this->description; ?></p>

		<p>
			<input type="checkbox" id="itelic-readme-enable" name="itelic_readme[enable]" <?php checked( true, $data['enable'] ); ?>>
			<label for="itelic-readme-enable"><?php _e( "Add a README for this product." ); ?></label>
		</p>

		<div class="itelic-readme-settings<?php echo esc_attr( $hidden ); ?>">

			<label for="itelic-readme-tested"><?php _e( "Tested Up To", Plugin::SLUG ); ?></label>
			<input type="text" id="itelic-readme-tested" name="itelic_readme[tested]" value="<?php echo esc_attr( $data['tested'] ); ?>">

			<p class="description"><?php _e( "Latest version of WordPress your product has been tested with.", Plugin::SLUG ); ?></p>

			<label for="itelic-readme-requires"><?php _e( "Minimum Required Version", Plugin::SLUG ); ?></label>
			<input type="text" id="itelic-readme-requires" name="itelic_readme[requires]" value="<?php echo esc_attr( $data['requires'] ); ?>">

			<p class="description"><?php _e( "Minimum required WordPress version to use your product.", Plugin::SLUG ); ?></p>

			<label for="itelic-readme-author"><?php _e( "Contributors", Plugin::SLUG ); ?></label>
			<input type="text" id="itelic-readme-author" name="itelic_readme[author]" value="<?php echo esc_attr( $data['author'] ); ?>">

			<p class="description"><?php _e( "Comma-separated list of WordPress.org usernames.", Plugin::SLUG ); ?></p>

			<label for="itelic-readme-last-updated"><?php _e( "Last Updated", Plugin::SLUG ); ?></label>
			<input type="text" id="itelic-readme-last-updated" name="itelic_readme[last_updated]"
			       value="<?php echo empty( $data['last_updated'] ) ? '' : $data['last_updated']->format( get_option( 'date_format' ) ); ?>">

			<p class="description"><?php _e( "Date of the last product update.", Plugin::SLUG ); ?></p>

			<label for="itelic-readme-banner-low"><?php _e( "Lo-res Plugin Banner", Plugin::SLUG ); ?></label>
			<input type="url" id="itelic-readme-banner-low" name="itelic_readme[banner_low]" value="<?php echo esc_attr( esc_url( $data['banner_low'] ) ); ?>">

			<p class="description"><?php _e( "Link to banner. 772x250 pixels, either a <b>png</b> or <b>jpg</b>.", Plugin::SLUG ); ?></p>

			<label for="itelic-readme-banner-high"><?php _e( "Hi-res Plugin Banner", Plugin::SLUG ); ?></label>
			<input type="url" id="itelic-readme-banner-high" name="itelic_readme[banner_high]" value="<?php echo esc_attr( esc_url( $data['banner_high'] ) ); ?>">

			<p class="description"><?php _e( "Link to banner. 1544x500 pixels, either a <b>png</b> or <b>jpg</b>.", Plugin::SLUG ); ?></p>
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

		$data           = $_POST['itelic_readme'];
		$data['enable'] = isset( $data['enable'] ) ? it_exchange_str_true( $data['enable'] ) : false;

		$last_updated         = new \DateTime( $data['last_updated'] );
		$data['last_updated'] = $last_updated->getTimestamp();


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

		return update_post_meta( $product_id, '_it_exchange_itelic_readme', $values );
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
			'enable'       => false,
			'tested'       => '',
			'requires'     => '',
			'author'       => '',
			'last_updated' => '',
			'banner_low'   => '',
			'banner_high'  => ''
		);

		$values   = get_post_meta( $product_id, '_it_exchange_itelic_readme', true );
		$raw_meta = \ITUtility::merge_defaults( $values, $defaults );

		if ( $raw_meta['last_updated'] ) {
			$raw_meta['last_updated'] = new \DateTime( "@{$raw_meta['last_updated']}" );
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