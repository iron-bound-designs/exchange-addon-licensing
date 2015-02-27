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

		var button = $(this);
		button.prop('disabled', true);

		$.post(ITELIC.ajax, data, function (response) {

			button.prop('disabled', false);

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

		var i = 0;
		var originalText = link.text();
		link.text(ITELIC.disabling);

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
				link.text(ITELIC.disabling);
				i = 0;
			}

		}, 500);


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

			clearTimeout(loading);
			link.text(originalText);
		});
	});

	$(document).on('click', '.remove-item', function (e) {

		e.preventDefault();

		var button = $(this);

		var degree = 0, timer;

		rotate();
		function rotate() {

			button.css({WebkitTransform: 'rotate(' + degree + 'deg)'});
			button.css({'-moz-transform': 'rotate(' + degree + 'deg)'});
			timer = setTimeout(function () {
				++degree;
				rotate();
			}, 1);
		}

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

			clearTimeout(timer);
			button.css({WebkitTransform: 'rotate(' + 0 + 'deg)'});
			button.css({'-moz-transform': 'rotate(' + 0 + 'deg)'});
		});
	});
});
