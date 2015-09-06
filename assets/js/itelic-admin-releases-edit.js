(function ($) {
	'use strict';

	var file_frame, image_data;

	var header = $(".header-block");
	var misc = $(".misc-block");
	var replace_file = $(".replace-file-block");
	var changelog = $(".changelog-block");
	var upgrades = $(".upgrade-progress-block");
	var notifications = $(".notifications-editor");


	$("#notify-button").click(function () {

		misc.slideUp();
		changelog.slideUp();
		upgrades.slideUp(400, function () {

			notifications.slideDown();
		});
	});

	$("#cancel-notification").click(function () {

		notifications.slideUp(400, function () {

			misc.slideDown();
			changelog.slideDown();
			upgrades.slideDown();
		});
	});

	/**
	 * When the upload inputs link is clicked, launch the media uploader.
	 */
	$("#replace-file").click(function (e) {

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

			$(".replace-file-container label").text(image_data.filename);

			// todo send new file to server

			console.log(image_data.id);
		});

		// Now display the actual file_frame
		file_frame.open();

	});
})(jQuery);