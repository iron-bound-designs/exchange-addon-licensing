/**
 * JS used for AJAX requests on the licenses list table page.
 *
 * @author Iron Bound Designs
 * @since 1.0
 */

jQuery(document).ready(function ($) {

	$(".extend a").click(function (e) {
		e.preventDefault();

		var link = $(this);

		var data = {
			action: 'itelic_admin_licenses_list_extend',
			key   : link.data('key'),
			nonce : link.data('nonce')
		};

		$.post(ITELIC.ajax, data, function (response) {
			if (!response.success) {
				alert(response.data.message);
			} else {
				var td = link.closest('td');
				$(".expires-date", td).text(response.data.expires);
			}
		});
	});

	$(".max_active a").click(function (e) {
		e.preventDefault();

		var link = $(this);

		var data = {
			action: 'itelic_admin_licenses_list_max',
			key   : link.data('key'),
			nonce : link.data('nonce'),
			dir   : link.data('dir')
		};

		$.post(ITELIC.ajax, data, function (response) {
			if (!response.success) {
				alert(response.data.message);
			} else {
				var td = link.closest('td');
				$(".max-active-count", td).text(response.data.max);
			}
		});
	});
});