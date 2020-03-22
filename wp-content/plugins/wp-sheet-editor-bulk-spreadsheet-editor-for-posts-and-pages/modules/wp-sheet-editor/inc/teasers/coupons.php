<?php
if (!class_exists('WP_Sheet_Editor_Coupons_Teaser')) {

	/**
	 * Display coupons item in the toolbar to tease users of the free 
	 * version into purchasing the premium plugin.
	 */
	class WP_Sheet_Editor_Coupons_Teaser {

		static private $instance = false;

		private function __construct() {
			
		}

		function init() {

			if (class_exists('WP_Sheet_Editor_WC_Coupons')) {
				return;
			}
			add_action('admin_notices', array($this, 'render_notice'));
		}

		function render_notice() {
			if (empty($_GET['post_type']) || $_GET['post_type'] !== 'shop_coupon') {
				return;
			}
			?>
			<div class="notice notice-success is-dismissible">
				<p><?php printf(__('Edit Coupons in a Spreadsheet.<br/>You can edit coupon codes, coupon amounts, coupon status, restrictions, and more. Make advanced searches. <a href="%s" class="button button-primary" target="_blank">Download Plugin</a>', VGSE()->textname), 'https://wpsheeteditor.com/extensions/woocommerce-coupons-spreadsheet/'); ?></p>
			</div>
			<?php
		}

		/**
		 * Creates or returns an instance of this class.
		 *
		 * 
		 */
		static function get_instance() {
			if (null == WP_Sheet_Editor_Coupons_Teaser::$instance) {
				WP_Sheet_Editor_Coupons_Teaser::$instance = new WP_Sheet_Editor_Coupons_Teaser();
				WP_Sheet_Editor_Coupons_Teaser::$instance->init();
			}
			return WP_Sheet_Editor_Coupons_Teaser::$instance;
		}

		function __set($name, $value) {
			$this->$name = $value;
		}

		function __get($name) {
			return $this->$name;
		}

	}

}


add_action('vg_sheet_editor/initialized', 'vgse_init_coupons_teaser');

if (!function_exists('vgse_init_coupons_teaser')) {

	function vgse_init_coupons_teaser() {
		WP_Sheet_Editor_Coupons_Teaser::get_instance();
	}

}
