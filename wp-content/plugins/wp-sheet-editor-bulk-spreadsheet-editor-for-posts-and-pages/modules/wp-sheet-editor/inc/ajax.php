<?php

if (!class_exists('WP_Sheet_Editor_Ajax')) {

	class WP_Sheet_Editor_Ajax {

		static private $instance = false;

		private function __construct() {
			
		}

		/*
		 * Controller for loading posts to the spreadsheet
		 */

		function load_rows() {

			$settings = $_REQUEST;
			if (empty($settings['nonce']) || !wp_verify_nonce($settings['nonce'], 'bep-nonce')) {
				$message = array('message' => __('You dont have enough permissions to view this page.', VGSE()->textname));
				wp_send_json_error($message);
			}

			$rows = VGSE()->helpers->get_rows($settings);

			if (is_wp_error($rows)) {
				wp_send_json_error(array(
					'message' => $rows->get_error_message()
				));
			}

			$rows['rows'] = array_values($rows['rows']);
			wp_send_json_success($rows);
		}

		/*
		 * Controller for saving posts changes
		 */

		function save_rows() {

			$result = VGSE()->helpers->save_rows($_REQUEST);

			if (is_wp_error($result)) {
				wp_send_json_error(array(
					'message' => $result->get_error_message()
				));
			}

			wp_send_json_success(array('message' => __('Changes saved successfully', VGSE()->textname)));
		}

		/*
		 * Controller for saving new post.
		 */

		function insert_individual_post() {
			if (!wp_verify_nonce($_REQUEST['nonce'], 'bep-nonce')) {
				wp_send_json_error(array('message' => __('You dont have enough permissions to view this page.', VGSE()->textname)));
			}
			$_REQUEST = VGSE()->helpers->clean_data($_REQUEST);
			$post_type = $_REQUEST['post_type'];
			$rows = (int) $_REQUEST['rows'];

			$result = VGSE()->helpers->create_placeholder_posts($post_type, $rows);

			if (is_wp_error($result)) {
				wp_send_json_error(array(
					'message' => $result->get_error_message()
				));
			}
			wp_send_json_success(array('message' => $result));
		}

		/**
		 * Find posts by name
		 */
		function find_post_by_name() {
			global $wpdb;
			$data = VGSE()->helpers->clean_data($_REQUEST);
			$nonce = $data['nonce'];

			if (!wp_verify_nonce($nonce, 'bep-nonce')) {
				wp_send_json_error(array('message' => __('Request not allowed. Try again later.', VGSE()->textname)));
			}

			$post_type = (!empty($data['post_type']) ) ? sanitize_text_field($data['post_type']) : false;
			$search = (!empty($data['search']) ) ? sanitize_text_field($data['search']) : false;

			if (empty($post_type) || empty($search)) {
				wp_send_json_error(array('message' => __('Missing parameters.', VGSE()->textname)));
			}

			$posts_found = $wpdb->get_results("SELECT * FROM $wpdb->posts WHERE post_type = '" . esc_sql($post_type) . "' AND post_title LIKE '%" . esc_sql($search) . "%' LIMIT 10");

			if (empty($posts_found)) {
				wp_send_json_error(array('message' => __('No items found.', VGSE()->textname)));
			}

			$out = array();
			foreach ($posts_found as $post) {
				$out[] = array(
					'id' => $post->post_type . '--' . $post->ID,
					'text' => $post->post_title . ' ( ID: ' . $post->ID . ', ' . $post->post_type . ' )',
				);
			}
			wp_send_json_success(array('data' => $out));
		}

		/**
		 * Controller for saving individual field of post
		 */
		function save_single_post_data() {
			if (!wp_verify_nonce($_REQUEST['nonce'], 'bep-nonce')) {
				wp_send_json_error(array('message' => __('You dont have enough permissions to view this page.', VGSE()->textname)));
			}
			$_REQUEST = VGSE()->helpers->clean_data($_REQUEST);
			$content = html_entity_decode($_REQUEST['content']);
			$id = (int) $_REQUEST['post_id'];
			$key = $_REQUEST['key'];
			$type = $_REQUEST['type'];
			$post_type = $_REQUEST['post_type'];

			if (VGSE()->options['be_disable_post_actions']) {
				$post_type = get_post_type($id);
				VGSE()->helpers->remove_all_post_actions($post_type);
			}

			do_action('vg_sheet_editor/save_single_post_data/before', $id, $content, $key, $type);
			$result = VGSE()->data_helpers->save_single_post_data($id, $content, $key, $type);
			
			do_action('vg_sheet_editor/save_single_post_data/after', $result, $id, $content, $key, $type);
			if (is_wp_error($result)) {

				$errors = $result->get_error_messages();
				wp_send_json_success(array('message' => sprintf(__('Error: %s', VGSE()->textname), implode(', ', $errors))));
			} else {
				VGSE()->helpers->increase_counter('editions');
				VGSE()->helpers->increase_counter('processed');

				$title = get_the_title($id);
				wp_send_json_success(array('message' => sprintf(__('Saved: %s', VGSE()->textname), $title)));
			}
		}

		/**
		 * Save image cell, single image
		 */
		function set_featured_image() {
			if (!wp_verify_nonce($_REQUEST['nonce'], 'bep-nonce')) {
				wp_send_json_error(array('message' => __('You dont have enough permissions to view this page.', VGSE()->textname)));
			}
			$data = VGSE()->helpers->clean_data($_REQUEST);

			$result = VGSE()->helpers->save_images(array(
				'post_id' => $data['post_id'], // int
				'id' => $data['id'], // str
				'key' => $data['key'], // str
			));
			if (!$result) {
				wp_send_json_error(array('message' => __('The image could not be saved. Try again later.', VGSE()->textname)));
			}

			wp_send_json_success(array('message' => __('Image saved.', VGSE()->textname)));
		}

		/*
		 * Get image preview html
		 */

		function get_image_preview() {
			if (!wp_verify_nonce($_REQUEST['nonce'], 'bep-nonce')) {
				wp_send_json_error(array('message' => __('You dont have enough permissions to view this page.', VGSE()->textname)));
			}
			$_REQUEST = VGSE()->helpers->clean_data($_REQUEST);
			$post_id = (int) $_REQUEST['id'];
			$key = $_REQUEST['key'];
			$imgid = $_REQUEST['localValue'];
			$post_type = $_REQUEST['post_type'];

			if (empty($imgid)) {
				$imgid = get_post_meta($post_id, $key, true);
			}

			$img = wp_get_attachment_image_src($imgid, 'full');

			if (!$img) {
				$out = '<div><p>' . __('Image not found', VGSE()->textname) . '</p></div>';
			} else {
				$out = '<div><img src="' . $img[0] . '" width="425px" /></div>';
			}
			wp_send_json_success(array('message' => $out));
		}

		/*
		 * Get gallery preview html
		 */

		function get_gallery_preview() {
			if (!wp_verify_nonce($_REQUEST['nonce'], 'bep-nonce')) {
				wp_send_json_error(array('message' => __('You dont have enough permissions to view this page.', VGSE()->textname)));
			}
			$_REQUEST = VGSE()->helpers->clean_data($_REQUEST);
			$post_id = (int) $_REQUEST['id'];
			$key = $_REQUEST['key'];

			$imgids = $_REQUEST['localValue'];
			$post_type = $_REQUEST['post_type'];

			if (empty($imgids)) {
				$imgids = get_post_meta($post_id, $key, true);
			}

			$imgs = explode(',', $imgids);

			$out = '<ul class="be-gallery">';
			foreach ($imgs as $img) {
				$image = wp_get_attachment_image_src($img, 'full');
				$out .= '<li><img src="' . $image[0] . '" width="225px" /></li>';
			}
			$out .= '</ul>';
			wp_send_json_success(array('message' => $out));
		}

		/**
		 * Save images cells, multiple images
		 */
		function set_featured_gallery() {
			if (!wp_verify_nonce($_REQUEST['nonce'], 'bep-nonce')) {
				wp_send_json_error(array('message' => __('You dont have enough permissions to view this page.', VGSE()->textname)));
			}
			$data = VGSE()->helpers->clean_data($_REQUEST);

			$result = VGSE()->helpers->save_images(array(
				'post_id' => $data['post_id'], // int
				'id' => $data['ids'], // str
				'key' => $data['key'], // str
			));
			if (!$result) {
				wp_send_json_error(array('message' => __('The image could not be saved. Try again later.', VGSE()->textname)));
			}

			wp_send_json_success(array('message' => __('Image saved.', VGSE()->textname)));
		}

		/*
		 * Get tinymce editor content
		 */

		function get_wp_post_single_data() {
			if (!wp_verify_nonce($_REQUEST['nonce'], 'bep-nonce')) {
				wp_send_json_error(array('message' => __('You dont have enough permissions to view this page.', VGSE()->textname)));
			}
			$_REQUEST = VGSE()->helpers->clean_data($_REQUEST);

			$post_id = (int) $_REQUEST['pid'];
			$key = $_REQUEST['key'];
			$type = $_REQUEST['type'];
			$raw = (!empty($_REQUEST['raw']) ) ? $_REQUEST['raw'] : false;
			$post_type = $_REQUEST['post_type'];

			$column_settings = VGSE()->helpers->get_column_settings($key, $post_type);

			$content = '';
			if ($type === 'post_data') {
				$content = VGSE()->data_helpers->get_post_data($key, $post_id);
			} elseif ($type === 'meta_data' || $type === 'post_meta') {
				$content = get_post_meta($post_id, $key, true);
			}
			if ($raw) {
				$out = $content;
			} else {
				$out = html_entity_decode(htmlspecialchars_decode($content));

				if (!empty($column_settings['type']) && $column_settings['type'] === 'boton_tiny') {
					$out = wpautop($out);
				}
			}


			wp_send_json_success(array('message' => $out));
		}

		/**
		 * Search taxonomy term
		 * @global obj $wpdb
		 */
		function search_taxonomy_terms() {
			$post_type = (!empty($_REQUEST['post_type']) ) ? sanitize_text_field($_REQUEST['post_type']) : false;
			$search = (!empty($_REQUEST['search']) ) ? sanitize_text_field($_REQUEST['search']) : false;

			if (empty($post_type) || empty($search)) {
				wp_send_json_error(array('message' => __('Missing parameters.', VGSE()->textname)));
			}

			$taxonomies = VGSE()->helpers->get_post_type_taxonomies_single_data($post_type, 'name');

			if (empty($taxonomies)) {
				wp_send_json_error(array('message' => __('No taxonomies found.', VGSE()->textname)));
			}
			global $wpdb;

			$sql = "SELECT term.slug id,term.name text,tax.taxonomy taxonomy FROM $wpdb->term_taxonomy as tax JOIN $wpdb->terms as term ON term.term_id = tax.term_id WHERE tax.taxonomy IN ('" . implode("','", $taxonomies) . "') AND term.name LIKE '%" . esc_sql($search) . "%' ";
			$results = $wpdb->get_results($sql, ARRAY_A);

			if (!$results || is_wp_error($results)) {
				$results = array();
			}

			$output_format = ( isset($_REQUEST['output_format'])) ? $_REQUEST['output_format'] : '';
			if (empty($output_format)) {
				$output_format = '%taxonomy%--%slug%';
			} else {
				$output_format = sanitize_text_field($output_format);
			}
			$taxonomies_labels = array();
			$out = array();
			foreach ($results as $result) {

				if (!isset($taxonomies_labels[$result['taxonomy']])) {
					$tmp_tax = get_taxonomy($result['taxonomy']);
					$taxonomies_labels[$result['taxonomy']] = $tmp_tax->label;
				}

				$output_key = strtr($output_format, array(
					'%name%' => $result['text'],
					'%taxonomy%' => $result['taxonomy'],
					'%slug%' => $result['id'],
				));
				$out[] = array(
					'id' => $output_key,
					'text' => $result['text'] . ' ( ' . $taxonomies_labels[$result['taxonomy']] . ' )',
				);
			}
			wp_send_json_success(array('data' => $out));
		}

		/**
		 * Enable the spreadsheet editor on some post types
		 */
		function save_post_types_setting() {
			$data = VGSE()->helpers->clean_data($_REQUEST);

			$post_types = $data['post_types'];
			$nonce = $data['nonce'];
			$append = $data['append'];

			if (!empty($post_types) && wp_verify_nonce($nonce, 'bep-nonce')) {
				$settings = get_option(VGSE()->options_key, array());

				if ($append === 'yes') {
					$new_post_types = array_unique(array_merge($settings['be_post_types'], $post_types));
				} else {
					$new_post_types = $post_types;
				}
				$settings['be_post_types'] = $new_post_types;


				update_option(VGSE()->options_key, $settings);

				do_action('vg_sheet_editor/quick_setup/post_types_saved/after', $new_post_types);

				wp_send_json_success();
			} else {
				wp_send_json_error();
			}
		}

		function save_gutenberg_content() {			
			$_REQUEST['content'] = $_REQUEST['data'];
			$_REQUEST['post_id'] = $_REQUEST['postId'];
			$_REQUEST['post_type'] = $_REQUEST['postType'];
			$_REQUEST['type'] = 'post_data';
			$_REQUEST['key'] = 'post_content';
			$this->save_single_post_data();
		}

		/**
		 * Disable quick setup screen. It will show "quick usage screen" instead.
		 */
		function disable_quick_setup() {
			$data = VGSE()->helpers->clean_data($_REQUEST);

			$nonce = $data['nonce'];

			if (!wp_verify_nonce($nonce, 'bep-nonce')) {
				wp_send_json_error();
			}
			update_option('vgse_disable_quick_setup', true);

			wp_send_json_success();
		}

		function init() {

// Ajax actions
			add_action('wp_ajax_vgse_load_data', array($this, 'load_rows'));
			add_action('wp_ajax_vgse_save_gutenberg_content', array($this, 'save_gutenberg_content'));
			add_action('wp_ajax_vgse_save_data', array($this, 'save_rows'));
			add_action('wp_ajax_vgse_find_post_by_name', array($this, 'find_post_by_name'));
			add_action('wp_ajax_vgse_save_individual_post', array($this, 'save_single_post_data'));
			add_action('wp_ajax_vgse_insert_individual_post', array($this, 'insert_individual_post'));
			add_action('wp_ajax_vgse_set_featured_image', array($this, 'set_featured_image'));
			add_action('wp_ajax_vgse_get_image_preview', array($this, 'get_image_preview'));
			add_action('wp_ajax_vgse_set_featured_gallery', array($this, 'set_featured_gallery'));
			add_action('wp_ajax_vgse_get_gallery_preview', array($this, 'get_gallery_preview'));
			add_action('wp_ajax_vgse_get_wp_post_single_data', array($this, 'get_wp_post_single_data'));
			add_action('wp_ajax_vgse_search_taxonomy_terms', array($this, 'search_taxonomy_terms'));
			add_action('wp_ajax_vgse_save_post_types_setting', array($this, 'save_post_types_setting'));
			add_action('wp_ajax_vgse_disable_quick_setup', array($this, 'disable_quick_setup'));
		}

		/**
		 * Creates or returns an instance of this class.
		 *
		 * @return  Foo A single instance of this class.
		 */
		static function get_instance() {
			if (null == WP_Sheet_Editor_Ajax::$instance) {
				WP_Sheet_Editor_Ajax::$instance = new WP_Sheet_Editor_Ajax();
				WP_Sheet_Editor_Ajax::$instance->init();
			}
			return WP_Sheet_Editor_Ajax::$instance;
		}

		function __set($name, $value) {
			$this->$name = $value;
		}

		function __get($name) {
			return $this->$name;
		}

	}

}

if (!function_exists('WP_Sheet_Editor_Ajax_Obj')) {

	function WP_Sheet_Editor_Ajax_Obj() {
		return WP_Sheet_Editor_Ajax::get_instance();
	}

}