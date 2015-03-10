<?php do_action( 'it_exchange_content_licenses_before_wrap' ); ?>
<div id="it-exchange-licenses" class="it-exchange-wrap it-exchange-account">
	<?php do_action( 'it_exchange_content_licenses_begin_wrap' ); ?>
	<?php it_exchange_get_template_part( 'messages' ); ?>
	<?php it_exchange( 'customer', 'menu' ); ?>

	<?php if ( it_exchange( 'licenses', 'has-licenses' ) ) : ?>
		<div class="it-exchange-licenses-list">
			<div class="it-exchange-licenses-list-header">
				<?php it_exchange_get_template_part( 'content-licenses/elements/licenses-list-header' ); ?>
			</div>
			<div class="it-exchange-licenses-list-body">
				<?php it_exchange_get_template_part( 'content-licenses/loops/licenses' ); ?>
			</div>
		</div>
	<?php else : ?>
		<?php it_exchange_get_template_part( 'content-licenses/elements/no-licenses-found' ); ?>
	<?php endif; ?>
	<?php do_action( 'it_exchange_content_licenses_end_wrap' ); ?>
</div>
<?php do_action( 'it_exchange_content_licenses_after_wrap' ); ?>