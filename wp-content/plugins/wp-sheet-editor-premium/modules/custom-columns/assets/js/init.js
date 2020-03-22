jQuery(document).ready(function () {

	function initTooltips() {
		jQuery('body').find('.tipso').tipso({
			size: 'small',
			tooltipHover: true,
			background: '#444444'

		});
	}

	jQuery('.select2').select2({
		tags: true,
		selectOnBlur: true
	});
	initTooltips();

	// Add #ohsnap element, which contains the user notifications
	jQuery('body').append('<div id="ohsnap" style="z-index: -1"></div>');

	/**
	 * Set column title as dropdown title
	 * @param obj $column Column element, jQuery object
	 * @returns null
	 */
	function setColumnTitle($column) {

		if (!$column) {
			$column = jQuery('.column-wrapper');
		}

		$name = $column.find('.name-field');

		if (!$column.find('.column-title').length) {
			$column.prepend('<h3 class="column-title"><span class="text"></span> <i class="fa fa-chevron-down"></i></h3>');
		}
		$column.find('.column-title .text').text($name.val());
	}

	// Set column titles
	jQuery('.column-wrapper').each(function () {
		setColumnTitle(jQuery(this));
	});

	// Set dropdown title and add column key automatically when changing column name.
	jQuery('body').on('change', '.column-wrapper .name-field', function () {
		var $column = jQuery(this).parents('.column-wrapper');
		setColumnTitle($column);

		var $key = $column.find('.key-field');
		var $name = $column.find('.name-field');

		// Set key automatically
		if (!$key.val() && $name.val()) {
			var name = $name.val();
			name = name.replace(/(\d+)/gi, '').replace(/(\s+)/gi, '_').replace(/[^a-z\_]/gi, '_').toLowerCase();
			$key.val(name);
		}

	});

	// Toggle dropdowns
	jQuery('body').on('click', '.column-title', function () {
		var $column = jQuery(this).parents('.column-wrapper');
		var $fields = $column.find('.column-fields-wrapper');

		$fields.slideToggle();
	});

	// Toggle existing columns after page load
	$columnTitles = jQuery('.column-title');

	if ($columnTitles.length > 1) {
		$columnTitles.trigger('click');
	}

	// Toggle existing columns when adding a new column
	jQuery('body').on('click', '.add-column', function () {
		var $columns = jQuery(this).parents('form').find('.column-wrapper:not(:last)');
		var $fields = $columns.find('.column-fields-wrapper:visible');

		$fields.each(function () {
			var $columnFields = jQuery(this);
			var $columnName = $columnFields.find('.name-field');
			if ($columnName.val()) {
				$columnFields.slideToggle();
			}
		});



		jQuery('.mode-field').trigger('change');

		var $newColumn = jQuery(this).parents('form').find('.column-wrapper:last');
		$newColumn.find('.column-title .text').text('');

		initTooltips();
		jQuery('.select2').select2({
			tags: true,
			selectOnBlur: true
		});
	});

	// Init columns repeater
	jQuery('.repeater').repeater({
		// (Optional)
		// start with an empty list of repeaters. Set your first (and only)
		// "data-repeater-item" with style="display:none;" and pass the
		// following configuration flag
		initEmpty: false,
		// (Optional)
		// "defaultValues" sets the values of added items.  The keys of
		// defaultValues refer to the value of the input's name attribute.
		// If a default value is not specified for an input, then it will
		// have its value cleared.
		defaultValues: vg_sheet_editor_custom_columns.default_values,
		// (Optional)
		// "show" is called just after an item is added.  The item is hidden
		// at this point.  If a show callback is not given the item will
		// have jQuery(this).show() called on it.
		show: function () {
			jQuery(this).slideDown();
			jQuery(this).find('.select2-container').remove();
			jQuery(this).find('select.select2').select2({
				tags: true,
				selectOnBlur: true
			});
		},
		// (Optional)
		// "hide" is called when a user clicks on a data-repeater-delete
		// element.  The item is still visible.  "hide" is passed a function
		// as its first argument which will properly remove the item.
		// "hide" allows for a confirmation step, to send a delete request
		// to the server, etc.  If a hide callback is not given the item
		// will be deleted.
		hide: function (deleteElement) {
			if (confirm(vg_sheet_editor_custom_columns.texts.confirm_delete)) {
				jQuery(this).slideUp(deleteElement);
			}
		},
		// (Optional)
		// You can use this if you need to manually re-index the list
		// for example if you are using a drag and drop library to reorder
		// list items.
		ready: function (setIndexes) {
		},
		// (Optional)
		// Removes the delete button from the first list item,
		// defaults to false.
		isFirstItemUndeletable: false
	});

	// Save columns
	jQuery('.save').click(function (e) {
		e.preventDefault();

		loading_ajax({estado: true});
		var $form = jQuery(this).parents('form');
		jQuery.post($form.attr('action'), $form.serializeArray(), function (response) {
			loading_ajax({estado: false});
			notification({
				'tipo': (response.success) ? 'success' : 'error',
				'mensaje': response.data
			});
		});
	});

	// Hide advanced fields
	jQuery('.mode-field').change(function () {
		var $checkbox = jQuery(this);
		var advancedFieldsSelectors = [
			'read-only',
			'formulas',
			'hide',
			'rename',
			'cell-type',
			'plain-renderer',
			'formatted-renderer',
			'width',
			'data-source'
		];


		advancedFieldsSelectors.forEach(function (selector, index) {
			var completeSelector = '.field-container-' + selector;
			console.log(completeSelector);
			if ($checkbox.is(':checked')) {
				jQuery(completeSelector).show();
			} else {
				jQuery(completeSelector).hide();
			}
		});
	});
	jQuery('.mode-field').trigger('change');
});