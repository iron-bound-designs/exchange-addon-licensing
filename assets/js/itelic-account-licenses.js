jQuery(document).ready(function ($) {

	$(".it-exchange-license-key-manage a").click(function (e) {

		e.preventDefault();

		var link = $(this);

		var licenseContainer = link.closest('.it-exchange-license');
		var activations = $(".it-exchange-item-data-bottom", licenseContainer);

		if (activations.is(':visible')) {
			activations.hide();
		} else {
			activations.show();
		}
	});

	$(".deactivate-location").click(function (e) {

		e.preventDefault();

		var link = $(this);

		var originalText = link.text();
		var working = link.data('working');
		var listElement = link.closest('li');

		var data = {
			action: 'itelic_account_licenses_deactivate_location',
			id    : link.data('id'),
			nonce : link.data('nonce')
		};

		var i = 0;
		link.text(working);

		/**
		 * Animate the working text to append 3 '.'
		 * then revert.
		 *
		 * @type {number}
		 */
		var loading = setInterval(function () {

			link.append(".");
			i++;

			if (i == 4) {
				link.text(working);
				i = 0;
			}

		}, 500);

		$.post(ITELIC.ajax, data, function (response) {
			if (!response.success) {
				alert(response.data.message);

				clearInterval(loading);
				link.text(originalText);

			} else {

				var ul = listElement.parent();
				listElement.remove();

				if ($("li", ul).length == 0) {
					ul.remove();
				}
			}
		});
	});

	$(".itelic-activate-form").submit(function (e) {

		e.preventDefault();

		var location = $(".remote-activate-url", $(this)).val();
		var submit = $(".remote-activate", $(this));

		if (location.length == 0) {
			alert(ITELIC.location_required);

			return;
		}

		var data = {
			action  : 'itelic_account_licenses_activate',
			key     : submit.data('key'),
			location: location,
			nonce   : submit.data('nonce')
		};

		submit.prop('disabled', true);

		$.post(ITELIC.ajax, data, function (response) {

			if (!response.success) {
				alert(response.data.message);

				submit.prop('disabled', false);
			} else {
				window.location.reload();
			}
		});
	});
});