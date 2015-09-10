/**
 * Add Edit Product Page
 *
 * @author Iron Bound Designs
 * @since 1.0
 */

jQuery(document).ready(function ($) {

	/**
	 * When the changelog textarea is clicked, popup the changelog editor.
	 */
	$("#itelic-changelog").click(function (e) {
		$("#itelic-edit-changelog").val($("#itelic-changelog").val());

		tb_show('Changelog', '#TB_inline?inlineId=itelic-edit-changelog-popup', false);

		this.blur();

		e.preventDefault();
	});

	/**
	 * When the update button is clicked, update the textarea.
	 */
	$(".update-changelog").click(function (e) {
		$("#itelic-changelog").val($("#itelic-edit-changelog").val());

		tb_remove();
	});

	/**
	 * When the cancel button is clicked, ignore latest changes.
	 */
	$(".cancel-update-changelog").click(function (e) {
		e.preventDefault();

		tb_remove()
	});

	/**
	 * When the enable checkbox is checked, show the configuration.
	 */
	$("#itelic-enable").click(function () {
		var options = $(".itelic-settings");

		if ($(this).attr('checked') == 'checked') {
			options.removeClass('hide-if-js').show();
		} else {
			options.hide();
		}
	});

	/**
	 * When a new key type is selected, get a new form.
	 */
	$("#itelic-key-type").change(function () {

		var val = $(this).val();

		if (val.length != -1) {
			get_key_type_settings(val);
		} else {
			$("#itelic-key-type-settings").html('');
		}
	});

	/**
	 * Get the key type settings form via AJAX.
	 *
	 * @param key_type string
	 */
	function get_key_type_settings(key_type) {

		var data = {
			action : 'itelic_get_key_type_settings',
			type   : key_type,
			product: ITELIC.product
		};

		$.post(ITELIC.ajax, data, function (response) {
			$("#itelic-key-type-settings").html(response);
		});
	}

	var simple_activation = $(".itelic-activation-limit");
	var variant_activation = $(".itelic-variants-activation-limit-table");

	$("#itelic-enable-variant-activations").click(function (e) {
		if ($(this).attr('checked') == 'checked') {
			variant_activation.removeClass('hide-if-js').show();
			simple_activation.hide();
		} else {
			simple_activation.removeClass('hide-if-js').show();
			variant_activation.hide();
		}
	});

	$("#itelic-discount-disable").click(function () {
		var override = $("#itelic-discount-override");
		var options = $("input, select", ".itelic-discount-settings");

		if ($(this).attr('checked') == 'checked') {
			override.prop('disabled', true);

			options.each(function () {
				$(this).prop('disabled', true);
			});
		} else {
			override.prop('disabled', false);

			options.each(function () {
				$(this).prop('disabled', false);
			});
		}
	});

	$("#itelic-discount-override").click(function () {
		var options = $(".itelic-discount-settings");

		if ($(this).attr('checked') == 'checked') {
			options.removeClass('hide-if-js').show();
		} else {
			options.hide();
		}
	});

	$("#itelic-readme-enable").click(function () {
		var options = $(".itelic-readme-settings");

		if ($(this).attr('checked') == 'checked') {
			options.removeClass('hide-if-js').show();
		} else {
			options.hide();
		}
	});


	$("#itelic-upgrade-enable").click(function () {
		var options = $(".itelic-upgrade-settings");

		if ($(this).attr('checked') == 'checked') {
			options.removeClass('hide-if-js').show();
		} else {
			options.hide();
		}
	});

	$("#itelic-readme-last-updated").datepicker({
		prevText  : '',
		nextText  : '',
		dateFormat: ITELIC.df
	});
});