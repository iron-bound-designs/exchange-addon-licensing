(function ($) {
	'use strict';

	var file_frame, image_data;

	/**
	 * When a release type is selected, display the main release editor.
	 */
	$(".release-types input").change(function () {

		var $main = $(".main-editor");
		var $securityMessage = $("#security-message-row");

		if ($(this).is(':checked')) {

			if ($(this).val() == 'security') {

				if ($main.css('opacity') == 0) {
					$securityMessage.show();
				} else {
					$securityMessage.slideDown();
				}
			} else {
				$securityMessage.slideUp();
			}

			$main.css({
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

	/**
	 * When the trash file icon is clicked,
	 * remove the currently listed file.
	 */
	$('.trash-file').click(function (e) {

		$(".upload-inputs label").text(ITELIC.uploadLabel);
		$("#upload-file").val('');
		$(".trash-file").css('display', 'none');
	});

	/**
	 * When the upload inputs link is clicked, launch the media uploader.
	 */
	$(".upload-inputs a").click(function (e) {

		e.preventDefault();

		/**
		 * If an instance of file_frame already exists, then we can open it
		 * rather than creating a new instance.
		 */
		if (undefined !== file_frame) {

			file_frame.open();
			return;

		}

		/**
		 * If we're this far, then an instance does not exist, so we need to
		 * create our own.
		 *
		 * Here, use the wp.media library to define the settings of the Media
		 * Uploader implementation by setting the title and the upload button
		 * text. We're also not allowing the user to select more than one image.
		 */
		file_frame = wp.media.frames.file_frame = wp.media({
			title   : ITELIC.uploadTitle,
			button  : {
				text: ITELIC.uploadButton
			},
			multiple: false,
			library : {
				type: 'application/zip,application/octet-stream'
			}
		});

		/**
		 * Setup an event handler for what to do when an image has been
		 * selected.
		 */
		file_frame.on('select', function () {

			image_data = file_frame.state().get('selection').first().toJSON();

			$("#upload-file").val(image_data.id);
			$(".upload-inputs label").text(image_data.filename);
			$(".trash-file").css('display', 'inline');


			/*for (var image_property in image_data) {

			 /!**
			 * Here, you have access to all of the properties
			 * provided by WordPress to the selected image.
			 *
			 * This is generally where you take the data and so whatever
			 * it is that you want to do.
			 *
			 * For purposes of example, we're just going to dump the
			 * properties into the console.
			 *!/
			 console.log(image_property + ': ' + image_data[image_property]);

			 }*/

		});

		// Now display the actual file_frame
		file_frame.open();

	});
})(jQuery);