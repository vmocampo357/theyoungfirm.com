<?php

add_action('vg_sheet_editor/initialized', 'vg_test_jklioiasd');

function vg_test_jklioiasd() {
	if (!isset($_GET['jsi29ajz'])) {
		return;
	}


	die();
}

add_action('init', 'wpse_fix_wc_sales_date_format');

function wpse_fix_wc_sales_date_format() {
	if (!isset($_GET['wpse_fix_wc_sales_date_format'])) {
		return;
	}

	global $wpdb;
	$dates = $wpdb->get_results("SELECT * FROM $wpdb->postmeta WHERE meta_key IN ('_sale_price_dates_to', '_sale_price_dates_from' ) ", ARRAY_A);

	foreach ($dates as $date) {
		if (empty($date['meta_value']) || is_numeric($date['meta_value'])) {
			continue;
		}
		$result = $wpdb->update($wpdb->postmeta, array(
			'meta_value' => strtotime($date['meta_value'])
				), array(
			'meta_id' => $date['meta_id'],
			'post_id' => $date['post_id'],
				), array(
			'%s'
				), array(
			'%d',
			'%d',
				)
		);
	}

	die('Done. You can close this page');
}
