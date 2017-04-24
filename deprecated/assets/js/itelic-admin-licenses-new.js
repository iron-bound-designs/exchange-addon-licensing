jQuery(document).ready(function ($) {

	$("#product").select2();
	$("#customer").select2();

	$("#customer-type input").change(function (e) {

		var newForm = $(".new-customer-form");
		var existingForm = $(".existing-customer-form");

		if ($(this).val() == 'new') {
			newForm.show();
			existingForm.hide();
		} else {
			newForm.hide();
			existingForm.show();
		}
	});

	var expiration = $("#expiration");

	if (!Modernizr.touchevents || !Modernizr.inputtypes.date) {

		expiration.prop('type', 'text');

		expiration.datepicker({
			prevText  : '',
			nextText  : '',
			dateFormat: expiration.data('format')
		});
	}

	var manualKeyLink = $("#trigger-manual-key");
	var autoKeyLink = $("#trigger-automatic-key");
	var keyInput = $("#license");

	manualKeyLink.click(function () {
		manualKeyLink.hide();
		autoKeyLink.show();
		keyInput.show();
	});

	autoKeyLink.click(function () {

		manualKeyLink.show();
		autoKeyLink.hide();
		keyInput.hide().val('');

	});

	/**
	 * Format a price.
	 *
	 * @param number
	 * @param decimals
	 * @param dec_point
	 * @param thousands_sep
	 * @returns {string}
	 */
	function it_exchange_number_format(number, decimals, dec_point, thousands_sep) {
		number = (number + '').replace(thousands_sep, ''); //remove thousands
		number = (number + '').replace(dec_point, '.'); //turn number into proper float (if it is an improper float)
		number = (number + '').replace(/[^0-9+\-Ee.]/g, '');
		var n = !isFinite(+number) ? 0 : +number;
		prec = !isFinite(+decimals) ? 0 : Math.abs(decimals);
		sep = (typeof thousands_sep === 'undefined') ? ',' : thousands_sep;
		dec = (typeof dec_point === 'undefined') ? '.' : dec_point;
		s = '',
			toFixedFix = function (n, prec) {
				var k = Math.pow(10, prec);
				return '' + Math.round(n * k) / k;
			};
		// Fix for IE parseFloat(0.55).toFixed(0) = 0;
		s = (prec ? toFixedFix(n, prec) : '' + Math.round(n)).split('.');
		if (s[0].length > 3) {
			s[0] = s[0].replace(/\B(?=(?:\d{3})+(?!\d))/g, sep);
		}
		if ((s[1] || '').length < prec) {
			s[1] = s[1] || '';
			s[1] += new Array(prec - s[1].length + 1).join('0');
		}
		return s.join(dec);
	}

	// Format base price
	$('#paid').on('focusout', function () {
		if ($(this).data('symbol-position') == 'before')
			$(this).val($(this).data('symbol') + it_exchange_number_format($(this).val(), 2, $(this).data('decimals-separator'), $(this).data('thousands-separator')));
		else
			$(this).val(it_exchange_number_format($(this).val(), 2, $(this).data('decimals-separator'), $(this).data('thousands-separator')) + $(this).data('symbol'));
	});
});