<?php
/**
 * Product factory.
 *
 * @author Iron Bound Designs
 * @since  1.0
 */

/**
 * Class ITELIC_UnitTest_Factory_For_Products
 */
class ITELIC_UnitTest_Factory_For_Products extends WP_UnitTest_Factory_For_Post {

	/**
	 * Create a product.
	 *
	 * @param array $args
	 *
	 * @return int|WP_Error
	 */
	function create_object( $args ) {

		$args['post_type']   = 'it_exchange_prod';
		$args['post_status'] = 'publish';

		$product_id = parent::create_object( $args );

		if ( is_wp_error( $product_id ) || ! $product_id ) {
			return $product_id;
		}

		$defaults = array(
			'product_type'  => 'digital-downloads-product-type',
			'show_in_store' => true,
			'limit'         => 2,
			'key-type'      => 'random',
			'version'       => '1.0',
			'base-price'    => '99.00',
			'update-file'   => ''
		);

		$args = wp_parse_args( $args, $defaults );

		if ( empty( $args['update-file'] ) ) {

			$name = get_the_title( $product_id );
			$name .= '.zip';

			$attachment_factory = new WP_UnitTest_Factory_For_Attachment();

			$attachment_id = $attachment_factory->create_object( $name, $product_id, array(
				'post_mime_type' => 'application/zip'
			) );

			$download_id = parent::create_object( array(
				'post_type' => 'it_exchange_download'
			) );

			update_post_meta( $download_id, '_it-exchange-download-info', array(
				'source'      => wp_get_attachment_url( $attachment_id ),
				'product_id'  => $product_id,
				'download_id' => $download_id,
				'name'        => $name
			) );

			$args['update-file'] = $download_id;
		}

		if ( ! empty( $args['product_type'] ) ) {
			update_post_meta( $product_id, '_it_exchange_product_type', $args['product_type'] );
		}

		update_post_meta( $product_id, '_it-exchange-visibility', empty( $args['show_in_store'] ) ? 'hidden' : 'visible' );

		it_exchange_update_product_feature( $product_id, 'licensing', array(
			'enabled'     => true,
			'limit'       => $args['limit'],
			'key-type'    => $args['key-type'],
			'version'     => $args['version'],
			'update-file' => $args['update-file']
		) );

		if ( isset( $args['interval'] ) ) {

			it_exchange_update_product_feature( $product_id, 'recurring-payments', 'on' );
			it_exchange_update_product_feature( $product_id, 'recurring-payments', $args['interval'], array(
				'setting' => 'interval'
			) );
			it_exchange_update_product_feature( $product_id, 'recurring-payments', $args['interval-count'], array(
				'setting' => 'interval-count'
			) );
		}

		return $product_id;
	}

	/**
	 * Get a product.
	 *
	 * @param int $post_id
	 *
	 * @return \ITELIC\Product
	 */
	function get_object_by_id( $post_id ) {
		return itelic_get_product( $post_id );
	}
}