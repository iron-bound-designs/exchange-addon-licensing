<?php
if ( it_exchange_get_next_purchase_requirement_property( 'slug' ) != 'itelic-renew-product' ) {
	return;
}

$session = it_exchange_get_session_data( 'purchase_req_itelic-renew-product' );
?>

<form method="POST" action="<?php echo esc_attr( it_exchange_get_page_url( 'checkout' ) ); ?>" class="itelic-renew-keys-checkout">

	<h3><?php echo _n( "Renew your license key", "Renew your license keys", count( $session ), ITELIC\Plugin::SLUG ); ?></h3>

	<ul>
		<?php foreach ( $session as $product => $license ): ?>
			<?php $product = str_replace( 'p', '', $product ); ?>
			<?php if ( $license !== null ): ?>
				<?php continue; ?>
			<?php else: ?>
				<li>
					<label for="itelic-renew-product-<?php echo esc_attr( $product ); ?>">
						<?php echo itelic_get_product( $product )->post_title; ?>
					</label>
					<select id="itelic-renew-product-<?php echo esc_attr( $product ); ?>" name="itelic_key[<?php echo esc_attr( $product ); ?>]">
						<?php $keys = itelic_get_keys( array(
							'customer' => it_exchange_get_current_customer_id(),
							'product'  => $product
						) ); ?>

						<?php foreach ( $keys as $key ): ?>
							<?php if ( $key->get_expires() !== null ) : ?>
								<option value="<?php echo esc_attr( $key->get_key() ); ?>">
									<?php echo $key->get_key(); ?>
								</option>
							<?php endif; ?>
						<?php endforeach; ?>
					</select>
				</li>
			<?php endif; ?>
		<?php endforeach; ?>
	</ul>

	<?php wp_nonce_field( 'itelic-renew-keys-checkout' ); ?>
	<input type="submit" name="itelic_renew_keys_checkout" value="<?php esc_attr_e( "Renew", ITELIC\Plugin::SLUG ); ?>">
</form>