<?php
/**
 * Template file for the renew product purchase requirement
 *
 * @author Iron Bound Designs
 * @since  1.0
 */

$product = it_exchange_get_product( \ITELIC\get_current_product_id() );

$query = new \ITELIC_API\Query\Keys(array(
	'customer' => it_exchange_get_current_customer_id(),
	'product'  => $product->ID
));

$keys = $query->get_results();
?>

<style type="text/css">
	.renew-product-wrapper {
		background: #F6F6F6;
		padding: 1em;
	}

	#itelic-key-to-renew {
		display: block;
		max-width: 250px;
		white-space: nowrap;
	}
</style>

<div class="it-exchange-sw-processing it-exchange-sw-processing-renew-product">

	<form method="POST" class="it-exchange-sw-renew-product">
		<div class="renew-product-wrapper">

			<label for="itelic-key-to-renew"><?php _e( "Select Key to Renew", ITELIC\Plugin::SLUG ); ?></label>
			<select id="itelic-key-to-renew">
				<?php foreach ( $keys as $key ): ?>
					<?php if ( $key->get_expires() !== null ) : ?>
						<option value="<?php echo esc_attr( $key->get_key() ); ?>">
							<?php echo $key->get_key(); ?>
						</option>
					<?php endif; ?>
				<?php endforeach; ?>
			</select>

			<input type="hidden" name="itelic_nonce" value="<?php echo wp_create_nonce( 'itelic_renew_product_sw' ); ?>">
			<input type="hidden" name="itelic_product" value="<?php echo esc_attr( $product->ID ); ?>">
			<input type="submit" class="itelic-submit" name="itelic_purchase_another" value="<?php esc_attr_e( "Purchase Another", ITELIC\Plugin::SLUG ); ?>">
			<input type="submit" class="itelic-submit" name="itelic_renew" value="<?php esc_attr_e( "Renew", ITELIC\Plugin::SLUG ); ?>">
		</div>
	</form>
</div>