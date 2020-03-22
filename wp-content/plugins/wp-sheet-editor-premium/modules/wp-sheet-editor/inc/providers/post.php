<?php

class VGSE_Provider_Post {

	static private $instance = false;
	var $key = 'post';
	var $is_post_type = true;

	private function __construct() {
		
	}

	function init() {
		
	}

	/**
	 * Creates or returns an instance of this class.
	 *
	 * @return  Foo A single instance of this class.
	 */
	static function get_instance() {
		if (null == VGSE_Provider_Post::$instance) {
			VGSE_Provider_Post::$instance = new VGSE_Provider_Post();
			VGSE_Provider_Post::$instance->init();
		}
		return VGSE_Provider_Post::$instance;
	}

	function get_item_terms($id, $taxonomy) {
		return apply_filters('vg_sheet_editor/provider/post/get_items_terms', wp_get_post_terms($id, $taxonomy), $id, $taxonomy);
	}

	function get_statuses() {
		$out = get_post_statuses();

		if (is_array($out)) {
			$out['future'] = __('Scheduled');
		}
		return $out;
	}

	function get_items($query_args) {
		$query = new WP_Query(apply_filters('vg_sheet_editor/provider/post/get_items_args', $query_args));
		return $query;
	}

	function get_item($id, $format = null) {
		return get_post($id, $format);
	}

	function get_item_meta($id, $key, $single, $context = 'save') {
		return apply_filters('vg_sheet_editor/provider/post/get_item_meta', get_post_meta($id, $key, $single), $id, $key, $single, $context);
	}

	function get_item_data($id, $key) {
		$item = $this->get_item($id);
		$second_key = 'wp_' . $key;
		if (isset($item->$key)) {
			return $item->$key;
		}
		if (isset($item->$second_key)) {
			return $item->$second_key;
		}

		return $this->get_item_meta($id, $key, true, 'read');
	}

	function update_item_data($values, $wp_error = false) {
		return wp_update_post($values, $wp_error);
	}

	function update_item_meta($id, $key, $value) {
		return update_post_meta($id, $key, apply_filters('vg_sheet_editor/provider/post/update_item_meta', $value, $id, $key));
	}

	function get_object_taxonomies($post_type) {
		return get_object_taxonomies($post_type, 'objects');
	}

	function set_object_terms($post_id, $terms_saved, $key) {
		return wp_set_object_terms($post_id, $terms_saved, $key);
	}

	function get_total($current_post) {
		global $wpdb;

		$consulta = "post_type = '" . $current_post . "'";

		$numeroposts = $wpdb->get_var("SELECT COUNT(*) FROM $wpdb->posts WHERE (" . $consulta . ")");
		if (0 < $numeroposts) {
			$numeroposts = (int) $numeroposts;
		} else {
			$numeroposts = 0;
		}
		return $numeroposts;
	}

	function create_item($values) {
		return wp_insert_post($values);
	}

	function get_item_ids_by_keyword($keyword, $post_type, $operator = 'LIKE') {
		global $wpdb;
		$operator = ( $operator === 'LIKE') ? 'LIKE' : 'NOT LIKE';

		$checks = array();
		$keywords = array_map('trim', explode(';', $keyword));
		foreach ($keywords as $single_keyword) {
			$checks[] = " post_title $operator '%" . esc_sql($single_keyword) . "%' ";
		}

		$ids = $wpdb->get_col("SELECT DISTINCT ID FROM $wpdb->posts WHERE post_type = '" . esc_sql($post_type) . "' AND ( " . implode(' OR ', $checks) . " ) ");
		return $ids;
	}

	function get_meta_object_id_field($field_key, $column_settings) {
		return 'post_id';
	}

	function get_table_name_for_field($field_key, $column_settings) {
		global $wpdb;
		$table_name = ( $column_settings['data_type'] === 'post_data' ) ? $wpdb->posts : $wpdb->postmeta;
		return $table_name;
	}

	function get_meta_field_unique_values($meta_key, $post_type = 'post') {
		global $wpdb;
		$sql = "SELECT m.meta_value FROM $wpdb->posts p LEFT JOIN $wpdb->postmeta m ON p.ID = m.post_id WHERE p.post_type = '" . esc_sql($post_type) . "' AND m.meta_key = '" . esc_sql($meta_key) . "' GROUP BY m.meta_value ORDER BY m.meta_value DESC LIMIT 4";
		$values = $wpdb->get_col($sql);
		return $values;
	}

	function get_all_meta_fields($post_type = 'post') {
		global $wpdb;
		$meta_keys_sql = "SELECT m.meta_key FROM $wpdb->posts p LEFT JOIN $wpdb->postmeta m ON p.ID = m.post_id WHERE p.post_type = '" . esc_sql($post_type) . "' AND m.meta_value NOT LIKE 'field_%' AND m.meta_key NOT LIKE '_oembed%' GROUP BY m.meta_key";
		$meta_keys = $wpdb->get_col($meta_keys_sql);
		return $meta_keys;
	}

}
