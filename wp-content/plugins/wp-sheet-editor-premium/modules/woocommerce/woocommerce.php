<?php

if (!defined('VGSE_WC_FILE')) {
	define('VGSE_WC_FILE', __FILE__);
}
if (!defined('VGSE_WC_DIR')) {
	define('VGSE_WC_DIR', __DIR__);
}
if (!class_exists('WP_Sheet_Editor_WooCommerce')) {

	/**
	 * Edit all your products information in the spreadsheet editor.
	 */
	class WP_Sheet_Editor_WooCommerce {

		static private $instance = false;
		var $post_type = null;
		var $variations = null;
		var $wc_after_save_prices_synced = array();

		private function __construct() {
			
		}

		/**
		 * Creates or returns an instance of this class.
		 */
		static function get_instance() {
			if (null == WP_Sheet_Editor_WooCommerce::$instance) {
				WP_Sheet_Editor_WooCommerce::$instance = new WP_Sheet_Editor_WooCommerce();
				WP_Sheet_Editor_WooCommerce::$instance->init();
			}
			return WP_Sheet_Editor_WooCommerce::$instance;
		}

		/**
		 * is woocommerce plugin active?
		 * @return boolean
		 */
		function is_woocommerce_active() {
			if (in_array('woocommerce/woocommerce.php', apply_filters('active_plugins', get_option('active_plugins')))) {
				return true;
			} else {
				return false;
			}
		}

		function init() {

			$this->post_type = apply_filters('vg_sheet_editor/woocommerce/product_post_type_key', 'product');

			// exit if woocommerce plugin is not active
			if (!$this->is_woocommerce_active()) {
				return;
			}

			// Include files
			require 'inc/attributes.php';
			require 'inc/variations.php';

			$this->variations = WP_Sheet_Editor_WooCommerce_Variations::get_instance();

			// init wp hooks
			add_action('vg_sheet_editor/save_rows/after_saving_cell', array($this, 'product_cell_updated_on_spreadsheet'), 10, 6);
			add_action('vg_sheet_editor/save_rows/after_saving_post', array($this, 'product_updated_on_spreadsheet'), 10, 6);
			add_action('vg_sheet_editor/formulas/execute_formula/after_execution_on_field', array($this, 'product_updated_with_formula'), 10, 8);
			add_action('vg_sheet_editor/columns/provider_items', array($this, 'filter_columns_settings'), 10, 3);
			add_action('vg_sheet_editor/editor/before_init', array($this, 'register_columns'));
			add_filter('vg_sheet_editor/allowed_post_types', array($this, 'allow_product_post_type'));
			add_filter('vg_sheet_editor/add_new_posts/create_new_posts', array($this, 'create_new_products'), 10, 3);

			add_action('wp_ajax_vgse_save_download_files', array($this, 'save_download_files'));
			add_action('wp_ajax_vgse_wc_get_downloadable_files', array($this, 'get_download_files'));
			add_filter('admin_enqueue_scripts', array($this, 'enqueue_assets'));
			add_filter('vg_sheet_editor/load_rows/full_output', array($this, 'calculate_inventory_totals'), 10, 2);
			add_filter('vg_sheet_editor/load_rows/output', array($this, 'format_sale_dates'), 10, 3);
			add_filter('vg_sheet_editor/formulas/execute_formula/custom_formula_handler_executed', array($this, 'execute_formula_on_downloadable_files'), 10, 7);
			add_filter('vg_sheet_editor/formulas/form_settings', array($this, 'filter_formula_builder_for_downloadable_files'), 10, 2);
			add_filter('vg_sheet_editor/formulas/sql_execution/unsupported_value_types', array($this, 'disallow_formula_sql_execution_on_downloadable_files'), 10, 3);
			add_filter('vg_sheet_editor/formulas/sql_execution/can_execute', array($this, 'disallow_formula_sql_execution_on_special_columns'), 10, 4);
			add_filter('vg_sheet_editor/save_rows/incoming_data', array($this, 'convert_sales_date_to_timestamp'), 10, 2);
			add_filter('vg_sheet_editor/columns/blacklisted_columns', array($this, 'disable_wc_private_columns'), 10, 3);
			add_filter('vg_sheet_editor/js_data', array($this, 'watch_cells_to_lock'), 10, 2);
			add_filter('vg_sheet_editor/filters/allowed_fields', array($this, 'register_filters'), 11, 2);
			add_filter('vg_sheet_editor/provider/post/update_item_meta', array($this, 'filter_cell_data_for_saving'), 10, 3);
			add_filter('vg_sheet_editor/provider/post/get_item_meta', array($this, 'filter_cell_data_for_readings'), 10, 5);
			add_filter('vg_sheet_editor/custom_columns/all_meta_keys', array($this, 'disable_serialized_keys_from_automatic_columns'), 10, 2);
		}

		/**
		 * The custom columns module finds all the meta keys and registers columns for them.
		 * In this case we remove the "serialized fields" from the list because we already register
		 * special columns for them.
		 * 
		 * @param array $columns
		 * @param string $post_type
		 * @return array
		 */
		function disable_serialized_keys_from_automatic_columns($columns, $post_type) {
			if ($post_type === $this->post_type) {
				$disallowed_keys = array('_crosssell_ids', '_upsell_ids', '_product_attributes', '_downloadable_files');
				$columns = array_diff($columns, $disallowed_keys);
			}
			return $columns;
		}

		function filter_cell_data_for_readings($value, $id, $cell_key, $single, $context) {
			if ($context !== 'read' || get_post_type($id) !== $this->post_type) {
				return $value;
			}
			if (in_array($cell_key, array('_crosssell_ids', '_upsell_ids'))) {
				if (!empty($value)) {
					$value = implode(', ', $value);
				} else {
					$value = '';
				}
			}
			if ($cell_key === '_featured') {
				$terms = get_the_terms($id, 'product_visibility');
				$term_names = is_array($terms) ? wp_list_pluck($terms, 'name') : array();
				$value = ( in_array('featured', $term_names, true) ) ? 'featured' : '';
			}
			return $value;
		}

		function filter_cell_data_for_saving($new_value, $id, $key) {
			if (get_post_type($id) !== $this->post_type) {
				return $new_value;
			}

			if (in_array($key, array('_crosssell_ids', '_upsell_ids'))) {
				if (!is_array($new_value)) {
					$new_value = array_map('intval', explode(',', $new_value));
				}
			}
			if ($key === '_featured') {
				if ($new_value === 'no') {
					wp_remove_object_terms($id, 'featured', 'product_visibility');
				} elseif ($new_value) {
					wp_set_object_terms($id, 'featured', 'product_visibility', true);
				}
			}

			return $new_value;
		}

		function register_filters($filters, $post_type) {

			if ($post_type === $this->post_type && isset($filters['post_parent'])) {
				unset($filters['post_parent']);
			}
			return $filters;
		}

		function watch_cells_to_lock($data, $post_type) {
			if ($post_type === $this->post_type) {
				$data['watch_cells_to_lock'] = true;
			}
			return $data;
		}

		function disable_wc_private_columns($blacklisted_columns, $args, $provider) {
			if ($provider === $this->post_type) {
				$blacklisted_columns = array_merge($blacklisted_columns, array('_price', 'post_parent', '_wc_attachment_source', '_wc_average_rating', '_wc_review_count', 'total_sales', '_default_attributes'));
			}
			return $blacklisted_columns;
		}

		function convert_sales_date_to_timestamp($data, $settings) {
			if (!in_array($settings['post_type'], array($this->post_type, 'product_variation'))) {
				return $data;
			}

			foreach ($data as $row_index => $row) {
				if (!empty($row['_sale_price_dates_from']) && !is_numeric($row['_sale_price_dates_from'])) {
					$data[$row_index]['_sale_price_dates_from'] = strtotime($row['_sale_price_dates_from']);
				}
				if (!empty($row['_sale_price_dates_to']) && !is_numeric($row['_sale_price_dates_to'])) {
					$data[$row_index]['_sale_price_dates_to'] = strtotime($row['_sale_price_dates_to']);
				}
			}

			return $data;
		}

		function disallow_formula_sql_execution_on_downloadable_files($disallowed, $formula, $column) {
			$disallowed[] = 'wc_downloadable_files';
		}

		function disallow_formula_sql_execution_on_special_columns($allowed, $formula, $column, $post_type) {
			if ($post_type !== $this->post_type) {
				return $allowed;
			}
			$disallowed = array();
			// Price dates require a price/date sync, so we´re forced to use 
			// the slower execution method in order to know the updated post ids to sync them afterwards
			$disallowed[] = '_sale_price_dates_from';
			$disallowed[] = '_sale_price_dates_to';

			if (in_array($column['key'], $disallowed)) {
				$allowed = false;
			}
			return $allowed;
		}

		function filter_formula_builder_for_downloadable_files($settings, $post_type) {
			if ($post_type !== $this->post_type) {
				return $settings;
			}
			$settings['columns_actions']['wc_downloadable_files'] = array(
				'replace' => 'default',
				'set_value' =>
				array(
					'description' => __('We will save these files. Existing files will be overwritten. Enter file URL only, you can enter multiple URLs separated by comma.', VGSE()->textname),
				),
				'append' =>
				array(
					'description' => __('We will append the new file to the existing files in the products. Enter file URL only, you can enter multiple URLs separated by comma.', VGSE()->textname),
				),
			);

			return $settings;
		}

		function execute_formula_on_downloadable_files($results, $post_id, $spreadsheet_column, $formula, $post_type, $spreadsheet_columns, $raw_form_data) {

			if ($post_type !== $this->post_type || $spreadsheet_column['key'] !== '_downloadable_files') {
				return $results;
			}

			$initial_data = get_post_meta($post_id, '_downloadable_files', true);
			$modified_data = $initial_data;

			$wildcard = WP_Sheet_Editor_Formulas::$wildcard;

			// Replace
			if ($raw_form_data['action_name'] === 'replace') {
				$search = $raw_form_data['formula_data'][0];
				$replace = $raw_form_data['formula_data'][1];

				if (strpos($search, $wildcard) !== false) {
					$regex_search = str_replace(preg_quote($wildcard, '/'), '.', preg_quote($search, '/'));
				}
				foreach ($modified_data as $file_key => $file) {

					if (strpos($search, $wildcard) !== false) {
						$modified_data[$file_key]['file'] = preg_replace("/$regex_search/", $replace, $file['file']);
					} else {
						$modified_data[$file_key]['file'] = str_replace($search, $replace, $file['file']);
					}

					// Update the file "name" only if the file was modified by the formula
					if ($modified_data[$file_key]['file'] !== $initial_data[$file_key]['file']) {
						$modified_data[$file_key]['name'] = basename($modified_data[$file_key]['file']);
					}
				}
			} elseif ($raw_form_data['action_name'] === 'set_value') {
				$files = explode(',', $raw_form_data['formula_data'][0]);

				$modified_data = array();
				foreach ($files as $new_file) {
					$modified_data[] = array(
						'name' => basename($new_file),
						'file' => $new_file
					);
				}
			} elseif ($raw_form_data['action_name'] === 'append') {
				$files = explode(',', $raw_form_data['formula_data'][0]);

				foreach ($files as $new_file) {
					$modified_data[] = array(
						'name' => basename($new_file),
						'file' => $new_file
					);
				}
			}


			$response = $this->_save_download_files(array_values($modified_data), $post_id);

			$out = array(
				'initial_data' => $initial_data,
				'modified_data' => $modified_data,
			);
			return $out;
		}

		function format_sale_dates($data, $query, $spreadsheet_columns) {
			if ($query['post_type'] !== $this->post_type || (!isset($spreadsheet_columns['_sale_price_dates_from']) && !isset($spreadsheet_columns['_sale_price_dates_to']) )) {
				return $data;
			}

		VGSE()->helpers->profile_record('Before ' . __FUNCTION__);
			foreach ($data as $row_index => $row) {
				if (!empty($row['_sale_price_dates_from']) && is_numeric($row['_sale_price_dates_from'])) {
					$data[$row_index]['_sale_price_dates_from'] = date('Y-m-d', $row['_sale_price_dates_from']);
				}
				if (!empty($row['_sale_price_dates_to']) && is_numeric($row['_sale_price_dates_to'])) {
					$data[$row_index]['_sale_price_dates_to'] = date('Y-m-d', $row['_sale_price_dates_to']);
				}
			}
		VGSE()->helpers->profile_record('After ' . __FUNCTION__);
			return $data;
		}

		function calculate_inventory_totals($data, $qry) {
			global $wpdb;
			if ($qry['post_type'] !== $this->post_type) {
				return $data;
			}

			// We use custom queries for performance reasons.

			$main_query_sql = $GLOBALS['wpse_main_query']->request;
			$main_products_ids_sql = str_replace(array(
				'SELECT SQL_CALC_FOUND_ROWS',
					), array(
				'SELECT ',
					), $main_query_sql);
			$main_products_ids_sql = substr($main_products_ids_sql, 0, strpos($main_products_ids_sql, ' ORDER BY '));
			$variable_products_ids_sql = "SELECT ID FROM $wpdb->posts WHERE post_parent IN (" . $main_products_ids_sql . ")";


			$main_products_sql = "SELECT SUM(m1.meta_value) as stock, SUM(m1.meta_value * m2.meta_value) as price FROM $wpdb->postmeta as m1 JOIN $wpdb->postmeta as m2 ON m1.post_id = m2.post_id WHERE m1.meta_key = '_stock' AND m2.meta_key = '_regular_price' AND m1.post_id IN (" . $main_products_ids_sql . ") GROUP BY m2.post_id ";
			$variable_products_sql = "SELECT SUM(m1.meta_value) as stock, SUM(m1.meta_value * m2.meta_value) as price FROM $wpdb->postmeta as m1 JOIN $wpdb->postmeta as m2 ON m1.post_id = m2.post_id  WHERE m1.meta_key = '_stock' AND m2.meta_key = '_regular_price' AND m1.post_id IN (" . $variable_products_ids_sql . ") GROUP BY m2.post_id  ";

			$main_products_results = $wpdb->get_results($main_products_sql, ARRAY_A);
			$variable_products_results = $wpdb->get_results($variable_products_sql, ARRAY_A);

			$main_products_stock_total = (int) array_sum(wp_list_pluck($main_products_results, 'stock'));
			$variable_products_stock_total = (int) array_sum(wp_list_pluck($variable_products_results, 'stock'));

			$main_products_price_total = (int) array_sum(wp_list_pluck($main_products_results, 'price'));
			$variable_products_price_total = (int) array_sum(wp_list_pluck($variable_products_results, 'price'));

			$total_units = $main_products_stock_total + $variable_products_stock_total;
			$total_inventory_price = $main_products_price_total + $variable_products_price_total;

			$data['total_inventory_units'] = $total_units;
			$data['total_inventory_price'] = wc_price($total_inventory_price);

			return $data;
		}

		function enqueue_assets() {
			$current_post = VGSE()->helpers->get_provider_from_query_string();

			if ($current_post !== $this->post_type) {
				return;
			}

			$pages_to_load_assets = VGSE()->frontend_assets_allowed_on_pages();
			if (!empty($_GET['page']) && in_array($_GET['page'], $pages_to_load_assets)) {

				$this->_enqueue_assets();
			}
		}

		function _enqueue_assets() {

			wp_enqueue_script('wp-sheet-editor-wc-attributes', plugins_url('/assets/js/init.js', VGSE_WC_FILE), array('jquery'), true);
			wp_localize_script('wp-sheet-editor-wc-attributes', 'vgse_wc_attr_data', array(
				'texts' => array(
					'variations_on_reload_needed' => __('We need to reload the Spreadsheet to load the variations. If you have unsaved changes you should save them now or you will lose those changes. Do you want to reload now?', VGSE()->textname),
					'variations_off_reload_needed' => __('We need to reload the Spreadsheet to remove the variations. If you have unsaved changes you should save them now or you will lose those changes. Do you want to reload now?', VGSE()->textname),
				),
			));
		}

		/**
		 * Convert spreadsheet rows array to WC API format
		 * @param array $rows
		 * @return array
		 */
		function convert_row_to_api_format($rows = array()) {
			$products = array();
			$out = array();

			$rows = VGSE()->helpers->add_post_type_to_rows($rows);


			$parent_products = wp_list_filter($rows, array(
				'post_type' => $this->post_type
			));

			$parent_products_ids = wp_list_pluck($parent_products, 'ID');

			$variations_rows = wp_list_filter($rows, array(
				'post_type' => 'product_variation'
			));

			foreach ($variations_rows as $data_obj) {
				$id = (int) $data_obj['ID'];
				$post_obj = get_post($id);
				$parent_id = $post_obj->post_parent;

				if (!isset($products[$parent_id])) {
					$products[$parent_id] = array();
				}
				$products[$parent_id][$id] = $data_obj;

				if (!in_array($parent_id, $parent_products_ids) && !isset($parent_products[$parent_id])) {
					$parent_products[$parent_id] = array(
						'ID' => $parent_id
					);
				}
			}



			if (empty($parent_products)) {
				return $out;
			}


			foreach ($parent_products as $parent_product) {
				// Es necesario solo cuando $parent_product es un objeto
				// $parent_product = get_object_vars($parent_product);

				$new_data = array();
				if (isset($parent_product['ID'])) {
					$new_data['ID'] = $parent_product['ID'];
				}
				if (isset($parent_product['post_title'])) {
					$new_data['name'] = $parent_product['post_title'];
				}
				if (isset($parent_product['_downloadable_files'])) {
					$new_data['downloads'] = $parent_product['_downloadable_files'];
				}
				if (isset($parent_product['_download_type'])) {
					$new_data['download_type'] = (!empty($parent_product['_download_type'])) ? $parent_product['_download_type'] : 'standard';
				}
				if (isset($parent_product['product_shipping_class'])) {
					$term = get_term_by('name', $parent_product['product_shipping_class'], 'product_shipping_class');
					$new_data['shipping_class'] = $term->slug;
				}
				if (isset($parent_product['_download_expiry'])) {
					$new_data['download_expiry'] = (int) $parent_product['_download_expiry'];
				}
				if (isset($parent_product['_download_limit'])) {
					$new_data['download_limit'] = $parent_product['_download_limit'];
				}
				if (isset($parent_product['post_name'])) {
					$new_data['slug'] = $parent_product['post_name'];
				}
				if (isset($parent_product['content'])) {
					$new_data['description'] = $parent_product['content'];
				}
				if (isset($parent_product['date'])) {
					$new_data['date_created'] = $parent_product['date'];
				}
				if (isset($parent_product['excerpt'])) {
					$new_data['short_description'] = $parent_product['excerpt'];
				}
				if (isset($parent_product['status'])) {
					$new_data['status'] = $parent_product['status'];
				}
				if (isset($parent_product['comment_status'])) {
					$new_data['reviews_allowed'] = $this->_do_booleable($parent_product['comment_status']);
				}

				$taxonomies = get_object_taxonomies($this->post_type, 'objects');

				if (!empty($taxonomies) && is_array($taxonomies)) {
					foreach ($taxonomies as $taxonomy) {
						if (strpos($taxonomy->name, 'pa_') === false) {
							continue;
						}
						$taxonomy_key = $taxonomy->name;

						if (isset($parent_product[$taxonomy_key])) {
							if (!isset($new_data['attributes'])) {
								$new_data['attributes'] = array();
							}
							$brands = explode(',', $parent_product[$taxonomy_key]);
							$outbrand = array();
							foreach ($brands as $marca) {
								$brand = get_term_by('name', $marca, $taxonomy_key);
								$outbrand[] = $brand;
							}

							$new_data['attributes'][] = array(
								'name' => $taxonomy_key,
								'option' => $outbrand,
							);
						}
					}
				}
				if (isset($parent_product['_thumbnail_id'])) {
					$new_data['images'] = array(
						array(
							'id' => $parent_product['_thumbnail_id'],
							'position' => 0
						)
					);
				}
				if (isset($parent_product['product_cat'])) {
					$cats = explode(',', $parent_product['product_cat']);
					$outcat = array();
					foreach ($cats as $cate) {
						$cat = get_term_by('name', $cate, 'product_cat');
						$outcat[] = $cat;
					}

					$new_data['categories'] = $outcat;
				}
				if (isset($parent_product['product_tag'])) {
					$tags = explode(',', $parent_product['product_tag']);
					$outtag = array();
					foreach ($tags as $eti) {
						$tag = get_term_by('name', $eti, 'product_tag');
						$outtag[] = $tag;
					}

					$new_data['tags'] = $outtag;
				}
				if (isset($parent_product['_sku'])) {
					$new_data['sku'] = $parent_product['_sku'];
				}
				if (isset($parent_product['_regular_price'])) {
					$new_data['regular_price'] = $parent_product['_regular_price'];
				}
				if (isset($parent_product['_sale_price'])) {
					$new_data['sale_price'] = $parent_product['_sale_price'];
				}
				if (isset($parent_product['_weight'])) {
					$new_data['weight'] = $parent_product['_weight'];
				}
				if (isset($parent_product['_height'])) {
					$new_data['dimensions']['height'] = $parent_product['_height'];
				}
				if (isset($parent_product['_length'])) {
					$new_data['dimensions']['length'] = $parent_product['_length'];
				}
				if (isset($parent_product['_width'])) {
					$new_data['dimensions']['width'] = $parent_product['_width'];
				}
				if (isset($parent_product['_manage_stock'])) {
					$new_data['manage_stock'] = $this->_do_booleable($parent_product['_manage_stock']);
				}
				if (isset($parent_product['_stock_status'])) {
					$new_data['in_stock'] = $this->_do_booleable($parent_product['_stock_status']);
				}
				if (isset($parent_product['_stock'])) {
					$new_data['stock_quantity'] = $parent_product['_stock'];
				}
				if (isset($parent_product['_visibility'])) {
					$new_data['visible'] = $parent_product['_visibility'];
				}
				if (isset($parent_product['_product_image_gallery'])) {
					if (!isset($new_data['images'])) {
						$new_data['images'] = array();
					}
					$gallery = explode(',', $parent_product['_product_image_gallery']);

					foreach ($gallery as $image_index => $image_id) {
						$new_data['images'][] = array(
							'id' => (int) $image_id,
							'position' => $image_index + 1
						);
					}
				}
				if (isset($parent_product['_downloadable'])) {
					$new_data['downloadable'] = $this->_do_booleable($parent_product['_downloadable']);
				}
				if (isset($parent_product['_virtual'])) {
					$new_data['virtual'] = $this->_do_booleable($parent_product['_virtual']);
				}
				if (isset($parent_product['_sale_price_dates_from'])) {
					if (!empty($parent_product['_sale_price_dates_from'])) {
						$parent_product['_sale_price_dates_from'] = date('Y-m-d', strtotime($parent_product['_sale_price_dates_from']));
					}
					$new_data['date_on_sale_from'] = $parent_product['_sale_price_dates_from'];
				}
				if (isset($parent_product['_sale_price_dates_to'])) {
					if (!empty($parent_product['_sale_price_dates_to'])) {
						$parent_product['_sale_price_dates_to'] = date('Y-m-d', strtotime($parent_product['_sale_price_dates_to']));
					}
					$new_data['date_on_sale_to'] = $parent_product['_sale_price_dates_to'];
				}
				if (isset($parent_product['_sold_individually'])) {
					$new_data['sold_individually'] = $this->_do_booleable($parent_product['_sold_individually']);
				}
				if (isset($parent_product['_featured'])) {
					$new_data['featured'] = $this->_do_booleable($parent_product['_featured']);
				}
				if (isset($parent_product['_backorders'])) {
					$new_data['backorders'] = $parent_product['_backorders'];
				}
				if (isset($parent_product['_purchase_note'])) {
					$new_data['purchase_note'] = $parent_product['_purchase_note'];
				}

				if (isset($products[$parent_product['ID']])) {
					foreach ($products[$parent_product['ID']] as $index => $variation) {
						if (!isset($new_data['variations'])) {
							$new_data['variations'] = array();
						}
						if (isset($variation['ID'])) {
							$new_data['variations'][$index]['id'] = $variation['ID'];
						}

						if (isset($variation['_variation_description'])) {
							$new_data['variations'][$index]['description'] = $variation['_variation_description'];
						}
						if (isset($variation['post_title'])) {
							$new_data['variations'][$index]['name'] = $variation['post_title'];
						}
						if (isset($variation['_downloadable_files'])) {
							$new_data['variations'][$index]['downloads'] = $variation['_downloadable_files'];
						}
						if (isset($variation['_download_type'])) {
							$new_data['variations'][$index]['download_type'] = (!empty($variation['_download_type'])) ? $variation['_download_type'] : 'standard';
						}
						if (isset($variation['product_shipping_class'])) {
							$term = get_term_by('name', $variation['product_shipping_class'], 'product_shipping_class');
							$new_data['variations'][$index]['shipping_class'] = $term->slug;
						}
						if (isset($variation['_download_expiry'])) {
							$new_data['variations'][$index]['download_expiry'] = (int) $variation['_download_expiry'];
						}
						if (isset($variation['_download_limit'])) {
							$new_data['variations'][$index]['download_limit'] = $variation['_download_limit'];
						}
						if (isset($variation['post_name'])) {
							$new_data['variations'][$index]['slug'] = $variation['post_name'];
						}

						if (isset($variation['_vgse_variation_enabled'])) {
							$new_data['variations'][$index]['visible'] = $this->_do_booleable($variation['_vgse_variation_enabled']);
						}
						if (isset($variation['comment_status'])) {
							$new_data['variations'][$index]['reviews_allowed'] = $this->_do_booleable($variation['comment_status']);
						}
						$taxonomies = get_object_taxonomies($this->post_type, 'objects');

						if (!empty($taxonomies) && is_array($taxonomies)) {
							foreach ($taxonomies as $taxonomy) {
								if (strpos($taxonomy->name, 'pa_') === false) {
									continue;
								}
								$taxonomy_key = $taxonomy->name;

								if (isset($variation[$taxonomy_key])) {
									if (!isset($new_data['variations'][$index]['attributes'])) {
										$new_data['variations'][$index]['attributes'] = array();
									}
									// We save only the first term
									$brand_raw = current(explode(',', $variation[$taxonomy_key]));
									$brand = get_term_by('name', $brand_raw, $taxonomy_key);
									$brand_slug = $brand->slug;

									$new_data['variations'][$index]['attributes'][$taxonomy_key] = array(
										'id' => wc_attribute_taxonomy_id_by_name($taxonomy_key),
										'name' => $taxonomy_key,
										'option' => $brand_slug,
									);
								}
							}
						}
						if (isset($variation['_thumbnail_id'])) {
							$new_data['variations'][$index]['image'] = array(array(
									'id' => $variation['_thumbnail_id'],
									'position' => 0
							));
						}
						if (isset($variation['_sku'])) {
							$new_data['variations'][$index]['sku'] = $variation['_sku'];
						}
						if (isset($variation['_regular_price'])) {
							$new_data['variations'][$index]['regular_price'] = $variation['_regular_price'];
						}
						if (isset($variation['_sale_price'])) {
							$new_data['variations'][$index]['sale_price'] = $variation['_sale_price'];
						}
						if (isset($variation['_weight'])) {
							$new_data['variations'][$index]['weight'] = $variation['_weight'];
						}
						if (isset($variation['_height'])) {
							$new_data['variations'][$index]['dimensions']['height'] = $variation['_height'];
						}
						if (isset($variation['_length'])) {
							$new_data['variations'][$index]['dimensions']['length'] = $variation['_length'];
						}
						if (isset($variation['_width'])) {
							$new_data['variations'][$index]['dimensions']['width'] = $variation['_width'];
						}
						if (isset($variation['_manage_stock'])) {
							$new_data['variations'][$index]['manage_stock'] = $this->_do_booleable($variation['_manage_stock']);
						}
						if (isset($variation['_stock_status'])) {
							$new_data['variations'][$index]['in_stock'] = $this->_do_booleable($variation['_stock_status']);
						}
						if (isset($variation['_stock'])) {
							$new_data['variations'][$index]['stock_quantity'] = (int) $variation['_stock'];
						}
						if (isset($variation['_visibility'])) {
							$new_data['variations'][$index]['visible'] = $variation['_visibility'];
						}
						if (isset($variation['_downloadable'])) {
							$new_data['variations'][$index]['downloadable'] = $this->_do_booleable($variation['_downloadable']);
						}
						if (isset($variation['_virtual'])) {
							$new_data['variations'][$index]['virtual'] = $this->_do_booleable($variation['_virtual']);
						}
						if (isset($variation['_sale_price_dates_from'])) {
							if (!empty($variation['_sale_price_dates_from'])) {
								$variation['_sale_price_dates_from'] = date('Y-m-d', strtotime($variation['_sale_price_dates_from']));
							}
							$new_data['variations'][$index]['date_on_sale_from'] = $variation['_sale_price_dates_from'];
						}
						if (isset($variation['_sale_price_dates_to'])) {
							if (!empty($variation['_sale_price_dates_to'])) {
								$variation['_sale_price_dates_to'] = date('Y-m-d', strtotime($variation['_sale_price_dates_to']));
							}
							$new_data['variations'][$index]['date_on_sale_to'] = $variation['_sale_price_dates_to'];
						}
						if (isset($variation['_sold_individually'])) {
							$new_data['variations'][$index]['sold_individually'] = $this->_do_booleable($variation['_sold_individually']);
						}
						if (isset($variation['_backorders'])) {
							$new_data['variations'][$index]['backorders'] = $variation['_backorders'];
						}
						if (isset($variation['_purchase_note'])) {
							$new_data['variations'][$index]['purchase_note'] = $variation['_purchase_note'];
						}
					}
				}

				$out[] = $new_data;
			}
			return $out;
		}

		/**
		 * Convert a value to boolean
		 * @param str|bool $item
		 * @return boolean
		 */
		function _do_booleable($item) {
			if (in_array($item, array('yes', 'instock', 'open', '1', 1, true, 'true'), true)) {
				return true;
			}
			return false;
		}

		/**
		 * Ejemplo de uso: $this->update_products_with_api( $this->convert_row_to_api_format( $rows ) );
		 */
		function update_products_with_api($product) {
			if (isset($product['ID'])) {
				$put = VGSE()->helpers->create_rest_request('PUT', '/wc/v1/products/' . $product['ID'], $product);
				return $put;
			} else {
				$post = VGSE()->helpers->create_rest_request('POST', '/wc/v1/products', $product);
				return $post;
			}
		}

		function _get_product_id_from_search($search_value) {

			$product_parts = explode('--', $search_value);
			return (int) end($product_parts);
		}

		/**
		 * Get download files via ajax
		 */
		function get_download_files() {
			$data = VGSE()->helpers->clean_data($_REQUEST);

			if (!wp_verify_nonce($data['nonce'], 'bep-nonce')) {
				wp_send_json_error(array('message' => __('You dont have enough permissions to view this page.', VGSE()->textname)));
			}
			if (empty($data['product_id'])) {
				wp_send_json_error(array('message' => __('Please select a product.', VGSE()->textname)));
			}

			$product_id = (int) VGSE()->WC->_get_product_id_from_search($data['product_id']);

			$out = maybe_unserialize(get_post_meta($product_id, '_downloadable_files', true));

			wp_send_json_success($out);
		}

		/**
		 * Save dowload files via ajax
		 */
		function save_download_files() {
			$data = VGSE()->helpers->clean_data($_REQUEST);

			if (!wp_verify_nonce($data['nonce'], 'bep-nonce')) {
				wp_send_json_error(array('message' => __('You dont have enough permissions to view this page.', VGSE()->textname)));
			}
			$post_id = (int) $data['postId'];

			$response = $this->_save_download_files($data['data'], $post_id);

			if ($response) {
				wp_send_json_success(array('message' => __('Files saved.', VGSE()->textname)));
			}

			wp_send_json_error(array('message' => __('The files could not be saved.', VGSE()->textname)));
		}

		function _save_download_files($data, $post_id) {

			$formatted = current($this->convert_row_to_api_format(array(array(
							'ID' => $post_id,
							'_downloadable' => true,
							'_downloadable_files' => $data
			))));
			$api_response = $this->update_products_with_api($formatted);

			$out = false;
			if ($api_response->status === 200 || $api_response->status === 201) {
				$out = true;
			}

			return $out;
		}

		/**
		 * Save product price.
		 * 
		 * This handles synchronization between regular and sale prices, and sale price dates.
		 *
		 * @param int $product_id
		 * @param float $regular_price
		 * @param float $sale_price
		 * @param string $date_from
		 * @param string $date_to
		 */
		function _sync_produce_price($product_id, $modified_data = array()) {


			// sync prices
			$product_price_keys = array('_price', '_regular_price', '_sale_price', '_sale_price_dates_from', '_sale_price_dates_to');
			$price_related_fields_were_updated = array_intersect($product_price_keys, $modified_data);

			// Exit If prices are already synced for this product, or if no price related field was updated
			if (in_array($product_id, $this->wc_after_save_prices_synced) || empty($price_related_fields_were_updated)) {
				return;
			}
			$this->wc_after_save_prices_synced[] = $product_id;

			$post_meta = get_post_meta($product_id);
			$price_data = wp_array_slice_assoc($post_meta, $product_price_keys);

			$regular_price = (!empty($price_data['_regular_price'])) ? $price_data['_regular_price'][0] : '';
			$sale_price = (!empty($price_data['_sale_price'])) ? $price_data['_sale_price'][0] : '';
			$date_from = (!empty($price_data['_sale_price_dates_from'])) ? $price_data['_sale_price_dates_from'][0] : '';
			$date_to = (!empty($price_data['_sale_price_dates_to'])) ? $price_data['_sale_price_dates_to'][0] : '';

			$product_id = absint($product_id);
			$regular_price = wc_format_decimal($regular_price);
			$sale_price = $sale_price === '' ? '' : wc_format_decimal($sale_price);
			$date_from = wc_clean($date_from);
			$date_to = wc_clean($date_to);

			// Convert dates to friendly format
			$date_from = ( is_numeric($date_from) ) ? date('Y-m-d', $date_from) : $date_from;
			$date_to = ( is_numeric($date_to) ) ? date('Y-m-d', $date_to) : $date_to;

			if (!empty($date_from)) {
				update_post_meta($product_id, '_sale_price_dates_from', strtotime($date_from));
			}
			if (!empty($date_to)) {
				update_post_meta($product_id, '_sale_price_dates_to', strtotime($date_to));
			}

			// Auto set date_from if empty
			if ($date_to && !$date_from) {
				update_post_meta($product_id, '_sale_price_dates_from', strtotime('NOW', current_time('timestamp')));
			}

			// Update price if on sale
			if ('' !== $sale_price && '' === $date_to && '' === $date_from) {
				update_post_meta($product_id, '_price', $sale_price);
			} else {
				update_post_meta($product_id, '_price', $regular_price);
			}

			if ('' !== $sale_price && $date_from && strtotime($date_from) < strtotime('NOW', current_time('timestamp'))) {
				update_post_meta($product_id, '_price', $sale_price);
			}

			if ($date_to && strtotime($date_to) < strtotime('NOW', current_time('timestamp'))) {
				update_post_meta($product_id, '_price', $regular_price);
				update_post_meta($product_id, '_sale_price_dates_from', '');
				update_post_meta($product_id, '_sale_price_dates_to', '');
			}
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
		 * Modify spreadsheet columns settings.
		 * 
		 * It changes the names and settings of some columns.
		 * @param array $spreadsheet_columns
		 * @param string $post_type
		 * @param bool $exclude_formatted_settings
		 * @return array
		 */
		function filter_columns_settings($spreadsheet_columns, $post_type, $exclude_formatted_settings) {

			if ($post_type !== $this->post_type) {
				return $spreadsheet_columns;
			}

			if (!empty($spreadsheet_columns['excerpt'])) {
				$spreadsheet_columns['excerpt']['title'] = __('Short description', VGSE()->textname);
				$spreadsheet_columns['excerpt']['unformatted'] = array('data' => 'excerpt', 'renderer' => 'html', 'readOnly' => true);
				$spreadsheet_columns['excerpt']['formatted'] = array('data' => 'excerpt', 'renderer' => 'html', 'readOnly' => true);
				$spreadsheet_columns['excerpt']['type'] = 'boton_tiny';
				$spreadsheet_columns['excerpt']['column_width'] = 150;
				$spreadsheet_columns['excerpt']['allow_to_save'] = false;
			}
			if (!empty($spreadsheet_columns['comment_status'])) {
				$spreadsheet_columns['comment_status']['title'] = __('Enable reviews', VGSE()->textname);
			}

			$readonly_columns = array('_wc_average_rating', 'total_sales', '_wc_review_count');
			foreach ($readonly_columns as $readonly_column) {
				if (!empty($spreadsheet_columns[$readonly_column])) {
					$spreadsheet_columns[$readonly_column]['unformatted'] = array('data' => $readonly_column, 'renderer' => 'html', 'readOnly' => true);
					$spreadsheet_columns[$readonly_column]['formatted'] = array('data' => $readonly_column, 'renderer' => 'html', 'readOnly' => true);
				}
			}

			// Remove private columns
			$private_columns = array('_price', '_wc_rating_count', '_product_version');
			foreach ($private_columns as $private_column) {
				if (!empty($spreadsheet_columns[$private_column])) {
					unset($spreadsheet_columns[$private_column]);
				}
			}
			return $spreadsheet_columns;
		}

		function product_updated_on_spreadsheet($product_id, $item, $data, $post_type, $spreadsheet_columns, $settings) {
			if (!in_array($post_type, array($this->post_type, 'product_variation'))) {
				return;
			}
			$this->_sync_produce_price($product_id, array_keys($item));
		}

		/**
		 * The product was updated
		 * @param string $post_type
		 * @param int $post_id
		 * @param string $key
		 * @param mixed $new_value
		 * @param array $cell_args
		 * @param array $spreadsheet_columns
		 * @return null
		 */
		function product_cell_updated_on_spreadsheet($post_type, $post_id, $key, $new_value, $cell_args, $spreadsheet_columns) {
			if ($post_type !== $this->post_type) {
				return;
			}

			$this->_sync_product_terms($post_id, $new_value, $key, $cell_args['data_type']);
		}

		/**
		 * The product was updated using a formula
		 * @param int $post_id
		 * @param string $initial_data
		 * @param string $modified_data
		 * @param string $column
		 * @param string $formula
		 * @param string $post_type
		 * @param array $cell_args
		 * @param array $spreadsheet_columns
		 * @return null
		 */
		function product_updated_with_formula($post_id, $initial_data, $modified_data, $column, $formula, $post_type, $cell_args, $spreadsheet_columns) {
			if ($post_type !== $this->post_type) {
				return;
			}

			$this->_sync_product_terms($post_id, $modified_data, $column, $cell_args['data_type']);
			$this->_sync_produce_price($post_id, array($column));
		}

		/**
		 * Callback when the product was updated.
		 * @param int $product_id
		 * @param mixed $new_value
		 * @param string $key
		 * @param string $data_source
		 */
		function _sync_product_terms($product_id, $new_value, $key, $data_source) {


			// sync woocommerce attributes
			if ($data_source === 'post_terms' && strpos($key, 'pa_') !== false) {
				$attributes = maybe_unserialize(get_post_meta($product_id, '_product_attributes', true));

				// Add attribute association only if it doesn´t exist.
				if (!isset($attributes[sanitize_title($key)])) {
					if (empty($attributes) || !is_array($attributes)) {
						$attributes = array();
					}
					$attributes[sanitize_title($key)] = array(
						'name' => wc_clean($key),
						'value' => '',
						'is_visible' => 1,
						'is_taxonomy' => 1
					);
					update_post_meta($product_id, '_product_attributes', $attributes);
				}
			}
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
				$api_response = $this->update_products_with_api(array(
					'name' => __('...', VGSE()->textname)
				));

				if ($api_response->status === 200 || $api_response->status === 201) {
					$api_data = $api_response->get_data();
					$post_ids[] = $api_data['id'];
				}
			}

			return $post_ids;
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
			$editor->args['columns']->register_item('_crosssell_ids', $post_type, array(
				'data_type' => 'meta_data',
				'unformatted' => array('data' => '_crosssell_ids'),
				'column_width' => 150,
				'title' => __('Cross-sells', 'woocommerce'),
				'type' => '',
				'supports_formulas' => true,
				'formatted' => array('data' => '_crosssell_ids'),
				'allow_to_hide' => true,
				'allow_to_rename' => true,
			));
			$editor->args['columns']->register_item('_upsell_ids', $post_type, array(
				'data_type' => 'meta_data',
				'unformatted' => array('data' => '_upsell_ids'),
				'column_width' => 150,
				'title' => __('Upsells', VGSE()->textname),
				'type' => '',
				'supports_formulas' => true,
				'formatted' => array('data' => '_upsell_ids'),
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


			$visibility_taxonomy = 'product_visibility';
			$editor->args['columns']->register_item($visibility_taxonomy, $post_type, array(
				'data_type' => 'post_terms',
				'unformatted' => array('data' => $visibility_taxonomy),
				'column_width' => 150,
				'title' => __('Visibility', VGSE()->textname),
				'type' => '',
				'supports_formulas' => true,
				'formatted' => array('data' => $visibility_taxonomy, 'type' => 'autocomplete', 'source' => array(VGSE()->data_helpers, 'get_taxonomy_terms'), 'callback_args' => array($visibility_taxonomy)),
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
					'checkedTemplate' => 'featured',
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
				'supports_formulas' => true,
				'value_type' => 'wc_downloadable_files',
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
				'supports_formulas' => true,
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
				'supports_formulas' => true,
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

		function __set($name, $value) {
			$this->$name = $value;
		}

		function __get($name) {
			return $this->$name;
		}

	}

	add_action('vg_sheet_editor/initialized', 'vgse_woocommerce_init');

	function vgse_woocommerce_init() {
		WP_Sheet_Editor_WooCommerce::get_instance();
		VGSE()->WC = WP_Sheet_Editor_WooCommerce::get_instance();
	}

}