<?php do_action( 'it_exchange_content_licenses_begin_license_list_header' ); ?>

<span class="it-exchange-license-product-name it-exchange-license-product-name-header">
	<?php it_exchange( 'license', 'product-name', array( 'format' => 'label' ) ); ?>
</span>

<span class="it-exchange-license-key it-exchange-license-key-header" id="it-exchange-license-key">
	<?php it_exchange( 'license', 'key', array( 'format' => 'label' ) ); ?>
</span>

<span class="it-exchange-license-key-status it-exchange-license-key-status-header">
	<?php it_exchange( 'license', 'status', array( 'format' => 'label' ) ); ?>
</span>

<span class="it-exchange-license-key-activations it-exchange-license-key-activations-header">
	<?php it_exchange( 'license', 'activation-count', array( 'format' => 'label' ) ); ?>
</span>

<span class="it-exchange-license-key-expiration it-exchange-license-key-expiration-header">
	<?php it_exchange( 'license', 'expiration-date', array( 'format' => 'label' ) ); ?>
</span>

<?php do_action( 'it_exchange_content_licenses_end_license_list_header' ); ?>