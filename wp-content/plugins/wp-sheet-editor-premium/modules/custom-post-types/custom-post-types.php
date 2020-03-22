<?php

if (!class_exists('WP_Sheet_Editor_CPTs')) {

	/**
	 * Use the spreadsheet editor to edit all the posts from any custom post type.
	 */
	class WP_Sheet_Editor_CPTs {

		static private $instance = false;
		var $addon_helper = null;

		private function __construct() {
			
		}

		/**
		 * Creates or returns an instance of this class.
		 */
		static function get_instance() {
			if (null == WP_Sheet_Editor_CPTs::$instance) {
				WP_Sheet_Editor_CPTs::$instance = new WP_Sheet_Editor_CPTs();
				WP_Sheet_Editor_CPTs::$instance->init();
			}
			return WP_Sheet_Editor_CPTs::$instance;
		}

		function init() {

			add_filter('redux/field/' . VGSE()->options_key . '/render/after', array($this, 'add_all_post_types_options'), 10, 2);

			add_filter('vg_sheet_editor/allowed_post_types', array($this, 'allow_all_post_types'));
		}

		// Add options to the select field to include all post types. We need this to include post types added late.
		function add_all_post_types_options($field_html, $field) {

			if ($field['id'] !== 'be_post_types') {
				return $field_html;
			}

			$all_post_types = apply_filters('vg_sheet_editor/custom_post_types/get_all_post_types_names', VGSE()->helpers->get_all_post_types_names());

			$current_post_types = VGSE()->options['be_post_types'];

			if (empty($current_post_types) || !is_array($current_post_types)) {
				$current_post_types = array($current_post_types);
			}

			$field_html = '<select  multiple="multiple" id="be_post_types-select" data-placeholder="Select an item" name="vg_sheet_editor[be_post_types][]" class="redux-select-item " style="width: 40%;" rows="6"><option></option>';

			foreach ($all_post_types as $post_type) {
				$field_html .= '<option value="' . $post_type . '" ';

				if (in_array($post_type, $current_post_types) || isset($current_post_types[$post_type])) {
					$field_html .= ' selected';
				}

				$field_html .= '>' . $post_type . '</option>';
			}

			$field_html .= '</select>';


			return $field_html;
		}

		/**
		 * Allow all custom post types
		 * @param array $allowed_post_types
		 * @return array
		 */
		function allow_all_post_types($allowed_post_types) {

			$current_post_types = VGSE()->options['be_post_types'];

			if (empty($current_post_types) || !is_array($current_post_types)) {
				$current_post_types = array();
			}

			$new_current_post_types = array();
			foreach ($current_post_types as $current_post_type) {
				$new_current_post_types[$current_post_type] = $current_post_type;
			}
			$all_post_types = apply_filters('vg_sheet_editor/custom_post_types/get_all_post_types', VGSE()->helpers->get_all_post_types());
			foreach ($all_post_types as $post_type) {
				$allowed_post_types[$post_type->name] = $post_type->label;
			}
			$allowed_post_types = wp_parse_args($allowed_post_types, $new_current_post_types);

			return $allowed_post_types;
		}

		function __set($name, $value) {
			$this->$name = $value;
		}

		function __get($name) {
			return $this->$name;
		}

	}

	add_action('vg_sheet_editor/initialized', 'vgse_cpt_init');

	function vgse_cpt_init() {
		WP_Sheet_Editor_CPTs::get_instance();
	}

}