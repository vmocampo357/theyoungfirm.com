/**
 * Add html field to container
 * @param obj $container jQuery object
 * @param obj settings
 * @returns obj jQuery object
 */
function vgseAddField($container, settings) {
	$container.append('<div class="vg-field"><' + settings.tag + ' /></div>');
	$field = $container.find('.vg-field').last();

	if (settings.tag === 'select' && settings.options) {
		$field.find(settings.tag).append(settings.options);
	}

	$field.find(settings.tag).attr(settings.html_attrs);
	if (settings.label && settings.tag !== 'a') {
		$field.prepend('<label>' + settings.label + '</label>');
	}
	if (settings.label && settings.tag === 'a') {
		$field.find(settings.tag).text(settings.label);
	}
	if (settings.description) {
		$field.append('<p>' + settings.description + '</p>');
	}

	if (settings.tag === 'a') {
		$field.append('<input type="hidden" />');
	}
	return $field;
}
jQuery(document).ready(function () {
	// Display loading screen when submitting formulas modal form
	var $formFormulas = jQuery(".modal-formula form");
	
	// When we are in the formulas modal and click on "search rows",
	// we open the search modal and modify the search button to open
	// back the formulas modal
	$formFormulas.find('.wpse-formula-post-query').click(function(e){
		e.preventDefault();
		window.vgseBackToFormula = true;
		jQuery('[name="run_filters"]').first().click();
	});
	
	// When we are in the formulas modal we revert the changes in the search button
	jQuery(document).on('closed', '[data-remodal-id="modal-filters"]', function () {
		if( typeof window.vgseBackToFormula !== 'undefined' && window.vgseBackToFormula){
			window.vgseBackToFormula = false;
			jQuery('[data-remodal-target="modal-formula"]').first().click();
		}
	});
	$formFormulas.find('input[name="use_search_query"]').change(function(){
		if( jQuery(this).is(':checked')){
			$formFormulas.find('.taxonomies,.keyword,.keyword-exclude').hide();
		} else {
			$formFormulas.find('.taxonomies,.keyword,.keyword-exclude').show();
		}
	});
	
	jQuery(".modal-formula form").submit(function () {
		loading_ajax({estado: true});
		
		var $form = jQuery(this);

		if( $form.find('input[name="use_search_query"]:checked').length && typeof beGetRowsFilters === 'function' ){
			$form.find('input[name="filters"]').val(beGetRowsFilters());
			$form.find('input[name="filters_found_rows"]').val(window.beFoundRows);
		}
		
		if( ! window.beFoundRows ){
			alert(vgse_editor_settings.texts.no_rows_for_formula);
			loading_ajax({estado: false});
			return false;
		}

		var $formula = jQuery('.formula-field input');

		if (!$formula.val()) {
			alert(vgse_formulas_data.texts.formula_required);
			loading_ajax({estado: false});
			return false;
		}
	});

	// Formula builder
	var $formula = jQuery('.formula-field');
	var $formulaBuilder = jQuery('.formula-builder');
	var $columnSelector = jQuery('.column-selector select')

	var $actions = vgseAddField($formulaBuilder, {
		tag: 'select',
		label: vgse_formulas_data.texts.action_select_label,
		html_attrs: {
			name: 'action_name',
			class: 'action_name',
			id: 'action_name',
		},
		options: '<option value="" class="placeholder">' + vgse_formulas_data.texts.action_select_placeholder + '</option>'
	});
	$formulaBuilder.append('<div class="builder-fields"></div>');
	var $builderData = $formulaBuilder.find('.builder-fields');
	jQuery.each(vgse_formulas_data.columns_actions, function (columnKey, columnFields) {
		if (columnFields === 'default') {
			columnFields = vgse_formulas_data.default_actions;
		} else {
			var newColumnFields = {};

			jQuery.each(columnFields, function (fieldKey, fieldSettings) {
				if (fieldSettings === 'default') {
					fieldSettings = vgse_formulas_data.default_actions[fieldKey];
				} else {
					fieldSettings = jQuery.extend({}, vgse_formulas_data.default_actions[fieldKey], fieldSettings);
					/*if (columnKey === 'file_upload_multiple') {
					 console.log(columnKey);
					 console.log(fieldKey);
					 console.log(fieldSettings);
					 }*/
				}
				newColumnFields[fieldKey] = fieldSettings;
			});

			columnFields = newColumnFields;
		}


		vgse_formulas_data.columns_actions[columnKey] = columnFields;
	});


// Update actions when selecting a column
	$columnSelector.change(function (e) {
		var $column = jQuery(this);
		var column = $column.val();
		var valueType = $column.find('option:selected').data('value-type');

		columnFields = vgse_formulas_data.columns_actions[valueType];

		$actions.find('select option:not(.placeholder)').remove();
		$builderData.empty();

		// Add actions options
		jQuery.each(columnFields, function (fieldKey, fieldSettings) {
			$actions.find('select').append('<option value="' + fieldKey + '">' + fieldSettings.label + '</option>');
		});

	});

	// Update action fields when action changes
	$actions.find('select').change(function (e) {
		var $action = jQuery(this);
		var action = $action.val();

		if (!action) {
			return true;
		}
		var columnValueType = $columnSelector.find('option:selected').data('value-type');
		var actionSettings = vgse_formulas_data.columns_actions[columnValueType][action];
		var columnFields = actionSettings.input_fields;

		// Clear existing fields
		$builderData.empty();

//		if (actionSettings.label) {
//			$builderData.append('<h3 class="action-title">' + actionSettings.label + '</h3>');
//		}
		if (actionSettings.description) {
			actionSettings.description = actionSettings.description.replace('%target_column%', $columnSelector.val());
			$builderData.append('<p class="action-description">' + actionSettings.description + '</p>');
		}
		$builderData.append('<div class="action-fields"></div>');
		var $actionFields = $builderData.find('.action-fields');
		
		var defaultFieldSettings = {
			tag: '',
			label: '',
			description: '',
			html_attrs: {}
		};
		jQuery.each(columnFields, function (fieldKey, fieldSettings) {
			fieldSettings = jQuery.extend(defaultFieldSettings, fieldSettings );
			if (!actionSettings.fields_relationship) {
				actionSettings.fields_relationship = 'AND';
			}
			if ( typeof fieldSettings.html_attrs === 'undefined' || typeof fieldSettings.html_attrs.name === 'undefined' || !fieldSettings.html_attrs.name) {
				fieldSettings.html_attrs.name = 'formula_data[]';
			}
			$actionFields.append('<span class="relationship-' + actionSettings.fields_relationship.toLowerCase() + ' relationship">' + actionSettings.fields_relationship + '</span>');
			vgseAddField($actionFields, fieldSettings);
		});

		// Init select2 on selects        
		if ($actionFields.find('.select2') && typeof vgseInitSelect2 === 'function') {
			vgseInitSelect2();
		}

		var $actionFieldsInputs = $actionFields.find('input,select,textarea');

		// Generate spreadsheet function when action fields change
		$actionFieldsInputs.change(function (e) {
			var $formula = jQuery('.formula-field input');
			var formula = vgseExecuteFunctionByName(actionSettings.jsCallback, window, {
				changedField: jQuery(this),
				actionFields: $actionFieldsInputs,
				actionSettings: actionSettings,
				firstField: $actionFieldsInputs.first(),
				firstFieldValue: $actionFieldsInputs.first().val(),
			});

			if (!formula) {
				formula = '';
			}
			$formula.val(formula);
		});
	});


// media button
	jQuery('body').on('click', '.wp-media.button', function (e) {
		loading_ajax({estado: true});

		var $button = jQuery(this);
		var $field = $button.parent().find('input');
		var multiple = $button.data('multiple');

		var scrollLeft = jQuery('body').scrollLeft();
		var gallery = [];

		var scrollTop = jQuery(document).scrollTop();
		var currentInfiniteScrollStatus = jQuery('#infinito').prop('checked');
		jQuery('#infinito').prop('checked', false);

		media_uploader = wp.media({
			frame: "post",
			state: "insert",
			multiple: multiple
		});


		media_uploader.state('embed').on('select', function () {
			var state = media_uploader.state(),
					type = state.get('type'),
					embed = state.props.toJSON();

			embed.url = embed.url || '';

			console.log(embed);
			console.log(type);
			console.log(state);

			if (type === 'image' && embed.url) {
				// Guardar img					
				$field.val(embed.url).trigger('change');
				$field.after('<div class="selected-files"></div>');
				$field.next('.selected-files').append('<img src="' + embed.url + '" width="80" height="80"/>');

			}



		});

		media_uploader.on('close', function () {
			jQuery('body').scrollLeft(scrollLeft);
			jQuery(window).scrollTop(scrollTop);
			jQuery('#infinito').prop('checked', currentInfiniteScrollStatus);
		});
		media_uploader.on("insert", function () {
			jQuery('body').scrollLeft(scrollLeft);
			var selection = media_uploader.state().get("selection");
			var length = selection.length;
			var images = selection.models


			console.log(images);
			if (!images.length) {
				return true;
			}
			$field.after('<div class="selected-files"></div>');
			for (var iii = 0; iii < length; iii++) {
				file = images[iii].toJSON();
				console.log(file);
				gallery.push(images[iii].id);
				$field.next('.selected-files').append('<img src="' + file.sizes.thumbnail.url + '" width="80" height="80"/>');

			}

			$field.val(gallery).trigger('change');



		});
		media_uploader.open();
		loading_ajax({estado: false});
	});
});

