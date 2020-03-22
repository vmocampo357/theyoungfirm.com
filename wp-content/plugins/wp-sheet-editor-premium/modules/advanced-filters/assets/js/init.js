jQuery(document).ready(function () {
	var $filtersForm = jQuery('#be-filters');

	if (!$filtersForm.length) {
		return true;
	}

	var $advancedGroupTemplate = $filtersForm.find('.advanced-filters .base');
	$advancedGroupTemplate.removeClass('base');
	window.advancedGroupTemplate = $advancedGroupTemplate[0].outerHTML;
	$advancedGroupTemplate.remove();
	$filtersForm.on('click', '.remove-advanced-filter', function (e) {
		e.preventDefault();

		jQuery(this).parents('li').remove();
	});
	$filtersForm.find('.new-advanced-filter').click(function (e) {
		e.preventDefault();

		var html = window.advancedGroupTemplate.replace(new RegExp(/\[\]/, 'g'), '[' + ($filtersForm.find('.advanced-filters ul').children().length + 1) + ']');
		$filtersForm.find('.advanced-filters ul').prepend(html);
		$filtersForm.find('.advanced-filters ul').children().show();
	});
	$filtersForm.find('.new-advanced-filter').trigger('click');
	$filtersForm.find('.advanced-filters-toggle').change(function () {
		if (jQuery(this).is(':checked')) {
			$filtersForm.find('.advanced-filters').show();
		} else {
			$filtersForm.find('.advanced-filters').hide();
		}
	});
});