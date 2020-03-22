jQuery(document).ready(function () {
	var $filtersForm = jQuery('#be-filters');
	var $filtersPopup = $filtersForm.parents('.remodal');

	if (!$filtersForm.length) {
		return true;
	}

	$filtersPopup.submit(function (e) {
		e.preventDefault();

		var filters = $filtersForm.serialize();
		var $selects = $filtersForm.find('select.select2');
		$selects.each(function () {
			var $select = jQuery(this);
			if (!$select.val()) {
				filters += '&' + $select.attr('name') + '=';
			}
		});

		beAddRowsFilter(filters);

		vgseReloadSpreadsheet();

		$filtersPopup.find('.remodal-cancel').trigger('click');
		return false;
	});
});


/**
 * Cell locator
 */
jQuery(document).ready(function () {

	jQuery('body').on('vgSheetEditor:beforeRowsInsert', function (event, response) {

		if (typeof window.cellLocatorAlreadyInit === 'undefined') {
			window.cellLocatorAlreadyInit = true;
			var searchField = document.getElementById('cell-locator-input');
			if (searchField) {
				Handsontable.dom.addEvent(searchField, 'keyup', function (event) {
					if (event.keyCode == 13) {
						var queryResult = hot.getPlugin('search').query(this.value);
						if (queryResult.length) {
							hot.scrollViewportTo(queryResult[0].row, queryResult[0].col, true);
						} else if (this.value) {
							alert('Cells not found. Try with another search criteria.');
						}
						hot.render();
						if (!jQuery('#responseConsole .rows-located').length) {
							jQuery('#responseConsole').append('. <span class="rows-located" />');
						}
						jQuery('#responseConsole .rows-located').text(queryResult.length + ' cells located');
					}
				});
			}
		}
	});
})
		;

