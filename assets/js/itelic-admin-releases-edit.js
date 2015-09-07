(function ($) {
	'use strict';

	var file_frame, image_data;

	var header = $(".header-block");
	var misc = $(".misc-block");
	var replace_file = $(".replace-file-block");
	var changelog = $(".changelog-block");
	var upgrades = $(".upgrade-progress-block");
	var notifications = $(".notifications-editor");
	var line_graph = $(".progress-line-chart");
	var pie_chart = $(".versions-pie-chart");
	var full_notify_button_block = $(".full-notify-button");

	var notify_button = $("#notify-button");
	var full_notify_button = $("#notify-button-full");
	var prev_notify_view;

	var upgrade_details_showing = false;
	var loaded_progress_chart = false;
	var loaded_versions_chart = false;

	notify_button.click(function () {

		misc.slideUp();
		changelog.slideUp();
		upgrades.slideUp(400, function () {

			prev_notify_view = 'main';

			notifications.slideDown();
		});
	});

	$("#cancel-notification").click(function () {

		notifications.slideUp(400, function () {

			if (prev_notify_view == 'main') {
				misc.slideDown();
				changelog.slideDown();
				upgrades.slideDown();
			} else {
				upgrades.slideDown();
				line_graph.slideDown();
				pie_chart.slideDown();
				full_notify_button_block.slideDown();
			}
		});
	});

	full_notify_button.click(function () {

		upgrades.slideUp();
		line_graph.slideUp();
		pie_chart.slideUp();
		full_notify_button_block.slideUp(400, function () {

			prev_notify_view = 'upgrades';

			notifications.slideDown();
		});
	});

	$("#more-upgrades-link").click(function () {

		if (upgrade_details_showing) {

			line_graph.slideUp();
			pie_chart.slideUp();

			full_notify_button_block.slideUp(400, function () {

				misc.slideDown();
				changelog.slideDown();
			});

			$(".progress-container progress").animate({
				width: '90%'
			}, function () {
				notify_button.fadeIn(100);
			});

			upgrades.removeClass('full-upgrade-details');

			$(this).text(ITELIC.moreUpgrade);

			upgrade_details_showing = false;

		} else {

			misc.slideUp();
			changelog.slideUp(400, function () {

				line_graph.slideDown(400, function () {

					if (!loaded_progress_chart) {
						$('body').trigger('loadProgressChart');

						loaded_progress_chart = true;
					}
				});

				pie_chart.slideDown(400, function () {

					if (!loaded_versions_chart) {
						$('body').trigger('loadVersionsChart');

						loaded_versions_chart = true;
					}
				});

				full_notify_button_block.slideDown();
			});

			notify_button.fadeOut(100, function () {

				$(".progress-container progress").animate({
					width: '100%'
				});
			});

			upgrades.addClass('full-upgrade-details');

			$(this).text(ITELIC.lessUpgrade);

			upgrade_details_showing = true;
		}
	});

	/**
	 * Editable Implementation
	 *
	 * @return {object}
	 */
	function get_status_options_for_status() {

		console.log(status_span);
		var current = status_span.data('value');

		var all = ITELIC.statuses;

		var selected = {};

		switch (current) {
			case 'draft':
				selected['draft'] = all.draft;
				selected['active'] = all.active;
				break;

			case 'active':
				selected['active'] = all.active;
				selected['paused'] = all.paused;
				selected['archived'] = all.archived;
				break;

			case 'paused':
				selected['paused'] = all.paused;
				selected['active'] = all.active;
				break;

			case 'archived':
				selected['archived'] = all.archived;
		}

		return selected;
	}

	/**
	 * Callback function that parses the WP Ajax response.
	 *
	 * @param response
	 * @param newValue
	 * @returns {*}
	 */
	function editable_success_callback(response, newValue) {

		if (!response.success) {
			alert(response.data.message);
			return false;
		} else {
			return {"newValue": newValue};
		}
	}

	/**
	 * Callback function that processes a change from editable
	 * and posts it to a WP Ajax handle.
	 *
	 * @param params
	 * @returns {$.promise|*}
	 */
	function editable_ajax(params) {
		var data = {
			action : 'itelic_admin_releases_single_update',
			release: ITELIC.release,
			prop   : params.name,
			val    : params.value,
			nonce  : ITELIC.update_nonce
		};

		return $.post(ajaxurl, data);
	}

	var status_span = $(".status span");

	status_span.editable({
		type       : 'select',
		pk         : ITELIC.release,
		name       : 'status',
		source     : function () {
			return get_status_options_for_status();
		},
		sourceCache: false,
		showbuttons: false,
		placement  : "top",
		title      : ' ',
		mode       : 'inline',
		url        : function (params) {
			return editable_ajax(params);
		},
		success    : function (response, newValue) {
			return editable_success_callback(response, newValue);
		}
	});

	status_span.on('shown', function (e, editable) {
		$(this).closest('.status').addClass('status-hovered');
	});

	status_span.on('hidden', function (e, editable) {
		$(this).closest('.status').removeClass('status-hovered');
	});

	status_span.on('save', function (e, params) {
		var container = $(this).closest('.status');

		$.each(ITELIC.statuses, function (key, value) {
			container.removeClass('status-' + key);
		});

		container.addClass('status-' + params.newValue);

		status_span.data('value', params.newValue);
	});

	if (status_span.data('value') == 'draft') {

		$(".type h3").editable({
			type       : 'select',
			pk         : ITELIC.release,
			name       : 'type',
			source     : ITELIC.types,
			sourceCache: true,
			showbuttons: false,
			placement  : "top",
			title      : ' ',
			mode       : 'inline',
			url        : function (params) {
				return editable_ajax(params);
			},
			success    : function (response, newValue) {
				return editable_success_callback(response, newValue);
			}
		});

		var version = $(".version h3");

		version.editable({
			type       : 'text',
			pk         : ITELIC.release,
			name       : 'version',
			showbuttons: false,
			placement  : "top",
			title      : ' ',
			mode       : 'inline',
			url        : function (params) {
				return editable_ajax(params);
			},
			success    : function (response, newValue) {
				return editable_success_callback(response, newValue);
			}
		});

		version.on('save', function (e, params) {
			$(".version-name").text(params.newValue);
		});
	}

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

			var label = $(".replace-file-container label");

			label.data('prev', label.text());
			label.text(image_data.filename);

			var promise = editable_ajax({
				name : 'download',
				value: image_data.id
			});

			promise.done(function (response) {

				if (!response.success) {
					label.text(label.data('prev'));

					alert(response.data.message);
				}
			});
		});

		// Now display the actual file_frame
		file_frame.open();

	});
})(jQuery);