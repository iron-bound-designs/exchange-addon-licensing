<?php do_action( 'it_exchange_content_licenses_begin_license_activation_loop' ); ?>

<?php $parts = array( 'activation-location' );

if ( it_exchange( 'license', 'can-remote-deactivate' ) ) {
	$parts[] = 'activation-deactivate-link';
}
?>

<?php foreach (
	it_exchange_get_template_part_elements( "content_licenses", "activations", $parts ) as $detail
): ?>

	<?php it_exchange_get_template_part( 'content-licenses/elements/' . $detail ); ?>

<?php endforeach; ?>

<?php do_action( 'it_exchange_content_licenses_end_license_activation_loop' ); ?>
