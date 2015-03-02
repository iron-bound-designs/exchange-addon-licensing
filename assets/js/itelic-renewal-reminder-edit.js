/**
 * Scripts rendered on the add edit screen for renewal reminders.
 *
 * @author Iron Bound Designs
 * @since  1.0
 */

jQuery(document).ready(function ($) {

	$(".insert-shortcode").click(function (e) {
		e.preventDefault();

		ITELICInsertEmailShortcode();
	});

	$(".cancel-shortcode-insert").click(function (e) {
		e.preventDefault();

		tb_remove();
	});

	function ITELICInsertEmailShortcode() {
		var shortcode = jQuery("#add-shortcode-value").val();
		if (shortcode.length == 0 || shortcode == -1) {
			alert(ITELIC.must_select);
			return;
		}
		window.send_to_editor(shortcode);
		tb_remove();
	}
});