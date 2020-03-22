function vgseCustomTooltip($element, text, position, multipleTimes) {
	if (!position) {
		position = 'bottom';
	}
	$element.tipso({
		position: position,
		tooltipHover: true,
		content: text,
	}).tipso('show');

	if (multipleTimes) {
		$element.hover(function () {
			$element.tipso('hide');
		});
		setTimeout(function () {
			$element.tipso('hide');
		}, 8000);
	}
	setTimeout(function () {
		$element.tipso('hide');
		if (!multipleTimes) {
			$element.tipso('destroy');
		}
	}, 8000);
}
/**
 * Turn query string into object
 * @param str query
 * @returns obj
 */
function beParseParams(query) {
	var query_string = {};
	var vars = query.split("&");
	for (var i = 0; i < vars.length; i++) {
		var pair = vars[i].split("=");
		pair[0] = decodeURIComponent(pair[0]);
		pair[1] = decodeURIComponent(pair[1]);
		// If first entry with this name
		if (typeof query_string[pair[0]] === "undefined") {
			query_string[pair[0]] = pair[1];
			// If second entry with this name
		} else if (typeof query_string[pair[0]] === "string") {
			var arr = [query_string[pair[0]], pair[1]];
			query_string[pair[0]] = arr;
			// If third or later entry with this name
		} else {
			query_string[pair[0]].push(pair[1]);
		}
	}
	return query_string;
}

/**
 * Get rows filters
 * @returns str Filters as query string
 */
function beGetRowsFilters() {
	return (jQuery('body').data('be-filters')) ? jQuery.param(jQuery('body').data('be-filters')) : '';
}
/**
 * Add rows filter
 * @param str|obj filter as query string or object
 * @returns Object|Boolean Current filters object or false on error
 */
function beAddRowsFilter(filter) {
	if (!filter) {
		return false;
	}
	var currentFilters = jQuery('body').data('be-filters');
	if (!currentFilters) {
		currentFilters = {};
	}

	var newFilterObj = (typeof filter === 'string') ? beParseParams(filter) : filter;
	var allFilters = jQuery.extend(currentFilters, newFilterObj);

	var $currentFiltersHolders = jQuery('.vgse-current-filters');
	$currentFiltersHolders.empty();

	$currentFiltersHolders.each(function () {
		var $currentFilters = jQuery(this);
		jQuery.each(allFilters, function (filterKey, filterValue) {
			if (filterValue && filterKey.indexOf('meta_query') < 0) {
				var publicValue = (typeof filterValue === 'string') ? filterValue : filterValue.join(', ');
				var publicKey = filterKey.replace('[]', '');
				$currentFilters.append('<a href="#" class="button" data-filter-key="' + filterKey + '"><i class="fa fa-remove"></i> ' + publicKey + ': ' + publicValue + '</a>');
			}
			if (filterValue && filterKey.indexOf('meta_query') > -1 && !$currentFilters.find('.advanced-filter').length) {
				var filterKey = 'meta_query';
				var publicKey = 'Advanced filter';
				$currentFilters.append('<a href="#" class="button advanced-filter" data-filter-key="' + filterKey + '"><i class="fa fa-remove"></i> ' + publicKey + '</a>');
			}
		});
	});

	jQuery('body').data('be-filters', allFilters);

	return allFilters;
}

/* Ajax calls loop 
 * Execute ajax calls one after another
 * */
function beAjaxLoop(args) {

	//setup an array of AJAX options, each object is an index that will specify information for a single AJAX request

	var defaults = {
		totalCalls: null,
		current: 1,
		url: '',
		method: 'GET',
		dataType: 'json',
		data: {},
		prepareData: function (data, settings) {
			return data;
		},
		onSuccess: function (response, settings) {

		},
		onError: function (jqXHR, textStatus, settings) {

		},
		status: 'running',
	};

	var settings = jQuery.extend(defaults, args);


	//declare your function to run AJAX requests
	function do_ajax() {

		//check to make sure there are more requests to make
		if (settings.current < settings.totalCalls + 1) {

			if (settings.status !== 'running') {
//				console.log('not running');
				return true;
			}

			if (jQuery.isArray(settings.data)) {
				settings.data.push({
					name: 'page',
					value: settings.current
				});
			} else {
				settings.data.page = settings.current;
			}

//			console.log(settings);

			var data = {
				url: settings.url,
				dataType: settings.dataType,
				data: settings.prepareData(settings.data, settings),
				method: settings.method,
			};
//			console.log(data);
			jQuery.ajax(data).done(function (serverResponse) {

//				console.log(serverResponse);
				var goNext = settings.onSuccess(serverResponse, settings);

				//increment the `settings.current` counter and recursively call this function again
				if (goNext) {
					settings.current++;

					setTimeout(function () {
						do_ajax();
					}, parseInt(vgse_editor_settings.wait_between_batches) * 1000);
				}
			}).fail(function (jqXHR, textStatus) {

//				console.log(jqXHR);
//				console.log(textStatus);
				var goNext = settings.onError(jqXHR, textStatus, settings);
				//increment the `settings.current` counter and recursively call this function again
				if (goNext) {
					settings.current++;
					setTimeout(function () {
						do_ajax();
					}, parseInt(vgse_editor_settings.wait_between_batches) * 1000);
				}
			});
		}
	}

	//run the AJAX function for the first time once `document.ready` fires
	do_ajax();

	return {
		pause: function () {
			settings.status = 'paused';
		},
		resume: function () {
			settings.status = 'running';
//			console.log('resuming');
			do_ajax();
		}
	};
}


//  show or hide loading screen
function loading_ajax(options) {
	
	if( typeof options === 'boolean'){		
		options = {
			'estado': options
		};
	}
	
	var defaults = {
		'estado': true
	}
	jQuery.extend(defaults, options);

	if (defaults.estado == true) {
		if (!jQuery('body').find('.sombra_popup').length) {
			jQuery('body').append('<div class="sombra_popup be-ajax"><div class="sk-three-bounce"><div class="sk-child sk-bounce1"></div><div class="sk-child sk-bounce2"></div><div class="sk-child sk-bounce3"></div></div></div>');
		}
		jQuery('.sombra_popup').fadeIn(1000);
	} else {
		jQuery('.sombra_popup').fadeOut(800, function () {
//			jQuery('.sombra_popup').remove();
		});
	}
}


// Show notification to user
function notification(options) {
	var defaults = {
		'tipo': 'success',
		'mensaje': '',
		'time': 8600
	}
	jQuery.extend(defaults, options);

	setTimeout(function () {
		if (defaults.tipo == 'success') {
			var color = 'green';
		} else if (defaults.tipo == 'error') {
			var color = 'red';
		} else if (defaults.tipo == 'warning') {
			var color = 'orange';
		} else {
			var color = 'blue';
		}

		jQuery('#ohsnap').css('z-index', '1100000');
		setTimeout(function () {
			jQuery('#ohsnap').css('z-index', '-1');
		}, defaults.time);
		ohSnap(defaults.mensaje, {time: defaults.time, color: color});

	}, 500);
}


// Define chunk method to split arrays in groups
if (typeof Array.prototype.chunk === 'undefined') {
	Object.defineProperty(Array.prototype, 'chunk', {
		value: function (chunkSize) {
			var array = this;
			return [].concat.apply([],
					array.map(function (elem, i) {
						return i % chunkSize ? [] : [array.slice(i, i + chunkSize)];
					})
					);
		}
	});
}

/**
 * Show notification to user after a failed ajax request.
 * Ex. the server is not available
 */
jQuery(document).ajaxError(function (event, xhr, ajaxOptions, thrownError) {
//	console.log(event);
//	console.log(xhr);
//	console.log(ajaxOptions);
//	console.log(thrownError);

	loading_ajax({estado: false});
	if (xhr.statusText !== 'abort') {
		if (xhr.status == 400) {
			notification({mensaje: vgse_editor_settings.texts.http_error_400, tipo: 'error', tiempo: 60000});
		} else if (xhr.status == 403) {
			notification({mensaje: vgse_editor_settings.texts.http_error_403, tipo: 'error', tiempo: 60000});
		} else if (xhr.status == 500 || xhr.status == 502 || xhr.status == 505) {
			notification({mensaje: vgse_editor_settings.texts.http_error_500_502_505, tipo: 'error', tiempo: 60000});
		} else if (xhr.status == 503) {
			notification({mensaje: vgse_editor_settings.texts.http_error_503, tipo: 'error', tiempo: 60000});
		} else if (xhr.status == 509) {
			notification({mensaje: vgse_editor_settings.texts.http_error_509, tipo: 'error', tiempo: 60000});
		} else if (xhr.status == 504) {
			notification({mensaje: vgse_editor_settings.texts.http_error_504, tipo: 'error', tiempo: 60000});
		} else {
			notification({mensaje: vgse_editor_settings.texts.http_error_default, tipo: 'error', tiempo: 60000});
		}
	}
});

