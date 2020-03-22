<?php

$pro_items = array(
	__('<p>NEW - WooCommerce - Allow to clone variations from one product to other products</p>', VGSE()->textname),
	__('<p>NEW - WooCommerce - Create variation - Allow to create variations for multiple products at once.</p>', VGSE()->textname),
	__('<p>NEW - WooCommerce - allow to copy downloadable files from other product in popup</p>', VGSE()->textname),
	__('<p>NEW - FORMULAS - Allow to execute formula only on WooCommerce variations.</p>', VGSE()->textname),
	__('<p>NEW - CUSTOM COLUMNS - Added settings page button in the toolbar for quick access.</p>', VGSE()->textname),
	__('<p>NEW - FORMULAS - Added option for executing custom formula on any field type.</p>', VGSE()->textname),
	__('<p>NEW - PRO - Added set up wizard for post types that allows you to set up a post type and custom columns quickly.</p>', VGSE()->textname),
	__('<p>NEW - FORMULAS - improved the speed of simple formulas allowing to update thousands of posts in just a few seconds. Huge speed improvement.</p>', VGSE()->textname),
	__('<p>NEW - PRO - Added option to create new spreadsheets for new post types.</p>', VGSE()->textname),
	__('<p>NEW - FILTERS - Added advanced meta filters with operators like =, !=, <, >, LIKE, etc.</p>', VGSE()->textname),
	__('<p>FIX - WooCommerce - Variation titles appear empty.</p>', VGSE()->textname),
	__('<p>FIX - FORMULAS - Sometimes it doesn´t update the last posts.</p>', VGSE()->textname),
	__('<p>FIX - FORMULAS - Sometimes when searching post terms while preparing formula, it doesn´t find any.</p>', VGSE()->textname),
	__('<p>FIX - CUSTOM POST TYPES - It doesn´t allow post types created after the settings page is registered, ignoring post types added by "types" plugin.</p>', VGSE()->textname),
	__('<p>FIX - FORMULAS - It doesn´t accept decimal numbers.</p>', VGSE()->textname),
	__('<p>FIX - CUSTOM COLUMNS - It doesn´t allow to remove the first column.</p>', VGSE()->textname),
	__('<p>FIX - WooCommerce - When editing attributes it marks "visible" and "is taxonomy" as true ignoring the set value.</p>', VGSE()->textname),
	__('<p>FIX - FORMULAS - it doesn´t allow to replace something with nothing (remove a value).</p>', VGSE()->textname),
	__('<p>FIX - FORMULAS - File columns dont save files when using url with query strings</p>', VGSE()->textname),
	__('<p>FIX - WooCommerce - download files popup doesn´t show previously edited values</p>', VGSE()->textname),
	__('<p>FIX - COLUMNS VISIBILITY - Drag and drop is not working on popup</p>', VGSE()->textname),
	__('<p>FIX - FORMULAS - Wrong formula generated for "decrease by %" option.</p>', VGSE()->textname),
);


// FIX. Some columns keys were changed, for example, "title" became "post_title". So we 
// will reset the columns visibility settings to avoid confusing users because the 
// renamed columns would get hidden.
$options = get_option(VGSE()->options_key, array());

foreach ($options as $option_key => $value) {
	if (strpos($option_key, 'be_visibility_') !== false) {
		unset($options[$option_key]);
	}
}
update_option(VGSE()->options_key, $options);
