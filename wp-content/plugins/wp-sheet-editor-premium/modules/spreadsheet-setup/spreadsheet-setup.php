<?php

if (!class_exists('WPSE_Post_Type_Setup_Wizard')) {

	class WPSE_Post_Type_Setup_Wizard {

		static private $instance = false;
		var $page_slug = 'vg_sheet_editor_post_type_setup';
		var $custom_post_types_key = 'vg_sheet_editor_custom_post_types';

		private function __construct() {
			
		}

		function init() {

			// Exit if "columns visibility " or "custom columns" extensions are disabled
			if (!class_exists('WP_Sheet_Editor_Columns_Visibility') || !class_exists('WP_Sheet_Editor_Custom_Columns')) {
				return;
			}

			add_action('admin_menu', array($this, 'register_menu'), 99);
			add_action('init', array($this, 'register_post_types'));
			add_action('wp_ajax_vgse_register_post_type', array($this, 'save_post_type'));
			add_action('wp_ajax_vgse_delete_post_type', array($this, 'delete_post_type'));
			add_action('wp_ajax_vgse_post_type_setup_columns_visibility', array($this, 'get_columns_visibility_html'));
			add_action('wp_ajax_vgse_post_type_setup_save_column', array($this, 'save_column'));
			add_filter('vg_sheet_editor/welcome_url', array($this, 'filter_welcome_url'));
		}

		function filter_welcome_url($url) {
			$url = admin_url('admin.php?page=' . $this->page_slug);
			return $url;
		}

		function save_column() {
			if (empty($_REQUEST['nonce']) || empty($_REQUEST['current_post_type']) || empty($_REQUEST['label']) || !wp_verify_nonce($_REQUEST['nonce'], 'bep-nonce')) {
				wp_send_json_error();
			}

			if (empty($_REQUEST['key'])) {
				$_REQUEST['key'] = sanitize_key($_REQUEST['label']);
			}

			$columns = WP_Sheet_Editor_Custom_Columns::get_instance();

			$columns->add_columns(array(array(
					'name' => sanitize_text_field($_REQUEST['label']),
					'key' => sanitize_key($_REQUEST['key']),
					'post_types' => sanitize_text_field($_REQUEST['current_post_type']),
				)), array(
				'append' => true,
			));

			wp_send_json_success(array(
				'key' => sanitize_key($_REQUEST['key']),
				'label' => sanitize_text_field($_REQUEST['label']),
			));
		}

		function get_columns_visibility_html() {
			if (empty($_REQUEST['nonce']) || empty($_REQUEST['post_type']) || !wp_verify_nonce($_REQUEST['nonce'], 'bep-nonce')) {
				wp_send_json_error();
			}

			$post_type = sanitize_text_field($_REQUEST['post_type']);
			$out = '';
			// Columns visibility section
			if (class_exists('WP_Sheet_Editor_Columns_Visibility')) {
				$columns_visibility_module = WP_Sheet_Editor_Columns_Visibility::get_instance();
				ob_start();
				$columns_visibility_module->render_settings_modal($post_type);
				$columns_visibility_html = ob_get_clean();

				$out = str_replace(array(
					'data-remodal-id="modal-columns-visibility" data-remodal-options="closeOnOutsideClick: false, hashTracking: false" class="remodal remodal',
					'button primary hidden form-submit-inside',
					'Apply settings',
					'<form',
						), array(
					'class="',
					'button primary form-submit-inside button-primary',
					'Save',
					'<form data-callback="vgsePostTypeSetupColumnsVisibilitySaved" ',
						), $columns_visibility_html);
			}

			wp_send_json_success(array(
				'html' => $out,
			));
		}

		function delete_post_type() {
			if (empty($_REQUEST['nonce']) || empty($_REQUEST['post_type']) || !wp_verify_nonce($_REQUEST['nonce'], 'bep-nonce')) {
				wp_send_json_error();
			}

			$existing_post_types = get_option($this->custom_post_types_key, array());
			$existing_post_types_slugs = array_map('sanitize_title', $existing_post_types);
			$new_post_type = sanitize_title($_REQUEST['post_type']);


			if ($post_type_index = array_search($new_post_type, $existing_post_types_slugs)) {
				unset($existing_post_types[$post_type_index]);
			}

			update_option($this->custom_post_types_key, $existing_post_types);

			$posts = new WP_Query(array(
				'post_type' => $new_post_type,
				'fields' => 'ids',
				'posts_per_page' => -1,
			));
			foreach ($posts->posts as $post_id) {
				wp_delete_post($post_id, true);
			}

			wp_send_json_success(array(
				'message' => __('Post type deleted', VGSE()->textname),
			));
		}

		function save_post_type() {
			if (empty($_REQUEST['nonce']) || empty($_REQUEST['post_type']) || !wp_verify_nonce($_REQUEST['nonce'], 'bep-nonce')) {
				wp_send_json_error();
			}

			$existing_post_types = get_option($this->custom_post_types_key, array());
			$post_types = $existing_post_types;
			$post_types[] = sanitize_title($_REQUEST['post_type']);

			$post_types = array_unique($post_types);

			$registered_post_type = current(VGSE()->helpers->get_all_post_types(array(
						'name' => sanitize_text_field($_REQUEST['post_type']),
						'label' => sanitize_text_field($_REQUEST['post_type']),
			)));

			if (!empty($registered_post_type)) {
				$out = array(
					'slug' => $registered_post_type->name,
					'label' => $registered_post_type->label
				);
			} else {
				if ($existing_post_types !== $post_types) {
					update_option($this->custom_post_types_key, $post_types);
				}
				$out = array(
					'slug' => sanitize_title($_REQUEST['post_type']),
					'label' => sanitize_text_field($_REQUEST['post_type']),
				);
			}

			wp_send_json_success($out);
		}

		function register_post_types() {
			$post_types = get_option($this->custom_post_types_key, array());

			if (empty($post_types) || !is_array($post_types)) {
				return;
			}
			
			if( isset($_GET['wpse_delete_all_post_types'])){				
				update_option($this->custom_post_types_key, null);
				return;
			}
			
			foreach ($post_types as $post_type) {
				$post_type_key = sanitize_title($post_type);
				$args = array(
					'public' => true,
					'label' => $post_type,
				);
				register_post_type($post_type_key, $args);
			}
		}

		function register_menu() {
			add_submenu_page('vg_sheet_editor_setup', __('Setup spreadsheet', VGSE()->textname), __('Setup spreadsheet', VGSE()->textname), 'manage_options', $this->page_slug, array($this, 'render_post_type_setup'), VGSE()->plugin_url . 'assets/imgs/icon-20x20.png');
		}

		/**
		 * Render post type setup page
		 */
		function render_post_type_setup() {
			if (!current_user_can('manage_options')) {
				wp_die(__('You dont have enough permissions to view this page.', VGSE()->textname));
			}

			require __DIR__ . '/views/page.php';
		}

		/**
		 * Creates or returns an instance of this class.
		 *
		 * @return  Foo A single instance of this class.
		 */
		static function get_instance() {
			if (null == WPSE_Post_Type_Setup_Wizard::$instance) {
				WPSE_Post_Type_Setup_Wizard::$instance = new WPSE_Post_Type_Setup_Wizard();
				WPSE_Post_Type_Setup_Wizard::$instance->init();
			}
			return WPSE_Post_Type_Setup_Wizard::$instance;
		}

		function __set($name, $value) {
			$this->$name = $value;
		}

		function __get($name) {
			return $this->$name;
		}

	}

	if (!function_exists('WPSE_Post_Type_Setup_Wizard_Obj')) {

		function WPSE_Post_Type_Setup_Wizard_Obj() {
			return WPSE_Post_Type_Setup_Wizard::get_instance();
		}

	}
	WPSE_Post_Type_Setup_Wizard_Obj();
}