/**
 * Show notification to user after a successful ajax request.
 */
jQuery(document).ajaxComplete(function (event, xhr, ajaxOptions, thrownError) {
//	console.log(event);
//	console.log(xhr);
//	console.log(ajaxOptions);
//	console.log(thrownError);

	if (xhr.statusText !== 'abort') {
		if (xhr.responseText === '0' || xhr.responseText === 0 || thrownError) {
//		console.log('empty response');
			loading_ajax({estado: false});
			notification({mensaje: vgse_editor_settings.texts.http_error_500_502_505, tipo: 'error', tiempo: 60000});
		}
	}
});

/**
 * Load posts into the spreadsheet
 * @param obj data ajax request data parameters
 * @param fun callback
 * @param bool customInsert If we want to load rows but use custom success controller.
 */
function beLoadPosts(data, callback, customInsert, removeExisting) {
	loading_ajax({estado: true});

	if (!customInsert) {
		customInsert = true;
	}
	if (!removeExisting) {
		removeExisting = false;
	}
	data.action = 'vgse_load_data';

	// Apply filters to request
	data.filters = beGetRowsFilters();
	jQuery.ajax({
		url: ajaxurl,
		dataType: 'json',
		type: 'POST',
		data: data,
		dataType: 'json',
	}).success(function (response) {

		jQuery('body').trigger('vgSheetEditor:beforeRowsInsert', [response, data, callback, customInsert, removeExisting]);
		if (typeof callback === 'function') {
			callback(response);

			if (customInsert) {
				return true;
			}
		}
		if (response.success) {

			// Add rows to spreadsheet			
			vgseAddFoundRowsCount(response.data.total);

			vgAddRowsToSheet(response.data.rows, null, removeExisting);

			notification({mensaje: vgse_editor_settings.texts.posts_loaded});
			loading_ajax({estado: false});

			jQuery('.ht_clone_top.handsontable').remove();
		} else {
			// Disable loading screen and notify of error
			loading_ajax({estado: false});

			notification({mensaje: response.data.message, tipo: 'error', tiempo: 60000});
			vgseAddFoundRowsCount(0);
		}
	});
}

/**
 * Remove duplicated items from array
 * @param array data
 * @returns array
 */
function beDeduplicateItems(data) {
	var out = [];
	var type = (data[0] instanceof Array) ? 'array' : 'object';
	jQuery.each(data, function (key, item) {
		var id = (type === 'array') ? item[0] : item.ID;

		if (typeof id === 'string') {
			id = id.replace(/[^0-9]/gi, '');
		}
		if (!out[ id ]) {
			out[ id ] = item;
		}
	});
	return out;
}

/**
 * Get modified object properties
 * @param obj orig
 * @param obj update
 * @returns obj
 */
function beGetModifiedObjectProperties(orig, update) {
	var diff = {};

	Object.keys(update).forEach(function (key) {
		if (!orig || typeof orig[key] === 'undefined' || update[key] != orig[key]) {
			diff[key] = update[key];
		}
	})

	console.log(diff);
	return diff;
}

/**
 * Check if arrays are identical recursively
 * @param array arr1
 * @param array arr2
 * @returns Boolean
 */
function beArraysIdenticalCheck(arr1, arr2) {
	console.log(arr1);
	console.log(arr2);
	if (arr1.length !== arr2.length) {
		return false;
	}
	for (var i = arr1.length; i--; ) {
		if (arr1[i] !== arr2[i]) {
			return false;
		}
	}

	return true;
}
/**
 * Compare arrays and return modified items only.
 * 
 * @param array newData
 * @param array originalData
 * @returns array
 */
function beGetModifiedItems(newData, originalData) {
	var newData = beDeduplicateItems(newData);
	var originalData = beDeduplicateItems(originalData);
	var out = [];

	console.log(newData);
	console.log(originalData);

	var type = (newData[0] instanceof Array) ? 'array' : 'object';

	console.log(type);
	newData.forEach(function (item, id) {
		console.log(id);
		console.log(item);
		console.log(newData[ id ]);
		console.log(originalData[ id ]);

		if (type === 'array' && (typeof originalData[ id ] === 'undefined' || !beArraysIdenticalCheck(newData[ id ], originalData[ id ]))) {
			out.push(item);
		} else if (type === 'object') {

			var modifiedProperties = beGetModifiedObjectProperties(originalData[id], newData[id]);
			console.log(modifiedProperties);

			var saveData;
			if (typeof originalData[id] === 'undefined' || !jQuery.isEmptyObject(modifiedProperties)) {
				if (!originalData[id] || (originalData[id].provider && vgse_editor_settings.saveFullRowPostTypes && vgse_editor_settings.saveFullRowPostTypes.indexOf(originalData[id].provider) > -1)) {
					saveData = newData[id];
				} else {
					modifiedProperties.ID = id;
					saveData = modifiedProperties;
				}
				// Replace file columns html with the file value
				jQuery.each(saveData, function (key, value) {
					if (typeof value === 'string' && value.indexOf('set_custom_images') > -1) {
						var $cellData = jQuery('<div/>').html(value);
						var gallery = $cellData.find('.set_custom_images').attr('data-images');

						saveData[key] = (gallery) ? gallery : '';
					}
				});



				out.push(saveData);
			}


		}
	});

	console.log(out);
	return out;
}

/**
 * Get tinymce editor content
 * @returns string
 */
function beGetTinymceContent() {
	if (jQuery('.wp-editor-area').css('display') !== 'none') {
		var content = jQuery('.wp-editor-area').val() || '';
	} else {
		if (document.getElementById('editpost_ifr')) {
			var frame = document.getElementById('editpost_ifr').contentWindow.document || document.getElementById('editpost_ifr').contentDocument;
			var content = frame.body.innerHTML;
		} else {
			var content = '';
		}
	}

	return content;
}

/**
 * Execute function by string name
 */
function vgseExecuteFunctionByName(functionName, context /*, args */) {
	var args = [].slice.call(arguments).splice(2);
	var namespaces = functionName.split(".");
	var func = namespaces.pop();
	for (var i = 0; i < namespaces.length; i++) {
		context = context[namespaces[i]];
	}
	return context[func].apply(context, args);
}

/**
 * Convert an object to array of values
 * @param obj object
 * @returns Array
 */
function vgObjectToArray(object) {
	var values = [];
	for (var property in object) {
		values.push(object[property]);
	}
	return values;
}


/**
 * Returns a function, that, as long as it continues to be invoked, will not be triggered. The function will be called after it stops being called for N milliseconds. If immediate is passed, trigger the function on the leading edge, instead of the trailing.
 * @param func func
 * @param int wait
 * @param bool immediate
 * @returns func
 */
function _debounce(func, wait, immediate) {
	var timeout, args, context, timestamp, result;

	var later = function () {
		var last = _now() - timestamp;

		if (last < wait && last >= 0) {
			timeout = setTimeout(later, wait - last);
		} else {
			timeout = null;
			if (!immediate) {
				result = func.apply(context, args);
				if (!timeout)
					context = args = null;
			}
		}
	};

	return function () {
		context = this;
		args = arguments;
		timestamp = _now();
		var callNow = immediate && !timeout;
		if (!timeout)
			timeout = setTimeout(later, wait);
		if (callNow) {
			result = func.apply(context, args);
			context = args = null;
		}

		return result;
	};
}
;

/**
 * A (possibly faster) way to get the current timestamp as an integer.
 * @returns int
 */
function _now() {
	var out = Date.now() || new Date().getTime();
	return out;
}

/**
 * Returns a function, that, when invoked, will only be triggered at most once during a given window of time. Normally, the throttled function will run as much as it can, without ever going more than once per wait duration; but if you’d like to disable the execution on the leading edge, pass {leading: false}. To disable execution on the trailing edge, ditto.
 * @param func
 * @param int wait
 * @param obj options
 * @returns func
 */
function _throttle(func, wait, options) {

	if (!wait) {
		wait = 300;
	}
	var context, args, result;
	var timeout = null;
	var previous = 0;
	if (!options)
		options = {};
	var later = function () {
		previous = options.leading === false ? 0 : _now();
		timeout = null;
		result = func.apply(context, args);
		if (!timeout)
			context = args = null;
	};
	return function () {
		var now = _now();
		if (!previous && options.leading === false)
			previous = now;
		var remaining = wait - (now - previous);
		context = this;
		args = arguments;
		if (remaining <= 0 || remaining > wait) {
			if (timeout) {
				clearTimeout(timeout);
				timeout = null;
			}
			previous = now;
			result = func.apply(context, args);
			if (!timeout)
				context = args = null;
		} else if (!timeout && options.trailing !== false) {
			timeout = setTimeout(later, remaining);
		}
		return result;
	};
}
;

/**
 * Remove post ID from array of data
 */
