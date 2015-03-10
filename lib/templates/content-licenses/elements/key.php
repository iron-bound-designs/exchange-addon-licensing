<?php do_action( 'it_exchange_content_licenses_before_key_element' ); ?>
	<span class="it-exchange-license-key">
	<input type="text" readonly aria-labelledby="it-exchange-license-key"
	       value="<?php echo esc_attr( it_exchange( 'license', 'get-key' ) ); ?>">
	</span>
<?php do_action( 'it_exchange_content_licenses_before_after_key_element' ); ?>