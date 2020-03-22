<?php

if (!class_exists('WP_Sheet_Editor_Columns_Visibility')) {

	/**
	 * Hide the columns of the spreadsheet editor that you don´t need.
	 */
	class WP_Sheet_Editor_Columns_Visibility {

		static private $instance = false;
		var $addon_helper = null;
		var $removed_columns_key = 'vgse_removed_columns';
		static $columns_visibility_key = 'vgse_columns_visibility';
		static $unfiltered_columns = array();

		private function __construct() {
			
		}

		/**
		 * Creates or returns an instance of this class.
		 */
		static function get_instance() {
			if (null == WP_Sheet_Editor_Columns_Visibility::$instance) {
				WP_Sheet_Editor_Columns_Visibility::$instance = new WP_Sheet_Editor_Columns_Visibility();
				WP_Sheet_Editor_Columns_Visibility::$instance->init();
			}
			return WP_Sheet_Editor_Columns_Visibility::$instance;
		}

		function init() {
			add_action('admin_init', array($this, 'migrate_old_settings'));
			add_filter('vg_sheet_editor/columns/all_items', array('WP_Sheet_Editor_Columns_Visibility', 'filter_columns_for_visibility'), 9999);
			add_action('vg_sheet_editor/editor/before_init', array($this, 'register_toolbar_items'));
			add_action('vg_sheet_editor/editor_page/after_content', array($this, 'render_settings_modal'));
			add_action('admin_enqueue_scripts', array($this, 'enqueue_assets'));
			add_action('wp_ajax_vgse_update_columns_visibility', array($this, 'update_columns_settings'));
			add_action('wp_ajax_vgse_remove_column', array($this, 'remove_column'));
			add_action('wp_ajax_vgse_restore_columns', array($this, 'restore_columns'));
			add_action('vg_sheet_editor/columns/blacklisted_columns', array($this, 'blacklist_removed_columns'), 10, 4);
		}

		static function get_visibility_options($post_type = null) {
			$options = get_option(WP_Sheet_Editor_Columns_Visibility::$columns_visibility_key, array());

			if ($post_type) {
				$options = isset($options[$post_type]) ? $options[$post_type] : array();
			}
			return $options;
		}

		function change_columns_status($columns) {
			$options = WP_Sheet_Editor_Columns_Visibility::get_visibility_options();

			$changed = false;
			foreach ($columns as $column) {
				$status = !empty($column['status']) ? $column['status'] : 'enabled';
				if (is_string($column['post_types'])) {
					$column['post_types'] = array($column['post_types']);
				}

				foreach ($column['post_types'] as $post_type_key) {
					if (isset($options[$post_type_key]) && !isset($options[$post_type_key]['disabled'][$column['key']]) && !isset($options[$post_type_key][$status][$column['key']])) {
						$options[$post_type_key][$status][$column['key']] = $column['name'];
						$changed = true;
					}
				}
			}

			if ($changed) {
				update_option(WP_Sheet_Editor_Columns_Visibility::$columns_visibility_key, $options);
			}
		}

		function migrate_old_settings() {
			if ((int) get_option(WP_Sheet_Editor_Columns_Visibility::$columns_visibility_key . '_migrated')) {
				return;
			}

			// Migrate frontend editors
			$frontend_editors = new WP_Query(array(
				'post_type' => 'vgse_editors',
				'posts_per_page' => -1,
				'fields' => 'ids'
			));
			foreach ($frontend_editors->posts as $post_id) {
				$old_settings = get_post_meta($post_id, 'vgse_columns', true);
				$new_settings = $this->migrate_old_settings_raw($old_settings);
				update_post_meta($post_id, 'vgse_columns', $new_settings);
			}

			// Migrate sheets
			$old_settings = VGSE()->options;
			$new_settings = $this->migrate_old_settings_raw($old_settings);

			update_option(WP_Sheet_Editor_Columns_Visibility::$columns_visibility_key, $new_settings);
			update_option(WP_Sheet_Editor_Columns_Visibility::$columns_visibility_key . '_migrated', 1);
		}

		function migrate_old_settings_raw($old_settings) {
			$new_settings = array();

			foreach ($old_settings as $key => $value) {
				if (strpos($key, 'be_visibility_') !== false) {
					if (isset($value['enabled']['placebo'])) {
						unset($value['enabled']['placebo']);
					}
					if (isset($value['disabled']['placebo'])) {
						unset($value['disabled']['placebo']);
					}
					$new_settings[str_replace('be_visibility_', '', $key)] = $value;
				}
			}
			return $new_settings;
		}

		function save_removed_columns($columns, $post_type) {
			$removed_columns = $this->get_removed_columns($post_type);
			$removed_columns[$post_type] = $columns;

			$removed_columns[$post_type] = array_unique(array_filter($removed_columns[$post_type]));
			update_option($this->removed_columns_key, $removed_columns);
		}

		function get_removed_columns($post_type) {
			$removed_columns = get_option($this->removed_columns_key, array());

			if (!is_array($removed_columns)) {
				$removed_columns = array();
			}
			if (!isset($removed_columns[$post_type])) {
				$removed_columns[$post_type] = array();
			}
			return $removed_columns;
		}

		function blacklist_removed_columns($blacklisted_columns, $key, $column_settings, $post_type) {
			$removed_columns = $this->get_removed_columns($post_type);
			$blacklisted_columns = array_merge($blacklisted_columns, $removed_columns[$post_type]);
			return $blacklisted_columns;
		}

		/**
		 * Remove column
		 */
		function restore_columns() {
			$data = VGSE()->helpers->clean_data($_REQUEST);
			if (empty($data['nonce']) || empty($data['post_type'])) {
				wp_send_json_error(array('message' => __('Missing parameters.', VGSE()->textname)));
			}

			if (!wp_verify_nonce($data['nonce'], 'bep-nonce')) {
				wp_send_json_error(array('message' => __('You dont have enough permissions to execute this action.', VGSE()->textname)));
			}
			$post_type = $data['post_type'];
			$this->save_removed_columns(array(), $post_type);
			wp_send_json_success(array('message' => __('Columns restored successfully, please reload the page to see the restored columns and enable them', VGSE()->textname)));
		}

		function remove_column() {
			$data = VGSE()->helpers->clean_data($_REQUEST);
			if (empty($data['nonce']) || empty($data['post_type']) || empty($data['column_key'])) {
				wp_send_json_error(array('message' => __('Missing parameters.', VGSE()->textname)));
			}

			if (!wp_verify_nonce($data['nonce'], 'bep-nonce')) {
				wp_send_json_error(array('message' => __('You dont have enough permissions to execute this action.', VGSE()->textname)));
			}
			$post_type = $data['post_type'];

			$removed_columns = $this->get_removed_columns($post_type);
			$removed_columns[$post_type][] = $data['column_key'];
			$this->save_removed_columns($removed_columns[$post_type], $post_type);
			wp_send_json_success();
		}

		/**
		 * Save modified settings
		 */
		function update_columns_settings($data = null, $custom_options = null, $silent = false) {
			if (!$data) {
				$data = VGSE()->helpers->clean_data($_REQUEST);
			}

			if (!wp_verify_nonce($data['nonce'], 'bep-nonce')) {
				if ($silent) {
					return false;
				} else {
					wp_send_json_error(array('message' => __('You dont have enough permissions to view this page.', VGSE()->textname)));
				}
			}

			$post_type = $data['post_type'];

			if (!is_null($custom_options)) {
				$options = $custom_options;
			} else {
				$options = WP_Sheet_Editor_Columns_Visibility::get_visibility_options();
			}

			$unfiltered_columns = WP_Sheet_Editor_Columns_Visibility::$unfiltered_columns;
			$post_type_columns = isset($unfiltered_columns[$post_type]) ? $unfiltered_columns[$post_type] : array();

			$new_columns = array(
				'enabled' => array(),
				'disabled' => array()
			);

			$columns_keys = $data['columns'];
			foreach ($columns_keys as $column_index => $column_key) {
				$new_columns['enabled'][$column_key] = (!empty($data['columns_names'][$column_index])) ? $data['columns_names'][$column_index] : $column_key;
			}
			foreach ($post_type_columns as $key => $existing_column) {
				if (isset($new_columns['enabled'][$key])) {
					continue;
				}
				$new_columns['disabled'][$key] = $existing_column['title'];
			}

			$options[$post_type] = $new_columns;

			if (is_null($custom_options)) {
				update_option(WP_Sheet_Editor_Columns_Visibility::$columns_visibility_key, $options);
			}

			if ($silent) {
				return $options;
			} else {
				wp_send_json_success(array(
					'post_type_editor_url' => VGSE()->helpers->get_editor_url($post_type),
				));
			}
		}

		/**
		 * Enqueue frontend assets
		 */
		function enqueue_assets() {
			$pages_to_load_assets = VGSE()->frontend_assets_allowed_on_pages();
			if (empty($_GET['page']) || !in_array($_GET['page'], $pages_to_load_assets)) {
				return;
			}

			$this->_enqueue_assets();
		}

		function _enqueue_assets() {
			wp_enqueue_script('wp-sheet-editor-sortable', plugins_url('/assets/vendor/Sortable/Sortable.min.js', __FILE__), array('jquery'), true);
			wp_enqueue_script('wp-sheet-editor-columns-visibility-modal', plugins_url('/assets/js/init.js', __FILE__), array('wp-sheet-editor-sortable'), true);
		}

		/**
		 * Render modal html
		 * @param str $post_type
		 */
		function render_settings_modal($post_type, $partial_form = false, $options = null) {
			$nonce = wp_create_nonce('bep-nonce');
			$random_id = rand();

			// disable columns visibility filter temporarily
			$unfiltered_columns = WP_Sheet_Editor_Columns_Visibility::$unfiltered_columns;
			$columns = isset($unfiltered_columns[$post_type]) ? $unfiltered_columns[$post_type] : array();

			$filtered_columns = wp_list_filter($columns, array(
				'allow_to_hide' => true,
			));
			$not_allowed_columns = apply_filters('vg_sheet_editor/columns_visibility/not_allowed_columns', array_keys(wp_list_filter($columns, array(
				'allow_to_hide' => false,
			))));
			$visible_columns = VGSE()->helpers->get_provider_columns($post_type);

			if (!$options) {
				$options = WP_Sheet_Editor_Columns_Visibility::get_visibility_options();
			}

			include __DIR__ . '/views/form.php';
		}

		/**
		 * Register toolbar item to edit columns visibility live on the spreadsheet
		 */
		function register_toolbar_items($editor) {
			if ($editor->provider->is_post_type) {
				$post_types = $editor->args['enabled_post_types'];
			} elseif ($editor->provider->key === 'user') {
				$post_types = array(
					'user'
				);
			}
			foreach ($post_types as $post_type) {
				$editor->args['toolbars']->register_item('visibility_settings', array(
					'type' => 'button',
					'allow_in_frontend' => false,
					'content' => __('Hide / Display / Sort columns', VGSE()->textname),
					'icon' => 'fa fa-sort',
					'toolbar_key' => 'secondary',
					'extra_html_attributes' => 'data-remodal-target="modal-columns-visibility"',
					'parent' => 'settings',
						), $post_type);
			}
		}

		/**
		 * Filter columns, remove the columns that were marked as hidden in the options page.
		 * @param array $columns
		 * @return array
		 */
		static function filter_columns_for_visibility($columns, $options = null) {
			if (VGSE()->helpers->is_settings_page()) {
				return $columns;
			}

			if (!WP_Sheet_Editor_Columns_Visibility::$unfiltered_columns) {
				WP_Sheet_Editor_Columns_Visibility::$unfiltered_columns = $columns;
			} 

			if (!$options) {
				$options = WP_Sheet_Editor_Columns_Visibility::get_visibility_options();
			}

			$sorted_columns = array();
			foreach ($columns as $post_type_key => $post_type) {
				$settings = array();

				if (isset($options[$post_type_key])) {
					$settings = $options[$post_type_key];
				}


				if (!isset($sorted_columns[$post_type_key])) {
					$sorted_columns[$post_type_key] = array();
				}

				// If zero columns are enabled, enable all
				if (empty($settings) || empty($settings['enabled'])) {
					$sorted_columns[$post_type_key] = $post_type;
				}

				if (empty($settings['enabled'])) {
					$settings['enabled'] = array();
				}
				foreach ($settings['enabled'] as $key => $enabled_column_label) {


					if (!isset($post_type[$key])) {

						continue;
					}
					$sorted_columns[$post_type_key][$key] = $post_type[$key];
				}

				$disallow_to_hide = wp_list_filter($post_type, array(
					'allow_to_hide' => false,
				));


				$sorted_columns[$post_type_key] = array_merge($disallow_to_hide, $sorted_columns[$post_type_key]);

				// Show columns that were added after the 
				// columns visiliby was saved, we hide columns that were
				// hidden explicitely only
				$columns_sorted = (count($settings) > 1 ) ? array_merge($settings['enabled'], $settings['disabled']) : current($settings);
				foreach ($post_type as $key => $column) {
					if (!isset($columns_sorted[$key])) {
						$sorted_columns[$post_type_key][$key] = $column;
					}
				}
			}

			return $sorted_columns;
		}

		function __set($name, $value) {
			$this->$name = $value;
		}

		function __get($name) {
			return $this->$name;
		}

	}

	add_action('vg_sheet_editor/initialized', 'vgse_columns_visibility_init');

	function vgse_columns_visibility_init() {
		WP_Sheet_Editor_Columns_Visibility::get_instance();
	}

}