function vgseRemovePostFromSheet(postId, data) {

	console.log(data);
	var newData = [];

	postId = parseInt(postId);
	data.forEach(function (item, id) {
		var item2 = jQuery.extend(true, {}, item);
		console.log(item.ID);

		if (typeof item2.ID === 'string') {
			item2.ID = parseInt(item2.ID.replace(/[^0-9]/gi, ''));
		}
		console.log(item2.ID);
		console.log(postId);
		if (postId !== item2.ID) {
			newData.push(item);
		}
	});
	return newData;
}

/**
 * Add rows to spreadsheet
 * @param array data Array of objects
 * @param str method append | prepend
 * @returns null
 */
function vgAddRowsToSheet(data, method, removeExisting) {
	if (!method) {
		method = 'append';
	}

	if (!data) {
		data = [];
	}

	if (method === 'prepend') {
		data = data.reverse();
	}

	var hotData = hot.getSourceData();
	console.log(hotData);


	// Remove existing items from spreadsheet
	if (removeExisting) {
		data.forEach(function (item, id) {
			var item2 = jQuery.extend(true, {}, item);
			if (typeof item2.ID === 'string') {
				item2.ID = parseInt(item2.ID.replace(/[^0-9]/gi, ''));
			}
			console.log(item2.ID);
			hotData = vgseRemovePostFromSheet(item2.ID, hotData);
		});
	}

	for (i = 0; i < data.length; i++) {
		// Don't add new items already existing on spreadsheet,
		// fixes rare mysql bug that paginated requests sometimes bring repeated rows
		var sheetIds = hot.getDataAtCol(0);
		if (sheetIds.indexOf(data[i].ID) < 0) {
			if (method === 'append') {
				hotData.push(jQuery.extend(true, {}, data[i]));
			} else {
				hotData.unshift(jQuery.extend(true, {}, data[i]));
			}
		}

	}
	hot.loadData(hotData);
	console.log(hotData);

	jQuery('body').trigger('vgSheetEditor:afterRowsInsert', [data, method, removeExisting]);

	// Save original data, used to compare posts 
	// before saving and save only modified posts.
	if (!window.beOriginalData) {
		window.beOriginalData = [];
	}

	window.beOriginalData = jQuery.merge(window.beOriginalData, data);
}
/**
 * save image in local cache
 */
function beSendImageIdToWP(gallery, id, key, type, cellCoords, callback) {

	setTimeout(function () {
		var fileIds = [];
		jQuery.each(gallery, function (index, file) {
			fileIds.push(file.id);
		});

		var cellData = hot.getDataAtCell(cellCoords.row, cellCoords.col);
		var $cellData = jQuery('<div/>').html(cellData);
		$cellData.find('.set_custom_images').text(vgse_editor_settings.texts.use_other_image);
		$cellData.find('.set_custom_images').attr('data-images', fileIds);
		$cellData.find('img').attr('src', gallery[0].url);
		$cellData.find('.hidden').removeClass('hidden');
		console.log(cellData);
		console.log(cellCoords);
		console.log($cellData.html());
		hot.setDataAtCell(cellCoords.row, cellCoords.col, $cellData.html());


		if (typeof callback === 'function') {
			callback(response);
		}
	}, 800);
}

/**
 *Init select2 on <select>s
 */
function vgseInitSelect2( $selects ) {

	if( ! $selects ){
		var $selects = jQuery("select.select2");
	}
	$selects.each(function () {
		var config = {
			placeholder: jQuery(this).data('placeholder'),
			minimumInputLength: jQuery(this).data('min-input-length') || 0,
			allowClear: true
		};
		if (jQuery(this).data('remote')) {
			config.ajax = {
				url: ajaxurl,
				delay: 1000,
				data: function (params) {
					var query = {
						search: params.term,
						page: params.page,
						action: jQuery(this).data('action'),
						output_format: jQuery(this).data('output-format'),
						post_type: jQuery(this).data('post-type') || jQuery('#post-data').data('post-type'),
						nonce: jQuery(this).data('nonce'),
					}

					// Query paramters will be ?search=[term]&page=[page]
					return query;
				},
				processResults: function (response) {
					console.log(response);
					if (!response.success) {
						return {
							results: []
						};
					}
					return {
						results: response.data.data
					};
				},
				cache: true
			};
		}
		jQuery(this).select2(config);
	});
}

/**
 * Reload spreadsheet.
 * Removes current rows and loads the rows from the server again.
 */
function vgseReloadSpreadsheet() {
	var nonce = jQuery('.remodal-bg').data('nonce');
	var $container = jQuery("#post-data")

	// Reset internal cache, used to find the modified cells for saving        
	window.beOriginalData = [];
	// Reset spreadsheet
	hot.loadData([]);

	beLoadPosts({
		post_type: $container.data('post-type'),
		nonce: nonce
	});
}

