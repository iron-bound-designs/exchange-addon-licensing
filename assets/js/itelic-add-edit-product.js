/**
 * Add Edit Product Page
 *
 * @author Iron Bound Designs
 * @since 1.0
 */

jQuery(document).ready(function ($) {

	var key_type_select = $("#itelic-key-type");

	/**
	 * When a new key type is selected, get a new form.
	 */
	key_type_select.change(function () {

		var val = $(this).val();

		if (val.length > 0) {
			get_key_type_settings(val);
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
});