function vgseGenerateMathFormula(data) {
	console.log(data);
	if (!data.firstFieldValue) {
		return false;
	}
	return '=MATH("' + data.firstFieldValue + '")';
}

function vgseGenerateDecreasePercentageFormula(data) {
	console.log(data);
	if (!data.firstFieldValue) {
		return false;
	}
	var percentageDecrease = parseFloat(data.firstFieldValue);
	var finalPercentage = 100 - percentageDecrease;
	var toDoubleDigits = ("0" + finalPercentage).slice(-2);
	return '=MATH("$current_value$ * 0.' + toDoubleDigits + '")';
}

function vgseGenerateIncreasePercentageFormula(data) {
	console.log(data);
	if (!data.firstFieldValue) {
		return false;
	}
	var value = ("0" + data.firstFieldValue).slice(-2);
	return '=MATH("$current_value$ * 1.' + value + '")';
}
function vgseGenerateDecreaseFormula(data) {
	console.log(data);
	if (!data.firstFieldValue) {
		return false;
	}
	return '=MATH("$current_value$ - ' + data.firstFieldValue + '")';
}
function vgseGenerateIncreaseFormula(data) {
	console.log(data);
	if (!data.firstFieldValue) {
		return false;
	}
	return '=MATH("$current_value$ + ' + data.firstFieldValue + '")';
}
function vgseGenerateSetValueFormula(data) {
	console.log(data);

	if (!data.actionSettings.fields_relationship) {
		data.actionSettings.fields_relationship = 'AND';
	}
	if (data.actionSettings.fields_relationship.toLowerCase() === 'or' && data.actionFields.length > 1) {
		var finalValue = '';
		data.actionFields.each(function () {
			if (jQuery(this).val()) {
				finalValue = jQuery(this).val();
			}
		});

		data.firstFieldValue = finalValue;
	}
	if (!data.firstFieldValue) {
		return false;
	}
	return '=REPLACE(""$current_value$"",""' + data.firstFieldValue + '"")';
}
function vgseGenerateAppendFormula(data) {
	console.log(data);
	if (!data.firstFieldValue) {
		return false;
	}
	var $columnSelector = jQuery('.column-selector select');

	var joiner = '';
	if ($columnSelector.find('option:selected').data('value-type') === 'post_terms') {
		joiner = ',';
	}
	return '=REPLACE(""$current_value$"",""$current_value$' + joiner + data.firstFieldValue + '"")';
}
function vgseGenerateCustomFormula(data) {
	console.log(data);
	if (!data.firstFieldValue) {
		return false;
	}
	if (data.firstFieldValue.indexOf('=REPLACE') < 0 && data.firstFieldValue.indexOf('=MATH') < 0) {
		alert(vgse_formulas_data.texts.wrong_formula);
		return '';
	}

	return data.firstFieldValue;
}
function vgseGeneratePrependFormula(data) {
	console.log(data);
	if (!data.firstFieldValue) {
		return false;
	}
	return '=REPLACE(""$current_value$"",""' + data.firstFieldValue + '$current_value$"")';
}
function vgseGenerateReplaceFormula(data) {
	console.log(data);
	var $columnSelector = jQuery('.column-selector select');

	if ($columnSelector.find('option:selected').data('value-type') === 'post_terms') {
		data.actionFields = data.actionFields.filter('select');
	}

	var parts = [];
	data.actionFields.each(function () {
		var value = jQuery(this).val() || '';

		parts.push('""' + value + '""');
	});
	if (parts.length < 2) {
		return false;
	}
	return '=REPLACE(' + parts.join(',') + ')';
}
function vgseGenerateMergeFormula(data) {
	console.log(data);

	var columns = '';
	data.actionFields.each(function () {
		var value = jQuery(this).val();
		if (value && !columns) {
			columns = value;
		}
	});
	if (!columns) {
		return false;
	}
	return '=REPLACE(""$current_value$"",""' + columns + '"")';
}

