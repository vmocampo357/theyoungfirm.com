/**
 * Apply columns changes to the spreadsheet
 * @param array loadedColumns
 * @param array loadedColumnsNames
 * @param array loadedColumnsWidths
 * @param str context softUpdate or empty. If the changes will be saved to the database
 * @returns Boolean | null
 */
function vgseColumnsVisibilityUpdateHOT(loadedColumns, loadedColumnsNames, loadedColumnsWidths, context) {
	var $form = jQuery('.modal-columns-visibility form');
	var $enabled = $form.find('.columns-enabled li .js-column-key');
	if (!$enabled.length && window.vgseColumnsVisibilityEnabled) {
		$enabled = window.vgseColumnsVisibilityEnabled;
	}
	var $save = jQuery('.save_post_type_settings');
	var modalInstance = $form.parents('.remodal').remodal();

	// Exit if no column is enabled.        
	if (!$enabled.length) {
		loading_ajax({estado: false});
		modalInstance.close();
		console.log('exit, columns not enabled');
		return false;
	}


	if (typeof hot !== 'undefined') {
		// Cache to be able to call this function when the modal is closed.    
		window.vgseColumnsVisibilityEnabled = $enabled;

		// Apply changes live to the spreadsheet
		if (!loadedColumns) {
			loadedColumns = hot.getSettings().columns;
		}
		if (!loadedColumnsNames) {
			loadedColumnsNames = vgse_editor_settings.colHeaders;
		}
		if (!loadedColumnsWidths) {
			loadedColumnsWidths = vgse_editor_settings.colWidths;
		}
		var indexedLoadedColumns = [];
		var newColumns = [];
		var newColumnsNames = [];
		var newColumnsWidths = [];

		// Add key index to loadedColumns so we can access specific items later.
		loadedColumns.forEach(function (item, index) {
			item.vgOriginalIndex = index;
			indexedLoadedColumns[item.data] = item;
		}, this);

		// Generate list of enabled columns, including fixed columns.  
		var disallowedColumns = $form.find('.not-allowed-columns').val();
		disallowedColumns = disallowedColumns.replace('ID,', '').split(',');
		var enabledColumns = [];
		$enabled.each(function () {
			var columnKey = jQuery(this).val();
			enabledColumns.push(columnKey);
		});

//		enabledColumns = enabledColumns.concat(disallowedColumns);
		enabledColumns = disallowedColumns.concat(enabledColumns);

		// Iterate over enabledColumns and generate the list of final columns, columnsNames, and columns Widths.  
		enabledColumns.forEach(function (columnKey, index) {
			if (indexedLoadedColumns[columnKey]) {
				newColumns.push(indexedLoadedColumns[columnKey]);
				newColumnsNames.push(loadedColumnsNames[columnKey]);
				newColumnsWidths.push(loadedColumnsWidths[columnKey]);
			}
		}, this);


		console.log(indexedLoadedColumns);
		console.log(newColumns);
		console.log(enabledColumns);
		console.log(loadedColumns);
		console.log(newColumnsNames);
		console.log(loadedColumnsNames);


		hot.updateSettings({
			columns: newColumns,
			colHeaders: newColumnsNames,
			colWidths: newColumnsWidths
		});

		if (!$save.is(':checked') || context === 'softUpdate') {
			loading_ajax({estado: false});
			modalInstance.close();
			console.log('exit, saving not checked');
			return false;
		}

	}
	var formData = $form.children('input').serializeArray();
	var enabledData = $form.find('.columns-enabled li:visible input').serializeArray();

	finalFormData = formData.concat(enabledData);

	console.log(finalFormData);
	jQuery.post($form.attr('action'), finalFormData, function (response) {
		console.log(response);

		var callback = $form.data('callback');
		if (callback) {
			vgseExecuteFunctionByName(callback, window, {
				response: response,
				form: $form
			});
		}
	});
	loading_ajax({estado: false});
	if (modalInstance) {
		modalInstance.close();
	}

	return false;
}