function vgseAddFoundRowsCount(total) {

	window.beFoundRows = total;
	jQuery('.be-total-rows').text(jQuery('.be-total-rows').text().replace(/\d+/, total));
}
jQuery(document).ready(function (e) {

	jQuery('body').on('click', '.wpse-toggle-head', function () {
		jQuery(this).next('.wpse-toggle-content').slideToggle();
	});

	if (!jQuery('.be-spreadsheet-wrapper').length) {
		return true;
	}


	vgseCustomTooltip(jQuery('.vg-toolbar .settings-container'), vgse_editor_settings.texts.settings_moved_submenu, 'top');


// Image previews
	jQuery('.vi-preview-wrapper').appendTo('body');
	jQuery('body').on('mouseleave', '.vi-preview-img', function (e) {
		console.log(jQuery(this));
		jQuery('.vi-preview-wrapper').hide();
	});
	jQuery('body').on('mouseenter', '.vi-preview-img', function (e) {
		console.log(jQuery(this));
		var $img = jQuery(this).first();
		var img = jQuery(this)[0].outerHTML;
		var imgTag = '<img src="' + $img.attr('src') + '" />';
		console.log(img);
		console.log('imgTag: ', imgTag);
		var $wrapper = jQuery('.vi-preview-wrapper');
		var largeImageAtTheLeft = (jQuery(window).width() - $wrapper.width()) < ($img.offset().left + $img.width() - jQuery(document).scrollLeft());

		if (largeImageAtTheLeft) {
			$wrapper.css({
				right: 'auto',
				left: '0px'
			});
		} else {
			$wrapper.css({
				right: '0px',
				left: 'auto'
			});
		}

		$wrapper.empty();
		$wrapper.show();

		$wrapper.append(imgTag);
	});

	/**
	 * Fix toolbar on scroll
	 */
	function sticky_relocate() {
//		console.log('scrolled');
		var window_top = jQuery(window).scrollTop();
		var div_top = jQuery('#vg-header-toolbar-placeholder').offset().top;
		if (window_top > div_top) {
			jQuery('#vg-header-toolbar').css('top', '');
			jQuery('#vg-header-toolbar').addClass('sticky');
//			jQuery('#vg-header-toolbar').css('left', jQuery( '#vg-header-toolbar' ).position().left + 'px' );
			jQuery('#wpadminbar').hide();
			jQuery('#vg-header-toolbar-placeholder').height(jQuery('#vg-header-toolbar').outerHeight());

		} else {
			jQuery('#wpadminbar').show();
//			jQuery('#vg-header-toolbar').css('left', '' );
			jQuery('#vg-header-toolbar').removeClass('sticky');
			jQuery('#vg-header-toolbar-placeholder').height(0);
		}
	}

	if (jQuery('#vg-header-toolbar').length) {
//		jQuery(window).scroll(sticky_relocate);
		jQuery(window).scroll(_throttle(sticky_relocate, 350));
		sticky_relocate();
	}



	// go to the top
	jQuery('#go-top').click(function (e) {
		e.preventDefault();
		var body = jQuery("html, body");
		body.stop().animate({scrollTop: 0}, '300', 'swing', function () {
		});
	});


	// Add #ohsnap element, which contains the user notifications
	jQuery('body').append('<div id="ohsnap" style="z-index: -1"></div>');

	// Init labelauty, which converts checkboxes into switch buttons
	var $wrapper = jQuery('#vgse-wrapper');

	if ($wrapper.length) {
		$wrapper.find(".vg-toolbar input:checkbox").labelauty();
	}

	// Init tooltips
	jQuery('body').find('.tipso').tipso({
		size: 'small',
		tooltipHover: true,
		background: '#444444'

	});

	/* internal variables */
	var
			$container = jQuery("#post-data"),
			$console = jQuery("#responseConsole"),
			$parent = $container.parent(),
			autosaveNotification,
			maxed = false,
			hot;

	// is cells formatting enabled
	if (jQuery('#formato').is(':checked')) {
		format = false;
	} else {
		format = true;
	}

// Initialize select2 on selects

	setTimeout(function () {
		vgseInitSelect2();
	}, 2000);

// Handsontable settings
	var handsontableArgs = {
		colWidths: vgObjectToArray(vgse_editor_settings.colWidths),
		colHeaders: vgObjectToArray(vgse_editor_settings.colHeaders),
		columns: columns_format(format),
		rowHeaders: true, //Cabeceras
		startRows: vgse_editor_settings.startRows, //Cantidad de filas
		startCols: vgse_editor_settings.startCols, //Cantidad de columnas
		currentRowClassName: 'currentRow',
		currentColClassName: 'currentCol',
		fillHandle: false,
		columnSorting: true,
		contextMenu: ['undo', 'redo', '---------', 'copy', 'cut', 'Paste using keyboard: Ctrl+V', '---------', 'freeze_column', 'unfreeze_column'],
		autoWrapRow: true,
		autoRowSize: false,
		autoColumnSize: false,
		viewportRowRenderingOffset: 20,
		viewportColumnRenderingOffset: 4,
		wordWrap: true,
		minSpareCols: 0,
		minSpareRows: 0,
		width: null,
		height: null,
		manualColumnFreeze: true,
		copyRowsLimit: 99999999, // maximum number of rows that can be copied
		copyColsLimit: 99999999, // maximum number of columns that can be copied
	};

	if (vgse_editor_settings.debug) {
		handsontableArgs.debug = vgse_editor_settings.debug;
	}

	var customHandsontableArgs = (vgse_editor_settings.custom_handsontable_args) ? JSON.parse(vgse_editor_settings.custom_handsontable_args) : {};
	var finalHandsontableArgs = jQuery.extend(handsontableArgs, customHandsontableArgs);

	hot = new Handsontable($container[0], finalHandsontableArgs);

	window.hot = hot;
	window.beFoundRows = 0;

	/**
	 * Load initial posts
	 */
	$parent.find('button[name=load]').click(function () {
		var nonce = jQuery('.remodal-bg').data('nonce');

		beLoadPosts({
			post_type: $container.data('post-type'),
			nonce: nonce
		});

	}).click(); // execute immediately

	/*
	 * If there are no posts, show tooltip asking to create posts
	 */
	jQuery('body').on('vgSheetEditor:beforeRowsInsert', function (event, response) {
		console.log('beforeRowsInsert');
		console.log(response);

		if (!response.success) {

			vgseCustomTooltip(jQuery('#addrow'), vgse_editor_settings.texts.add_posts_here);
		}
	});

	/**
	 * Save changes
	 */
	// Close modal when clicking the cancel button
	jQuery('.bulk-save.remodal').find('.remodal-cancel').click(function (e) {
		var modalInstance = jQuery('[data-remodal-id="bulk-save"]').remodal();
		modalInstance.close();
		jQuery('html,body').scrollLeft(0)
	});
	/**
	 * Change from "saving" state to "confirm before saving" state after closing the modal
	 */
	jQuery('.bulk-save.remodal .bulk-saving-screen').find('.remodal-cancel').click(function (e) {
		jQuery('html,body').scrollLeft(0)

		var $button = jQuery(this);
		var $modal = $button.parents('.remodal');

		$modal.find('.be-saving-warning').show();
		$modal.find('.bulk-saving-screen').hide();
		$modal.find('#be-nanobar-container').empty();
		$button.addClass('hidden');
		$modal.find('.response').empty();
	});
	/**
	 * Change from "confirm before saving" state to "saving" on save modal
	 */
	jQuery('.bulk-save.remodal').find('.remodal-confirm').click(function (e) {
		var $button = jQuery(this);
		var $modal = $button.parents('.remodal');

		$modal.find('.be-saving-warning').show();
		$modal.find('.bulk-saving-screen').hide();
		$modal.find('#be-nanobar-container').empty();
		$modal.find('.response').empty();
	});
	/**
	 * Save changes - Start saving
	 */
	jQuery('body').find('.be-start-saving').click(function (e) {
		e.preventDefault();

		// Hide warning and start saving screen

		var $warning = jQuery(this).parents('.be-saving-warning');
		var $progress = $warning.next();

		$progress.find('.be-loading-anim').show();
		$warning.fadeOut();
		$progress.fadeIn();

		console.log($warning);
		console.log($progress);


		var nonce = jQuery('.remodal-bg').data('nonce');


		// Init progress bar
		var options = {
			classname: 'be-progress-bar',
			id: 'be-progress-bar',
			target: document.getElementById('be-nanobar-container')
		};

		var nanobar = new Nanobar(options);

		// Get posts that need saving
		var fullData = hot.getSourceData();

		fullData = beGetModifiedItems(fullData, window.beOriginalData);

		console.log(fullData);
		console.log(!fullData);

		// No posts to save found
		if (!fullData.length) {

			jQuery($progress).find('.response').append('<p>' + vgse_editor_settings.texts.no_changes_to_save + '</p>');
			loading_ajax({estado: false});

			$progress.find('.remodal-cancel').removeClass('hidden');
			$progress.find('.be-loading-anim').hide();

			setFormSubmitting();
			return true;
		}

		// Start saving posts, start ajax loop
		beAjaxLoop({
			totalCalls: Math.ceil(fullData.length / parseInt(vgse_editor_settings.save_posts_per_page)),
			url: ajaxurl,
			dataType: 'json',
			method: 'POST',
			data: {
				'data': [],
				'post_type': $container.data('post-type'),
				'action': 'vgse_save_data',
				'nonce': nonce,
				'filters': beGetRowsFilters()
			},
			prepareData: function (data, settings) {
				var dataParts = fullData.chunk(parseInt(vgse_editor_settings.save_posts_per_page));

				data.data = dataParts[ settings.current - 1 ];

				return data;
			},
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
					jQuery($progress).find('.response').append('<p>' + res.data.message + '</p>');

					// Ask the user if he wants to retry the same post
					var goNext = confirm(res.data.message);

					// stop saving if the user chose to not try again
					if (!goNext) {
						jQuery($progress).find('.response').append(vgse_editor_settings.texts.saving_stop_error);
						jQuery('.bulk-saving-screen .response').scrollTop(jQuery('.bulk-saving-screen .response')[0].scrollHeight);
						return false;
					}
					// reset pointer to try the same batch again
					settings.current = 0;
					jQuery('.bulk-saving-screen .response').scrollTop(jQuery('.bulk-saving-screen .response')[0].scrollHeight);
					return true;
				}

				nanobar.go(settings.current / settings.totalCalls * 100);


				// Display message saying the number of posts saved so far
				var updated = (parseInt(vgse_editor_settings.save_posts_per_page) * settings.current > fullData.length) ? fullData.length : parseInt(vgse_editor_settings.save_posts_per_page) * settings.current;
				var text = vgse_editor_settings.texts.paged_batch_saved.replace('{updated}', updated);
				var text = text.replace('{total}', fullData.length);
				jQuery($progress).find('.response').append('<p>' + text + '</p>');

				// is complete, show notification to user, hide loading screen, and display "close" button
				if (settings.current === settings.totalCalls) {
					jQuery($progress).find('.response').append('<p>' + vgse_editor_settings.texts.everything_saved + '</p>');

					loading_ajax({estado: false});


					notification({mensaje: vgse_editor_settings.texts.everything_saved});

					$progress.find('.remodal-cancel').removeClass('hidden');
					$progress.find('.be-loading-anim').hide();

					jQuery('body').trigger('vgSheetEditor/afterSavingChanges');

					setFormSubmitting();

					// Reset original data cache, so the modified cells that we save are not considered modified anymore.
					window.beOriginalData = jQuery.extend(true, {}, hot.getSourceData());
				} else {

				}

				// Move scroll to the button to show always the last message in the saving status section
				setTimeout(function () {
					jQuery('.bulk-saving-screen .response').scrollTop(jQuery('.bulk-saving-screen .response')[0].scrollHeight);
				}, 600);

				return true;
			}});
	});

	/**
	 * Save image cells, single image
	 */
	if (typeof wp !== 'undefined' && wp.media) {
		jQuery('body').delegate('.set_custom_images:not(.multiple)', 'click', function (e) {
			e.preventDefault();
			loading_ajax({estado: true});
			var button = jQuery(this);
			var $cell = button.parent('td');
			var cellCoords = hot.getCoords($cell[0]);
			console.log(hot.getDataAtCell(cellCoords.row, cellCoords.col));
			var scrollLeft = jQuery('html,body').scrollLeft();
			var id = button.data('id');
			var key = button.data('key');
			var type = button.data('type');
			var file = button.data('file');
			var gallery = [];

			var scrollTop = jQuery(document).scrollTop();
			var currentInfiniteScrollStatus = jQuery('#infinito').prop('checked');
			jQuery('#infinito').prop('checked', false);

			media_uploader = wp.media({
				frame: "post",
				state: "insert",
				multiple: false
			});

// Allow to save images by URL
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
					beSendImageIdToWP([{
							url: embed.url,
							id: embed.url
						}], id, key, type, cellCoords);
				}



			});
			media_uploader.on('open', function () {
				var selection = media_uploader.state().get('selection');
				var selected = file; // the id of the image
				if (selected) {
					selection.add(wp.media.attachment(selected));
				}
			});
			media_uploader.on('close', function () {
				jQuery('html,body').scrollLeft(scrollLeft);
				jQuery(window).scrollTop(scrollTop);
				jQuery('#infinito').prop('checked', currentInfiniteScrollStatus);
			});
			media_uploader.on("insert", function () {
				jQuery('html,body').scrollLeft(scrollLeft);

				var length = media_uploader.state().get("selection").length;
				var images = media_uploader.state().get("selection").models

				console.log(images);
				if (!images.length) {
					return true;
				}
				for (var iii = 0; iii < length; iii++) {
					gallery.push({
						url: images[iii].attributes.url,
						id: images[iii].id
					});
				}

				button.data('file', images[0].id);

				beSendImageIdToWP(gallery, id, key, type, cellCoords);
			});
			media_uploader.open();
			loading_ajax({estado: false});
			return false;
		});
	}

	/**
	 * Save image cells, multiple images
	 */
	if (typeof wp !== 'undefined' && wp.media) {
		jQuery('body').delegate('.set_custom_images.multiple', 'click', function (e) {
			e.preventDefault();

			loading_ajax({estado: true});
			var button = jQuery(this);
			var $cell = button.parent('td');
			var cellCoords = hot.getCoords($cell[0]);
			console.log(hot.getDataAtCell(cellCoords.row, cellCoords.col));
			var scrollLeft = jQuery('html,body').scrollLeft();
			var id = button.data('id');
			var key = button.data('key');
			var type = button.data('type');
			var gallery = [];

			var scrollTop = jQuery(document).scrollTop();
			var currentInfiniteScrollStatus = jQuery('#infinito').prop('checked');
			jQuery('#infinito').prop('checked', false);

			media_uploader = wp.media({
				frame: "post",
				state: "insert",
				multiple: true
			});

// Allow to save images by url
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

					beSendImageIdToWP([{
							url: embed.url,
							id: embed.url
						}], id, key, type, cellCoords);
				}



			});

			media_uploader.on('close', function () {
				jQuery('html,body').scrollLeft(scrollLeft);
				jQuery(window).scrollTop(scrollTop);
				jQuery('#infinito').prop('checked', currentInfiniteScrollStatus);
			});
			media_uploader.on("insert", function () {
				jQuery('html,body').scrollLeft(scrollLeft);

				var length = media_uploader.state().get("selection").length;
				var images = media_uploader.state().get("selection").models
				console.log(images);
				for (var iii = 0; iii < length; iii++) {
					gallery.push({
						url: images[iii].attributes.url,
						id: images[iii].id
					});
				}

				beSendImageIdToWP(gallery, id, key, type, cellCoords);
			});
			media_uploader.open();
			loading_ajax({estado: false});
			return false;
		});
	}

	/**
	 * Preview image on image cells, single image
	 */
	jQuery('body').delegate('.view_custom_images:not(.multiple)', 'click', function () {
		loading_ajax({estado: true});
		var element = jQuery(this);
		post_id = element.data('id');
		var nonce = jQuery('.remodal-bg').data('nonce');
		var key = element.data('key');
		var type = element.data('type');
		var $setButton = element.siblings('.set_custom_images');
		var localValue = $setButton.data('images');
		jQuery.ajax({
			type: "POST",
			url: ajaxurl,
			data: {id: post_id, action: "vgse_get_image_preview", nonce: nonce, key: key, type: type, localValue: localValue, post_type: jQuery('#post-data').data('post-type')},
			dataType: 'json',
			success: function (response) {
				loading_ajax({estado: false});

				if (response.success) {
					jQuery('div[data-remodal-id=image] .modal-content').html(response.data.message);
					jQuery('[data-remodal-id=image]').remodal();
				} else {
					notification({mensaje: response.data.message, tipo: 'error', tiempo: 60000});
				}
			}
		});
	});

	/**
	 * Preview image on image cells, multiple images
	 */
	jQuery('body').delegate('.view_custom_images.multiple', 'click', function () {
		loading_ajax({estado: true});
		var element = jQuery(this);
		post_id = element.data('id');
		var nonce = jQuery('.remodal-bg').data('nonce');
		var key = element.data('key');
		var type = element.data('type');
		var $setButton = element.siblings('.set_custom_images');
		var localValue = $setButton.data('images');
		jQuery.ajax({
			type: "POST",
			url: ajaxurl,
			data: {id: post_id, action: "vgse_get_gallery_preview", nonce: nonce, key: key, type: type, localValue: localValue, post_type: jQuery('#post-data').data('post-type')},
			dataType: 'json',
			success: function (response) {
				loading_ajax({estado: false});

				if (response.success) {
					jQuery('div[data-remodal-id=image] .modal-content').html(response.data.message);
					jQuery('[data-remodal-id=image]').remodal();
				} else {
					notification({mensaje: response.data.message, tipo: 'error', tiempo: 60000});
				}
			}
		});
	});

	/**
	 * Move to next post on tinymce cells modal
	 */
	jQuery('button.siguiente').click(function () {
		var element = jQuery(this);
		var pos = element.data('pos');
		var $remodalWrapper = element.parents('.remodal-wrapper');
		var key = $remodalWrapper.find('.remodal-confirm.guardar-popup-tinymce').data('key');
		jQuery('.btn-popup-content.button-tinymce-' + key).eq(pos).trigger('click');
	});

	/**
	 * Move to previous post on tinymce cells modal
	 */
	jQuery('button.anterior').click(function () {
		var element = jQuery(this);
		var pos = element.data('pos');
		var $remodalWrapper = element.parents('.remodal-wrapper');
		var key = $remodalWrapper.find('.remodal-confirm.guardar-popup-tinymce').data('key');
		jQuery('.btn-popup-content.button-tinymce-' + key).eq(pos).trigger('click');
	});


	/**
	 * Open tinymce cell modal
	 */
	jQuery('body').delegate('.btn-popup-content', 'click', function () {
		loading_ajax({estado: true});
		var element = jQuery(this);
		var post_id = element.data('id');
		var key = element.data('key');
		var type = element.data('type');
		var pos = element.parents('tr').index();
		var length = element.parents('tbody').find('tr').length;
		var nonce = jQuery('.remodal-bg').data('nonce');


		// Display or hide the unnecesary navigation buttons.
		// If first post, hide "previous" button.
		// If last post, hide "next" button
		if (pos === 0) {
			jQuery('button.anterior').hide();
			jQuery('button.anterior').next('.tipso').hide();
		} else {
			jQuery('button.anterior').show();
			jQuery('button.anterior').next('.tipso').show();
		}
		if (pos === (length - 1)) {
			jQuery('button.siguiente').hide();
			jQuery('button.siguiente').next('.tipso').hide();
		} else {
			jQuery('button.siguiente').show();
			jQuery('button.siguiente').next('.tipso').show();
		}

		jQuery('button.anterior').data('pos', pos - 1);
		jQuery('button.siguiente').data('pos', pos + 1);

		/**
		 * Get post title
		 */
		jQuery.ajax({
			type: "POST",
			url: ajaxurl,
			data: {pid: post_id, action: "vgse_get_wp_post_single_data", nonce: nonce, key: 'post_title', type: 'post_data', post_type: jQuery('#post-data').data('post-type')},
			dataType: 'json',
			success: function (response) {

				if (response.success) {
					jQuery('.modal-tinymce-editor .post-title-modal span').text(response.data.message).show();
				} else {
					jQuery('.modal-tinymce-editor .post-title-modal').hide();
				}
			}
		});

		/**
		 * Get cell content
		 */
		jQuery.ajax({
			type: "POST",
			url: ajaxurl,
			data: {pid: post_id, action: "vgse_get_wp_post_single_data", nonce: nonce, key: key, type: type, post_type: jQuery('#post-data').data('post-type')},
			dataType: 'json',
			success: function (response) {

				if (response.success) {

					// Add content to tinymce editor
					if (jQuery('.wp-editor-area').css('display') !== 'none') {
						jQuery('.wp-editor-area').empty();
						jQuery('.wp-editor-area').val(response.data.message);
					} else {
						if (document.getElementById('editpost_ifr')) {
							var frame = document.getElementById('editpost_ifr').contentWindow.document || document.getElementById('editpost_ifr').contentDocument;
							frame.body.innerHTML = response.data.message;
						}
					}


					window.originalTinyMCEData = beGetTinymceContent();

					jQuery('.remodal2 .remodal-confirm').data('post_id', post_id);
					jQuery('.remodal2 .remodal-confirm').data('key', key);
					jQuery('.remodal2 .remodal-confirm').data('type', type);
					//console.log(jQuery('.remodal2 .remodal-confirm').data('post_id'));

					jQuery('[data-remodal-id="editor"]').remodal().open();
					loading_ajax({estado: false});

				} else {

					notification({mensaje: response.data.message, tipo: 'error', tiempo: 60000});
				}
			}
		});
	});

	/**
	 * Save changes on tinymce editor
	 */
	jQuery('.guardar-popup-tinymce').click(function (e) {
		loading_ajax({estado: true});
		var element = jQuery('.remodal2 .remodal-confirm');
//		var element = jQuery(this);
		post_id = element.data('post_id');
		key = element.data('key');
		type = element.data('type');

		// Get tinymce editor content
		var content = beGetTinymceContent();
		var nonce = jQuery('.remodal-bg').data('nonce');

		// Save content
		if (!window.originalTinyMCEData || (window.originalTinyMCEData && content !== window.originalTinyMCEData)) {
			jQuery.ajax({
				type: "POST",
				url: ajaxurl,
				data: {post_id: post_id, content: content, action: "vgse_save_individual_post", nonce: nonce, key: key, type: type, post_type: jQuery('#post-data').data('post-type')},
				dataType: 'json',
				success: function (response) {
					loading_ajax({estado: false});
					if (response.success) {
						notification({mensaje: response.data.message});
					} else {
						notification({mensaje: response.data.message, tipo: 'error', tiempo: 60000});
					}
				}
			});
		} else {
			console.log('Existing tinymce content is the same, not saved');
			loading_ajax({estado: false});
		}
	});

	/**
	 * Load more posts in the spreadsheet
	 */
	$parent.find('button[name=mas]').click(function () {
		if (jQuery('#formato').is(':checked')) {
			format = true;
		} else {
			format = false;
		}
		var nonce = jQuery('.remodal-bg').data('nonce');

		beLoadPosts({
			post_type: $container.data('post-type'),
			paged: Math.ceil(hot.countRows() / vgse_editor_settings.posts_per_page) + 1,
			nonce: nonce
		}, function (response) {

			if (response.success) {
				vgseAddFoundRowsCount(response.data.total);
				vgAddRowsToSheet(response.data.rows);

				loading_ajax({estado: false});
				notification({mensaje: vgse_editor_settings.texts.posts_loaded})
				//Para detener el scroll mientras se ejecuta otro y volver a activarlo
				window.scrroll = true;

				if (!response.data || !response.data.rows.length) {
					window.scrroll = false;
				}
			} else {

				loading_ajax({estado: false});
				notification({mensaje: response.data.message, tipo: 'error', tiempo: 60000});
				window.scrroll = false;
			}
		});
	});


	/**
	 * Init infinite scroll
	 */
	var contenedor = jQuery('#post-data');
	var cont_offset = contenedor.offset();
	window.scrroll = true;
	var countRows = hot.countRows();
	jQuery(window).on('scroll', _throttle(function () {
		console.log('scrolled2');
		if (jQuery('#infinito').is(':checked')) {
			if ((jQuery(window).scrollTop() + jQuery(window).height() == jQuery(document).height()) && window.scrroll === true && scrollDown()) {
				jQuery('button[name="mas"]').trigger('click');
				window.scrroll = false;
			}
		}
	}, 500));

	/**
	 * Change cell formatting setting
	 * @param boolean active
	 * @returns boolean
	 */
	function columns_format(active) {
		if (active === true) {
			var defaultColumns = vgse_editor_settings.columnsFormat
		} else {
			var defaultColumns = vgse_editor_settings.columnsUnformat
		}

		return vgObjectToArray(defaultColumns);

	}

	/**
	 * Update cells formatting = change to plain text and viceversa
	 */
	jQuery('#formato').change(function () {
		if (jQuery(this).is(':checked')) {
			format = false;
		} else {
			format = true;
		}
		//console.log(format);

		var defaultColumns = columns_format(format);

		if (typeof vgseColumnsVisibilityUpdateHOT === 'function' && window.vgseColumnsVisibilityUsed) {
			vgseColumnsVisibilityUpdateHOT(defaultColumns, vgse_editor_settings.colHeaders, vgse_editor_settings.colWidth, 'softUpdate');

		} else {
			hot.updateSettings({
				columns: defaultColumns
			});
		}
	});

	/**
	 * Update posts count on spreadsheet
	 */
	setInterval(function () {
		var total = hot.countRows();
		jQuery('input[name="visibles"]').val(total);
	}, 1000);

	/**
	 * Add new rows to spreadsheet
	 */
	jQuery("#addrow").click(function () {
		var nonce = jQuery('.remodal-bg').data('nonce');
		var post_type = jQuery('#post_type_new_row').val();
		var rows = (jQuery(this).next('.number_rows').length && jQuery(this).next('.number_rows').val()) ? parseInt(jQuery(this).next('.number_rows').val()) : 1;
		loading_ajax({estado: true});

		// Create posts as drafts
		jQuery.ajax({
			type: "POST",
			url: ajaxurl,
			data: {action: "vgse_insert_individual_post", nonce: nonce, post_type: post_type, rows: rows},
			dataType: 'json',
			success: function (res) {

				console.log(res);
				if (res.success) {
					// Add rows to spreadsheet							
					vgseAddFoundRowsCount(window.beFoundRows + parseInt(rows));
					vgAddRowsToSheet(res.data.message, 'prepend');

					loading_ajax({estado: false});
					notification({mensaje: vgse_editor_settings.texts.new_rows_added});

					// Scroll up to the new rows
					jQuery(window).scrollTop(jQuery('.be-spreadsheet-wrapper').offset().top - jQuery('#vg-header-toolbar').height() - 20);
				} else {
					loading_ajax({estado: false});
					notification({mensaje: res.data.message, tipo: 'error', tiempo: 60000});
				}
			}
		});
	});

	jQuery('#addrow2').click(function () {
		jQuery('#addrow').trigger('click');
	});

});


