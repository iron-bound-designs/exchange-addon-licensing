
<?php do_action( 'it_exchange_content_licenses_before_licenses_loop' ); ?>

<?php while ( it_exchange( 'licenses', 'licenses' ) ): ?>

	<?php do_action( 'it_exchange_content_licenses_begin_licenses_loop' ); ?>

	<div class="it-exchange-license">
		<?php it_exchange_get_template_part( 'content-licenses/loops/license' ); ?>
	</div>

	<?php do_action( 'it_exchange_content_licenses_end_licenses_loop' ); ?>

<?php endwhile; ?>

<?php do_action( 'it_exchange_content_licenses_after_licenses_loop' ); ?>