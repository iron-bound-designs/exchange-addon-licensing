(function ($) {
	'use strict';

	var file_frame, image_data;

	var header = $(".header-block");
	var misc = $(".misc-block");
	var replace_file = $(".replace-file-block");
	var changelog = $(".changelog-block");
    var security_message = $('.security-message-block');
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

	var changelog_text = $(".whats-changed");
	var changelog_editor = $(".whats-changed-editor");

    var type_span = $(".type h3");

    /**
     * When the notify button next to the upgrade progress bar is clicked
     * launch the notifications editor, and hide the main views. This leaves
     * only the header block visible.
     */
	notify_button.click(function () {

		misc.slideUp();
		changelog.slideUp();
        security_message.slideUp();
		upgrades.slideUp(400, function () {

			prev_notify_view = 'main';

			notifications.slideDown();
		});
	});

    /**
     * When the cancel notification button is clicked, hide the notifications
     * editor and show the last viewed UI. This is either the upgrades view,
     * or the main view.
     */
	$("#cancel-notification").click(function () {

		notifications.slideUp(400, function () {

			if (prev_notify_view == 'main') {
				misc.slideDown();
				changelog.slideDown();
				upgrades.slideDown();

                if (type_span.data('value') == 'security') {
                    security_message.slideDown();
                }

			} else {
				upgrades.slideDown();
				line_graph.slideDown();
				pie_chart.slideDown();
				full_notify_button_block.slideDown();
			}
		});
	});

    /**
     * When the full-width notify button in the upgrades view, load the
     * notification editor and hide the upgrades view.
     */
	full_notify_button.click(function () {

		upgrades.slideUp();
		line_graph.slideUp();
		pie_chart.slideUp();
		full_notify_button_block.slideUp(400, function () {

			prev_notify_view = 'upgrades';

			notifications.slideDown();
		});
	});

    /**
     * When the more upgrades link is clicked, which is displayed near the
     * upgrade progress bar, toggle the state of the advanced upgrades UI.
     */
	$("#more-upgrades-link").click(function () {

        // we are viewing the detailed view, go back to main
		if (upgrade_details_showing) {

            // first hide the charts
			line_graph.slideUp();
			pie_chart.slideUp();

            // lastly hide the full with notify button
			full_notify_button_block.slideUp(400, function () {

                // when this is completed, reveal the main UI view
				misc.slideDown();
				changelog.slideDown();

                // if this release is a security release, also show the
                // security message block
                if (type_span.data('value') == 'security') {
                    security_message.slideDown();
                }
			});

            // animate the progress bar back to 90% width and add back in the
            // small notify button
			$(".progress-container progress").animate({
				width: '90%'
			}, function () {
				notify_button.fadeIn(100);
			});

            // remove class designating that we in the detail view
			upgrades.removeClass('full-upgrade-details');

			$(this).text(ITELIC.moreUpgrade);

			upgrade_details_showing = false;

		} else {

            // hide the main sections on the main view.
			misc.slideUp();
            security_message.slideUp();

            // when the changelog is hidden, load in the detail view
			changelog.slideUp(400, function () {

                // slide down the line graph
				line_graph.slideDown(400, function () {

                    // if we haven't loaded the progress chart yet
                    // load it now. This is done here because Chart JS can't
                    // render elements that are display: none on initial site load
                    // this will animate the charts on the first view toggle
                    // but won't on any subsequent views
					if (!loaded_progress_chart) {
						$('body').trigger('loadProgressChart');

						loaded_progress_chart = true;
					}
				});

                // same thing as the line chart
				pie_chart.slideDown(400, function () {

					if (!loaded_versions_chart) {
						$('body').trigger('loadVersionsChart');

						loaded_versions_chart = true;
					}
				});

                // finally show the full width notify button
				full_notify_button_block.slideDown();
			});

            // fade out the small notify button
			notify_button.fadeOut(100, function () {

                // then make the progress bar full width
                // trying to do this simultaneously causes awful height changes
				$(".progress-container progress").animate({
					width: '100%'
				});
			});

            // make note that we are in the details view
			upgrades.addClass('full-upgrade-details');

            // change the More link to say Less
			$(this).text(ITELIC.lessUpgrade);

			upgrade_details_showing = true;
		}
	});

    /**
     * When the changelog text is clicked, display the WP tinyMCE editor
     * and hide the changelog div
     */
	changelog_text.click(function () {

		changelog_editor.show();
		changelog_text.hide();
	});

    /**
     * When the cancel button is clicked from the changelog editor,
     * hide the tinyMCE editor, and show the changelog div.
     */
	$("#cancel-changelog-editor").click(function () {

		changelog_text.show();
		changelog_editor.hide()
	});

    /**
     * When the save button is clicked from the changelog editor,
     * persist the user's changes to the server.
     */
	$("#save-changelog-editor").click(function () {

        // block the user from interacting with tinyMCE
		changelog_editor.block({
			message: ITELIC.saving,
			css    : {
				border                 : 'none',
				padding                : '15px',
				backgroundColor        : '#000',
				'-webkit-border-radius': '10px',
				'-moz-border-radius'   : '10px',
				opacity                : .5,
				color                  : '#fff',
				'z-index'              : '99999'
			}
		});

		var text = tinyMCE.activeEditor.getContent();

		editable_ajax({
			name : 'changelog',
			value: text
		}).done(function (response) {

			if (!response.success) {

				alert(response.data.message);

				changelog_editor.unblock();
			} else {
				changelog_editor.unblock();
				changelog_text.html(text);
				changelog_text.show();
				changelog_editor.hide();
			}
		});
	});

    /**
     * When the send notification button is clicked,
     * send the notification. This is done over AJAX.
     *
     * todo provide better completion UI
     */
	$("#send-notification").click(function () {

		var data = {
			action : 'itelic_admin_releases_send_notification',
			release: ITELIC.release,
			subject: $("#notification-subject").val(),
			message: window.tinyMCE.activeEditor.getContent(),
			nonce  : ITELIC.update_nonce
		};

        // block the entire admin area. This might not be strictly necessary,
        // but if the notification queue fails to be saved, we can show the
        // user if they are still on this page.
		$.blockUI({
			message: 'Sending',
			css    : {
				border                 : 'none',
				padding                : '15px',
				backgroundColor        : '#000',
				'-webkit-border-radius': '10px',
				'-moz-border-radius'   : '10px',
				opacity                : .5,
				color                  : '#fff'
			}
		});

		$.post(ajaxurl, data, function (response) {

			$.unblockUI();

			if (!response.success) {
				alert(response.data.message);
			} else {
				//alert(response.data.message);
			}
		});
	});

	/**
	 * Editable Implementation
	 *
	 * @return {object}
	 */
	function get_status_options_for_status() {

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

    /**
     * Override the buttons UI provided by the editable implementation,
     * and add the WP button classes instead.
     *
     * @type {string}
     */
    $.fn.editableform.buttons =
			'<button class="editable-submit button">' +
                ITELIC.cancel +
            '</button>' +
			'<button class="editable-submit button button-primary">' +
			ITELIC.ok +
			'</button>';

    // This is the span that is revealed on rollover
	var status_span = $(".status span");

    /**
     * Launch the editable UI on the status span.
     */
	status_span.editable({
		type       : 'select',
		pk         : ITELIC.release,
		name       : 'status',
		source     : function () {

            // the options are dynamic based on the current status
            // so we can't provide a simple list
			return get_status_options_for_status();
		},

        // don't cache the source results, so they are recalled when the status changes
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

    /**
     * When the status span is displayed,
     * add a class for css targeting and animating.
     */
	status_span.on('shown', function (e, editable) {
		$(this).closest('.status').addClass('status-hovered');
	});

    /**
     * When the status is hidden, remove that class.
     */
	status_span.on('hidden', function (e, editable) {
		$(this).closest('.status').removeClass('status-hovered');
	});

    /**
     * Fires when editable finishes saving the new status via AJAX.
     */
	status_span.on('save', function (e, params) {
		var container = $(this).closest('.status');

		var old = status_span.data('value');

        // remove all old status classes,
        // so the CSS doesn't get confused about coloring
		$.each(ITELIC.statuses, function (key, value) {
			container.removeClass('status-' + key);
		});

		container.addClass('status-' + params.newValue);

		status_span.data('value', params.newValue);

        if (old == 'draft') {

            // if we are activating a release
            // hide the replace file UI and show the upgrades UI
            replace_file.slideUp(400, function() {

                upgrades.slideDown();
            });
        }
	});

    // Editor for the security message. Only shown when type is security
    var security_message_editor = $(".security-message");

    /**
     * Launch the editor for the security message.
     *
     * This is a simple content area, no HTML is supported,
     * so we haven't loaded tinyMCE in this case.
     */
    security_message_editor.editable({
        type       : 'textarea',
        pk         : ITELIC.release,
        name       : 'security-message',

        // we need the lib's buttons because hitting "enter" in a textarea
        // just makes a newline instead of saving
        showbuttons: true,
        placement  : "top",
        title      : ' ',
        mode       : 'inline',
        rows       : 3, // limit the rows to 3
        maxlength  : 200, // currently not implemented by editable
        url        : function (params) {
            return editable_ajax(params);
        },
        success    : function (response, newValue) {
            return editable_success_callback(response, newValue);
        }
    });

    /**
     * Fires when the type of the release is changed.
     */
    type_span.on('save', function (e, params) {

        var old = type_span.data('value');

        type_span.data('value', params.newValue);

        // if the previous type is security,
        // then this new type doesn't need the security message editor
        if (old == 'security') {
            security_message.slideUp();
        }

        // but if this is now a security release, display the message editor
        if (params.newValue == 'security') {
            security_message.slideDown();
        }
    });

    // we can only edit the release type and version when the release is in draft mode
	if (status_span.data('value') == 'draft') {

        /**
         * Launch the editable UI for changing the release's type
         */
		type_span.editable({
			type       : 'select',
			pk         : ITELIC.release,
			name       : 'type',
			source     : ITELIC.types, // this is a static list
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

        /**
         * Launch the editable UI for changing the version number
         */
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

            // limit the mime type to zips
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

            // store the previous file label in case of error
			label.data('prev', label.text());
			label.text(image_data.filename);

            // send the new image ID to WP
			var promise = editable_ajax({
				name : 'download',
				value: image_data.id
			});

			promise.done(function (response) {

				if (!response.success) {

                    // revert file label and alert() the user
					label.text(label.data('prev'));

					alert(response.data.message);
				}
			});
		});

		// Now display the actual file_frame
		file_frame.open();

	});

    /**
     * We have to provide this for ourselves for use by the WP_Notifications package
     *
     * @param html
     * @returns {boolean}
     */
	window.send_to_editor = function (html) {
		var editor,
			hasTinymce = typeof tinymce !== 'undefined',
			hasQuicktags = typeof QTags !== 'undefined';

		if (!wpActiveEditor) {
			if (hasTinymce && tinymce.activeEditor) {
				editor = tinymce.activeEditor;
				wpActiveEditor = editor.id;
			} else if (!hasQuicktags) {
				return false;
			}
		} else if (hasTinymce) {
			editor = tinymce.get(wpActiveEditor);
		}

		if (editor && !editor.isHidden()) {
			editor.execCommand('mceInsertContent', false, html);
		} else if (hasQuicktags) {
			QTags.insertContent(html);
		} else {
			document.getElementById(wpActiveEditor).value += html;
		}

		// If the old thickbox remove function exists, call it
		if (window.tb_remove) {
			try {
				window.tb_remove();
			} catch (e) {
			}
		}
	};

})(jQuery);