/**
 * Verify we´re scrolling vertically, not horizontally
 */
var lastScrollTop = 0;
function scrollDown() {
	var st = jQuery(window).scrollTop();
	if (st > lastScrollTop) {
		down = true;
	} else {
		down = false;
	}
	lastScrollTop = st;
	return down;
}


/**
 * Display warning before closing the page to ask the user to save changes
 */
var formSubmitting = false;
var setFormSubmitting = function () {
	formSubmitting = true;
};

jQuery(window).on("beforeunload", function () {
	if (jQuery('.be-spreadsheet-wrapper').length) {
		var modifiedData = beGetModifiedItems(hot.getSourceData(), window.beOriginalData);
	} else {
		var modifiedData = [];
	}

	if (!jQuery('.be-spreadsheet-wrapper').length || !modifiedData.length || formSubmitting) {
		return undefined;
	}
	return vgse_editor_settings.texts.save_changes_on_leave;
});


jQuery(document).ready(function () {
	var $quickSetupContent = jQuery('.quick-setup-page-content');

	if (!$quickSetupContent.length) {
		return true;
	}

	function nextStep() {
		jQuery('.setup-step.active').removeClass('active').next().addClass('active');
		jQuery(' #vgse-wrapper .progressbar li.active').removeClass('active').next().addClass('active');
	}
	function prevStep() {
		jQuery('.setup-step.active').removeClass('active').prev().addClass('active');
		jQuery(' #vgse-wrapper .progressbar li.active').removeClass('active').prev().addClass('active');
	}

	$quickSetupContent.find('.step-back').click(function (e) {
		e.preventDefault();
		prevStep();
	});
	$quickSetupContent.find('.save-all-trigger').click(function (e) {
		e.preventDefault();
		var $allTrigger = jQuery(this);
		var $step = $allTrigger.parents('.setup-step');
		var $forms = $step.find('form');
		loading_ajax({estado: true});

		if (!$forms.length) {
			nextStep();
			loading_ajax({estado: false});
			return true;
		}

		$step.find('.save-trigger').each(function () {
			jQuery(this).trigger('click');
		});

		var savedCount = 0;
		var savedNeeded = $step.find('.save-trigger').length;

		var intervalId = setInterval(function () {
			var $saved = $step.find('.save-trigger').filter(function () {
				return jQuery(this).data("saved") === 'yes';
			});
			// finished saving all forms.
			if ($saved.length === savedNeeded) {
				clearInterval(intervalId);
				nextStep();
				loading_ajax({estado: false});
			}
		}, 800);
	});

	$quickSetupContent.find('.save-trigger').click(function (e) {
		e.preventDefault();
		var $button = jQuery(this);

		var $form = $button.parents('form');
		var callback = $form.data('callback');
		jQuery.post($form.attr('action'), $form.serializeArray(), function (response) {
			$button.data('saved', 'yes');

			if (callback) {
				vgseExecuteFunctionByName(callback, window, {
					response: response,
					form: $form
				});
			}
		});

	});
});


