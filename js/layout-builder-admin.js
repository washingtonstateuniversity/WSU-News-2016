/**
 * Handle various features required for the drag/drop layout builder.
 */
(function ($, window) {
	'use strict';

	/**
	 * Selector cache of the container holding all of the items for a build.
	 *
	 * @type {*|HTMLElement}
	 */
	var $staged_items = $('#wsuwp-layout-builder-items');

	if (window.wsuwp_layout_build.items instanceof Array) {
		load_layout_build_items(window.wsuwp_layout_build.items);
	}

	/**
	 * Use jQuery UI Sortable to add sorting functionality to layout builds.
	 */
	sortable_layout();
	function sortable_layout() {
		var item_column;
		$('.wsuwp-spine-builder-column').sortable({
			connectWith: '.wsuwp-spine-builder-column',
			handle: '.ttfmake-sortable-handle',
			opacity: 0.6,
			placeholder: 'layout-builder-placeholder',
			start: function (event, ui) {
				item_column = $(ui.item).parent();
			},
			stop: function (event, ui) {
				var existing_item = ui.item.siblings('.wsuwp-layout-builder-item');
				if (existing_item && ui.item.parent('#wsuwp-layout-builder-items').length == 0) {
					$(existing_item).appendTo(item_column).find('.wsuwp-layout-builder-item-body').css('display', '');
				}
				if (ui.item.parent('#wsuwp-layout-builder-items').length == 1) {
					ui.item.find('.wsuwp-layout-builder-item-body').css('display', '');
				}
				process_sorted_data();
			}
		});
	}

	/**
	 * Process a list of items and add them to the front end view of the layout build.
	 *
	 * @param raw_data
	 */
	function load_layout_build_items(raw_data) {
		var data = '';

		// Append the results to the existing build of items.
		$.each(raw_data, function (index, val) {
			data += '<div id="wsuwp-layout-builder-item-' + val.id + '" class="wsuwp-layout-builder-item">' +
				'<div class="ttfmake-sortable-handle" title="Drag-and-drop this post into place">' +
					'<a href="#" class="spine-builder-column-configure"><span>Configure this column</span></a>' +
					'<a href="#" class="wsuwp-column-toggle" title="Click to toggle"><div class="handlediv"></div></a>' +
					'<div class="wsuwp-builder-column-title">' + val.title + '</div>' +
				'</div>' +
				'<div class="wsuwp-layout-builder-item-body wsuwp-column-content">' +
					'<h2>' + val.title + '</h2>' +
					'<div class="wsuwp-layout-builder-item-excerpt">' + val.excerpt + '</div>' +
				'</div>' +
			'</div>';
		});

		$staged_items.html(data);

		sortable_layout();
	}

	/**
	 * As issue articles are sorted, process their associate information.
	 */
	function process_sorted_data() {
		var new_val = '',
            placed_items = $('#ttfmake-stage').find('.wsuwp-spine-builder-column'),
            staged_items = $staged_items.sortable('toArray');

		// Items added to the Page Builder interface.
		$.each(placed_items, function () {
			var column  = $(this),
				article = column.children('.wsuwp-layout-builder-item');

			if (article.length) {
				var new_val = article[0].id.replace('wsuwp-layout-builder-item-', '');
				column.children('.wsuwp-column-post-id').val(new_val);
			} else {
				column.children('.wsuwp-column-post-id').val('');
			}
		});

		// Items in the staging area.
		$.each(staged_items, function (index, val) {
			new_val = val.replace('wsuwp-layout-builder-item-', '');
			staged_items[index] = new_val;
		});

		$('#wsuwp-layout-builder-staged-items').val(staged_items);

	}

	// Load items into the staging area.
	$('#wsuwp-builder-load-items').on('click', function (e) {
		e.preventDefault();

		var category = $('[name="wsuwp_layout_builder_category_terms[]"]:checked').map(function(){ return $(this).val(); }).get(),
			tag = $('[name="wsuwp_layout_builder_post_tag_terms[]"]:checked').map(function(){ return $(this).val(); }).get(),
			u_category = $('#wsuwp_university_category-terms').val(),
			location = $('#wsuwp_university_location-terms').val(),
			organization = $('#wsuwp_university_org-terms').val(),
			relation = $('input[name="wsuwp-builder-term-relation"]:checked').val();

		// Cache the issue build area for future use.
		var data = {
			action: 'set_layout_builder_items',
			category: category,
			tag: tag,
			u_category: u_category,
			location: location,
			organizatoin: organization,
			relation: relation,
		};

		// Make the ajax call
		$.post(window.ajaxurl, data, function (response) {
			var data = '',
				response_data = $.parseJSON(response);

			load_layout_build_items(response_data);
			process_sorted_data();
		});
	});

	// Make sure newly-added Page Builder elements are made sortable.
	$('.ttfmake-menu-list').on('click', '.ttfmake-menu-list-item', function () {
		$oneApp.on('afterSectionViewAdded', function () {
			sortable_layout();
		});
	});

	// Show/hide the taxonomy relation options as appropriate.
	$('.wsuwp-layout-builder-terms input[type=checkbox]').on('click', function () {
		if (1 < $('.wsuwp-layout-builder-terms input[type=checkbox]:checked').length) {
			$('.wsuwp-builder-term-relation').show();
		} else {
			$('.wsuwp-builder-term-relation').hide();
		}
	});

	$(document).ready(function () {
		$('.wsuwp-layout-builder-terms input[type=checkbox]').triggerHandler('click');
	});

	// spine/inc/builder-custom/js/edit.page.js
	$('#page_template').on('change', function () {
		if ('template-dragdrop.php' === $(this).val()) {
			$('body').addClass('wsuwp-drag-drop');
			$('#postdivrich').css('display', '');
			$('#ttfmake-builder').css('display', '');
		} else {
			$('body').removeClass('wsuwp-drag-drop');
		}
	});

}(jQuery, window));
