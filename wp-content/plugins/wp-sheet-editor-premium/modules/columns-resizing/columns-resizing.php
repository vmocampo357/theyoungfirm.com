<?php

if (!class_exists('VGSE_Columns_Resizing')) {

	class VGSE_Columns_Resizing {

		static private $instance = false;
		var $version = '1.0.0';
		var $db_key = 'vgse_column_sizes';

		private function __construct() {
			
		}

		function init() {

			add_action('admin_enqueue_scripts', array($this, 'enqueue_assets'), 99);
			add_action('wp_enqueue_scripts', array($this, 'enqueue_assets'), 99);
			add_filter('vg_sheet_editor/handsontable/custom_args', array($this, 'allow_column_resize'));
			add_action('wp_ajax_vgse_save_manual_column_resize', array($this, 'save_manual_column_resize'));

			// CORE >= v2.0.0
			add_filter('vg_sheet_editor/columns/provider_items', array($this, 'filter_columns_settings'), 20, 2);

			// CORE < v2.0.0
			add_filter('vg_sheet_editor/columns/post_type_items', array($this, 'filter_columns_settings'), 20, 2);
		}

		/**
		 * Modify spreadsheet columns settings.
		 * 
		 * Add custom column sizes.
		 * @param array $spreadsheet_columns
		 * @param string $post_type
		 * @param bool $exclude_formatted_settings
		 * @return array
		 */
		function filter_columns_settings($spreadsheet_columns, $post_type) {


			$option = get_user_meta(get_current_user_id(), $this->db_key, true);

			if (empty($option) || empty($option[$post_type])) {
				return $spreadsheet_columns;
			}

			foreach ($option[$post_type] as $column_key => $column_width) {
				if (!isset($spreadsheet_columns[$column_key])) {
					continue;
				}
				$spreadsheet_columns[$column_key]['column_width'] = (int) $column_width;
			}

			return $spreadsheet_columns;
		}

		function enqueue_assets() {
			$pages_to_load_assets = VGSE()->frontend_assets_allowed_on_pages();
			if (!empty($_GET['page']) && in_array($_GET['page'], $pages_to_load_assets)) {
				wp_enqueue_script('vgse-columns-resizing-init', plugins_url('/assets/js/init.js', __FILE__), array('bep_init_js'), $this->version, false);
			}
		}

		function save_manual_column_resize() {

			if (!wp_verify_nonce($_REQUEST['nonce'], 'bep-nonce')) {
				wp_send_json_error(array('message' => __('You dont have enough permissions to view this page.', VGSE()->textname)));
			}
			$data = VGSE()->helpers->clean_data($_REQUEST);

			$option = get_user_meta(get_current_user_id(), $this->db_key, true);

			if (empty($option)) {
				$option = array();
			}

			$post_type = $data['post_type'];
			$sizes = $data['sizes'];

			$option[$post_type] = $sizes;

			update_user_meta(get_current_user_id(), $this->db_key, $option);

			wp_send_json_success();
		}

		function allow_column_resize($args) {
			$args['manualColumnResize'] = true;
			return $args;
		}

		/**
		 * Creates or returns an instance of this class.
		 *
		 * @return  Foo A single instance of this class.
		 */
		static function get_instance() {
			if (null == VGSE_Columns_Resizing::$instance) {
				VGSE_Columns_Resizing::$instance = new VGSE_Columns_Resizing();
				VGSE_Columns_Resizing::$instance->init();
			}
			return VGSE_Columns_Resizing::$instance;
		}

		function __set($name, $value) {
			$this->$name = $value;
		}

		function __get($name) {
			return $this->$name;
		}

	}

	add_action('vg_sheet_editor/initialized', 'vgse_columns_resizing_init');

	function vgse_columns_resizing_init() {
		VGSE_Columns_Resizing::get_instance();
	}

}