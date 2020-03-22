<?php
if (!class_exists('WP_Sheet_Editor_WooCommerce_Teaser')) {

	/**
	 * Display woocommerce item in the toolbar to tease users of the free 
	 * version into purchasing the premium plugin.
	 */
	class WP_Sheet_Editor_WooCommerce_Teaser {

		static private $instance = false;
		var $post_type = null;
		var $allowed_columns = null;

		private function __construct() {
			
		}

		function init() {
			if (!is_admin()) {
				return;
			}
			$this->post_type = apply_filters('vg_sheet_editor/woocommerce/product_post_type_key', 'product');

			if (class_exists('WP_Sheet_Editor_WooCommerce') || !class_exists('WooCommerce')) {
				return;
			}

			$this->allowed_columns = apply_filters('vg_sheet_editor/woocommerce/teasers/allowed_columns', array(
				'ID',
				'post_title',
				'_sku',
				'_regular_price',
				'_sale_price',
				'_manage_stock',
				'_stock_status',
				'_stock',
			));


			add_filter('vg_sheet_editor/allowed_post_types', array($this, 'allow_product_post_type'));
			add_filter('vg_sheet_editor/load_rows/wp_query_args', array($this, 'display_only_simple_products'));
			add_filter('vg_sheet_editor/add_new_posts/create_new_posts', array($this, 'create_new_products'), 10, 3);
			add_filter('vg_sheet_editor/load_rows/output', array($this, 'lock_premium_columns'), 9, 2);
			add_action('vg_sheet_editor/editor/before_init', array($this, 'register_columns'));
			add_action('vg_sheet_editor/columns/provider_items', array($this, 'filter_columns_settings'), 10, 4);
			add_filter('vg_sheet_editor/custom_columns/teaser/allow_to_lock_column', array($this, 'dont_lock_allowed_columns'), 10, 2);
			add_action('woocommerce_variable_product_before_variations', array($this, 'render_variations_metabox_teaser'));
		}

		function render_variations_metabox_teaser() {
			?>
			<style>
			.wpse-variation-metabox-teaser {
				padding: 10px;
			}
			</style>
			<div class="notice-success is-dismissible wpse-variation-metabox-teaser">
				<?php printf(__('<b>Tip from WP Sheet Editor:</b> You can view and edit Product Variations in a spreadsheet, bulk edit, make advanced searches, edit hundreds of variations at once, copy variations to multiple products, etc. <a href="%s" class="" target="_blank">Download Plugin</a>', VGSE()->textname), 'https://wpsheeteditor.com/extensions/woocommerce-spreadsheet/'); ?>
			</div>
			<?php
		}

		function dont_lock_allowed_columns($allowed_to_lock, $column_key) {
			if (in_array($column_key, $this->allowed_columns)) {
				$allowed_to_lock = false;
			}

			return $allowed_to_lock;
		}

		/**
		 * Modify spreadsheet columns settings.
		 * 
		 * It changes the names and settings of some columns.
		 * @param array $spreadsheet_columns
		 * @param string $post_type
		 * @param bool $exclude_formatted_settings
		 * @return array
		 */
		function filter_columns_settings($spreadsheet_columns, $post_type, $exclude_formatted_settings, $columns) {

			if (defined('VGSE_WC_TEASER_LIMIT_COLUMNS') && !VGSE_WC_TEASER_LIMIT_COLUMNS) {
				return $spreadsheet_columns;
			}
			if ($post_type !== $this->post_type) {
				return $spreadsheet_columns;
			}

			// Adapt core columns to woocommerce format
			if (!empty($spreadsheet_columns['post_excerpt'])) {
				$spreadsheet_columns['post_excerpt']['title'] = __('Short description', VGSE()->textname);
				$spreadsheet_columns['post_excerpt']['column_width'] = 150;
			}
			if (!empty($spreadsheet_columns['comment_status'])) {
				$spreadsheet_columns['comment_status']['title'] = __('Enable reviews', VGSE()->textname);
			}


			$enabled = array();
			$disabled = array();

			// Increase column width for disabled columns, so the "premium" message fits
			foreach ($spreadsheet_columns as $key => $column) {
				if (in_array($key, $this->allowed_columns)) {
					$enabled[$key] = $column;
				} else {
					$disabled[$key] = $column;
					$disabled[$key]['column_width'] += 80;
					$disabled[$key]['formatted']['renderer'] = 'html';
					$disabled[$key]['formatted']['readOnly'] = true;
					$disabled[$key]['unformatted']['renderer'] = 'html';
					$disabled[$key]['unformatted']['readOnly'] = true;
				}
			}

			$new_columns = array_merge($enabled, $disabled);

			return $new_columns;
		}

		/**
		 * Register spreadsheet columns
		 */
		function register_columns($editor) {
			$post_type = $this->post_type;

			if ($editor->provider->key === 'user') {
				return;
			}

			$product_type_tax = 'product_type';
			$editor->args['columns']->register_item($product_type_tax, $post_type, array(
				'data_type' => 'post_terms',
				'unformatted' => array('data' => $product_type_tax),
				'column_width' => 150,
				'title' => __('Type', VGSE()->textname),
				'type' => '',
				'supports_formulas' => true,
				'formatted' => array('data' => $product_type_tax, 'type' => 'autocomplete', 'source' => array(VGSE()->data_helpers, 'get_taxonomy_terms'), 'callback_args' => array($product_type_tax)),
				'allow_to_hide' => true,
				'allow_to_rename' => true,
			));
			$editor->args['columns']->register_item('_sku', $post_type, array(
				'data_type' => 'meta_data',
				'unformatted' => array('data' => '_sku'),
				'column_width' => 150,
				'title' => __('SKU', VGSE()->textname),
				'type' => '',
				'supports_formulas' => true,
				'formatted' => array('data' => '_sku', 'renderer' => 'html'),
				'allow_to_hide' => true,
				'allow_to_rename' => true,
			));

			$editor->args['columns']->register_item('_regular_price', $post_type, array(
				'data_type' => 'meta_data',
				'unformatted' => array('data' => '_regular_price'),
				'column_width' => 150,
				'title' => __('Regular Price', VGSE()->textname),
				'type' => '',
				'supports_formulas' => true,
				'formatted' => array('data' => '_regular_price'),
				'allow_to_hide' => true,
				'allow_to_rename' => true,
				'value_type' => 'number',
			));

			$editor->args['columns']->register_item('_sale_price', $post_type, array(
				'value_type' => 'number',
				'data_type' => 'meta_data',
				'unformatted' => array('data' => '_sale_price'),
				'column_width' => 150,
				'title' => __('Sale Price', VGSE()->textname),
				'type' => '',
				'supports_formulas' => true,
				'formatted' => array('data' => '_sale_price', 'renderer' => 'html'),
				'allow_to_hide' => true,
				'allow_to_rename' => true,
			));

			$editor->args['columns']->register_item('_weight', $post_type, array(
				'data_type' => 'meta_data',
				'unformatted' => array('data' => '_weight'),
				'column_width' => 100,
				'title' => __('Weight', VGSE()->textname),
				'type' => '',
				'supports_formulas' => true,
				'formatted' => array('data' => '_weight', 'renderer' => 'html'),
				'allow_to_hide' => true,
				'allow_to_rename' => true,
			));

			$editor->args['columns']->register_item('_width', $post_type, array(
				'data_type' => 'meta_data',
				'unformatted' => array('data' => '_width'),
				'column_width' => 100,
				'title' => __('Width', VGSE()->textname),
				'type' => '',
				'supports_formulas' => true,
				'formatted' => array('data' => '_width', 'renderer' => 'html'),
				'allow_to_hide' => true,
				'allow_to_rename' => true,
			));

			$editor->args['columns']->register_item('_height', $post_type, array(
				'data_type' => 'meta_data',
				'unformatted' => array('data' => '_height'),
				'column_width' => 100,
				'title' => __('Height', VGSE()->textname),
				'type' => '',
				'supports_formulas' => true,
				'formatted' => array('data' => '_height', 'renderer' => 'html'),
				'allow_to_hide' => true,
				'allow_to_rename' => true,
			));

			$editor->args['columns']->register_item('_length', $post_type, array(
				'data_type' => 'meta_data',
				'unformatted' => array('data' => '_length'),
				'column_width' => 100,
				'title' => __('Length', VGSE()->textname),
				'type' => '',
				'supports_formulas' => true,
				'formatted' => array('data' => '_length', 'renderer' => 'html'),
				'allow_to_hide' => true,
				'allow_to_rename' => true,
			));
			$editor->args['columns']->register_item('_manage_stock', $post_type, array(
				'data_type' => 'meta_data',
				'unformatted' => array('data' => '_manage_stock'),
				'column_width' => 150,
				'title' => __('Manage stock', VGSE()->textname),
				'type' => '',
				'supports_formulas' => true,
				'formatted' => array(
					'data' => '_manage_stock',
					'type' => 'checkbox',
					'checkedTemplate' => 'yes',
					'uncheckedTemplate' => 'no',
				),
				'default_value' => 'no',
				'allow_to_hide' => true,
				'allow_to_rename' => true,
			));

			$editor->args['columns']->register_item('_stock_status', $post_type, array(
				'data_type' => 'meta_data',
				'unformatted' => array('data' => '_stock_status'),
				'column_width' => 150,
				'title' => __('Stock status', VGSE()->textname),
				'type' => '',
				'supports_formulas' => true,
				'formatted' => array(
					'data' => '_stock_status',
					'type' => 'checkbox',
					'checkedTemplate' => 'instock',
					'uncheckedTemplate' => 'outofstock',
				),
				'default_value' => 'instock',
				'allow_to_hide' => true,
				'allow_to_rename' => true,
			));

			$editor->args['columns']->register_item('_stock', $post_type, array(
				'data_type' => 'meta_data',
				'unformatted' => array('data' => '_stock'),
				'column_width' => 75,
				'title' => __('Stock', VGSE()->textname),
				'type' => '',
				'supports_formulas' => true,
				'formatted' => array('data' => '_stock'),
				'allow_to_hide' => true,
				'allow_to_rename' => true,
			));


			$editor->args['columns']->register_item('_visibility', $post_type, array(
				'data_type' => 'meta_data',
				'unformatted' => array('data' => '_visibility'),
				'column_width' => 150,
				'title' => __('Visibility', VGSE()->textname),
				'type' => '',
				'supports_formulas' => true,
				'formatted' => array('data' => '_visibility', 'editor' => 'select', 'selectOptions' => array('visible', 'catalog', 'search', 'hidden')),
				'allow_to_hide' => true,
				'allow_to_rename' => true,
			));

			$editor->args['columns']->register_item('_product_image_gallery', $post_type, array(
				'data_type' => 'meta_data',
				'unformatted' => array('data' => '_product_image_gallery', 'renderer' => 'html', 'readOnly' => true),
				'column_width' => 300,
				'supports_formulas' => true,
				'title' => __('Gallery', VGSE()->textname),
				'type' => 'boton_gallery_multiple',
				'formatted' => array('data' => '_product_image_gallery', 'renderer' => 'html', 'readOnly' => true),
				'allow_to_hide' => true,
				'allow_to_save' => true,
				'allow_to_rename' => true,
			));

			$editor->args['columns']->register_item('_downloadable', $post_type, array(
				'data_type' => 'meta_data',
				'unformatted' => array('data' => '_downloadable'),
				'column_width' => 150,
				'title' => __('Downloadable', VGSE()->textname),
				'type' => '',
				'supports_formulas' => true,
				'formatted' => array('data' => '_downloadable',
					'type' => 'checkbox',
					'checkedTemplate' => 'yes',
					'uncheckedTemplate' => 'no',
				),
				'default_value' => 'no',
				'allow_to_hide' => true,
				'allow_to_rename' => true,
			));

			$editor->args['columns']->register_item('_virtual', $post_type, array(
				'data_type' => 'meta_data',
				'unformatted' => array('data' => '_virtual'),
				'column_width' => 150,
				'title' => __('Virtual', VGSE()->textname),
				'type' => '',
				'supports_formulas' => true,
				'formatted' => array('data' => '_virtual',
					'type' => 'checkbox',
					'checkedTemplate' => 'yes',
					'uncheckedTemplate' => 'no',
				),
				'default_value' => 'no',
				'allow_to_hide' => true,
				'allow_to_rename' => true,
			));

			$editor->args['columns']->register_item('_sale_price_dates_from', $post_type, array(
				'data_type' => 'meta_data',
				'unformatted' => array('data' => '_sale_price_dates_from'),
				'column_width' => 150,
				'title' => __('Sales price date from', VGSE()->textname),
				'type' => '',
				'supports_formulas' => true,
				'formatted' => array('data' => '_sale_price_dates_from', 'type' => 'date', 'dateFormat' => 'YYYY-MM-DD', 'correctFormat' => true, 'defaultDate' => '', 'datePickerConfig' => array('firstDay' => 0, 'showWeekNumber' => true, 'numberOfMonths' => 1)),
				'allow_to_hide' => true,
				'allow_to_rename' => true,
			));

			$editor->args['columns']->register_item('_sale_price_dates_to', $post_type, array(
				'data_type' => 'meta_data',
				'unformatted' => array('data' => '_sale_price_dates_to'),
				'column_width' => 150,
				'title' => __('Sales price date to', VGSE()->textname),
				'type' => '',
				'supports_formulas' => true,
				'formatted' => array('data' => '_sale_price_dates_to', 'type' => 'date', 'dateFormat' => 'YYYY-MM-DD', 'correctFormat' => true, 'defaultDate' => '', 'datePickerConfig' => array('firstDay' => 0, 'showWeekNumber' => true, 'numberOfMonths' => 1)),
				'allow_to_hide' => true,
				'allow_to_rename' => true,
			));
			$editor->args['columns']->register_item('_sold_individually', $post_type, array(
				'data_type' => 'meta_data',
				'unformatted' => array('data' => '_sold_individually'),
				'column_width' => 150,
				'title' => __('Sold individually', VGSE()->textname),
				'type' => '',
				'supports_formulas' => true,
				'formatted' => array('data' => '_sold_individually',
					'type' => 'checkbox',
					'checkedTemplate' => 'yes',
					'uncheckedTemplate' => 'no',
				),
				'default_value' => 'no',
				'allow_to_hide' => true,
				'allow_to_rename' => true,
			));
			$editor->args['columns']->register_item('_featured', $post_type, array(
				'data_type' => 'meta_data',
				'unformatted' => array('data' => '_featured'),
				'column_width' => 150,
				'title' => __('is featured?', VGSE()->textname),
				'type' => '',
				'supports_formulas' => true,
				'formatted' => array('data' => '_featured',
					'type' => 'checkbox',
					'checkedTemplate' => 'yes',
					'uncheckedTemplate' => 'no',
				),
				'default_value' => 'no',
				'allow_to_hide' => true,
				'allow_to_rename' => true,
			));
			$editor->args['columns']->register_item('_backorders', $post_type, array(
				'data_type' => 'meta_data',
				'unformatted' => array('data' => '_backorders'),
				'column_width' => 150,
				'title' => __('Allow backorders', VGSE()->textname),
				'type' => '',
				'supports_formulas' => true,
				'formatted' => array('data' => '_backorders',
					'editor' => 'select',
					'selectOptions' => array(
						'no' => __('Do not allow', 'woocommerce'),
						'notify' => __('Allow, but notify customer', 'woocommerce'),
						'yes' => __('Allow', 'woocommerce'),
					)
				),
				'default_value' => 'no',
				'allow_to_hide' => true,
				'allow_to_rename' => true,
			));

			$editor->args['columns']->register_item('_purchase_note', $post_type, array(
				'data_type' => 'meta_data',
				'unformatted' => array('data' => '_purchase_note'),
				'column_width' => 250,
				'title' => __('Purchase note', VGSE()->textname),
				'type' => '',
				'supports_formulas' => true,
				'formatted' => array('data' => '_purchase_note',),
				'allow_to_hide' => true,
				'allow_to_rename' => true,
			));

			$shipping_tax_name = 'product_shipping_class';
			$editor->args['columns']->register_item($shipping_tax_name, $post_type, array(
				'data_type' => 'post_terms',
				'unformatted' => array('data' => $shipping_tax_name),
				'column_width' => 150,
				'title' => __('Shipping class', VGSE()->textname),
				'type' => '',
				'supports_formulas' => true,
				'formatted' => array('data' => $shipping_tax_name, 'type' => 'autocomplete', 'source' => array(VGSE()->data_helpers, 'get_taxonomy_terms'), 'callback_args' => array($shipping_tax_name)),
				'allow_to_hide' => true,
				'allow_to_rename' => true,
			));

			$editor->args['columns']->register_item('_download_limit', $post_type, array(
				'data_type' => 'meta_data',
				'unformatted' => array('data' => '_download_limit'),
				'column_width' => 150,
				'title' => __('Download limit', VGSE()->textname),
				'type' => '',
				'supports_formulas' => true,
				'formatted' => array('data' => '_download_limit',),
				'allow_to_hide' => true,
				'allow_to_rename' => true,
				'value_type' => 'number',
			));
			$editor->args['columns']->register_item('_download_expiry', $post_type, array(
				'data_type' => 'meta_data',
				'unformatted' => array('data' => '_download_expiry'),
				'column_width' => 150,
				'title' => __('Download expiry', VGSE()->textname),
				'type' => '',
				'supports_formulas' => true,
				'formatted' => array('data' => '_download_expiry',),
				'allow_to_hide' => true,
				'allow_to_rename' => true,
				'value_type' => 'number',
			));
			$editor->args['columns']->register_item('_download_type', $post_type, array(
				'data_type' => 'meta_data',
				'unformatted' => array('data' => '_download_type'),
				'column_width' => 250,
				'title' => __('Download type', VGSE()->textname),
				'type' => '',
				'supports_formulas' => true,
				'formatted' => array('data' => '_download_type', 'editor' => 'select', 'selectOptions' => array(
						'' => __('Standard Product', 'woocommerce'),
						'application' => __('Application/Software', 'woocommerce'),
						'music' => __('Music', 'woocommerce'),
					)),
				'allow_to_hide' => true,
				'allow_to_rename' => true,
			));
			$editor->args['columns']->register_item('_downloadable_files', $post_type, array(
				'data_type' => 'meta_data',
				'unformatted' => array('data' => '_downloadable_files', 'renderer' => 'html', 'readOnly' => true),
				'column_width' => 120,
				'title' => __('Download files', VGSE()->textname),
				'type' => 'handsontable',
				'edit_button_label' => __('Edit files', VGSE()->textname),
				'edit_modal_id' => 'vgse-download-files',
				'edit_modal_title' => __('Download files', VGSE()->textname),
				'edit_modal_description' => '<div class="vgse-copy-files-from-product-wrapper"><label>' . __('Copy files from this product: (You need to save the changes afterwards.)', VGSE()->textname) . ' </label><br/><select name="copy_from_product" data-remote="true" data-min-input-length="4" data-action="vgse_find_post_by_name" data-post-type="' . $this->post_type . '" data-nonce="' . wp_create_nonce('bep-nonce') . '" data-placeholder="' . __('Select product...', VGSE()->textname) . '" class="select2 vgse-copy-files-from-product">
									<option></option>
								</select><a href="#" class="button vgse-copy-files-from-product-trigger">Copy</a></div>',
				'edit_modal_local_cache' => true,
				'edit_modal_save_action' => 'vgse_save_download_files',
				'handsontable_columns' => array(
					$this->post_type => array(
						array(
							'data' => 'name'
						),
						array(
							'data' => 'file'
						),
					),
					'product_variation' => array(
						array(
							'data' => 'name'
						),
						array(
							'data' => 'file'
						),
					)),
				'handsontable_column_names' => array(
					$this->post_type => array(__('Name', VGSE()->textname), __('File (url or path)', VGSE()->textname)),
					'product_variation' => array(__('Name', VGSE()->textname), __('File (url or path)', VGSE()->textname)),
				),
				'handsontable_column_widths' => array(
					$this->post_type => array(160, 300),
					'product_variation' => array(160, 300),
				),
				'supports_formulas' => false,
				'formatted' => array('data' => '_downloadable_files', 'renderer' => 'html', 'readOnly' => true),
				'allow_to_hide' => true,
				'allow_to_save' => false,
				'allow_to_rename' => false,
			));

			$editor->args['columns']->register_item('_variation_description', $post_type, array(
				'key' => '_variation_description',
				'data_type' => 'post_meta',
				'unformatted' => array(
					'data' => '_variation_description'
				),
				'column_width' => 175,
				'title' => __('Variation description', VGSE()->textname),
				'type' => '',
				'supports_formulas' => false,
				'formatted' => array(
					'data' => '_variation_description',
				),
				'default_value' => '',
				'allow_to_hide' => true,
				'allow_to_save' => true,
				'allow_to_rename' => false
			));
			$editor->args['columns']->register_item('_vgse_variation_enabled', $post_type, array(
				'key' => '_vgse_variation_enabled',
				'data_type' => 'post_data',
				'unformatted' => array(
					'data' => '_vgse_variation_enabled'
				),
				'column_width' => 140,
				'title' => __('Variation enabled?', VGSE()->textname),
				'type' => '',
				'supports_formulas' => false,
				'formatted' => array(
					'data' => '_vgse_variation_enabled',
					'type' => 'checkbox',
					'checkedTemplate' => 'on',
					'uncheckedTemplate' => ''
				),
				'default_value' => 'on',
				'allow_to_hide' => true,
				'allow_to_save' => false,
				'allow_to_rename' => false
			));

			$editor->args['columns']->register_item('default_attributes', $post_type, array(
				'data_type' => 'meta_data',
				'unformatted' => array('data' => 'default_attributes', 'renderer' => 'html', 'readOnly' => true),
				'column_width' => 160,
				'title' => __('Default attributes', VGSE()->textname),
				'type' => 'handsontable',
				'edit_button_label' => __('Default attributes', VGSE()->textname),
				'edit_modal_id' => 'vgse-default-attributes',
				'edit_modal_title' => __('Default attributes', VGSE()->textname),
				'edit_modal_description' => sprintf(__('Note: Separate values with the character %s<br/>The product must be variable and have existing variations for this to work, otherwise the default attributes won´t be saved.</spán>'), WC_DELIMITER),
				'edit_modal_save_action' => 'vgse_save_default_attributes',
				'edit_modal_get_action' => 'vgse_save_default_attributes',
				'edit_modal_local_cache' => false,
				'handsontable_columns' => array(
					$this->post_type => array(
						array(
							'data' => 'name'
						),
						array(
							'data' => 'option'
						),
					)),
				'handsontable_column_names' => array(
					$this->post_type => array(
						__('Name', VGSE()->textname),
						__('Value', VGSE()->textname)
					)
				),
				'handsontable_column_widths' => array(
					$this->post_type => array(160, 300),
				),
				'supports_formulas' => false,
				'formatted' => array('data' => 'default_attributes', 'renderer' => 'html', 'readOnly' => true),
				'allow_to_hide' => true,
				'allow_to_save' => false,
				'allow_to_rename' => false,
			));
		}

		function lock_premium_columns($rows, $wp_query) {

			// Constant used by "wc inventory spreadsheet" plugin to allow all 
			// registered columns (the other plugin locks the premium columns).
			if (defined('VGSE_WC_TEASER_LIMIT_COLUMNS') && !VGSE_WC_TEASER_LIMIT_COLUMNS) {
				return $rows;
			}
			if ($wp_query['post_type'] !== $this->post_type) {
				return $rows;
			}

			$allowed_columns = $this->allowed_columns;
			foreach ($rows as $row_index => $row) {
				foreach ($row as $column_key => $value) {
					if (strpos($value, 'vg-cell-blocked') !== false) {
						continue;
					}
					if (!in_array($column_key, $allowed_columns)) {
						// Remove buttons classes to disable cell popups
						$value = str_replace(array(
							'set_custom_images',
							'view_custom_images',
							'button-handsontable',
							'button-custom-modal-editor',
							'data-remodal-target="image"',
								), '', $value);
						$rows[$row_index][$column_key] = '<i class="fa fa-lock vg-cell-blocked vg-wc-teaser-lock"></i> ' . $value . ' <a href="' . VGSE()->get_buy_link('wc-products-locked-cell') . '" target="_blank">(Premium)</a>';
					}
				}
			}

			return $rows;
		}

		/**
		 * Create new products using WC API
		 * @param array $post_ids
		 * @param str $post_type
		 * @param int $number
		 * @return array Post ids
		 */
		public function create_new_products($post_ids, $post_type, $number) {

			if ($post_type !== $this->post_type || !empty($post_ids)) {
				return $post_ids;
			}

			for ($i = 0; $i < $number; $i++) {
				$api_response = VGSE()->helpers->create_rest_request('POST', '/wc/v1/products', array(
					'name' => __('...', VGSE()->textname)
				));

				if ($api_response->status === 200 || $api_response->status === 201) {
					$api_data = $api_response->get_data();
					$post_ids[] = $api_data['id'];
				}
			}

			return $post_ids;
		}

		function display_only_simple_products($wp_query) {
			if ($wp_query['post_type'] === $this->post_type) {
				$wp_query['tax_query'] = array(
					array(
						'taxonomy' => 'product_type',
						'field' => 'slug',
						'terms' => 'simple',
					),
				);
			}

			return $wp_query;
		}

		/**
		 * Allow woocomerce product post type
		 * @param array $post_types
		 * @return array
		 */
		function allow_product_post_type($post_types) {

			if (!isset($post_types[$this->post_type])) {
				$post_types[$this->post_type] = VGSE()->helpers->get_post_type_label($this->post_type);
			}
			return $post_types;
		}

		/**
		 * Creates or returns an instance of this class.
		 *
		 * 
		 */
		static function get_instance() {
			if (null == WP_Sheet_Editor_WooCommerce_Teaser::$instance) {
				WP_Sheet_Editor_WooCommerce_Teaser::$instance = new WP_Sheet_Editor_WooCommerce_Teaser();
				WP_Sheet_Editor_WooCommerce_Teaser::$instance->init();
			}
			return WP_Sheet_Editor_WooCommerce_Teaser::$instance;
		}

		function __set($name, $value) {
			$this->$name = $value;
		}

		function __get($name) {
			return $this->$name;
		}

	}

}


add_action('vg_sheet_editor/initialized', 'vgse_init_woocommerce_teaser');

if (!function_exists('vgse_init_woocommerce_teaser')) {

	function vgse_init_woocommerce_teaser() {
		WP_Sheet_Editor_WooCommerce_Teaser::get_instance();
	}

}
