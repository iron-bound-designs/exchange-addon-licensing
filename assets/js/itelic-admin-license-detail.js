/**
 * JS used for AJAX requests on the single license detail page.
 *
 * @author Iron Bound Designs
 * @since 1.0
 */

jQuery(document).ready(function ($) {

	$(document).on('click', '#remote-activate-submit', function (e) {

		e.preventDefault();

		var data = {
			action  : 'itelic_admin_licenses_single_activate',
			location: $("#remote-activate-location").val(),
			key     : $("#remote-activate-key").val(),
			nonce   : $("#_wpnonce").val()
		};

		$.post(ITELIC.ajax, data, function (response) {

			if (!response.success) {
				alert(response.data.message);
			} else {
				var html = response.data.html;

				$("#activations-table tr:last").after(html);

				$("#remote-activate-location").val("");
			}
		});
	});

	$(document).on('click', '.deactivate', function (e) {

		e.preventDefault();

		var link = $(this);

		var data = {
			action: 'itelic_admin_licenses_single_deactivate',
			id    : link.data('id'),
			key   : $("#remote-activate-key").val(),
			nonce : link.data('nonce')
		};

		$.post(ITELIC.ajax, data, function (response) {

			if (!response.success) {
				alert(response.data.message);
			} else {
				var html = response.data.html;

				var row = link.closest('tr');
				row.replaceWith(html);
			}
		});
	});

	$(document).on('click', '.remove-item', function (e) {

		e.preventDefault();

		var button = $(this);

		var data = {
			action: 'itelic_admin_licenses_single_delete',
			id    : button.data('id'),
			key   : $("#remote-activate-key").val(),
			nonce : button.data('nonce')
		};

		$.post(ITELIC.ajax, data, function (response) {

			if (!response.success) {
				alert(response.data.message);
			} else {
				var row = button.closest('tr');
				row.remove();
			}
		});
	});
});
