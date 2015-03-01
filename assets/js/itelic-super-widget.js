/**
 * JS used in the super widget to power checkout requirements.
 *
 * @since 1.0
 */

jQuery(document).ready(function ($) {

	$(document).on('click', '.itelic-submit', function (e) {

		e.preventDefault();

		var renew;

		if ($(this).attr('name') == 'itelic_renew') {
			renew = 1;
		} else {
			renew = 0;
		}

		var data = {
			action : 'itelic_renew_product_purchase_requirement',
			nonce  : $('input[name="itelic_nonce"]').val(),
			key    : $("#itelic-key-to-renew").val(),
			product: $('input[name="itelic_product"]').val(),
			renew  : renew
		};

		$.post(ITELIC.ajax, data, function (response) {

			if (!response.success) {
				alert(response.data.message);
			}

			itExchangeGetSuperWidgetState('checkout', $('input[name="itelic_product"]').val());
		});
	});
});