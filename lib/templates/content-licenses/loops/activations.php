<?php do_action( 'it_exchange_content_licenses_before_license_activations_loop' ); ?>
<ul>
	<?php while ( it_exchange( 'license', 'activations' ) ): ?>

		<?php do_action( 'it_exchange_content_licenses_begin_license_activations_loop' ); ?>

		<li class="it-exchange-license-activation">
			<?php it_exchange_get_template_part( 'content-licenses/loops/activation' ); ?>
		</li>

		<?php do_action( 'it_exchange_content_licenses_end_license_activations_loop' ); ?>

	<?php endwhile; ?>

	<?php do_action( 'it_exchange_content_licenses_after_license_activations_loop' ); ?>
</ul>