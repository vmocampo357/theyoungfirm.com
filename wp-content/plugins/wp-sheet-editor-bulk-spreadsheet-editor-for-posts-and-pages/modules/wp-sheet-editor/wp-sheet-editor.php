<?php

if (!defined('VGSE_DEBUG')) {
	define('VGSE_DEBUG', false);
}
if (!defined('VGSE_DIR')) {
	define('VGSE_DIR', __DIR__);
}
if (!defined('VGSE_KEY')) {
	define('VGSE_KEY', 'vg_sheet_editor');
}
if (!defined('VGSE_MAIN_FILE')) {
	define('VGSE_MAIN_FILE', __FILE__);
}
if (!defined('VGSE_CORE_MAIN_FILE')) {
	define('VGSE_CORE_MAIN_FILE', __FILE__);
}
require_once VGSE_DIR . '/inc/api/helpers.php';
$vgse_helpers = WP_Sheet_Editor_Helpers::get_instance();

$api = $vgse_helpers->get_files_list(VGSE_DIR . '/inc/api');
$teasers = $vgse_helpers->get_files_list(VGSE_DIR . '/inc/teasers');
$inc = $vgse_helpers->get_files_list(VGSE_DIR . '/inc');
$providers = $vgse_helpers->get_files_list(VGSE_DIR . '/inc/providers');
$integrations = $vgse_helpers->get_files_list(VGSE_DIR . '/inc/integrations');

$files = array_merge($api, $teasers, $inc, $providers, $integrations);
foreach ($files as $file) {
	require_once $file;
}