/*Simple tabs*/
jQuery(document).ready(function () {
	var $simpleTabs = jQuery('.vgse-simple-tabs');

	$simpleTabs.each(function () {
		var $widget = jQuery(this);

		$widget.find('a').first().trigger('click');
		$widget.find('a').click(function (e) {
			e.preventDefault();

			var tabContentId = jQuery(this).attr('href');
			$widget.find('a').removeClass('active');
			jQuery(this).addClass('active');


			jQuery('.vgse-simple-tab-content').removeClass('active');
			jQuery(tabContentId).addClass('active');
		});
	});
});

jQuery(document).ready(function () {
	jQuery('body').on('click', '.save-formula', function (e) {
		e.preventDefault();

		loading_ajax({estado: true});
		var $button = jQuery(this);

		var $applyFuturePostsInput = jQuery('.apply-to-future-posts-field input:checkbox');
		if (!$applyFuturePostsInput.is(':checked')) {
			$applyFuturePostsInput.prop('checked', true);
		}

		var data = jQuery('#vgse-create-formula').serializeArray();
		data.push({
			name: 'action',
			value: 'vgse_save_formula'
		});

		jQuery.post(ajaxurl, data, function (response) {
			if (response.success) {
				// close modal
				var modalInstance = jQuery('[data-remodal-id="modal-formula"]').remodal();
				modalInstance.close();
			}

			loading_ajax({estado: false});
		});
	});
	jQuery('body').on('click', '.delete-saved-formula', function (e) {
		e.preventDefault();

		loading_ajax({estado: true});
		var $button = jQuery(this);

		jQuery.post(ajaxurl, {
			action: 'vgse_delete_saved_formula',
			nonce: jQuery('#vgse-wrapper').data('nonce'),
			formula_index: $button.data('formula-index')
		}, function (response) {
			if (response.success) {
				$button.parents('li').slideUp();
				$button.parents('li').remove();
			}

			loading_ajax({estado: false});
		});
	});
});