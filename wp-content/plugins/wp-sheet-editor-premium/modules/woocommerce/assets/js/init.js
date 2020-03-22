// Allow to copy downloadable files from other products
jQuery(document).ready(function () {
	jQuery(document).on('opened', '.remodal.custom-modal-editor', function () {
		console.log('Modal is opened');
		var data = window.vgseWCAttsCurrent;
		if (!data || data.modalSettings.edit_modal_save_action !== 'vgse_save_download_files') {
			return true;
		}
		jQuery('.remodal.custom-modal-editor .vgse-copy-files-from-product-wrapper').remove();

	});
	jQuery(document).on('opened', '.remodal.custom-modal-editor', function () {
		console.log('Modal is opened');
		var data = window.vgseWCAttsCurrent;
		if (!data || data.modalSettings.edit_modal_save_action !== 'vgse_save_download_files') {
			return true;
		}

		setTimeout(function () {
			$modal = jQuery('.remodal.custom-modal-editor');
			var $copyFrom = $modal.find('.vgse-copy-files-from-product');
			console.log($copyFrom);

			$modal.find('.vgse-copy-files-from-product-trigger').click(function () {
				if (!$copyFrom.val()) {
					return true;
				}
				loading_ajax({estado: true});
				jQuery.get(ajaxurl, {
					nonce: $copyFrom.data('nonce'),
					product_id: $copyFrom.val(),
					action: 'vgse_wc_get_downloadable_files',
				}, function (response) {
					console.log(response);

					if (response.success) {
						initHandsontableForPopup(response.data, data.modalSettings);
					} else {
						notification({mensaje: response.data.message, tipo: 'error', tiempo: 30000});

					}
					loading_ajax({estado: false});
				});
			});
		}, 400);
	});
});

// Create product variations
jQuery(document).ready(function () {

	// Display "link variations" field conditionally
	jQuery('body').on('change', '.vgse-copy-variation-from-product', function (e) {
		var copyFromProduct = jQuery(this).val();
		var $linkVariations = jQuery('.link-variations-attributes, .link-variations-number');

		if (copyFromProduct) {
			$linkVariations.parents('li').hide();
		} else {
			$linkVariations.parents('li').show();
		}
	});

	// Display "number of variations" field conditionally
	jQuery('body').on('change', '.link-variations-attributes', function (e) {
		var $number = jQuery('.link-variations-number');

		if (jQuery(this).is(':checked')) {
			$number.parents('li').hide();
		} else {
			$number.parents('li').show();
		}
	});


	// Submit create variation modal
	jQuery('body').on('submit', '.create-variation-modal form', function (e) {
		var $form = jQuery(this);

		if ($form.find('input[name="use_search_query"]:checked').length && typeof beGetRowsFilters === 'function') {
			$form.find('input[name="filters"]').val(beGetRowsFilters());
		}

		// Get data from the visible tool + fields from outside the tools
		var data = $form.find('input,select,textarea').filter(function () {
			return (jQuery(this).parents('.vgse-variations-tool').length && jQuery(this).parents('.vgse-variations-tool').is(':visible')) || !jQuery(this).parents('.vgse-variations-tool').length;
		}).serializeArray();

		loading_ajax({estado: true});

		if ($form.find('input[name="use_search_query"]:checked').length) {
			var $progress = jQuery('.vgse-variations-tool .response');
			// Init progress bar
			$progress.before('<div id="be-variations-nanobar-container" />');
			var options = {
				classname: 'be-progress-bar',
				target: document.getElementById('be-variations-nanobar-container')
			};

			var nanobar = new Nanobar(options);
			// We start progress bar with 1% so it doesn't look completely empty
			nanobar.go(1);
			var rowsCount = jQuery('.be-total-rows').text().match(/\d+/g).map(Number);
			var perPage = vgse_editor_settings.save_posts_per_page / 2;
			if (perPage < 1) {
				perPage = 1;
			}
			// Start saving posts, start ajax loop
			beAjaxLoop({
				totalCalls: Math.ceil(rowsCount / parseInt(perPage)),
				url: $form.attr('action'),
				method: $form.attr('method'),
				data: data,
				onSuccess: function (res, settings) {
					// if the response is empty or has any other format,
					// we create our custom false response
					if (res.success !== true && !res.data) {
						res = {
							data: {
								message: vgse_editor_settings.texts.http_error_try_now
							},
							success: false
						};
					}

					// If error
					if (!res.success) {
						// show error message
						jQuery($progress).append('<p>' + res.data.message + '</p>');

						// Ask the user if he wants to retry the same post
						var goNext = confirm(res.data.message);

						// stop saving if the user chose to not try again
						if (!goNext) {
							jQuery($progress).append(vgse_editor_settings.texts.saving_stop_error);
							$progress.scrollTop($progress[0].scrollHeight);
							return false;
						}
						// reset pointer to try the same batch again
						settings.current = 0;
						$progress.scrollTop($progress[0].scrollHeight);
						return true;
					}

					nanobar.go(settings.current / settings.totalCalls * 100);


					// Display message saying the number of posts saved so far
					var updated = (parseInt(perPage) * settings.current > rowsCount) ? rowsCount : parseInt(perPage) * settings.current;
					var text = vgse_editor_settings.texts.paged_batch_saved.replace('{updated}', updated);
					var text = text.replace('{total}', rowsCount);
					jQuery($progress).append('<p>' + text + '</p>');

					// is complete, show notification to user, hide loading screen, and display "close" button
					if (settings.current === settings.totalCalls) {
						jQuery($progress).append('<p>' + vgse_editor_settings.texts.everything_saved + '</p>');

						loading_ajax({estado: false});


						notification({mensaje: vgse_editor_settings.texts.everything_saved});

						$progress.find('.remodal-cancel').removeClass('hidden');
						$form.find('#be-variations-nanobar-container').remove();
					} else {

					}

					// Move scroll to the button to show always the last message in the saving status section
					setTimeout(function () {
						$progress.scrollTop($progress[0].scrollHeight);
					}, 600);

					return true;
				}});
		} else {

			jQuery.ajax({
				url: $form.attr('action'),
				method: $form.attr('method'),
				data: data,
			}).done(function (res) {
				console.log(res);
				loading_ajax({estado: false});

				if (res.success) {

					// Add rows to spreadsheet							
					vgAddRowsToSheet(res.data.data, 'prepend', true);

					notification({mensaje: res.data.message});

					// Scroll up to the new rows
					jQuery(window).scrollTop(jQuery('.be-spreadsheet-wrapper').offset().top - jQuery('#vg-header-toolbar').height() - 20);
				} else {
					notification({mensaje: res.data.message, tipo: 'error', tiempo: 60000});
				}
				jQuery('.create-variation-modal').remodal().close();
			});
		}

		return false;
	});

});
// Product variations
jQuery(document).ready(function () {
	jQuery('.vgse-current-filters').on('click', '.button', function (e) {
		if (jQuery(this).data('filter-key') === 'wc_display_variations') {
			jQuery('#display-variations').prop('checked', !jQuery('#display-variations').prop('checked'));
		}
	});
	// Reload data from server and include variations
	jQuery('#display-variations').click(function (e) {
		var $input = jQuery(this);

		var message;

		if ($input.is(':checked')) {
			var message = vgse_wc_attr_data.texts.variations_on_reload_needed;
		} else {
			var message = vgse_wc_attr_data.texts.variations_off_reload_needed;
		}
		var canReload = confirm(message);




		// Reload not allowed, disable option
		if (!canReload) {
			$input.prop('checked', false);
			return true;
		}

		// Add flag to body
		if ($input.is(':checked')) {
			jQuery('body').addClass('vg-variations-enabled');
		}

		var newFilter = {
			'wc_display_variations': $input.is(':checked') ? 'yes' : ''
		};

		beAddRowsFilter(newFilter);

		vgseReloadSpreadsheet();
	});

});


