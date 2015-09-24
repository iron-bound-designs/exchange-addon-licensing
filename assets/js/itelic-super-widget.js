/**
 * JS used in the super widget to power checkout requirements.
 *
 * @since 1.0
 */

jQuery(document).ready(function ($) {

	$(document).on('submit', 'form.it-exchange-sw-itelic-renew', function (e) {

		e.preventDefault();

		var quantity = 1;
		var product = $('input[name="it-exchange-renew-product"]').val();
		var additionalFields = $('input', this).serializeArray();
		var additionalFieldsString = '';

		/**
		 * Grab any additional fields from the form.
		 */
		$.each(additionalFields, function (index, field) {

			if (typeof field.name != 'undefined' && typeof field.value != 'undefined' &&
				field.name != 'it-exchange-action' && field.name != 'it-exchange-buy-now' &&
				field.name != 'it-exchange-renew-product' && field.name != '_wp_http_referer') {
				additionalFieldsString += '&' + field.name + '=' + field.value;
			}
		});

		/**
		 * Fire the AJAX request.
		 *
		 * When complete, proceed to the checkout state.
		 */
		$.get(itExchangeSWAjaxURL + '&sw-action=renew_key&sw-product=' + product + '&sw-quantity=' + quantity + additionalFieldsString, function (data) {

			itExchangeGetSuperWidgetState('checkout', product);

			itExchange.hooks.doAction('itExchangeSW.RenewProduct');
		});
	});

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


	function getParameterByName(name) {
		name = name.replace(/[\[]/, "\\[").replace(/[\]]/, "\\]");
		var regex = new RegExp("[\\?&]" + name + "=([^&#]*)"),
			results = regex.exec(location.search);
		return results === null ? "" : decodeURIComponent(results[1].replace(/\+/g, " "));
	}
});