if (!class_exists('WP_Sheet_Editor')) {

	class WP_Sheet_Editor {

		private $post_type;
		var $version = '2.8.2';
		var $textname = 'vg_sheet_editor';
		var $options_key = 'vg_sheet_editor';
		var $plugin_url = null;
		var $plugin_dir = null;
		var $options = null;
		var $texts = null;
		var $data_helpers = null;
		var $helpers = null;
		var $registered_columns = null;
		var $toolbar = null;
		var $columns = null;
		var $support_links = array();
		var $extensions = array();
		var $bundles = array();
		var $buy_link = null;
		static private $instance = null;
		var $current_provider = null;
		var $editors = array();
		var $user_path = array();

		/**
		 * Creates or returns an instance of this class.
		 *
		 * 
		 */
		static function get_instance() {
			if (null == WP_Sheet_Editor::$instance) {
				WP_Sheet_Editor::$instance = new WP_Sheet_Editor();
			}
			return WP_Sheet_Editor::$instance;
		}

		function __set($name, $value) {
			$this->$name = $value;
		}

		function __get($name) {
			return $this->$name;
		}

		private function __construct() {
			
		}

		/**
		 * Plugin init
		 */
		function init() {
			do_action('vg_sheet_editor/before_initialized');

			// Exit if frontend and it´s not allowed
			if (!is_admin() && !apply_filters('vg_sheet_editor/allowed_on_frontend', false)) {
				return;
			}

			// Init internal APIs
			$this->data_helpers = WP_Sheet_Editor_Data::get_instance();
			$this->helpers = WP_Sheet_Editor_Helpers::get_instance();

			$this->buy_link = ( function_exists('vgse_freemius') ) ? vgse_freemius()->checkout_url() : 'https://wpsheeteditor.com';

			$options = get_option($this->options_key);
			$default_options = array(
					'last_tab' => null,
					'be_post_types' => array(
						'post'
					),
					'be_posts_per_page' => 10,
					'be_load_items_on_scroll' => 1,
					'be_fix_first_columns' => 1,
					'be_posts_per_page_save' => 4,
					'be_timeout_between_batches' => 6,
					'be_disable_post_actions' => 0,
					'be_allow_edit_slugs' => 0,
				);
			if (empty($options)) {
				$options = $default_options;
				update_option($this->options_key, $options);
			} else {
				$options = wp_parse_args($options, $default_options);
			}

			$this->options = $options;

			do_action('vg_sheet_editor/initialized');

			$post_type = $options['be_post_types'];
			$this->post_type = $post_type;

			$this->plugin_url = plugins_url('/', __FILE__);
			$this->plugin_dir = __DIR__;

			$free_plugin_uri = 'plugin-install.php?tab=search&type=term&s=';
			$free_plugin_base_url = ( is_multisite() ) ? network_admin_url($free_plugin_uri) : admin_url($free_plugin_uri);
			$this->extensions = apply_filters('vg_sheet_editor/extensions', array(
				'users_lite' => array(
					'title' => __('Edit User Profiles in Spreadsheet - Basic', VGSE()->textname),
					'icon' => 'fa-users', // fa-search
					'image' => '', // fa-search
					'description' => __('<p >Edit WordPress users in spreadsheet, edit only basic profiles and make basic searches.</p>', VGSE()->textname), // incluir <p>
					'bundle' => array('users'),
					'class_function_name' => 'WP_Sheet_Editor_Users',
				),
				'users' => array(
					'title' => __('Edit User Profiles in Spreadsheet - FULL', VGSE()->textname),
					'icon' => 'fa-users', // fa-search
					'image' => '', // fa-search
					'description' => __('<p >Edit WordPress users in spreadsheet. Edit FULL user profiles, including custom fields. Add new columns to the spreadsheet, Good for ecommerce stores, membership sites, events directories, business directories, user directories</p>', VGSE()->textname), // incluir <p>
					'bundle' => array('users'),
					'class_function_name' => 'VGSE_USERS_IS_PREMIUM',
				),
				'wc_customers' => array(
					'title' => __('WooCommerce Customers Spreadsheet', VGSE()->textname),
					'icon' => 'fa-users', // fa-search
					'image' => '', // fa-search
					'description' => __('<p >View all your Customers in a Spreadsheet. View full profile, Edit Profiles Quickly, View Billing and Shipping information, Make advanced customer searches, Export Customers to Excel or Google Sheets, Import Customers from External Applications</p>', VGSE()->textname), // incluir <p>
					'bundle' => array('users'),
					'class_function_name' => 'VGSE_USERS_IS_PREMIUM',
				),
				'events' => array(
					'title' => __('Events Spreadsheet', VGSE()->textname),
					'icon' => 'fa-ticket', // fa-search
					'image' => '', // fa-search
					'description' => __('<p >View all the events in a spreadsheet, create events in bulk, edit hundreds of events at once using formulas, Advanced searches using multiple event fields, etc.</p>', VGSE()->textname), // incluir <p>
					'inactive_action_url' => 'https://wpsheeteditor.com/go/events-addon',
					'inactive_action_label' => __('Buy', VGSE()->textname),
					'freemius_function' => 'wpsee_fs',
					'bundle' => false,
					'class_function_name' => 'VGSE_EVENTS_IS_PREMIUM',
				),
				'edd' => array(
					'title' => __('Easy Digital Downloads Spreadsheet', VGSE()->textname),
					'icon' => 'fa-download', // fa-search
					'image' => '', // fa-search
					'description' => __('<p >View all the EDD products in a spreadsheet, create downloads and files in bulk, edit hundreds of products at once using formulas, Advanced searches using multiple fields, etc.</p>', VGSE()->textname), // incluir <p>
					'inactive_action_url' => 'https://wpsheeteditor.com/go/edd-downloads-addon',
					'inactive_action_label' => __('Buy', VGSE()->textname),
					'freemius_function' => 'wpseedd_fs',
					'bundle' => false,
					'class_function_name' => 'VGSE_EDD_DOWNLOADS_IS_PREMIUM',
				),
				'frontend_editor' => array(
					'title' => __('Custom Spreadsheets for Your Users or Employees', VGSE()->textname),
					'icon' => 'fa-rocket', // fa-search
					'image' => '', // fa-search
					'description' => __('<p >Create new spreadsheets with custom columns and Share the Spreadsheets with your Users or Employees. Useful for allowing Post Submissions on the Frontend, Events directories, Web Apps, Custom Dashboards, etc.</p>', VGSE()->textname), // incluir <p>
					'inactive_action_url' => 'https://wpsheeteditor.com/go/frontend-addon',
					'inactive_action_label' => __('Buy', VGSE()->textname),
					'freemius_function' => 'bepof_fs',
					'bundle' => false,
					'class_function_name' => 'VGSE_FRONTEND_IS_PREMIUM',
				),
				'woocommerce_coupons' => array(
					'title' => __('WooCommerce Coupons Spreadsheet', VGSE()->textname),
					'icon' => 'fa-rocket', // fa-search
					'image' => '', // fa-search
					'description' => __('<p >View WooCommerce Coupons in a spreadsheet. Edit all coupon fields, Advanced Search by any field , Auto generate hundreds of coupons, Update hundreds of coupons at once, and more.</p>', VGSE()->textname), // incluir <p>
					'inactive_action_url' => 'https://wpsheeteditor.com/go/wc-coupons-addon',
					'inactive_action_label' => __('Buy', VGSE()->textname),
					'freemius_function' => 'wpsewcc_fs',
					'bundle' => false,
					'class_function_name' => 'VGSE_WC_COUPONS_IS_PREMIUM',
				),
				'woocommerce' => array(
					'title' => __('WooCommerce - Products Integration', VGSE()->textname),
					'icon' => 'fa-shopping-cart', // fa-search
					'image' => '', // fa-search
					'description' => __('<p >Edit WooCommerce products in the spreadsheet. It supports all kinds of products, including Variable Products, Downloadable Products, External Products, Simple Products. You can edit all product fields in the spreadsheet, including attributes, images, etc.</p>', VGSE()->textname), // incluir <p>
//		//'status' => __('Included in "Pro Bundle".', VGSE()->textname), // vacío  o installed
					'bundle' => array('custom_post_types'),
					'class_function_name' => 'WP_Sheet_Editor_WooCommerce',
				),
				'custom_post_types' => array(
					'title' => __('Custom post types', VGSE()->textname),
					'icon' => 'fa-file', // fa-search
					'image' => '', // fa-search
					'description' => __('<p >Edit events, courses, projects, portfolios, and all custom post types.</p>', VGSE()->textname), // incluir <p>
//		//'status' => __('Included in "Pro Bundle".', VGSE()->textname), // vacío  o installed
					'bundle' => array('custom_post_types'),
					'class_function_name' => 'WP_Sheet_Editor_CPTs',
				),
				'columns_renaming' => array(
					'title' => __('Columns renaming', VGSE()->textname),
					'icon' => 'fa-exchange', // fa-search
					'image' => '', // fa-search
					'description' => __('<p >You can rename the columns of the spreadsheet.<br>Example. Instead of showing “Post author” on the spreadsheet, you can change it to “Uploaded by”.</p>', VGSE()->textname), // incluir <p>
					'inactive_action_label' => __('Install for Free', VGSE()->textname),
					'inactive_action_url' => $free_plugin_base_url . 'wp-sheet-editor-columns-renaming',
//		'status' => __('Free.', VGSE()->textname), // vacío  o installed
					'bundle' => false,
					'class_function_name' => 'WP_Sheet_Editor_Columns_Renaming',
				),
				'yoast' => array(
					'title' => __('YOAST SEO', VGSE()->textname),
					'icon' => 'fa-google', // fa-search
					'image' => '', // fa-search
					'description' => __('<p >Edit SEO title, description, keyword, and SEO score in spreadsheet</p>', VGSE()->textname), // incluir <p>
					'inactive_action_label' => __('Install for Free', VGSE()->textname),
					'inactive_action_url' => $free_plugin_base_url . 'wp-sheet-editor-yoast-seo',
//		'status' => __('Free.', VGSE()->textname), // vacío  o installed
					'bundle' => false,
					'class_function_name' => 'WP_Sheet_Editor_YOAST_SEO',
				),
				'advanced_filters' => array(
					'title' => __('Advanced Search', VGSE()->textname),
					'icon' => 'fa-search', // fa-search
					'image' => '', // fa-search
					'description' => __('<p >Find posts by keyword, taxonomies, author, date, status, or custom fields.</p><p>Search in multiple fields with advanced operators: =, !=, &lt;, &gt;, LIKE, NOT LIKE</p><p>Examples: Find products from category Audio with stock < 20, or products from category Apple without featured image, or products containing the keyword "Google" without image gallery.</p>', VGSE()->textname), // incluir <p>
					//'status' => __('Included in "Pro Bundle".', VGSE()->textname), // vacío  o installed
					'bundle' => array('users', 'custom_post_types'),
					'class_function_name' => 'WP_Sheet_Editor_Advanced_Filters',
				),
				'replace_formulas' => array(
					'title' => __('Formulas', VGSE()->textname),
					'icon' => 'fa fa-pencil-square', // fa-search
					'image' => '', // fa-search
					'description' => __('<p >Edit Hundreds of Posts at Once with just a few clicks. Search and replace, Replace urls and phrases, save values to fields in bulk, copy values between fields, merge fields, etc..</p><p>Examples: Copy regular price to sale price, update product attributes names, etc.</p>', VGSE()->textname), // incluir <p>
//		//'status' => __('Included in "Pro Bundle".', VGSE()->textname), // vacío  o installed
					'bundle' => array('users', 'custom_post_types'),
					'class_function_name' => 'WP_Sheet_Editor_Formulas',
				),
				'math_formulas' => array(
					'title' => __('Math Formulas', VGSE()->textname),
					'icon' => 'fa-hashtag', // fa-search
					'image' => '', // fa-search
					'description' => __('<p >Edit Hundreds of Posts at Once. Update numeric fields using advanced math formulas. Example, increase prices by 10% , manage inventory , etc. Run any math formula.</p><p>You can use multiple fields in the formula, for example, "Regular price x Inventory / Sales price</p>', VGSE()->textname), // incluir <p>
					//'status' => __('Included in "Pro Bundle".', VGSE()->textname), // vacío  o installed
					'bundle' => array('users', 'custom_post_types'),
					'class_function_name' => 'WP_Sheet_Editor_Formulas',
				),
				'acf' => array(
					'title' => __('Advanced Custom Fields', VGSE()->textname),
					'icon' => 'fa-files-o', // fa-search
					'image' => '', // fa-search
					'description' => __('<p >Advanced Custom Fields metaboxes appear in the Spreadsheet Automatically. So you can edit custom fields easily.</p>', VGSE()->textname), // incluir <p>
					//'status' => __('Included in "Pro Bundle".', VGSE()->textname), // vacío  o installed
					'bundle' => array('users', 'custom_post_types'),
					'class_function_name' => 'WP_Sheet_Editor_ACF',
				),
				'custom_columns' => array(
					'title' => __('Edit Custom Fields in Spreadsheet', VGSE()->textname),
					'icon' => 'fa-plus', // fa-search
					'image' => '', // fa-search
					'description' => __('<p >You can create columns for custom fields. <br>Edit page settings added by your theme, event details, products information, etc.</p>', VGSE()->textname), // incluir <p>
					//'status' => __('Included in "Pro Bundle".', VGSE()->textname), // vacío  o installed
					'bundle' => array('users', 'custom_post_types'),
					'class_function_name' => 'WP_Sheet_Editor_Custom_Columns',
				),
				'posts' => array(
					'title' => __('Edit Posts and Pages in Spreadsheet', VGSE()->textname),
					'icon' => 'fa-table', // fa-search
					'image' => '', // fa-search
					'description' => __('<p >Edit default post fields in spreadsheet.</p>', VGSE()->textname), // incluir <p>
//					'bundle' => array('custom_post_types'),
					'class_function_name' => 'WP_Sheet_Editor_Dist',
				),
				'wc_lite' => array(
					'title' => __('WooCommerce - BASIC integration', VGSE()->textname),
					'icon' => 'fa-table', // fa-search
					'image' => '', // fa-search
					'description' => __('<p>You can edit simple products only. Available columns: title, url, description, date, SKU, regular price, sale price, stock status, manage stock, stock quantity.</p><p>More columns and product types available as premium extension.</p>', VGSE()->textname), // incluir <p>
//					'bundle' => array('custom_post_types'),
					'class_function_name' => 'WP_Sheet_Editor_Dist',
				),
				'columns_visibility' => array(
					'title' => __('Columns visibility', VGSE()->textname),
					'icon' => 'fa-cog', // fa-search
					'image' => '', // fa-search
					'description' => __('<p >You can show, hide, and sort columns in the spreadsheet.</p>', VGSE()->textname), // incluir <p>
					'bundle' => false,
					'class_function_name' => 'WP_Sheet_Editor_Columns_Visibility',
				),
				'autofill' => array(
					'title' => __('Autofill cells', VGSE()->textname),
					'icon' => '', // fa-search
					'image' => '<img src="' . VGSE()->plugin_url . 'assets/imgs/drag-down-autofill-demo.gif" style="max-height: 65px;">', // fa-search
					'description' => __('<p >You can auto fill cells (copy) by dragging the cell corner into other cells, as you can do in excel.</p>', VGSE()->textname), // incluir <p>
					'button_label' => __('Install for Free', VGSE()->textname),
					'button_url' => $free_plugin_base_url . 'wp-sheet-editor-autofill',
					'bundle' => false,
					'class_function_name' => 'WP_Sheet_Editor_Autofill_Cells',
				),
				'basic_filters' => array(
					'title' => __('Basic search', VGSE()->textname),
					'icon' => 'fa-search', // fa-search
					'description' => __('<p >Search in the spreadsheet. Find posts by keyword, status, and author.</p>', VGSE()->textname), // incluir <p>
//		'button_label' => __('Install for Free', VGSE()->textname),
//		'button_url' => $free_plugin_base_url . 'wp-sheet-editor-autofill',
					'bundle' => false,
					'class_function_name' => 'WP_Sheet_Editor_Filters',
				),
				'columns_resizing' => array(
					'title' => __('Columns resizing', VGSE()->textname),
					'icon' => 'fa-arrows-h', // fa-search
					'description' => __('<p >Resize columns in the spreadsheet and save it for future sessions.</p>', VGSE()->textname), // incluir <p>
					'bundle' => false,
					'class_function_name' => 'VGSE_Columns_Resizing',
				),
				'post_templates' => array(
					'title' => __('Post Templates', VGSE()->textname),
					'icon' => 'fa-copy', // fa-search
					'description' => __('<p >Create one post/product as template, and all the new posts/products created in the spreadsheet will be copies of the template and all the fields will be filled automatically.</p><p>Example. Create 100 products with the same tags, dimensions, attributes, and variations. And only change a couple of fields.</p>', VGSE()->textname), // incluir <p>
					'inactive_action_label' => __('Install for Free', VGSE()->textname),
					'inactive_action_url' => $free_plugin_base_url . 'wp-sheet-editor-post-templates',
					'bundle' => false,
					'class_function_name' => 'WP_Sheet_Editor_Post_Templates',
				),
			));

			$this->bundles = apply_filters('vg_sheet_editor/extensions/bundles', array(
				'custom_post_types' => array(
					'name' => 'Everything you need for All Posts Types and Products',
					'old_price' => '39.99',
					'price' => '19.99',
					'percentage_off' => 50,
					'coupon' => null,
					'extensions' => array(),
					'inactive_action_url' => 'https://wpsheeteditor.com',
					'inactive_action_label' => __('Buy bundle', VGSE()->textname),
					'freemius_function' => 'vgse_freemius',
				),
				'users' => array(
					'name' => 'Everything you need for Users and Customers',
					'old_price' => '39.99',
					'price' => '19.99',
					'percentage_off' => 50,
					'coupon' => null,
					'extensions' => array(),
					'inactive_action_url' => 'https://wpsheeteditor.com/go/users-addon',
					'inactive_action_label' => __('Buy bundle', VGSE()->textname),
					'freemius_function' => 'beupis_fs',
				),
			));

			// Check if the extension is active
			foreach ($this->bundles as $index => $bundle) {
				$freemius = function_exists($bundle['freemius_function']) ? $bundle['freemius_function']() : null;
				if ($freemius) {
					$this->bundles[$index]['inactive_action_url'] = $freemius->checkout_url();
				}
			}
			foreach ($this->extensions as $index => $extension) {
				$this->extensions[$index]['is_active'] = !empty($extension['class_function_name']) && ( class_exists($extension['class_function_name']) || function_exists($extension['class_function_name']) || defined($extension['class_function_name']) );


				$this->extensions[$index]['has_paid_offering'] = !empty($extension['bundle']) || !empty($extension['freemius_function']);

				if (!empty($extension['bundle'])) {
					$bundle = $this->bundles[current($extension['bundle'])];
					$this->extensions[$index]['active_action_url'] = (isset($bundle['active_action_url'])) ? $bundle['active_action_url'] : '';
					$this->extensions[$index]['inactive_action_label'] = (isset($bundle['inactive_action_label'])) ? $bundle['inactive_action_label'] : '';
					$this->extensions[$index]['active_action_label'] = (isset($bundle['active_action_label'])) ? $bundle['active_action_label'] : '';
					$this->extensions[$index]['freemius_function'] = (isset($bundle['freemius_function'])) ? $bundle['freemius_function'] : '';

					$freemius = function_exists($bundle['freemius_function']) ? $bundle['freemius_function']() : null;
					if ($freemius) {
						$this->extensions[$index]['inactive_action_url'] = $freemius->checkout_url();
					}
				}
			}


			// Init wp hooks
			add_action('admin_menu', array($this, 'register_menu'));
			add_action('admin_enqueue_scripts', array($this, 'register_scripts'), 999);
			add_action('admin_footer', array($this, 'register_script_for_metabox_iframes'));
			add_action('admin_enqueue_scripts', array($this, 'register_styles'));
			add_action('admin_enqueue_scripts', array($this, 'enqueue_media_wp_media'));
			add_action('admin_init', array($this, 'redirect_to_whats_new_page'));
			add_action('wp_dashboard_setup', array($this, 'register_dashboard_widgets'));
			// After all extensions are loaded
			add_action('vg_sheet_editor/after_init', array($this, 'after_init'), 999);

			WP_Sheet_Editor_Ajax_Obj();

			do_action('vg_sheet_editor/after_init');
			add_action('admin_page_access_denied', array($this, 'catch_missing_reduxframework_error'));
		}

		function register_script_for_metabox_iframes() {
			?>
			<style>
				.vgca-only-admin-content body {
					background: transparent;
				}
				.vgca-only-admin-content #wpadminbar,
				.vgca-only-admin-content #adminmenumain,
				.vgca-only-admin-content #update-nag, 
				.vgca-only-admin-content .update-nag,
				.vgca-only-admin-content #wpfooter{
					display: none !important;
				}

				.vgca-only-admin-content .folded #wpcontent, 
				.vgca-only-admin-content .folded #wpfooter,
				.vgca-only-admin-content #wpcontent,
				.vgca-only-admin-content #wpfooter {
					margin-left: 0px !important;
					padding-left: 0px !important;
				}
				html.wp-toolbar.vgca-only-admin-content  {
					padding-top: 0px !important;
				}

				.vgca-only-admin-content .block-editor__container {
					min-height: 700px !important;
				}
				.vgca-only-admin-content button.components-button.editor-post-publish-panel__toggle,
				.vgca-only-admin-content .editor-post-publish-button,
				<?php if( ! empty($_GET['wpse_column']) && $_GET['wpse_column'] === 'post_content'){ ?>
				.vgca-only-admin-content .edit-post-layout__metaboxes, 
				<?php } ?>
				.vgca-only-admin-content .editor-post-title, 
				.vgca-only-admin-content .components-panel__header.edit-post-sidebar__panel-tabs li:first-child, 
				.vgca-only-admin-content div.fs-notice.updated, 
				.vgca-only-admin-content div.fs-notice.success, 
				.vgca-only-admin-content div.fs-notice.promotion {
					display: none !important;
				}
			</style>
			<script>

				function vgseGetGutenbergContent() {
					return wp.data.select("core/editor").getEditedPostContent();
				}
				function vgseSaveGutenbergContent() {
					wp.data.dispatch("core/editor").savePost();
				}
				function vgseCancelGutenbergEdit() {
					for(var i = 0; i < 1000; i++){
						wp.data.dispatch("core/editor").undo();
					}
				}
				function vgseInitMetaboxIframe() {

					if (window.parent.location.href !== window.location.href) {
						jQuery('html').addClass('vgca-only-admin-content');

						// If URL is not for wp-admin page, open outside the iframe
						jQuery(document).ready(function () {
							jQuery('body').on('click', 'a', function (e) {
								// If the link is in the post content, disable it to avoid 
								// navigating away from the editor
								if( jQuery(this).parents('.editor-styles-wrapper').length){
									e.preventDefault();
									return false; 
								}
								
								var url = jQuery(this).attr('href');

								if (typeof url === 'string' && url.indexOf('/wp-admin') < 0 && url.indexOf('http') > -1) {
									top.window.location.href = url;
									e.preventDefault();
									return false;
								}
							});
						});
						jQuery(window).load(function () {
							if (jQuery('.block-editor__container').length) {
								jQuery('.components-panel__header.edit-post-sidebar__panel-tabs li:last button').click();
							}
							parent.jQuery('.vgca-iframe-wrapper .vgca-loading-indicator').hide();

						});
					}
				}

				vgseInitMetaboxIframe();
			</script>
			<?php

		}

		function get_support_links($link_key = null, $field = 'all', $campaign = 'help') {
			$support_links = array(
				'tutorials' => array(
					'url' => 'https://wpsheeteditor.com/documentation/tutorials/?utm_source=product&utm_medium=' . VGSE()->helpers->get_plugin_mode() . '&utm_campaign=' . $campaign,
					'label' => __('Tutorials', VGSE()->textname),
				),
				'faq' => array(
					'url' => 'https://wpsheeteditor.com/documentation/faq/?utm_source=product&utm_medium=' . VGSE()->helpers->get_plugin_mode() . '&utm_campaign=' . $campaign,
					'label' => __('FAQ', VGSE()->textname),
				),
				'guides' => array(
					'url' => 'https://wpsheeteditor.com/blog/?utm_source=product&utm_medium=' . VGSE()->helpers->get_plugin_mode() . '&utm_campaign=' . $campaign,
					'label' => __('Articles / Guides', VGSE()->textname),
				),
				'contact_us' => array(
					'url' => 'https://wpsheeteditor.com/company/contact/?utm_source=product&utm_medium=' . VGSE()->helpers->get_plugin_mode() . '&utm_campaign=' . $campaign,
					'label' => __('Contact us', VGSE()->textname),
				)
			);
			$links = apply_filters('vg_sheet_editor/support_links', $support_links);
			if ($link_key && isset($links[$link_key])) {
				$link = $links[$link_key];
				$out = ( $field && $field !== 'all') ? $link[$field] : $link;
			} else {
				$out = $links;
			}
			return $out;
		}

		function after_init() {
			if (!defined('VGSE_ANY_PREMIUM_ADDON')) {
				define('VGSE_ANY_PREMIUM_ADDON', (bool) VGSE()->helpers->has_paid_addon_active());
			}

			$this->user_path = new WPSE_User_Path(array(
				'user_path_key' => 'vgse',
				'is_free' => !VGSE_ANY_PREMIUM_ADDON
			));
		}

		function get_plugin_install_url($plugin_slug) {
			$install_plugin_base_url = ( is_multisite() ) ? network_admin_url() : admin_url();
			$install_plugin_url = add_query_arg(array(
				's' => $plugin_slug,
				'tab' => 'search',
				'type' => 'term'
					), $install_plugin_base_url . 'plugin-install.php');
			return $install_plugin_url;
		}

		function catch_missing_reduxframework_error() {

			if (!empty($_GET['page']) && $_GET['page'] === VGSE()->options_key && !class_exists('ReduxFramework')) {
				echo '<p>' . __(sprintf('WP Sheet Editor: Please install the Redux Framework plugin. <a href="%s" target="_blank" class="button">Click here</a>.<br/>It´s required for the settings page.', $this->get_plugin_install_url('redux-framework')), VGSE()->textname) . '</p>';
			}
		}

		/**
		 * Register dashboard widgets.
		 * Currently the only widget is "Editions stats".
		 */
		function register_dashboard_widgets() {
			if (!empty(VGSE()->options['be_disable_dashboard_widget'])) {
				return;
			}
			add_meta_box('vg_sheet_editor_usage_stats', __('WP Sheet Editor Usage', VGSE()->textname), array($this, 'render_usage_stats_widget'), 'dashboard', 'normal', 'high');
		}

		function render_usage_stats_widget() {
			require 'views/usage-stats-widget.php';
		}

		/**
		 * Redirect to "whats new" page after plugin update
		 */
		function redirect_to_whats_new_page() {

			// bail if settings are empty = fresh install
			if (empty(VGSE()->options)) {
				return;
			}

			// bail if there aren´t new features for this release			
			if (!file_exists(VGSE_DIR . '/views/whats-new/' . VGSE()->version . '.php')) {
				return;
			}

			// exit if the welcome page hasn´t been showed 
			if (get_option('vgse_welcome_redirect') !== 'no') {
				return;
			}

			// exit if the page was already showed
			if (get_option('vgse_hide_whats_new_' . VGSE()->version)) {
				return;
			}

			// Delete the redirect transient
			update_option('vgse_hide_whats_new_' . VGSE()->version, 'yes');

			// Bail if activating from network, or bulk
			if (is_network_admin() || isset($_GET['activate-multi'])) {
				return;
			}

			if (!empty($_GET['sheet_skip_whatsnew'])) {
				return;
			}

			wp_redirect(add_query_arg(array('page' => 'vg_sheet_editor_whats_new'), admin_url('admin.php')));
			exit();
		}

		/**
		 * Register admin pages
		 */
		function register_menu() {
			if (apply_filters('vg_sheet_editor/register_admin_pages', true)) {
				add_menu_page(__('WP Sheet Editor', VGSE()->textname), __('WP Sheet Editor', VGSE()->textname), 'manage_options', 'vg_sheet_editor_setup', array($this, 'render_quick_setup_page'), VGSE()->plugin_url . 'assets/imgs/icon-20x20.png');
				add_submenu_page('vg_sheet_editor_setup', __('Extensions', VGSE()->textname), __('Extensions', VGSE()->textname), 'manage_options', 'vg_sheet_editor_extensions', array($this, 'render_extensions_page'));
			}

			add_submenu_page(null, __('Sheet Editor', VGSE()->textname), __('Sheet Editor', VGSE()->textname), 'manage_options', 'vg_sheet_editor_whats_new', array($this, 'render_whats_new_page'));
		}

		/**
		 * Render extensions page
		 */
		function render_extensions_page() {
			if (!current_user_can('manage_options')) {
				wp_die(__('You dont have enough permissions to view this page.', VGSE()->textname));
			}

			if (!apply_filters('vg_sheet_editor/extensions/is_page_allowed', true)) {
				return;
			}
			require 'views/extensions-page.php';
		}

		/**
		 * Render quick setup page
		 */
		function render_quick_setup_page() {
			if (!current_user_can('manage_options')) {
				wp_die(__('You dont have enough permissions to view this page.', VGSE()->textname));
			}

			require 'views/quick-setup.php';
		}

		/**
		 * Render "whats new" page
		 */
		function render_whats_new_page() {
			if (!current_user_can('manage_options')) {
				wp_die(__('You dont have enough permissions to view this page.', VGSE()->textname));
			}

			require 'views/whats-new.php';
		}

		/*
		 * Register js scripts
		 */

		function register_scripts() {
			$current_post = VGSE()->helpers->get_provider_from_query_string();
			$pages_to_load_assets = $this->frontend_assets_allowed_on_pages();
			if (empty($_GET['page']) ||
					!in_array($_GET['page'], $pages_to_load_assets)) {
				return;
			}

			$this->_register_scripts($current_post);
		}

		function _register_scripts_lite($current_post) {
			$spreadsheet_columns = VGSE()->helpers->get_provider_columns($current_post);


			if (VGSE_DEBUG) {
				wp_enqueue_script('notifications_js', VGSE()->plugin_url . 'assets/vendor/oh-snap/ohsnap.js', array('jquery'), '0.1', false);
				wp_enqueue_script('bep_global', VGSE()->plugin_url . 'assets/js/global.js', array(), '0.1', false);
			} else {
				wp_enqueue_script('bep_libraries_js', VGSE()->plugin_url . 'assets/vendor/js/libraries.min.js', array(), VGSE()->version, false);
				wp_enqueue_script('bep_global', VGSE()->plugin_url . 'assets/js/scripts.min.js', array('bep_libraries_js'), VGSE()->version, false);
			}


			wp_localize_script('bep_global', 'vgse_editor_settings', apply_filters('vg_sheet_editor/js_data', array(
				'startRows' => (!empty(VGSE()->options) && !empty(VGSE()->options['be_posts_per_page']) ) ? (int) VGSE()->options['be_posts_per_page'] : 10,
				'startCols' => isset($spreadsheet_columns) ? count($spreadsheet_columns) : 0,
				'total_posts' => VGSE()->data_helpers->total_posts($current_post),
				'posts_per_page' => (!empty(VGSE()->options) && !empty(VGSE()->options['be_posts_per_page']) ) ? (int) VGSE()->options['be_posts_per_page'] : 10,
				'save_posts_per_page' => (!empty(VGSE()->options) && !empty(VGSE()->options['be_posts_per_page_save']) ) ? (int) VGSE()->options['be_posts_per_page_save'] : 4,
				'texts' => VGSE()->texts,
				'wait_between_batches' => (!empty(VGSE()->options) && !empty(VGSE()->options['be_timeout_between_batches']) ) ? (int) VGSE()->options['be_timeout_between_batches'] : 6,
				'watch_cells_to_lock' => false
							), $current_post));


			if (VGSE_DEBUG) {
				wp_enqueue_style('fontawesome', VGSE()->plugin_url . 'assets/vendor/font-awesome/css/font-awesome.min.css', '', '0.1', 'all');
				wp_enqueue_style('plugin_css', VGSE()->plugin_url . 'assets/css/style.css', '', '0.1', 'all');
			} else {
				wp_enqueue_style('bep_libraries_css', VGSE()->plugin_url . 'assets/vendor/css/libraries.min.css', '', VGSE()->version, 'all');
				wp_enqueue_style('plugin_css', VGSE()->plugin_url . 'assets/css/styles.min.css', '', VGSE()->version, 'all');
			}
		}

		function _register_scripts($current_post = null) {

			wp_add_inline_script('jquery-core', '$ = jQuery;');

			if (VGSE_DEBUG) {
				wp_enqueue_script('select2_js', $this->plugin_url . 'assets/vendor/select2/dist/js/select2.min.js', array('jquery'), $this->version, false);
				wp_enqueue_script('tipso_js', $this->plugin_url . 'assets/vendor/tipso/src/tipso.min.js', array('jquery'), $this->version, false);
				wp_enqueue_script('modal_js', $this->plugin_url . 'assets/vendor/remodal/dist/remodal.min.js', array('jquery'), $this->version, false);
				wp_enqueue_script('labelauty', $this->plugin_url . 'assets/vendor/labelauty/source/jquery-labelauty.js', array('jquery'), $this->version, false);

				wp_enqueue_script('notifications_js', $this->plugin_url . 'assets/vendor/oh-snap/ohsnap.js', array('jquery'), $this->version, false);
				wp_enqueue_script('handsontable_js', $this->plugin_url . 'assets/vendor/handsontable/dist/handsontable.full.js', array(), $this->version, false);


				wp_enqueue_script('text_editor_js', $this->plugin_url . 'assets/vendor/jqueryte/dist/jquery-te-1.4.0.min.js', array(), $this->version, false);
				wp_enqueue_script('bep_nanobar', $this->plugin_url . 'assets/vendor/nanobar/nanobar.js', array(), $this->version, false);

				wp_enqueue_script('bep_global', $this->plugin_url . 'assets/js/global.js', array(), $this->version, false);

				wp_enqueue_script('bep_init_js', $this->plugin_url . 'assets/js/init.js', array('handsontable_js'), $this->version, false);
				wp_enqueue_script('bep_post-status-plugin_js', $this->plugin_url . 'assets/js/post-status-plugin.js', array('bep_init_js'), $this->version, false);
				$localize_handle = 'bep_global';
			} else {

				$min_extension = (!empty($_GET['wpse_debug']) || (defined('SCRIPT_DEBUG') && SCRIPT_DEBUG) ) ? '' : '.min';
				wp_enqueue_script('bep_libraries_js', $this->plugin_url . 'assets/vendor/js/libraries' . $min_extension . '.js', array(), $this->version, false);
				wp_enqueue_script('bep_init_js', $this->plugin_url . 'assets/js/scripts' . $min_extension . '.js', array('bep_libraries_js'), $this->version, false);
				$localize_handle = 'bep_init_js';
			}
		}

		/**
		 * Get pages allowed to load frontend assets.
		 * @return array
		 */
		function frontend_assets_allowed_on_pages() {

			$allowed_pages = array();
			if (!empty($_GET['page']) && (strpos($_GET['page'], 'vgse-bulk-') !== false || strpos($_GET['page'], 'vgse_') !== false || strpos($_GET['page'], 'vg_sheet_editor') !== false)) {
				$allowed_pages[] = $_GET['page'];
			}
			$allowed_pages = apply_filters('vg_sheet_editor/scripts/pages_allowed', $allowed_pages);

			return $allowed_pages;
		}

		function get_trigger_link($prefix, $url, $id = '', $append_page_slug = false) {
			$id = $prefix . '-' . $id;
			if ($append_page_slug && !empty($_GET['page'])) {
				$id .= '-' . sanitize_text_field($_GET['page']);
			}
			return add_query_arg('vgseup_t', $id, $url);
		}

		function get_buy_link($id = '', $url = null, $append_page_slug = false) {
			if (!$url) {
				$url = $this->buy_link;
			}

			return $this->get_trigger_link('buy', $url, $id, $append_page_slug);
		}

		/**
		 * Register CSS files.
		 */
		function register_styles() {
			$pages_to_load_assets = $this->frontend_assets_allowed_on_pages();
			if (empty($_GET['page']) ||
					!in_array($_GET['page'], $pages_to_load_assets)) {
				return;
			}

			$this->_register_styles();
		}

		function render_extensions_list() {

			$extensions = VGSE()->extensions;
			$bundles = VGSE()->bundles;
			foreach ($extensions as $index => $extension) {
				if (!empty($extension['bundle']) && is_array($extension['bundle']) && !$extensions[$index]['is_active']) {
					foreach ($extension['bundle'] as $bundle) {
						array_push($bundles[$bundle]['extensions'], $extensions[$index]);
					}
				}
			}

			include VGSE()->plugin_dir . '/views/extensions.php';
		}

		function render_extensions_group($extensions, $bundle = null) {

			$defaults = array(
				'title' => '',
				'icon' => '',
				'image' => '', // fa-search
				'description' => '',
				'status' => '',
				'active_action_url' => '',
				'inactive_action_url' => '',
				'freemius_function' => '',
				'inactive_action_label' => '',
				'active_action_label' => '',
				'bundle' => false, // any string, we'll group them by value
				'class_function_name' => '',
			);

			foreach ($extensions as $extension) {
				$extension = wp_parse_args($extension, $defaults);

				if (!empty($bundle)) {
					$bundle = wp_parse_args($bundle, $extension);
					$extension['active_action_url'] = $bundle['active_action_url'];
					$extension['inactive_action_url'] = $bundle['inactive_action_url'];
					$extension['inactive_action_label'] = $bundle['inactive_action_label'];
					$extension['active_action_label'] = $bundle['active_action_label'];
					$extension['freemius_function'] = $bundle['freemius_function'];
				}

				$is_active = $extension['is_active'];
				$freemius = function_exists($extension['freemius_function']) ? $extension['freemius_function']() : null;
				$button_label = $is_active ? $extension['active_action_label'] : $extension['inactive_action_label'];
				$button_url = $is_active ? $extension['active_action_url'] : $extension['inactive_action_url'];


				if ($freemius) {
					$button_url = ( $freemius->can_use_premium_code__premium_only() ) ? $freemius->get_account_url() : $this->get_buy_link('extensions', $freemius->checkout_url(), true);
					$button_label = ( $freemius->can_use_premium_code__premium_only() ) ? __('My license', VGSE()->textname) : $button_label;
				}
				include VGSE()->plugin_dir . '/views/single-extension.php';
			}
		}

		function _register_styles() {
			if (VGSE_DEBUG) {
				wp_enqueue_style('fontawesome', $this->plugin_url . 'assets/vendor/font-awesome/css/font-awesome.min.css', '', $this->version, 'all');
				wp_enqueue_style('select2_styles', $this->plugin_url . 'assets/vendor/select2/dist/css/select2.min.css', '', $this->version, 'all');
				wp_enqueue_style('tipso_styles', $this->plugin_url . 'assets/vendor/tipso/src/tipso.min.css', '', $this->version, 'all');
				wp_enqueue_style('labelauty_styles', $this->plugin_url . 'assets/vendor/labelauty/source/jquery-labelauty.css', '', $this->version, 'all');
				wp_enqueue_style('handsontable_css', $this->plugin_url . 'assets/vendor/handsontable/dist/handsontable.full.css', '', $this->version, 'all');

				wp_enqueue_style('text_editor_css', $this->plugin_url . 'assets/vendor/jqueryte/dist/jquery-te-1.4.0.css', '', $this->version, 'all');
				wp_enqueue_style('plugin_css', $this->plugin_url . 'assets/css/style.css', '', $this->version, 'all');
				wp_enqueue_style('loading_anim_css', $this->plugin_url . 'assets/css/loading-animation.css', '', $this->version, 'all');
				wp_enqueue_style('modal_css', $this->plugin_url . 'assets/vendor/remodal/dist/remodal.css', '', $this->version, 'all');
				wp_enqueue_style('modal_theme_css', $this->plugin_url . 'assets/vendor/remodal/dist/remodal-default-theme.css', '', $this->version, 'all');
			} else {
				wp_enqueue_style('bep_libraries_css', $this->plugin_url . 'assets/vendor/css/libraries.min.css', '', $this->version, 'all');
				wp_enqueue_style('plugin_css', $this->plugin_url . 'assets/css/styles.min.css', '', $this->version, 'all');
			}
			$css_src = includes_url('css/') . 'editor.css';
			wp_enqueue_style('tinymce_css', $css_src, '', $this->version, 'all');
		}

		/*
		 * Enqueue wp media scripts on editor page
		 */

		function enqueue_media_wp_media() {
			$current_post = VGSE()->helpers->get_provider_from_query_string();

			$pages_to_load_assets = $this->frontend_assets_allowed_on_pages();
			if (empty($_GET['page']) ||
					!in_array($_GET['page'], $pages_to_load_assets)) {
				return;
			}
			wp_enqueue_media();
		}

	}

}

if (!function_exists('VGSE')) {

	function VGSE() {
		return WP_Sheet_Editor::get_instance();
	}

	function vgse_init() {
		VGSE()->init();
	}

	add_action('wp_loaded', 'vgse_init', 999);
}