// Display total inventory units and price
jQuery(document).ready(function () {
	jQuery('body').on('vgSheetEditor:beforeRowsInsert', function (event, response) {
		console.log('event', event, 'response', response);

		if (typeof response.data.total_inventory_price === 'undefined' || typeof response.data.total_inventory_price === 'undefined') {
			return true;
		}

		if (!jQuery('#responseConsole .wc-inventory-totals').length) {
			jQuery('#responseConsole').append('<span class="wc-inventory-totals"></span>');
		}

		var $inventoryTotals = jQuery('#responseConsole .wc-inventory-totals');

		$inventoryTotals.html('. <b>Inventory:</b> ' + response.data.total_inventory_units + ' units (' + response.data.total_inventory_price + ')');
	});
});

// Variations manager
jQuery(document).ready(function () {
	var $toolSelector = jQuery('.vgse-variations-tool-selector');

	$toolSelector.click(function (e) {
		e.preventDefault();

		var toolWrapper = jQuery(this).data('target');

		jQuery('.vgse-variations-tool').hide();

		$toolSelector.removeClass('active');
		jQuery(this).addClass('active');

		var $tool = jQuery(toolWrapper);
		$tool.show();

		if (!$tool.find('[name="vgse_variation_manager_source"]').length) {
			$tool.find('.individual-product-selector').show();
		}

		var $select2 = $tool.find('select.select2');
		if ($select2.select2) {
			$select2.select2("destroy");
		}
		vgseInitSelect2($select2.filter(function () {
			return jQuery(this).is(':visible');
		}));
	});

	jQuery('[name="vgse_variation_manager_source"]').change(function (e) {
		var $tool = jQuery(this).parents('.vgse-variations-tool');
		var value = jQuery(this).val();

		$tool.find('.use-search-query-container').hide();

		if (value === 'search') {
			// We must use display block, otherwise it becomes inline element ruining alignment
			$tool.find('.use-search-query-container').css('display', 'block');
			$tool.find('.individual-product-selector ~ .select2').hide();

			// Open the search modal
			window.vgseBackToVariationsTool = true;
			jQuery('[name="run_filters"]').first().click();
		} else {
			$tool.find('.individual-product-selector').show();
			$tool.find('.individual-product-selector ~ .select2').show();
		}

		var $select2 = $tool.find('select.select2');
		vgseInitSelect2($select2.filter(function () {
			return jQuery(this).is(':visible');
		}));

	});
	// When we are in the formulas modal we revert the changes in the search button
	jQuery(document).on('closed', '[data-remodal-id="modal-filters"]', function () {
		if (typeof window.vgseBackToVariationsTool !== 'undefined' && window.vgseBackToVariationsTool) {
			window.vgseBackToVariationsTool = false;
			jQuery('[data-remodal-target="create-variation-modal"]').first().click();
		}
	});

	jQuery('.vgse-variations-tool .show-advanced-options').change(function () {
		if (jQuery(this).is(':checked')) {
			jQuery('.vgse-variations-tool .advanced-options').show();
		} else {
			jQuery('.vgse-variations-tool .advanced-options').hide();
		}
	});
	jQuery(document).on('closed', '[data-remodal-id="create-variation-modal"]', function () {
		jQuery('[data-remodal-id="create-variation-modal"]').find('.response').empty();
	});
});