jQuery(document).ready(function () {

	// Submit formulas modal form 
	jQuery('body').on('click', '.form-submit-outside', function (e) {
		e.preventDefault();

		jQuery(this).parents('.remodal').find('form .form-submit-inside').trigger('click');
	});




	// Disable infinite scroll when opening modals
	jQuery(document).on('opened', '.remodal', function () {
		console.log('Modal is opened');
		// Save the existing scroll position, and disable infinite scroll to
		// avoid loosing the scroll position and loading more posts while it´s opened.
		var scrollTop = jQuery(document).scrollTop();
		var currentInfiniteScrollStatus = jQuery('#infinito').prop('checked');
		jQuery('#infinito').prop('checked', false);
		jQuery('body').data('temp-status', currentInfiniteScrollStatus).data('temp-scrolltop', scrollTop);


		var scrollLeft = jQuery('html,body').scrollLeft();
		jQuery('body').data('temp-scrollleft', scrollLeft);
	});
	jQuery(document).on('closed', '.remodal', function () {
		console.log('Modal is closed');
		var scrollTop = jQuery('body').data('temp-scrolltop');
		var scrollLeft = jQuery('body').data('temp-scrollleft');
		var scrollInfinito = jQuery('body').data('temp-status');

		if (scrollTop) {
			jQuery(window).scrollTop(scrollTop);
		}
		if (scrollLeft) {
			jQuery('html,body').scrollLeft(scrollLeft);
		}
		if (scrollInfinito) {
			jQuery('#infinito').prop('checked', scrollInfinito);
		}
	});
});


