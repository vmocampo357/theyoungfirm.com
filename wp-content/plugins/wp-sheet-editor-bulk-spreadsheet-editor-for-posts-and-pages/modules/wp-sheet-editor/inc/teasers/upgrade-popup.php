<?php
if (!class_exists('WP_Sheet_Editor_Popup_Teaser')) {

	/**
	 * Display Popup to tease users of the free 
	 * version into purchasing the premium plugin.
	 */
	class WP_Sheet_Editor_Popup_Teaser {

		static private $instance = false;

		private function __construct() {
			
		}

		function init() {
			if (!is_admin()) {
				return;
			}

			add_action('vg_sheet_editor/editor_page/before_toolbars', array($this, 'render_teaser'));
		}

		function render_teaser($post_type) {
			if (defined('VGSE_ANY_PREMIUM_ADDON') && VGSE_ANY_PREMIUM_ADDON) {
				return;
			}
			?>
			<div class="teaser"><b><?php
					$message = 'Do you want to: Edit WooCommerce products, Custom Post Types, Custom Fields, Update hundreds of posts using Formulas, or use Advanced Search?';
					if ($post_type === 'user') {
						$message = 'Do you want to: View/Edit WooCommerce Customers in the Spreadsheet, Search Users, Edit Custom Fields, Update in bulk using Formulas, or Edit Posts?';
					} elseif ($post_type === apply_filters('vg_sheet_editor/woocommerce/product_post_type_key', 'product')) {
						$message = 'Edit Variable Products, Variations, Attributes, Download Files, Make Advanced searches, Update hundreds of rows with Formulas';
					}

					_e($message, VGSE()->textname);
					?> </b> . <a href="#" class="button button-primary button-primary" data-remodal-target="modal-extensions"><?php _e('View Extensions', VGSE()->textname); ?></a></div>
					<?php
					$flag_key = 'vgse_hide_extensions_popup';
					if (!get_option($flag_key)) {
						update_option($flag_key, 1);
						?>

				<script>
					setTimeout(function () {
						jQuery('[data-remodal-target="modal-extensions"]').first().trigger('click');
					}, 180000);
				</script>
				<?php
			}
		}

		/**
		 * Creates or returns an instance of this class.
		 *
		 * 
		 */
		static function get_instance() {
			if (null == WP_Sheet_Editor_Popup_Teaser::$instance) {
				WP_Sheet_Editor_Popup_Teaser::$instance = new WP_Sheet_Editor_Popup_Teaser();
				WP_Sheet_Editor_Popup_Teaser::$instance->init();
			}
			return WP_Sheet_Editor_Popup_Teaser::$instance;
		}

		function __set($name, $value) {
			$this->$name = $value;
		}

		function __get($name) {
			return $this->$name;
		}

	}

}


add_action('vg_sheet_editor/initialized', 'vgse_init_Popup_teaser');

if (!function_exists('vgse_init_Popup_teaser')) {

	function vgse_init_Popup_teaser() {
		WP_Sheet_Editor_Popup_Teaser::get_instance();
	}

}