function vgseColumnsVisibilityEqualizeHeight() {
	var enabledHeight = jQuery('#vgse-columns-enabled').height();
	var disabledHeight = jQuery('#vgse-columns-disabled').height();
	var maxHeight = enabledHeight > disabledHeight ? enabledHeight : disabledHeight;

	if (maxHeight > 0) {
		jQuery('#vgse-columns-enabled,#vgse-columns-disabled').height(maxHeight);
	}
}
function vgseColumnsVisibilityInit() {

// It should init once, otherwise it will make repeat ajax requests
	if (window.vgseColumnsVisibilityAlreadyInit) {
		return true;
	}
	window.vgseColumnsVisibilityAlreadyInit = true;

	// Initialize sortable lists
	var $columns = document.getElementById('vgse-columns-enabled');
	var $columnsDisabled = document.getElementById('vgse-columns-disabled');
	var $modal = jQuery('.modal-columns-visibility');

	if (!$columns || !$columnsDisabled) {
		return true;
	}

	// Equalize height between columns
//	setTimeout( function(){		
	vgseColumnsVisibilityEqualizeHeight();
//	}, 1000);

	function itemsMoved() {
		var $enabled = $modal.find('.columns-enabled li:visible .js-column-key');
		var allEnabled = $enabled.map(function () {
			return jQuery(this).val();
		}).get().join(',');

		$modal.find('.all-allowed-columns').val(allEnabled);
		window.vgseColumnsVisibilityUsed = true;
	}
	window.enabledSortable = Sortable.create($columns, {
		group: 'vgseColumns',
		animation: 100,
		onSort: function (evt) {

			console.log('moved');
			itemsMoved();
		}
	});
	window.disabledSortable = Sortable.create($columnsDisabled, {
		group: {
			name: 'vgseColumns',
			// put: ['foo', 'bar']
		},
		animation: 100
	});

	// Enable / disable all columns
	jQuery('body').on('click', '.modal-columns-visibility .vgse-change-all-states', function (e) {
		e.preventDefault();
		var toStatus = jQuery(this).data('to');

		if (toStatus === 'disabled') {
			$columnsDisabled.innerHTML += $columns.innerHTML;
			$columns.innerHTML = '';
		} else {
			$columns.innerHTML += $columnsDisabled.innerHTML;
			$columnsDisabled.innerHTML = '';
		}

		itemsMoved();
	});

	// Save changes
	jQuery('body').on('submit', '.modal-columns-visibility  form', function (e) {
		e.preventDefault();
		itemsMoved();
		var response = vgseColumnsVisibilityUpdateHOT(null, null, null, 'hardUpdate');
		console.log(response);
		if (typeof response === 'boolean') {
			return response;
		}

		return false;
	});
	jQuery('body').on('click', '.modal-columns-visibility  .vgse-restore-removed-columns', function (e) {
		e.preventDefault();
		jQuery.post(ajaxurl, {
			action: 'vgse_restore_columns',
			nonce: jQuery('.modal-columns-visibility form').data('nonce'),
			post_type: jQuery('.modal-columns-visibility form input[name="post_type"]').val()
		}, function (response) {
			if (response.success) {
				alert(response.data.message);
			} else {
				notification({mensaje: response.data.message, tipo: 'error', tiempo: 30000});
			}
		});
	});
	jQuery('body').on('click', '.modal-columns-visibility  form .remove-column', function (e) {
		e.preventDefault();
		var $button = jQuery(this);
		var columnKey = $button.parent().find('.js-column-key').val();

		window.lastColumnKeyRemoved = columnKey;
		$button.parent().remove();

		jQuery.post(ajaxurl, {
			action: 'vgse_remove_column',
			nonce: jQuery('.modal-columns-visibility form').data('nonce'),
			post_type: jQuery('.modal-columns-visibility form input[name="post_type"]').val(),
			column_key: columnKey
		}, function (response) {
			if (response.success) {
				itemsMoved();
			} else {
				notification({mensaje: response.data.message, tipo: 'error', tiempo: 30000});
			}
		});
		return false;
	});
	itemsMoved();
}

// We need to initialize the form when the popup opens, the sortable plugin 
// requires the elements to be visible
// When we're not in the spreadsheet page, we initialize on page load
jQuery(document).on('opened', '.modal-columns-visibility', function () {
	vgseColumnsVisibilityInit();
	vgseColumnsVisibilityEqualizeHeight();
});
jQuery(window).load(function () {
	if (!jQuery('.be-spreadsheet-wrapper').length) {
		vgseColumnsVisibilityInit();
	}
});