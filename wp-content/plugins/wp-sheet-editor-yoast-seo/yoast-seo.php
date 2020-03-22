<?php

/* start-wp-plugin-header */
/*
  Plugin Name: WP Sheet Editor - YOAST SEO
  Description: Use the spreadsheet editor to edit the SEO title, description, and keyword of your posts.
  Version: 1.1.1
  Author: VegaCorp
  Author URI: http://vegacorp.me
  Plugin URI: http://wpsheeteditor.com
 */
/* end-wp-plugin-header */
if (!class_exists('WP_Sheet_Editor_YOAST_SEO')) {

	class WP_Sheet_Editor_YOAST_SEO {

		static private $instance = false;

		private function __construct() {
			
		}

		function notify_wrong_core_version() {
			$plugin_data = get_plugin_data(__FILE__, false, false);
			?>
			<div class="notice notice-error">
				<p><?php _e('Please update the WP Sheet Editor plugin to the version 2.5.2 or higher. The plugin "' . $plugin_data['Name'] . '" requires that version.', VGSE()->textname); ?></p>
			</div>
			<?php
		}

		function init() {

			if (version_compare(VGSE()->version, '2.5.2') < 0) {
				$this->notify_wrong_core_version();
				return;
			}
			// exit if yoast seo plugin is not active
			if (!$this->is_yoast_seo_plugin_active()) {
				return;
			}

			add_action('vg_sheet_editor/editor/before_init', array($this, 'register_columns'));
			add_action('vg_sheet_editor/load_rows/output', array($this, 'filter_seo_score_cell_html'), 10, 3);
		}

		/**
		 * Filter html of SEO score cells to display the score icon.
		 * @param array $data
		 * @param array $qry
		 * @param array $spreadsheet_columns
		 * @return array
		 */
		function filter_seo_score_cell_html($data, $qry, $spreadsheet_columns) {

			if (!isset($spreadsheet_columns['_yoast_wpseo_linkdex'])) {
				return $data;
			}
			foreach ($data as $post_index => $post_row) {

				$noindex = (int) get_post_meta($post_row['ID'], '_yoast_wpseo_meta-robots-noindex', true);

				if ($noindex) {
					$score = 'noindex';
				} else {
					$score = WPSEO_Utils::translate_score($post_row['_yoast_wpseo_linkdex']);
				}
				$data[$post_index]['_yoast_wpseo_linkdex'] = '<div class="' . esc_attr('wpseo-score-icon ' . $score) . '"></div>';
			}
			return $data;
		}

		/**
		 * Is yoast seo plugin active
		 * @return boolean
		 */
		function is_yoast_seo_plugin_active() {
			if (in_array('wordpress-seo/wp-seo.php', apply_filters('active_plugins', get_option('active_plugins')))) {
				return true;
			} else {
				return false;
			}
		}

		/**
		 * Test whether the yoast metabox is hidden either by choice of the admin or because
		 * the post type is not a public post type
		 *
		 * @param  string $post_type (optional) The post type to test, defaults to the current post post_type
		 *
		 * @return  bool        Whether or not the meta box (and associated columns etc) should be hidden
		 */
		function is_yoast_metabox_hidden($post_type = null) {
			if (!isset($post_type)) {
				if (isset($GLOBALS['post']) && ( is_object($GLOBALS['post']) && isset($GLOBALS['post']->post_type) )) {
					$post_type = $GLOBALS['post']->post_type;
				} elseif (isset($_GET['post_type']) && $_GET['post_type'] !== '') {
					$post_type = sanitize_text_field($_GET['post_type']);
				}
			}
			if (isset($post_type)) {
				// Don't make static as post_types may still be added during the run
				$cpts = VGSE()->helpers->get_all_post_types(array('public' => true), 'names');
				$options = get_option('wpseo_titles');
				return ( ( isset($options['hideeditbox-' . $post_type]) && $options['hideeditbox-' . $post_type] === true ) || in_array($post_type, $cpts) === false );
			}
			return false;
		}

		/**
		 * Creates or returns an instance of this class.
		 */
		static function get_instance() {
			if (null == WP_Sheet_Editor_YOAST_SEO::$instance) {
				WP_Sheet_Editor_YOAST_SEO::$instance = new WP_Sheet_Editor_YOAST_SEO();
				WP_Sheet_Editor_YOAST_SEO::$instance->init();
			}
			return WP_Sheet_Editor_YOAST_SEO::$instance;
		}

		function __set($name, $value) {
			$this->$name = $value;
		}

		function __get($name) {
			return $this->$name;
		}

		/**
		 * Register spreadsheet columns
		 */
		function register_columns($editor) {
			if ($editor->provider->key === 'user') {
				return;
			}
			if ($editor->provider->key === 'post') {
				$post_types = $editor->args['enabled_post_types'];
			}

			foreach ($post_types as $post_type) {

				if ($this->is_yoast_metabox_hidden($post_type)) {
					continue;
				}
				$editor->args['columns']->register_item('_yoast_wpseo_title', $post_type, array(
					'data_type' => 'meta_data',
					'unformatted' => array('data' => '_yoast_wpseo_title'),
					'column_width' => 300,
					'title' => __('SEO Title', VGSE()->textname),
					'type' => '',
					'supports_formulas' => true,
					'formatted' => array('data' => '_yoast_wpseo_title', 'renderer' => 'html'),
					'allow_to_hide' => true,
					'allow_to_rename' => true,
				));
				$editor->args['columns']->register_item('_yoast_wpseo_metadesc', $post_type, array(
					'data_type' => 'meta_data',
					'unformatted' => array('data' => '_yoast_wpseo_metadesc'),
					'column_width' => 300,
					'title' => __('SEO Description', VGSE()->textname),
					'type' => '',
					'supports_formulas' => true,
					'formatted' => array('data' => '_yoast_wpseo_metadesc', 'renderer' => 'html'),
					'allow_to_hide' => true,
					'allow_to_rename' => true,
				));
				$editor->args['columns']->register_item('_yoast_wpseo_focuskw', $post_type, array(
					'data_type' => 'meta_data',
					'unformatted' => array('data' => '_yoast_wpseo_focuskw'),
					'column_width' => 120,
					'title' => __('SEO Keyword', VGSE()->textname),
					'type' => '',
					'supports_formulas' => true,
					'formatted' => array('data' => '_yoast_wpseo_focuskw', 'renderer' => 'html'),
					'allow_to_hide' => true,
					'allow_to_rename' => true,
				));
				$editor->args['columns']->register_item('_yoast_wpseo_meta-robots-noindex', $post_type, array(
					'data_type' => 'meta_data',
					'unformatted' => array('data' => '_yoast_wpseo_meta-robots-noindex'),
					'column_width' => 120,
					'title' => __('SEO No Index', VGSE()->textname),
					'type' => '',
					'supports_formulas' => true,
					'formatted' => array('data' => '_yoast_wpseo_meta-robots-noindex', 'type' => 'checkbox', 'checkedTemplate' => '1', 'uncheckedTemplate' => null, 'className' => 'htCenter htMiddle'),
					'allow_to_hide' => true,
					'allow_to_rename' => true,
				));
				$editor->args['columns']->register_item('_yoast_wpseo_linkdex', $post_type, array(
					'data_type' => 'meta_data',
					'unformatted' => array('data' => '_yoast_wpseo_linkdex', 'readOnly' => true, 'renderer' => 'html'),
					'column_width' => 50,
					'title' => __('SEO', VGSE()->textname),
					'type' => '',
					'supports_formulas' => false,
					'formatted' => array('data' => '_yoast_wpseo_linkdex', 'readOnly' => true, 'renderer' => 'html'),
					'allow_to_hide' => true,
					'allow_to_rename' => true,
					'allow_plain_text' => false,
				));
			}
		}

	}

}

if (!function_exists('WP_Sheet_Editor_YOAST_SEO_Obj')) {

	function WP_Sheet_Editor_YOAST_SEO_Obj() {
		return WP_Sheet_Editor_YOAST_SEO::get_instance();
	}

}


add_action('vg_sheet_editor/initialized', 'WP_Sheet_Editor_YOAST_SEO_Obj');
