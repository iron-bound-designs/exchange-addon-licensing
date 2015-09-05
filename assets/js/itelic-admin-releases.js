jQuery(document).ready(function ($) {

	/**
	 * When a release type is selected, display the main release editor.
	 */
	$(".release-types input").change(function () {

		if ($(this).is(':checked')) {
			$(".main-editor").css({
				opacity: 1
			});
		}
	});

	/**
	 * When a product is selected, display the previous version on the version input.
	 */
	$("#product").change(function () {

		var $this = $(this);

		if ($this.val().length > 0) {

			var $selected = $(this[this.selectedIndex]);

			var prevVersionText = ITELIC.prevVersion;

			var prevVersion = $selected.data('version');

			if (prevVersion.length == 0) {
				prevVersion = '–';
			}

			prevVersionText = prevVersionText.replace('%s', prevVersion);

			$("#prev-version").text(prevVersionText).css('opacity', 1);
		} else {
			$("#prev-version").css('opacity', 0);
		}
	});
});