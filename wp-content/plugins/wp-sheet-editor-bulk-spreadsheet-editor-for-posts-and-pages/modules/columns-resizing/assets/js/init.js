jQuery(document).ready(function () {

	if (typeof hot === 'undefined') {
		return false;
	}
	hot.updateSettings({
		afterColumnResize: _throttle(function () {
			var manualWidth = hot.getPlugin('ManualColumnResize').manualColumnWidths;
			var colWidth = hot.getSettings().colWidths;
			var newWidth = jQuery.extend(true, colWidth, manualWidth);

			var columns = hot.getSettings().columns;
			var finalSizes = {};

			jQuery.each(columns, function (key, value) {
				finalSizes[ value.data ] = newWidth[ key ];
			});

			console.log(newWidth, finalSizes);

			var nonce = jQuery('.remodal-bg').data('nonce');

			jQuery.ajax({
				type: "POST",
				url: ajaxurl,
				data: {action: "vgse_save_manual_column_resize", nonce: nonce, post_type: jQuery('#post-data').data('post-type'), sizes: finalSizes},
				dataType: 'json',
				success: function (response) {

					console.log(response);
				}
			});
		}, 15000, true)
	});
});