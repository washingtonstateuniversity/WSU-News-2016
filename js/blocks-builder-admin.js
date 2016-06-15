/**
 * Handle various features required for the blocks layout builder.
 */
(function ($, window) {
	'use strict';

	/**
	 * Selector cache of the container holding all of the items for a build.
	 *
	 * @type {*|HTMLElement}
	 */
	var $staged_items = $('#wsuwp-blocks-items');

	if (window.wsuwp_blocks.items instanceof Array) {
		load_blocks_items(window.wsuwp_blocks.items);
	}

	/**
	 * Use jQuery UI Sortable to add sorting functionality to blocks builds.
	 */
	function sortable_layout() {
		var item_column;

		$('.wsuwp-spine-builder-column').sortable({
			connectWith: '.wsuwp-spine-builder-column',
			handle: '.ttfmake-sortable-handle',
			opacity: 0.6,
			placeholder: 'blocks-item-placeholder',
			start: function (event, ui) {
				item_column = $(ui.item).parent();
			},
			stop: function (event, ui) {
				var existing_item = ui.item.siblings('.wsuwp-blocks-item');

				// If an item is being dropped into a column that already has an item, swap their locations.
				if (existing_item && ui.item.parent('#wsuwp-blocks-items').length == 0) {
					$(existing_item).appendTo(item_column).find('.wsuwp-blocks-item-body').css('display', '');
				}

				// Set the toggle state to closed for items being moved into the staging area.
				if (ui.item.parent('#wsuwp-blocks-items').length == 1) {
					ui.item.find('.handlediv').removeClass('wsuwp-toggle-closed');
					ui.item.find('.wsuwp-blocks-item-body').css('display', '');
				}

				process_sorted_data();
			}
		});
	}

	/**
	 * Process a list of items and add them to the front end view of the build.
	 *
	 * @param raw_data
	 */
	function load_blocks_items(raw_data) {
		var data = '';

		// Load the results into the staging area.
		$.each(raw_data, function (index, val) {
			data += '<div id="wsuwp-blocks-item-' + val.id + '" class="wsuwp-blocks-item">' +
				'<div class="ttfmake-sortable-handle" title="Drag-and-drop this post into place">' +
					'<a href="#" class="spine-builder-column-configure"><span>Configure</span></a>' +
					'<a href="#" class="ttfmake-builder-section-footer-link blocks-item-remove"><span>Remove</span></a>' +
					'<a href="#" class="wsuwp-column-toggle" title="Click to toggle"><div class="handlediv"></div></a>' +
					'<div class="wsuwp-builder-column-title">' + val.title + '</div>' +
				'</div>' +
				'<div class="wsuwp-blocks-item-body wsuwp-column-content">' +
					'<h2>' + val.title + '</h2>' +
					'<div class="wsuwp-blocks-item-excerpt">' + val.excerpt + '</div>' +
				'</div>' +
			'</div>';
		});

		$staged_items.html(data);

		sortable_layout();
	}

	/**
	 * As items are sorted, process their associated information.
	 */
	function process_sorted_data() {
		var new_val = '',
            placed_items = $('#ttfmake-stage').find('.wsuwp-spine-builder-column'),
            staged_items = $staged_items.sortable('toArray');

		// Items added to the Page Builder interface.
		$.each(placed_items, function () {
			var column  = $(this),
				article = column.children('.wsuwp-blocks-item');

			if (article.length) {
				var new_val = article[0].id.replace('wsuwp-blocks-item-', '');
				column.children('.wsuwp-column-post-id').val(new_val);
			} else {
				column.children('.wsuwp-column-post-id').val('');
			}
		});

		// Items in the staging area.
		$.each(staged_items, function (index, val) {
			new_val = val.replace('wsuwp-blocks-item-', '');
			staged_items[index] = new_val;
		});

		$('#wsuwp-blocks-staged-items').val(staged_items);
	}

	// Load items into the staging area.
	$('#wsuwp-blocks-load-items').on('click', function (e) {
		e.preventDefault();

		var post_type = $('[name="wsuwp_blocks_post_type[]"]:checked').map(function(){ return $(this).val(); }).get(),
			category = $('[name="wsuwp_blocks_category[]"]:checked').map(function(){ return $(this).val(); }).get(),
			tag = $('[name="wsuwp_blocks_post_tag[]"]:checked').map(function(){ return $(this).val(); }).get(),
			u_category = $('[name="wsuwp_blocks_wsuwp_university_category[]"]:checked').map(function(){ return $(this).val(); }).get(),
			location = $('[name="wsuwp_blocks_wsuwp_university_location[]"]:checked').map(function(){ return $(this).val(); }).get(),
			organization = $('[name="wsuwp_blocks_wsuwp_university_org_terms[]"]:checked').map(function(){ return $(this).val(); }).get(),
			relation = $('[name="wsuwp_blocks_term_relation"]:checked').val(),
			data = {
				action: 'set_blocks_items',
				post_type: post_type,
				category: category,
				tag: tag,
				u_category: u_category,
				location: location,
				organization: organization,
				relation: relation,
			};

		// At least one post type needs to be selected to make the ajax call.
		if ( 0 === $('[name="wsuwp_blocks_post_type[]"]:checked').length ) {
			return;
		}

		// Make the ajax call.
		$.post(window.ajaxurl, data, function (response) {
			var data = '',
				response_data = $.parseJSON(response);

			load_blocks_items(response_data);
			process_sorted_data();
		});

		// Change the button value.
		if ('Load Items' === $(this).val()) {
			$(this).val('Refresh Items');
		}
	});

	// Make sure newly-added Page Builder elements are made sortable.
	$('.ttfmake-menu-list').on('click', '.ttfmake-menu-list-item', function () {
		$oneApp.on('afterSectionViewAdded', function () {
			sortable_layout();
		});
	});

	// 'Publish block'-like interface handling.
	$('.wsuwp-blocks-content-options').on('click', 'a', function (e) {
		e.preventDefault();

		var link = $(this);

		if ( link.hasClass('edit') ) {
			// If the 'Edit' link is clicked, hide it and show the options div.
			link.hide().next('div').slideDown('fast');
		} else {
			// Update the selected options and `.display` span text accordingly when 'Okay' or 'Cancel' is clicked.
			var section = link.closest('.wsuwp-blocks-content-options'),
				display = section.find('.display'),
				options = link.closest('div');

			if ( link.hasClass('button') ) {
				var checked = options.find(':checked').map(function () { return $.trim($(this).closest('label').text()); }).get(),
					text    = checked.length ? checked.join(', ') : 'None';

				// When editing post type options, at least one needs to be selected before proceeding.
				if ( section.hasClass('post-type') && 0 === options.find(':checked').length ) {
					return;
				}

				display.text(text);
			} else if ( link.hasClass('button-cancel') ) {
				var checked = display.text().split(', ');

				options.find(':checked').attr('checked', false);

				$.each(checked, function (i, val) {
					options.find('label').filter(function () {
						return $(this).text() === ' ' + val;
					}).find('input').attr('checked', true).triggerHandler('change'); // `triggerHandler` here to check post type options.
				});
			}

			// Close the options div and show the 'Edit' link.
			link.closest('div').slideUp('fast').prev('.edit').show();
		}
	});

	// Let the user know that at least one post type option needs to be selected.
	$('[name="wsuwp_blocks_post_type[]"]').on('change', function () {
		var container = $(this).closest('div');

		if ( 0 === $('[name="wsuwp_blocks_post_type[]"]:checked').length ) {
			if ( 0 === container.find('.notice-warning').length ) {
				container.find('p').before('<p class="notice notice-warning">Please select at least one option</p>');
			}
		} else {
			container.find('.notice-warning').remove();
		}
	});

	// Taxonomy terms search.
	$('.wsuwp-blocks-taxonomy-terms-search').on('keyup change', function () {
		var	value = $(this).val(),
			terms = $(this).next('.wsuwp-blocks-taxonomy-terms').find('label'); // Could add `:has(:not(:checked))` to preserve checked options.

		if ( value.length > 0 ) {
			terms.each(function () {
				var term = $(this);

				if (term.text().toLowerCase().indexOf(value.toLowerCase()) > 0) {
					term.show();
				} else {
					term.hide();
				}
			});
		} else {
			terms.show();
		}

	});

	// Display the builder and custom sections when the blocks builder template is selected.
	$('#page_template').on('change', function () {
		if ('template-blocks-builder.php' === $(this).val()) {
			$('body').addClass('wsuwp-drag-drop');
			$('#postdivrich').hide();
			$('#ttfmake-builder').show();
			$('#wsuwp-blocks-content').show();
			$('#ttfmake-builder-hide').prop('checked', true).parent().show();
			$('#wsuwp-blocks-content-hide').prop('checked', true).parent().show();
		} else {
			$('body').removeClass('wsuwp-drag-drop');
			$('#wsuwp-blocks-content').hide();
		}
	});

	// Item removal handling.
	$('#ttfmake-stage').on('click', '.blocks-item-remove', function (e) {
		e.preventDefault();

		// Confirm before removing the item.
		if (false === window.confirm('Delete the item?')) {
			return;
		}

		// Animate removal in the same manner as a page builder section.
		$(this).closest('.wsuwp-blocks-item').animate({
			opacity: 'toggle',
			height: 'toggle'
		}, oneApp.options.closeSpeed, function () {
			// Remove any input values for the column the item was in.
			$(this).siblings('input').val('');
			// We'll also need to remove the configuration modal input values (if we use any).
			$(this).remove();
		});
	});

	// Fire the jQuery UI Sortable setup on page load.
	$(document).ready(function () {
		sortable_layout();
	});
}(jQuery, window));
