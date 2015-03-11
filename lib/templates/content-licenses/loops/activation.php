<?php do_action( 'it_exchange_content_licenses_begin_license_activation_loop' ); ?>

<?php foreach (
	it_exchange_get_template_part_elements( "content_licenses", "activations", array(
		'activation-location',
		'activation-deactivate-link'
	) ) as $detail
): ?>

	<?php it_exchange_get_template_part( 'content-licenses/elements/' . $detail ); ?>

<?php endforeach; ?>

<?php do_action( 'it_exchange_content_licenses_end_license_activation_loop' ); ?>