// handsontable cells

// Initialize spreadsheet
function initHandsontableForPopup(data, modalSettings) {

	if (!data) {
		data = [];
	}

	if (modalSettings.type === 'handsontable') {

		var columnWidths = modalSettings.handsontable_column_widths[modalSettings.post_type];
		var columnHeaders = modalSettings.handsontable_column_names[modalSettings.post_type];
		var columns = modalSettings.handsontable_columns[modalSettings.post_type];
		var container3 = document.getElementById('handsontable-in-modal');


		if (window.hotAttr) {
			window.hotAttr.destroy();
		}

		var responseData;
		if (data.custom_handsontable_args) {
			responseData = data.data;
		} else {
			responseData = data;
		}


		var cellHandsontableArgs = {
			data: responseData,
			minSpareRows: 1,
			wordWrap: true,
			colWidths: columnWidths,
			allowInsertRow: true,
			columnSorting: true,
			colHeaders: columnHeaders,
			columns: columns
		};

		var finalCellHandsontableArgs = jQuery.extend(cellHandsontableArgs, data.custom_handsontable_args);
		window.hotAttr = new Handsontable(container3, finalCellHandsontableArgs);
	} else if (modalSettings.type === 'metabox') {
		var $iframe = jQuery('.vgca-iframe-wrapper iframe');
		$iframe.parents('.vgca-iframe-wrapper ').show();
		$iframe.attr('src', $iframe.data('src') + modalSettings.post_id + '&wpse_column=' + modalSettings.key);
		initEditorIframe(modalSettings);
	}
	loading_ajax({estado: false});
}

function initEditorIframe(modalSettings) {
	// Bail if iframes were already initiated
//	if (typeof window.vgcaIsFrontendSession !== 'undefined' && window.vgcaIsFrontendSession) {
//		return true;
//	}
	window.$iframeWrappers = jQuery('.vgca-iframe-wrapper ');
	window.vgcaIsFrontendSession = [];
	$iframeWrappers.each(function () {
		var $iframeWrapper = jQuery(this);
		var $iframe = $iframeWrapper.find('iframe');
		var hash = window.location.hash;

		$iframe.data('lastPage', $iframe.contents().get(0).location.href);
		window.vgcaIsFrontendSession.push(setInterval(function () {
			var currentPage = $iframe.contents().get(0).location.href;

			// If the user navigated to another admin page, update the iframe height
			if (currentPage !== $iframe.data('lastPage')) {
				$iframeWrappers.css('height', '');
				$iframe.css('height', '');
				$iframe.data('lastPage', currentPage);
			}

			// Prevent js errors when the admin page hasn't loaded yet
			try {
				var iframeHeight = $iframe.contents().height();
				$iframe.height(iframeHeight);
				$iframeWrapper.height(iframeHeight);

				// Hide all elements except the metabox section that we'll use
				var $field = $iframe.contents().find(modalSettings.metabox_show_selector);
				// Make sure the element is visible
				$field.removeClass('acf-hidden').removeClass('hidden').attr('hidden', '').attr('style', 'display: block !important; visibility: 1 !important; opacity: 1 !important;');
				$field.siblings().attr('style', 'display: none !important');
				$field.parents().each(function () {
					jQuery(this).siblings().attr('style', 'display: none !important');
				});
			} finally {

			}
		}, 1000));
	});
}

jQuery(document).ready(function () {

	// Open modal
	jQuery('body').on('click', '.button-custom-modal-editor', function (e) {
		e.preventDefault();
		var $button = jQuery(this);
		var buttonData = $button.data();


		if (!window.hotModalCache) {
			window.hotModalCache = {};
		}
		if (!window.hotModalCache[buttonData.modalSettings.post_id]) {
			window.hotModalCache[buttonData.modalSettings.post_id] = {};
		}



		var existing;
		if (window.hotModalCache && window.hotModalCache[buttonData.modalSettings.post_id][buttonData.modalSettings.edit_modal_save_action]) {
			existing = window.hotModalCache[buttonData.modalSettings.post_id][buttonData.modalSettings.edit_modal_save_action];
		} else {
			existing = buttonData.existing;
		}

		var currentRowData = {
			'button': $button,
			'modalSettings': buttonData.modalSettings,
			'existing': existing,
		};

		window.vgseWCAttsCurrent = currentRowData;
		var modalInstance = jQuery('.custom-modal-editor').remodal().open();
		jQuery('.custom-modal-editor').addClass('modal-editor-' + buttonData.modalSettings.key);
	});

	// Cancel edit
	jQuery('body').on('click', '.custom-modal-editor .remodal-cancel', function (e) {
		var $button = jQuery(this);
		var $modal = $button.parents('.custom-modal-editor');
		var data = window.vgseWCAttsCurrent;

		if (data.modalSettings.edit_modal_cancel_action) {
			loading_ajax({estado: true});

			var functionNames = data.modalSettings.edit_modal_cancel_action.replace('js_function_name:', '').split(',');
			functionNames.forEach(function (functionName) {
				vgseExecuteFunctionByName(functionName, $modal.find('iframe')[0].contentWindow);
			});
			loading_ajax(false);
		}
	});

	// Save changes
	jQuery('body').on('click', '.custom-modal-editor .save-changes-handsontable', function (e) {
		var $button = jQuery(this);
		var $modal = $button.parents('.custom-modal-editor');
		var nonce = jQuery('.remodal-bg').data('nonce');
		var data = window.vgseWCAttsCurrent;

		loading_ajax({estado: true});

		if (data.modalSettings.type === 'handsontable') {
			var attrData = hotAttr.getSourceData();
		} else if (data.modalSettings.type === 'metabox') {

			if (data.modalSettings.metabox_value_selector.indexOf('js_function_name:') > -1) {
				var functionName = data.modalSettings.metabox_value_selector.replace('js_function_name:', '');
				var attrData = vgseExecuteFunctionByName(functionName, $modal.find('iframe')[0].contentWindow);
			} else {
				var $metaboxFields = $modal.find('iframe').contents().find(data.modalSettings.metabox_value_selector);
				var attrData = $metaboxFields.length === 1 ? $metaboxFields.val() : beParseParams($metaboxFields.serialize());
			}
		}

		if (!window.hotModalCache) {
			window.hotModalCache = {};
		}
		if (!window.hotModalCache[data.modalSettings.post_id]) {
			window.hotModalCache[data.modalSettings.post_id] = {};
		}

		// cache product data
		if (!data.modalSettings.edit_modal_get_action) {
			data.button.data('existing', attrData);

			window.hotModalCache[data.modalSettings.post_id][data.modalSettings.edit_modal_save_action] = attrData;
		}

		var saveHandlers = data.modalSettings.edit_modal_save_action.split(',');
		saveHandlers.forEach(function (saveHandler) {
			if (saveHandler.indexOf('js_function_name:') > -1) {
				var functionName = saveHandler.replace('js_function_name:', '');
				vgseExecuteFunctionByName(functionName, $modal.find('iframe')[0].contentWindow);
			} else {
				jQuery.post(ajaxurl, {
					action: saveHandler,
					nonce: nonce,
					postId: data.modalSettings.post_id,
					postType: data.modalSettings.post_type,
					modalSettings: data.modalSettings,
					data: attrData
				}, function (response) {
					console.log(response);
				});
			}
		});
		jQuery('.custom-modal-editor').remodal().close();
		loading_ajax({estado: false});
	});

	jQuery(document).on('closed', '.custom-modal-editor', function () {
		var data = window.vgseWCAttsCurrent;
		var $modal = jQuery('.custom-modal-editor');


		if (data.modalSettings.type === 'metabox' && typeof window.vgcaIsFrontendSession !== 'undefined' && window.vgcaIsFrontendSession.length) {
			$modal.find('iframe').attr('src', '');
			$modal.find('.vgca-iframe-wrapper ').hide();
			window.vgcaIsFrontendSession.forEach(function (intervalId, index) {
				clearInterval(intervalId);
			});
		}


		jQuery('.custom-modal-editor').removeClass('modal-editor-' + data.modalSettings.key);
		loading_ajax({estado: false});

	});
// Load modal and spreadsheet
	jQuery(document).on('opened', '.custom-modal-editor', function () {
		console.log('Modal is opened');
		var data = window.vgseWCAttsCurrent;

		loading_ajax({estado: true});

		if (!data) {
			return true;
		}
		var $modal = jQuery('.custom-modal-editor');

		// Display post title in modal
		if (!$modal.find('.modal-post-title').length) {
			$modal.find('.modal-general-title').after('<span class="modal-post-title"></span>');
		}
		$modal.find('.modal-post-title').html(data.modalSettings.post_title);
		if (data.modalSettings.edit_modal_title) {
			$modal.find('.modal-general-title').html(data.modalSettings.edit_modal_title + ': ');
		}
		if (data.modalSettings.edit_modal_description) {
			$modal.find('.modal-description').html(data.modalSettings.edit_modal_description);
		}

		if (!window.hotModalCache) {
			window.hotModalCache = {};
		}
		if (!window.hotModalCache[data.modalSettings.post_id]) {
			window.hotModalCache[data.modalSettings.post_id] = {};
		}

		// Get data for the spreadsheet if necessary
		if (data.modalSettings.edit_modal_get_action) {
			var nonce = jQuery('.remodal-bg').data('nonce');
			jQuery.get(ajaxurl, {
				action: data.modalSettings.edit_modal_get_action,
				nonce: nonce,
				postId: data.modalSettings.post_id
			}).done(function (response) {
				initHandsontableForPopup(response.data, data.modalSettings);
			});
		} else {

			if (window.hotModalCache && window.hotModalCache[data.modalSettings.post_id][data.modalSettings.edit_modal_save_action]) {
				var objectData = window.hotModalCache[data.modalSettings.post_id][data.modalSettings.edit_modal_save_action];
			} else {
				var objectData = data.existing;
			}
			initHandsontableForPopup(objectData, data.modalSettings);
		}

	});

	jQuery('body').on('click', 'button.remodal-confirm, a.remodal-cancel, .media-button-insert', function (e) {
		if (jQuery(this).attr('type') !== 'submit' && !jQuery(this).hasClass('submit')) {
			e.preventDefault();
		}
	});

});

