<?php do_action( 'it_exchange_content_licenses_before_license_loop' ); ?>

	<div class="it-exchange-item-data-top">
		<?php do_action( 'it_exchange_content_licenses_begin_license_loop_top' ); ?>

		<?php foreach (
			it_exchange_get_template_part_elements( "content_licenses", "fields_top", array(
				'product-name',
				'key',
				'key-status',
				'key-activations',
				'key-expiration',
				'key-manage'
			) ) as $detail
		): ?>

			<?php it_exchange_get_template_part( 'content-licenses/elements/' . $detail ); ?>

		<?php endforeach; ?>

		<?php do_action( 'it_exchange_content_licenses_end_license_loop_top' ); ?>
	</div>

	<div class="it-exchange-item-data-bottom">
		<?php do_action( 'it_exchange_content_licenses_begin_license_loop_bottom' ); ?>

		<?php it_exchange_get_template_part( 'content-licenses/elements/key-renew-link' ); ?>

		<div class="it-exchange-license-activations-list-header">
			<?php it_exchange_get_template_part( 'content-licenses/elements/activations-list-header' ); ?>
		</div>

		<?php if ( it_exchange( 'license', 'has-activations' ) ): ?>

			<?php do_action( 'it_exchange_content_licenses_begin_license_activations_loop' ); ?>

			<div class="it-exchange-license-activations-list-body">
				<?php it_exchange_get_template_part( 'content-licenses/loops/activations' ); ?>
			</div>

			<?php do_action( 'it_exchange_content_licenses_end_license_activations_loop' ); ?>

		<?php else: ?>

			<?php it_exchange_get_template_part( 'content-licenses/elements/no-activations-found' ); ?>

		<?php endif; ?>

		<?php if ( it_exchange( 'license', 'can-remote-activate' ) ): ?>
			<?php it_exchange_get_template_part( 'content-licenses/elements/key-activate' ) ?>
		<?php endif; ?>

		<?php do_action( 'it_exchange_content_licenses_end_license_loop_bottom' ); ?>
	</div>

<?php do_action( 'it_exchange_content_licenses_after_license_loop' ); ?>