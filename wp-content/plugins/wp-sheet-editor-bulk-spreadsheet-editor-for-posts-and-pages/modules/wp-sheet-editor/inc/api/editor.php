<?php
if (!class_exists('WP_Sheet_Editor_Factory')) {

	class WP_Sheet_Editor_Factory {

		var $args = array();
		var $texts = array();
		var $current_columns = array();
		var $current_toolbars = array();
		var $provider = null;
		static $registered_menus = array();

		function __construct($args = array()) {
			$defaults = array(
				'enabled_post_types' => array(),
				'posts_per_page' => 10,
				'save_posts_per_page' => 4,
				'wait_between_batches' => 10,
				'fixed_columns_left' => 2,
				'provider' => 'post',
				'provider_key' => 'post_type',
				'columns' => '',
				'toolbars' => '',
				'admin_menu' => array(
					array(
						'type' => 'submenu',
						'name' => 'Edit Post',
						'slug' => 'vgse-bulk-edit-post',
						'icon' => null,
					),
				),
				'texts' => array(),
			);
			$this->args = wp_parse_args($args, $defaults);
			$this->provider = VGSE()->helpers->get_data_provider($this->args['provider']);

			add_action('admin_menu', array($this, 'register_menu'));
			// When we bootstrap 2 separate spreadsheets for post different post types,
			// if the current sheet loaded is for a post type not enabled in this bootstrap config, 
			// don't bootstrap to avoid loading two spreadsheets in the same page
			if (!defined('WPSE_DISABLE_DOUBLE_SHEET_PROTECTION') && !in_array($this->args['provider'], $this->args['enabled_post_types'])) {
				return;
			}

			$this->texts = array(
				'save_changes_before_remove_filter' => __('You have modified posts. Please save the changes because we will refresh the spreadsheet.', VGSE()->textname),
				'no_rows_for_formula' => __("We didn't find rows to update from the search query. Please try another search query.", VGSE()->textname),
				'settings_moved_submenu' => __('You can find all the settings here, like columns visibility, etc.', VGSE()->textname),
				'posts_not_found' => __('Oops, nothing found', VGSE()->textname),
				'add_posts_here' => __('You can create new items here', VGSE()->textname),
				'use_other_image' => __('Select Image', VGSE()->textname),
				'no_options_available' => __('No options available', VGSE()->textname),
				'posts_loaded' => __('Items loaded in the spreadsheet', VGSE()->textname),
				'new_rows_added' => __('New rows added', VGSE()->textname),
				'formula_applied' => __('The formula has been executed. ¿Do you want to reload the page to see the changes?', VGSE()->textname),
				'saving_stop_error' => __('<p>The changes were not saved completely. The process was canceled due to an error .</p><p>You can close this popup.</p>', VGSE()->textname),
				'paged_batch_saved' => __('{updated} items saved of {total} items that need saving.', VGSE()->textname),
				'everything_saved' => __('All items have been saved.', VGSE()->textname),
				'save_changes_on_leave' => __('Please check if you have unsaved changes. If you have, please save them or they will be dismissed.', VGSE()->textname),
				'no_changes_to_save' => __('We did not find changes to save. You either haven´t made changes or you changed some cells that auto save (the item content and images cells save automatically.).', VGSE()->textname),
				'http_error_400' => __('The server did not accept our request. Bad request, please try refresh the page and try again.', VGSE()->textname),
				'http_error_403' => __('The server didn´t accept our request. You don´t have permission to do this action. Please log in again.', VGSE()->textname),
				'http_error_500_502_505' => __('The server is not available. Please try again later.', VGSE()->textname),
				'http_error_try_now' => __('The server is not available. Do you want to try again?', VGSE()->textname),
				'http_error_503' => __('The server wasn´t able to process our request. Server error. Please try again later.', VGSE()->textname),
				'http_error_509' => __('The server has exceeded its allocated resources and is not able to process our request.', VGSE()->textname),
				'http_error_504' => __('The server is busy and took too long to respond to our request. Please try again later.', VGSE()->textname),
				'http_error_default' => __('The server could not process our request. Please try again later.', VGSE()->textname),
			);

			do_action('vg_sheet_editor/editor/before_init', $this);

			$this->current_columns = $this->args['columns']->get_provider_items($this->args['provider'], false);
			$this->current_toolbars = $this->args['toolbars']->get_provider_items($this->args['provider'], null);

			// Internal hooks
			add_action('vg_sheet_editor/editor_page/after_content', array($this, 'render_support_modal'));
			add_action('vg_sheet_editor/editor_page/after_content', array($this, 'render_extensions_modal'));
			add_filter('vg_sheet_editor/load_rows/output', array($this, 'add_lock_to_readonly_cells'), 10, 3);

			// @todo Hook to the filter before registering columns
			add_filter('vg_sheet_editor/columns/can_add_item', array($this, 'disallow_file_uploads_for_some_users'), 10, 3);

			add_action('admin_head', array($this, 'add_editor_settings_to_header'));
			add_action('vg_sheet_editor/render_editor_js_settings', array($this, 'add_editor_settings_to_header'));

			add_action('admin_enqueue_scripts', array($this, 'remove_conflicting_assets'), 99999999);
			add_action('admin_print_styles', array($this, 'remove_conflicting_assets'), 99999999);

			VGSE()->editors[VGSE()->helpers->get_data_provider_class_key($this->args['provider'])] = & $this;
		}

		function remove_conflicting_assets() {

			$pages_to_load_assets = VGSE()->frontend_assets_allowed_on_pages();
			if (empty($_GET['page']) ||
					!in_array($_GET['page'], $pages_to_load_assets)) {
				return;
			}

			$this->_remove_conflicting_assets();
		}

		function _remove_conflicting_assets() {
			$remove = array(
				'select2',
				'tribe-select2',
				'wc-admin-meta-boxes',
				'woocommerce_settings',
				'wc-enhanced-select',
				'wc-shipping-zones',
				'woocommerce-shop-as-customer',
				'woocommerce_admin_styles',
				'woocommerce_admin',
				'jquery-chosen',
				'heartbeat',
				'wa-wps-admin-script',
				'edd-admin-scripts',
			);

			foreach ($remove as $handle) {
				wp_dequeue_style($handle);
				wp_deregister_style($handle);
				wp_dequeue_script($handle);
				wp_deregister_script($handle);
			}
		}

		/**
		 * Render editor page
		 */
		function render_editor_page() {
			if (!current_user_can('edit_posts')) {
				wp_die(__('You dont have enough permissions to view this page.', VGSE()->textname));
			}

			require VGSE_DIR . '/views/editor-page.php';
		}

		function add_editor_settings_to_header() {

			if (!VGSE()->helpers->is_editor_page()) {
				return;
			}

			$current_provider_in_page = VGSE()->helpers->get_provider_from_query_string();
			if ($current_provider_in_page !== $this->args['provider']) {
				return;
			}

			$spreadsheet_columns = $this->args['columns']->get_provider_items($this->args['provider'], true);
			$columns = array();
			$titles = array();
			$columsFormat = array();
			$columsUnformat = array();

			if (!empty($spreadsheet_columns)) {
				$columns = wp_list_pluck($spreadsheet_columns, 'column_width', 'key');
				$titles = wp_list_pluck($spreadsheet_columns, 'title', 'key');
				$columsFormat = wp_list_pluck($spreadsheet_columns, 'formatted');
				$columsUnformat = wp_list_pluck($spreadsheet_columns, 'unformatted');
			}
			$settings = array(
				'startRows' => 0,
				'startCols' => !empty($this->current_columns) ? count($this->current_columns) : 0,
				'colWidths' => !empty($columns) ? array_map('intval', $columns) : array(),
				'colHeaders' => $titles,
				'columnsUnformat' => ($columsUnformat),
				'columnsFormat' => ($columsFormat),
				'custom_handsontable_args' => array(),
				'debug' => (!empty(VGSE()->options['be_disable_cells_lazy_loading'])) ? true : null,
				'watch_cells_to_lock' => false
			);

			$all_settings = wp_parse_args($settings, $this->args);

			$all_settings['texts'] = wp_parse_args($all_settings['texts'], $this->texts);

			if (!empty($all_settings['fixed_columns_left'])) {
				$all_settings['custom_handsontable_args']['fixedColumnsLeft'] = $all_settings['fixed_columns_left'];
			}
			$all_settings['custom_handsontable_args'] = json_encode(apply_filters('vg_sheet_editor/handsontable/custom_args', $all_settings['custom_handsontable_args'], $this->args['provider']), JSON_FORCE_OBJECT);
			?>
			<script>
				var vgse_editor_settings = <?php echo json_encode(apply_filters('vg_sheet_editor/js_data', $all_settings, $current_provider_in_page)); ?>;
			</script>
			<?php
		}

		function disallow_file_uploads_for_some_users($allowed, $key, $args) {

			if (in_array($args['type'], array('boton_gallery', 'boton_gallery_multiple')) && !current_user_can('upload_files')) {
				return false;
			}
			return $allowed;
		}

		/**
		 * Register admin pages
		 */
		function register_menu() {

			if (empty($this->args['admin_menu'])) {
				return;
			}

			foreach ($this->args['admin_menu'] as $admin_menu) {

				if (!empty($admin_menu['treat_as_url'])) {
					$render_callback = null;
				} else {
					$render_callback = array($this, 'render_editor_page');
				}

				if ($admin_menu['type'] === 'submenu') {
					if (empty($admin_menu['parent'])) {
						$admin_menu['parent'] = 'vg_sheet_editor_setup';
					}
					add_submenu_page($admin_menu['parent'], $admin_menu['name'], $admin_menu['name'], 'edit_posts', $admin_menu['slug'], $render_callback);
				} else {

					if (empty($admin_menu['icon'])) {
						$admin_menu['icon'] = null;
					}
					add_menu_page($admin_menu['name'], $admin_menu['name'], 'edit_posts', $admin_menu['slug'], $render_callback, $admin_menu['icon']);
				}
			}
		}

		function render_support_modal($provider) {
			require VGSE_DIR . '/views/support-modal.php';
		}

		function render_extensions_modal($provider) {
			require VGSE_DIR . '/views/extensions-modal.php';
		}

		function add_lock_to_readonly_cells($data, $query, $spreadsheet_columns) {

			if (empty($data) || !is_array($data)) {
				return $data;
			}
			VGSE()->helpers->profile_record('Before ' . __FUNCTION__);
			foreach ($data as $post_index => $post_row) {


				foreach ($post_row as $column_key => $column_value) {

					$cell_settings = isset($spreadsheet_columns[$column_key]) ? $spreadsheet_columns[$column_key] : false;

					if (!$cell_settings || !isset($cell_settings['allow_to_save']) || $cell_settings['allow_to_save'] || strpos($data[$post_index][$column_key], 'vg-cell-blocked') !== false || strpos($data[$post_index][$column_key], 'button') !== false) {
						continue;
					}
					$data[$post_index][$column_key] = '<i class="fa fa-lock vg-cell-blocked vg-readonly-lock"></i> ' . $column_value;
				}
			}
			VGSE()->helpers->profile_record('After ' . __FUNCTION__);
			return $data;
		}

	}

}