<?php

if (!class_exists('WP_Sheet_Editor_Columns')) {

	class WP_Sheet_Editor_Columns {

		private $registered_items = array();

		function __construct() {
			
		}

		function has_item($key, $provider = null) {
			if (empty($provider)) {
				$provider = 'post';
			}
			return isset($this->registered_items[$provider][$key]);
		}

		/**
		 * Register spreadsheet column 
		 * @param string $key
		 * @param string $provider
		 * @param array $args
		 */
		function register_item($key, $provider = null, $args = array()) {
			if (empty($key)) {
				return;
			}
			$args = $this->_register_item($key, $args);

			if (empty($provider)) {
				$provider = 'post';
			}

			$blacklisted_keys = apply_filters('vg_sheet_editor/columns/blacklisted_columns', array('_edit_lock', '_edit_last', '_wp_old_slug', '_wpcom_is_markdown', 'vgse_column_sizes', 'wxr_import'), $key, $args, $provider);
			$allowed = true;
			// We use strpos instead of in_array because some fields might have wp prefix
			foreach ($blacklisted_keys as $blacklisted_field) {
				if (strpos($key, $blacklisted_field) !== false) {
					$allowed = false;
					break;
				}
			}
			if (!$allowed) {
				return;
			}

			if (!apply_filters('vg_sheet_editor/columns/can_add_item', true, $key, $args, $provider)) {
				return;
			}
			if (!isset($this->registered_items[$provider])) {
				$this->registered_items[$provider] = array();
			}
			$this->registered_items[$provider][$key] = $args;
		}

		function _register_item($key, $args = array()) {
			$defaults = array(
				'data_type' => 'post_data', //String (post_data,post_meta|meta_data)	
				'unformatted' => array(), //Array (Valores admitidos por el plugin de handsontable)
				'column_width' => 100, //int (Ancho de la columna)
				'title' => ucwords(str_replace(array('-', '_'), ' ', $key)), //String (Titulo de la columna)
				'type' => '', // String (Es para saber si será un boton que abre popup, si no dejar vacio) boton_tiny|boton_gallery|boton_gallery_multiple|view_post|handsontable|metabox|(vacio)
				'edit_button_label' => null,
				'edit_modal_id' => null,
				'edit_modal_title' => null,
				'edit_modal_description' => null,
				'edit_modal_local_cache' => true,
				'edit_modal_save_action' => null, // js_function_name:<function name>, <wp ajax action>
				'edit_modal_cancel_action' => null,
				'metabox_show_selector' => null,
				'metabox_value_selector' => null,
				'handsontable_columns' => array(), // array( 'product' => array( array( 'data' => 'name' ), ) ),
				'handsontable_column_names' => array(), // array('product' => array('Column name'),),
				'handsontable_column_widths' => array(), // array('product' => array(160),),
				'supports_formulas' => false,
				'formatted' => array(), //Array (Valores admitidos por el plugin de handsontable)
				'allow_to_hide' => true,
				'allow_to_rename' => true,
				'allow_to_save' => true,
				'allow_plain_text' => true,
				'default_value' => '',
			);

			$args = wp_parse_args($args, $defaults);

			if (in_array($args['type'], array('metabox'))) {
				$args['supports_formulas'] = false;
			}
			if (in_array($args['type'], array('metabox', 'handsontable'))) {
				$args['allow_plain_text'] = false;
				$args['allow_to_save'] = false;

				if (empty($args['edit_modal_title'])) {
					$args['edit_modal_title'] = $args['title'];
				}
				if (empty($args['edit_button_label'])) {
					$args['edit_button_label'] = sprintf(__('Edit %s', VGSE()->textname), $args['title']);
				}
				if (empty($args['edit_modal_id'])) {
					$args['edit_modal_id'] = 'vgse-modal-editor-' . wp_generate_password(5, false);
				}
			}

			if (empty($args['default_title'])) {
				$args['default_title'] = $args['title'];
			}

			if (empty($args['key'])) {
				$args['key'] = $key;
			}
			if (empty($args['unformatted'])) {
				$args['unformatted'] = array(
					'data' => $args['key']
				);
			}
			if (empty($args['formatted'])) {
				$args['formatted'] = array(
					'data' => $args['key']
				);
			}

			if (empty($args['value_type'])) {
				if (!empty($args['type'])) {
					$args['value_type'] = $args['type'];
				} elseif ($args['data_type'] === 'post_terms') {
					$args['value_type'] = 'post_terms';
				} else {
					$args['value_type'] = 'text';
				}
			}

			// post_meta is an alias of meta_data
			if ($args['data_type'] === 'post_meta') {
				$args['data_type'] = 'meta_data';
			}

			if (!$args['allow_to_save']) {
				$args['formatted']['renderer'] = 'html';
				$args['formatted']['readOnly'] = true;
				$args['unformatted']['renderer'] = 'html';
				$args['unformatted']['readOnly'] = true;
			}

			return $args;
		}

		/**
		 * Get all spreadsheet columns
		 * @return array
		 */
		function get_items($skip_cache = false) {
			$cache_key = 'wpse_columns_all';
			$spreadsheet_columns = wp_cache_get($cache_key);
			if (!$spreadsheet_columns) {
				$spreadsheet_columns = apply_filters('vg_sheet_editor/columns/all_items', $this->registered_items);
				wp_cache_set($cache_key, $spreadsheet_columns);
			}

			return $spreadsheet_columns;
		}

		/**
		 * Get individual spreadsheet column
		 * @return array
		 */
		function get_item($item_key, $provider = 'post', $run_callbacks = false) {
			$items = $this->get_provider_items($provider, $run_callbacks);
			if (isset($items[$item_key])) {
				return $items[$item_key];
			} else {
				return false;
			}
		}

		function _run_callbacks_on_items($items) {
			if (empty($items) || !is_array($items)) {
				return array();
			}
			foreach ($items as $column_key => $column_args) {
				if (isset($column_args['formatted'])) {
					if (empty($column_args['formatted']['callback_args'])) {
						$column_args['formatted']['callback_args'] = array();
					}
					if (isset($column_args['formatted']['selectOptions']) && is_callable($column_args['formatted']['selectOptions'])) {
						$items[$column_key]['formatted']['selectOptions'] = call_user_func_array($column_args['formatted']['selectOptions'], $column_args['formatted']['callback_args']);
					}
					if (isset($column_args['formatted']['source']) && is_callable($column_args['formatted']['source'])) {
						$items[$column_key]['formatted']['source'] = call_user_func_array($column_args['formatted']['source'], $column_args['formatted']['callback_args']);
					}
				}
			}
			return $items;
		}

		/**
		 * Get all spreadsheet columns by post type
		 * @return array
		 */
		function get_provider_items($provider, $run_callbacks = false) {
			$cache_key = 'wpse_columns_' . $provider . '_' . (int) $run_callbacks;
			$out = wp_cache_get($cache_key);
			if (!$out) {
				$items = $this->get_items();
				$out = array();
				if (isset($items[$provider])) {
					$out = $items[$provider];

					if ($run_callbacks) {
						$out = $this->_run_callbacks_on_items($out);
					}
				}

				$out = apply_filters('vg_sheet_editor/columns/provider_items', $out, $provider, $run_callbacks, $this);
				wp_cache_set($cache_key, $out);
			}
			return $out;
		}

		function __set($name, $value) {
			$this->$name = $value;
		}

		function __get($name) {
			return $this->$name;
		}

	}

}