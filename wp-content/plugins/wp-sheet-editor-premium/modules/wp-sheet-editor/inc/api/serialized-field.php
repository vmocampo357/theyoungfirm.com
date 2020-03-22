<?php

if (!class_exists('WP_Sheet_Editor_Serialized_Field')) {

	class WP_Sheet_Editor_Serialized_Field {

		var $snippets_options = array();
		var $settings = array();
		var $column_keys = array();

		function __construct($args) {

			$defaults = array(
				'sample_field_key' => '',
				'sample_field' => array(''),
				'column_width' => 150,
				'column_title_prefix' => '', // to remove the field key from the column title
				'level' => 1, // int
				'allowed_post_types' => array('post'),
				'is_single_level' => true,
				'allow_in_wc_product_variations' => false,
				'index_start' => 0,
				'label_index_start' => 1
			);
			$args = wp_parse_args($args, $defaults);
			$this->settings = $args;

			add_action('vg_sheet_editor/save_rows/before_saving_cell', array($this, 'save_column'), 10, 4);
			// Priority 12 to allow to instantiate from another editor/before_init function
			add_action('vg_sheet_editor/editor/before_init', array($this, 'register_columns'), 20);

			add_filter('vg_sheet_editor/provider/user/get_item_meta', array($this, 'filter_cell_data_for_readings_user'), 10, 5);
			add_filter('vg_sheet_editor/provider/post/get_item_meta', array($this, 'filter_cell_data_for_readings_post'), 10, 5);
			add_filter('vg_sheet_editor/formulas/sql_execution/can_execute', array($this, 'can_execute_sql_formula'), 10, 4);

			if (!empty($this->settings['allow_in_wc_product_variations'])) {
				add_filter('vg_sheet_editor/woocommerce/variation_columns', array($this, 'allow_in_variations'));
			}
		}

		function can_execute_sql_formula($can, $formula, $column, $post_type) {
			if (!$this->post_type_has_serialized_field($post_type) || strpos($column['key'], $this->settings['sample_field_key']) === false) {
				return $can;
			}

			return false;
		}

		function filter_cell_data_for_readings_user($value, $id, $key, $single, $context) {
			if ($context !== 'read' || !$this->post_type_has_serialized_field('user')) {
				return $value;
			}
			return $this->filter_cell_data_for_readings($value, $id, $key);
		}

		function filter_cell_data_for_readings_post($value, $id, $key, $single, $context) {
			if ($context !== 'read' || !$this->post_type_has_serialized_field(get_post_type($id))) {
				return $value;
			}
			return $this->filter_cell_data_for_readings($value, $id, $key);
		}

		function filter_cell_data_for_readings($value, $post_id, $column_key) {
			$post_value = maybe_unserialize(VGSE()->helpers->get_current_provider()->get_item_meta($post_id, $this->settings['sample_field_key'], true));

			if (empty($post_value) || !is_array($post_value)) {
				return $value;
			}

			if (!empty($this->settings['is_single_level'])) {
				$post_value_index = $this->settings['index_start'];
				foreach ($post_value as $post_value_key => $post_value_value) {
					$key = $this->settings['sample_field_key'] . '_' . $post_value_key . '_i_' . $post_value_index;

					if ($column_key === $key) {
						$value = apply_filters('vg_sheet_editor/serialized_addon/load_cell_value', $post_value_value, $key, $post_value, $post_id, $this->settings);
					}
				}
			} else {
				foreach ($post_value as $post_value_index => $post_value_inside) {
					if (!is_array($post_value_inside)) {
						continue;
					}
					foreach ($post_value_inside as $post_value_key => $post_value_value) {
						$key = $this->settings['sample_field_key'] . '_' . $post_value_key . '_i_' . $post_value_index;

						if ($column_key === $key) {
							$value = apply_filters('vg_sheet_editor/serialized_addon/load_cell_value', $post_value_value, $key, $post_value, $post_id, $this->settings);
						}
					}
				}
			}

			return $value;
		}

		function allow_in_variations($variation_columns) {
			$variation_columns = array_merge($variation_columns, $this->column_keys);
			return $variation_columns;
		}

		function save_column($item, $post_type, $column_settings, $key) {
			if (!$this->post_type_has_serialized_field($post_type) || strpos($key, $this->settings['sample_field_key']) === false) {
				return;
			}

			$post_id = VGSE()->helpers->sanitize_integer($item['ID']);

			$value = $item[$key];

			$criteria_parts = explode('_i_', str_replace($this->settings['sample_field_key'] . '_', '', $key));
			$criteria_key = current($criteria_parts);
			$criteria_index = end($criteria_parts);

			if (is_numeric($criteria_index)) {
				$criteria_index = (int) $criteria_index;
			}

			$post_criterias = maybe_unserialize(VGSE()->helpers->get_current_provider()->get_item_meta($post_id, $this->settings['sample_field_key'], true));

			if (empty($post_criterias) || !is_array($post_criterias)) {
				$post_criterias = array();
			}

			if (!empty($column_settings['is_single_level'])) {
				$post_criterias[$criteria_key] = $value;
			} else {
				$post_criterias[$criteria_index][$criteria_key] = $value;
			}

			VGSE()->helpers->get_current_provider()->update_item_meta($post_id, $this->settings['sample_field_key'], apply_filters('vg_sheet_editor/serialized_addon/save_cell', $post_criterias, $post_id, $this->settings, $item, $post_type, $column_settings, $key));
		}

		function post_type_has_serialized_field($post_type) {

			$allowed = in_array($post_type, $this->settings['allowed_post_types']);

			return $allowed;
		}

		function get_first_set_keys() {
			if (!empty($this->settings['sample_field']) && is_array($this->settings['sample_field'])) {
				$sample_field = $this->settings['sample_field'];
			} else {
				$sample_field = maybe_unserialize(VGSE()->helpers->get_current_provider()->get_item_meta($this->settings['sample_post_id'], $this->settings['sample_field_key'], true));
			}
			if (!is_array($sample_field)) {
				return array();
			}

			if ($this->settings['is_single_level']) {
				$first_set_keys = array_keys($sample_field);
			} else {
				$first_set = current($sample_field);
				if (empty($first_set)) {
					return array();
				}
				$first_set_keys = array_keys(current($sample_field));
			}
			return $first_set_keys;
		}

		function register_columns($editor) {

			if ($editor->provider->is_post_type) {
				$post_types = $editor->args['enabled_post_types'];
			} elseif ($editor->provider->key === 'user') {
				$post_types = array(
					'user'
				);
			}


			$first_set_keys = $this->get_first_set_keys();
			if (empty($first_set_keys)) {
				return;
			}

			foreach ($post_types as $post_type) {

				if (!$this->post_type_has_serialized_field($post_type)) {
					continue;
				}

				if (is_int($this->settings['level'])) {
					$this->settings['level'] = range($this->settings['index_start'], $this->settings['index_start'] + $this->settings['level'] - 1);
				}
				$label_index = $this->settings['label_index_start'];
				foreach ($this->settings['level'] as $i) {

					foreach ($first_set_keys as $field) {
						$field_label = (!empty($this->settings['column_title_prefix']) ? $this->settings['column_title_prefix'] : $this->settings['sample_field_key']) . ': ' . $field . ': ' . ( is_numeric($i) ? $label_index : $i );
						$key = $this->settings['sample_field_key'] . '_' . $field . '_i_' . $i;
						$column_settings = apply_filters('vg_sheet_editor/serialized_addon/column_settings', array(
							'data_type' => 'meta_data',
							'unformatted' => array('data' => $key),
							'column_width' => ( empty($this->settings['column_width'])) ? 150 : $this->settings['column_width'],
							'title' => ucwords(str_replace(array('_', '-'), ' ', $field_label)),
							'type' => '',
							'supports_formulas' => true,
							'formatted' => array('data' => $key),
							'allow_to_hide' => true,
							'allow_to_rename' => true,
							// Allow to edit on view requests, this will prevent core from adding the 
							// lock icon and locking edits, and it will disable saving because this class 
							// has its own saving controller
							'allow_to_save' => ( (isset($_REQUEST['action']) && in_array($_REQUEST['action'], array('vgse_load_data', 'vgse_insert_individual_post'))) || empty($_POST) ) ? true : false,
							'is_single_level' => $this->settings['is_single_level'],
							'default_value' => (!empty($this->settings['sample_field'][$field])) ? $this->settings['sample_field'][$field] : '',
							'serialized_field_settings' => $this->settings
								), $first_set_keys, $field, $key, $post_type, $this->settings);

						if (!empty($column_settings)) {
							$editor->args['columns']->register_item($key, $post_type, $column_settings);
							$this->column_keys[] = $key;
						}
					}
					$label_index++;
				}
			}
		}

		function __set($name, $value) {
			$this->$name = $value;
		}

		function __get($name) {
			return $this->$name;
		}

	}

}