jQuery(document).ready(function () {
	jQuery('.vgse-current-filters').on('click', '.button', function (e) {
		e.preventDefault();
		var $button = jQuery(this);


		var fullData = hot.getSourceData();
		fullData = beGetModifiedItems(fullData, window.beOriginalData);
		if (fullData.length) {
			alert(vgse_editor_settings.texts.save_changes_before_remove_filter);
			return true;
		}

		var toRemove = $button.data('filter-key');

		if (toRemove && toRemove.indexOf('meta_query') > -1) {
			var currentFilters = jQuery('body').data('be-filters');
			var toRemoveFinal = {};

			jQuery.each(currentFilters, function (filterKey, filterValue) {
				if (filterKey && filterKey.indexOf('meta_query') > -1) {
					toRemoveFinal[filterKey] = '';
				}
			});

		} else {
			var toRemoveFinal = toRemove + '=';
		}


		beAddRowsFilter(toRemoveFinal);
		$button.remove();

		vgseReloadSpreadsheet();

	});
});

/* Post type setup wizard */
jQuery(document).ready(function () {
	var $wrapper = jQuery('.post-type-setup-wizard');

	if (!$wrapper.length) {
		return false;
	}

	// Create post type
	$wrapper.find('form.inline-add').submit(function (e) {

		var $form = jQuery(this);
		var callback = $form.data('callback');
		jQuery.ajax({
			method: $form.attr('method'),
			url: $form.attr('action'),
			data: $form.serialize() + '&current_post_type=' + jQuery('.post-types-form input:radio:checked').val()
		})
				.done(function (response) {
					$form.find('input:text').val('');
					$form.find('input:text').first().focus();
					vgseExecuteFunctionByName(callback, window, {
						response: response,
						form: $form,
					});
				});


		return false;
	});

	// Add delete button to custom post types
	var customPostTypes = $wrapper.find('.post-types-form').data('custom-post-types').split(',');
	jQuery.each(customPostTypes, function (index, postType) {
		var $fieldWrapper = $wrapper.find('.post-types-form .post-type-' + postType);
		$fieldWrapper.append('<button class="button vgse-delete-post-type" data-post-type="' + postType + '"><i class="fa fa-remove"></i></button>');
	});

	// Delete post type
	$wrapper.on('click', '.vgse-delete-post-type', function (e) {
		e.preventDefault();
		var $button = jQuery(this);
		var postType = $button.data('post-type');

		var allowed = confirm($wrapper.find('.post-types-form').data('confirm-delete'));

		if (!allowed) {
			return true;
		}
		jQuery.post(ajaxurl, {
			post_type: postType,
			action: 'vgse_delete_post_type',
			nonce: jQuery('.post-type-setup-wizard').data('nonce'),
		}, function (response) {
			if (response.success) {
				notification({mensaje: response.data.message, tipo: 'success', tiempo: 3000});
				$wrapper.find('.post-types-form .post-type-' + postType).remove();
			}
		});
	});
});
function vgsePostTypeSaved(data) {
	if (data.response.success) {
		jQuery('.post-types-form .post-type-field').first().before('<div class="post-type-field"><input type="radio" name="post_types[]" value="' + data.response.data.slug + '" id="' + data.response.data.slug + '"> <label for="' + data.response.data.slug + '">' + data.response.data.label + '</label></div>');
		jQuery('.post-types-form input:radio').first().prop('checked', true);
		jQuery('.post-types-form .save-trigger').trigger('click');
	}
}
function vgsePostTypeSetupPostTypesSaved(data) {
	var $step = data.form.parents('li');

	$step.hide();

	var $next = $step.next();
	$next.show();

	if ($next.hasClass('setup_columns')) {
		jQuery.get(ajaxurl, {
			action: 'vgse_post_type_setup_columns_visibility',
			nonce: jQuery('.post-type-setup-wizard').data('nonce'),
			post_type: jQuery('.post-types-form input:radio:checked').val(),
		}, function (response) {
			$next.append(response.data.html);

			$next.find('[name="save_post_type_settings"]').prop('checked', true);

			if (typeof vgseColumnsVisibilityInit !== 'undefined') {
				vgseColumnsVisibilityInit();
			}
		});
	}
}

function vgsePostTypeSetupColumnSaved(data) {
	jQuery('#vgse-columns-enabled').append('<li><span class="handle">::</span> ' + data.response.data.label + ' <input type="hidden" name="columns[]" class="js-column-key" value="' + data.response.data.key + '"><input type="hidden" name="columns_names[]" class="js-column-title" value="' + data.response.data.label + '"></li>');
}
function vgsePostTypeSetupColumnsVisibilitySaved(data) {
	window.location.href = data.response.data.post_type_editor_url;
}
jQuery(document).ready(function () {

	var $postTypesAvailable = jQuery('.quick-setup-page-content .post-type-field input');

	if (!$postTypesAvailable.length) {
		return false;
	}

	var $postTypesEnabled = jQuery('.quick-setup-page-content .post-types-enabled');
	$postTypesAvailable.change(function (e) {
		console.log('test: ', jQuery(this));
		$postTypesEnabled.empty();

		$postTypesAvailable.each(function () {
			var postTypeKey = jQuery(this).val();

			if (jQuery(this).is(':checked')) {

				var label = jQuery(this).siblings('label').text();
				var html = '<a class="button post-type-' + postTypeKey + '" href="admin.php?page=vgse-bulk-edit-' + postTypeKey + '">Edit ' + label + '</a> - ';
				console.log('html: ', html);
				$postTypesEnabled.append(html);
			}
		});

	});
});
jQuery(document).ready(function () {

	if( typeof hot === 'undefined' ){
		return true;
	}
	/**
	 * Disable post status cells that contain readonly statuses.
	 * ex. scheduled posts
	 */
	hot.updateSettings({
		afterLoadData: function (firstTime) {

		},
		cells: function (row, col, prop) {
			var cellProperties = {};

			if (vgse_editor_settings.watch_cells_to_lock || prop === 'post_status' || prop === 'post_mime_type' ) {
					var cellData = hot.getDataAtCell(row, col);
					if (cellData && typeof cellData === 'string' && cellData.indexOf('vg-cell-blocked') > -1) {
						cellProperties.readOnly = true;
						cellProperties.editor = false;
						cellProperties.renderer = 'html';
						cellProperties.fillHandle = false;
					}
				}


			return cellProperties;
		}
	});
});

