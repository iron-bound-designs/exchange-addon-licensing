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

	expiration.datepicker({
		prevText  : '',
		nextText  : '',
		dateFormat: expiration.data('format')
	});

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
});