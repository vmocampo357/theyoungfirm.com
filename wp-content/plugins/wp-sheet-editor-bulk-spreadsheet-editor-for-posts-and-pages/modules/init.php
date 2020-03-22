<?php

if (!class_exists('WP_Sheet_Editor_CORE_Modules_Init')) {

	class WP_Sheet_Editor_CORE_Modules_Init {

		var $product_directory = null;

		function __construct($product_directory, $auto_init = true) {
			$this->product_directory = $product_directory;

			if ($auto_init) {
				$this->init();
			}
		}

		/**
		 * Get all modules in the folder
		 * @return array
		 */
		function get_modules_list() {
			$directories = glob($this->product_directory . '/modules/*', GLOB_ONLYDIR);

			if (!empty($directories)) {
				$directories = array_map('basename', $directories);
			}
			$parent_plugin_slug = str_replace(array('-premium'), '', basename(dirname(__DIR__)) );
			return apply_filters('vg_sheet_editor/modules/' . $parent_plugin_slug . '/list', $directories);
		}

		function init() {
			
			$modules = $this->get_modules_list();
			if (empty($modules)) {
				return;
			}

			// Load all modules
			foreach ($modules as $module) {
				$paths = array($this->product_directory . "/modules/$module/$module.php");
				if ($module === 'wp-sheet-editor') {
					$paths[] = $this->product_directory . "/modules/$module/dev/$module.php";
				}

				foreach ($paths as $path) {
					if (file_exists($path)) {
						require_once $path;
					}
				}
			}

			$plugin_inc_files = glob(untrailingslashit($this->product_directory) . '/inc/*.php');
			$inc_files = array_merge(glob(untrailingslashit(__DIR__) . '/*.php'), $plugin_inc_files);
			foreach ($inc_files as $inc_file) {
				if (!is_file($inc_file)) {
					continue;
				}

				require_once $inc_file;
			}
		}

		/**
		 * Creates or returns an instance of this class.
		 *
		 * @return  Foo A single instance of this class.
		 */
		static function get_instance() {
			if (null == WP_Sheet_Editor_CORE_Modules_Init::$instance) {
				WP_Sheet_Editor_CORE_Modules_Init::$instance = new WP_Sheet_Editor_CORE_Modules_Init();
				WP_Sheet_Editor_CORE_Modules_Init::$instance->init();
			}
			return WP_Sheet_Editor_CORE_Modules_Init::$instance;
		}

		function __set($name, $value) {
			$this->$name = $value;
		}

		function __get($name) {
			return $this->$name;
		}

	}

}