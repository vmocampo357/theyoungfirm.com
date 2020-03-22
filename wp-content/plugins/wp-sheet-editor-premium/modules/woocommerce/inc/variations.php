<?php
if (!class_exists('WP_Sheet_Editor_WooCommerce_Variations')) {

	/**
	 * Display woocommerce item in the toolbar to tease users of the free 
	 * version into purchasing the premium plugin.
	 */
	class WP_Sheet_Editor_WooCommerce_Variations {

		static private $instance = false;
		var $post_type = null;
		var $wc_variation_columns = null;

		private function __construct() {
			
		}

		/**
		 * Register toolbar item
		 */
		function register_toolbar_items($editor) {

			$editor->args['toolbars']->register_item('create_variation', array(
				'type' => 'button', // html | switch | button
				'content' => __('Create variations', VGSE()->textname),
				'id' => 'create-variation',
				'help_tooltip' => __('Create variations for variable products.', VGSE()->textname),
				'extra_html_attributes' => 'data-remodal-target="create-variation-modal"',
					), $this->post_type);

			$editor->args['toolbars']->register_item('display_variations', array(
				'type' => 'switch', // html | switch | button
				'content' => __('Display variations', VGSE()->textname),
				'id' => 'display-variations',
				'help_tooltip' => __('When this is enabled the products variations will be displayed and you will able to edit them.', VGSE()->textname),
				'default_value' => false
					), $this->post_type);
		}

		/**
		 * Add a lock icon to the cells enabled for variations or products.
		 * 
		 * @param array $posts Rows for display in spreadsheet
		 * @param array $wp_query Arguments used to query the posts.
		 * @param array $spreadsheet_columns
		 * @param array $request_data Data received in the ajax request
		 * @return array
		 */
		function maybe_lock_general_columns_in_variations($posts, $wp_query, $spreadsheet_columns, $request_data) {
			if ($wp_query['post_type'] !== $this->post_type || empty($posts) || !is_array($posts)) {
				return $posts;
			}
			VGSE()->helpers->profile_record('Before ' . __FUNCTION__);

			$products = wp_list_filter($posts, array(
				'post_type' => $this->post_type
			));
			$first_product_keys = array_keys(current($products));

			$whitelist_variations = $this->get_variation_whitelisted_columns();

			$locked_keys_in_variations = array_diff($first_product_keys, $whitelist_variations);

			$locked_keys_in_general = array_diff($whitelist_variations, $first_product_keys);

			$columns_with_visibility = array_keys($spreadsheet_columns);

			$lock_icon = '<i class="fa fa-lock vg-cell-blocked vg-variation-lock"></i>';

			foreach ($posts as $index => $post) {


				if ($post['post_type'] === $this->post_type) {
					$locked_keys = $locked_keys_in_general;
				} else {
					$locked_keys = $locked_keys_in_variations;
					$locked_keys[] = 'post_title';
				}
				if (isset($posts[$index]['_stock'])) {
					$posts[$index]['_stock'] = (int) $posts[$index]['_stock'];
				}
				foreach ($locked_keys as $locked_key) {

					if (isset($posts[$index][$locked_key]) && strpos($posts[$index][$locked_key], 'vg-cell-blocked') !== false || !in_array($locked_key, $columns_with_visibility)) {
						continue;
					}
					if (!isset($posts[$index][$locked_key])) {
						$posts[$index][$locked_key] = '';
					}
					if (in_array($locked_key, array('title', 'post_title'))) {
						$posts[$index][$locked_key] = $lock_icon . ' ' . $posts[$index][$locked_key];
					} else {
						$posts[$index][$locked_key] = $lock_icon;
					}
				}
			}

			VGSE()->helpers->profile_record('After ' . __FUNCTION__);
			return $posts;
		}

		/**
		 * Are variations enabled in the spreadsheet according to the request data?
		 * @param str $post_type
		 * @param array $request_data Data received in the ajax request
		 * @return boolean
		 */
		function variations_enabled($post_type, $request_data) {
			if (empty($request_data['filters'])) {
				return false;
			}

			parse_str(html_entity_decode($request_data['filters']), $filters);

			if (empty($filters['wc_display_variations']) || $post_type !== $this->post_type) {
				return false;
			}

			return true;
		}

		/**
		 * Include variations posts to the posts list before processing.
		 * @param type $posts
		 * @param type $wp_query
		 * @param array $request_data Data received in the ajax request
		 * @return array
		 */
		function maybe_include_variations_posts($posts, $wp_query, $request_data) {

			if (!$this->variations_enabled($wp_query['post_type'], $request_data) || empty($posts) || !is_array($posts)) {
				return $posts;
			}

			$post_ids = wp_list_pluck($posts, 'ID');

			// Get variations
			$variations_query = new WP_Query(array(
				'post_type' => 'product_variation',
				'posts_per_page' => -1,
				'post_parent__in' => $post_ids,
				'orderby' => array('menu_order' => 'ASC', 'ID' => 'ASC'),
			));

			if (!$variations_query->have_posts()) {
				return $posts;
			}

			// Cache list of variations for future use
			$GLOBALS['variations_query'] = $variations_query;

			$new_posts = array();

			foreach ($posts as $post) {
				$new_posts[] = $post;

				$product = wc_get_product($post->ID);
				if (!$product->is_type('variable')) {
					continue;
				}

				$product_variations = wp_list_filter($variations_query->posts, array(
					'post_parent' => $post->ID
				));

				$new_posts = array_merge($new_posts, $product_variations);
			}
			return $new_posts;
		}

		function init() {
			$this->post_type = apply_filters('vg_sheet_editor/woocommerce/product_post_type_key', 'product');

			$this->wc_variation_only_columns = array(
				'_vgse_variation_enabled',
				'_variation_description',
			);

			// We need to set the properties
			$this->get_variation_whitelisted_columns();

			// Register toolbar button to enable the display of variations and create variations
			add_action('vg_sheet_editor/editor/before_init', array(
				$this,
				'register_toolbar_items'
			));

			// Filter load_rows to include variations if toolbar item is enabled.
			// The general fields will contain he same info as the parent post.
			add_action('vg_sheet_editor/load_rows/output', array(
				$this,
				'maybe_modify_variations_output'
					), 10, 4);

			// Filter load_rows to preload variations custom data
			add_action('vg_sheet_editor/load_rows/found_posts', array(
				$this,
				'maybe_include_variations_posts'
					), 10, 3);

			// Filter load_rows output to remove data in general columns and display a lock icon instead, also modify some columns values
			add_action('vg_sheet_editor/load_rows/output', array(
				$this,
				'maybe_lock_general_columns_in_variations'
					), 10, 4);


			// Exclude variations from the saving list
			add_action('vg_sheet_editor/save_rows/incoming_data', array(
				$this,
				'exclude_variations_from_saving_list'
					), 10, 2);

			// Save variations
			add_action('vg_sheet_editor/save_rows/after_saving_rows', array(
				$this,
				'maybe_save_variations'
					), 10, 4);


			// Render modal for "create variation" button
			add_action('vg_sheet_editor/editor_page/after_content', array(
				$this,
				'render_create_variation_modal'
			));

			// Create variations via ajax
			add_action('wp_ajax_vgse_create_variations', array(
				$this,
				'create_variations_rows'
			));

			// Save default attributes via ajax
			add_action('wp_ajax_vgse_save_default_attributes', array(
				$this,
				'update_default_attributes'
			));

			// When loading posts, disable product columns in variations
			add_action('vg_sheet_editor/load_rows/allowed_post_columns', array(
				$this,
				'disable_general_columns_for_variations'
					), 10, 2);

			// Allow the user to enable the execution of formulas on variations
			add_action('vg_sheet_editor/formulas/after_form_fields', array(
				$this,
				'allow_formulas_on_variations'
			));

			// Filter formulas execution posts query to apply on variations
			add_action('vg_sheet_editor/formulas/execute/posts_query', array(
				$this,
				'execute_formulas_on_variations'
					), 10, 2);

			// When we create the products in the spreadsheet, the variations will be enabled automatically
			// so we display the new rows with the right cells disabled
			add_action('vg_sheet_editor/add_new_posts/get_rows_args', array(
				$this,
				'enable_variations_when_fetching_created_rows'
					), 10, 2);

			add_filter('vg_sheet_editor/provider/post/get_items_terms', array($this, 'get_variation_attributes'), 10, 3);
		}

		function get_variation_whitelisted_columns() {
			$this->wc_variation_columns = array(
				'_vgse_variation_enabled',
				'ID',
				'post_type',
				'_sku',
				'_regular_price',
				'_sale_price',
				'_sale_price_dates_from',
				'_sale_price_dates_to',
				'_downloadable',
				'_virtual',
				'_downloadable_files',
				'_download_expiry',
				'_download_limit',
				'_tax_status',
				'_tax_class',
				'_manage_stock',
				'_stock_status',
				'_stock',
				'_backorders',
				'product_shipping_class',
				'_variation_description',
				'_thumbnail_id',
				'_vgse_create_attribute',
				'_weight',
				'_width',
				'_height',
				'_length',
			);
			$this->wc_core_variation_columns = $this->wc_variation_columns;

			// We enable the global attribute columns for variations too
			$this->wc_variation_columns = array_merge($this->wc_variation_columns, wc_get_attribute_taxonomy_names());
			return apply_filters('vg_sheet_editor/woocommerce/variation_columns', $this->wc_variation_columns);
		}

		function get_variation_attributes($terms, $id, $taxonomy) {
			if (get_post_type($id) !== 'product_variation' || strpos($taxonomy, 'pa_') === false) {
				return $terms;
			}


			$term_slug = get_post_meta($id, 'attribute_' . $taxonomy, true);

			if (!empty($term_slug) && $term = get_term_by('slug', $term_slug, $taxonomy)) {
				$terms = array($term);
			}
			return $terms;
		}

		function enable_variations_when_fetching_created_rows($args) {
			if ($args['post_type'] !== $this->post_type) {
				return $args;
			}

			$args['filters'] .= '&wc_display_variations=yes';
			return $args;
		}

		function execute_formulas_on_variations($query, $query_strings) {
			if ($query['post_type'] !== $this->post_type || empty($query_strings['raw_form_data']['apply_to_variations'])) {
				return $query;
			}
			$parent_products_query = $query;
			$parent_products_query['fields'] = 'ids';
			$parent_products = new WP_Query($parent_products_query);

			if (empty($parent_products->posts)) {
				// If no parent products were found, make sure the wp_query will come back empty
				$query['post__in'] = array(time() * 2);
			} else {
				$query['post_type'] = 'product_variation';
				$query['posts_per_page'] = -1;
				$query['post_parent__in'] = $parent_products->posts;
			}

			return $query;
		}

		function allow_formulas_on_variations($post_type) {
			if ($post_type !== $this->post_type) {
				return;
			}
			?>
			<li class="only-variations-field">
				<label><input type="checkbox" value="yes" name="apply_to_variations"><?php _e('Execute formula only on product variations', VGSE()->textname); ?> <a href="#" class="tipso tipso_style" data-tipso="<?php _e('If disabled the formula will apply only to main products, if enabled it will apply only to product variations.', VGSE()->textname); ?>">( ? )</a></label>								

			</li>
			<?php
		}

		/**
		 * Modify variations fields before returning the spreadsheet rows.
		 * @param type $rows
		 * @param array $wp_query
		 * @param array $spreadsheet_columns
		 * @return array
		 */
		function maybe_modify_variations_output($rows, $wp_query, $spreadsheet_columns) {

			if (empty($rows) || !is_array($rows) || $wp_query['post_type'] !== $this->post_type) {
				return $rows;
			}

			VGSE()->helpers->profile_record('before ' . __FUNCTION__);
			foreach ($rows as $row_index => $post) {

				if (isset($post['_download_expiry']) && $post['_download_expiry'] === '-1') {
					$rows[$row_index]['_download_expiry'] = '';
				}
				if (isset($post['_download_limit']) && $post['_download_limit'] === '-1') {
					$rows[$row_index]['_download_limit'] = '';
				}

				if ($post['post_type'] !== 'product_variation') {
					continue;
				}
				$post_obj = get_post($post['ID']);
				$rows[$row_index]['_vgse_variation_enabled'] = ($post_obj->post_status !== 'publish') ? '' : 'on';



				// Set variation titles
				$parent = get_post($post_obj->post_parent);
				$rows[$row_index]['post_title'] = sprintf(__('Variation of %s, #%s', VGSE()->textname), $parent->post_title, $post_obj->ID);
			}

			VGSE()->helpers->profile_record('After ' . __FUNCTION__);
			return $rows;
		}

		/**
		 * Make sure that product variations dont have the columns exclusive to general products.
		 * @param array $columns
		 * @param obj $post
		 * @return array
		 */
		function disable_general_columns_for_variations($columns, $post) {

			if ($post->post_type !== 'product_variation' && $post->post_type !== $this->post_type) {
				return $columns;
			}

			if ($post->post_type === 'product_variation') {
				$disallowed = array_diff(array_keys($columns), $this->get_variation_whitelisted_columns());
			} else {
				$disallowed = $this->wc_variation_only_columns;
			}

			$new_columns = array();

			foreach ($columns as $key => $column) {
				if (!in_array($key, $disallowed)) {
					$new_columns[$key] = $column;
				}
			}

			return $new_columns;
		}

		function copy_variations($data, $product_ids) {
			global $wpdb;
			$copy_from_product = VGSE()->WC->_get_product_id_from_search($data['copy_from_product']);
			$api_response = VGSE()->helpers->create_rest_request('GET', '/wc/v1/products/' . $copy_from_product);
			$product_data = $api_response->get_data();
			$variations = array();
			$attributes = array();

			if (empty($product_data['variations'])) {
				wp_send_json_error(array('message' => __('The source product doesn´t have variations.', VGSE()->textname)));
			}

			foreach ($product_data['variations'] as $variation) {
				unset($variation['id']);
				unset($variation['date_created']);
				unset($variation['date_modified']);
				unset($variation['permalink']);
				unset($variation['sku']);
				// We force WC to set the price from the regular/sale price
				unset($variation['price']);

				if (!empty($variation['attributes'])) {
					foreach ($variation['attributes'] as $variation_attribute_index => $variation_attribute) {

						$attribute_name = wc_attribute_taxonomy_name_by_id($variation['attributes'][$variation_attribute_index]['id']);

						if ($variation['attributes'][$variation_attribute_index]['id']) {
							$variation['attributes'][$variation_attribute_index]['name'] = $attribute_name;
						}
						unset($variation['attributes'][$variation_attribute_index]['id']);
					}
				}
				if (!empty($variation['image'])) {
					$first_image = current($variation['image']);

					if (strpos($first_image['src'], 'plugins/woocommerce') !== false) {
						unset($variation['image']);
					}
				}
				$variations[] = array_filter($variation);
			}

			foreach ($product_data['attributes'] as $attribute) {
				$attributes[] = $attribute;
			}

			foreach ($product_ids as $product_id) {
				if (!empty($data['use_parent_product_price'])) {
					foreach ($variations as $variation_index => $variation) {
						$variations[$variation_index]['regular_price'] = get_post_meta($product_id, '_regular_price', true);
						$variations[$variation_index]['sale_price'] = get_post_meta($product_id, '_sale_price', true);
					}
				}

				// Delete existing variations
				$existing_variations = $wpdb->get_col("SELECT ID FROM $wpdb->posts WHERE post_type = 'product_variation' AND post_parent = " . (int) $product_id);
				foreach ($existing_variations as $variation_id) {
					$variation = wc_get_product($variation_id);
					$variation->delete(true);
				}

				$api_response = VGSE()->WC->update_products_with_api(array(
					'ID' => $product_id,
					'type' => 'variable',
					'default_attributes' => $product_data['default_attributes'],
					'attributes' => $attributes,
					'variations' => $variations,
				));
			}

			$variations_count = count($variations) * count($product_ids);
			return $variations_count;
		}

		/**
		 * Create variations rows
		 */
		function create_variations_rows() {
			global $wpdb;
			$data = VGSE()->helpers->clean_data($_REQUEST);

			if (!wp_verify_nonce($data['nonce'], 'bep-nonce')) {
				wp_send_json_error(array('message' => __('Request not allowed. Try again later.', VGSE()->textname)));
			}

			if ((isset($data['vgse_variation_manager_source']) && $data['vgse_variation_manager_source'] === 'individual' ) || $data['vgse_variation_tool'] === 'create') {

				if (empty($data[$this->post_type])) {
					wp_send_json_error(array('message' => __('Please select a product.', VGSE()->textname)));
				}
				$product_ids = array();
				if (is_string($data[$this->post_type])) {
					$product_ids[] = VGSE()->WC->_get_product_id_from_search($data[$this->post_type]);
				} elseif (is_array($data[$this->post_type])) {
					foreach ($data[$this->post_type] as $product) {
						$product_ids[] = VGSE()->WC->_get_product_id_from_search($product);
					}
				}
			} elseif ($data['vgse_variation_manager_source'] === 'search') {

				$get_rows_args = apply_filters('vg_sheet_editor/woocommerce/copy_variations/search_query/get_rows_args', array(
					'nonce' => wp_create_nonce('bep-nonce'),
					'post_type' => $this->post_type,
					'filters' => $_REQUEST['filters'],
					'paged' => $data['page']
				));
				$base_query = VGSE()->helpers->prepare_query_params_for_retrieving_rows($get_rows_args, $get_rows_args);
				$base_query = apply_filters('vg_sheet_editor/woocommerce/copy_variations/posts_query', $base_query, $data);

				$base_query['fields'] = 'ids';
				$per_page = (!empty(VGSE()->options) && !empty(VGSE()->options['be_posts_per_page_save']) ) ? (int) VGSE()->options['be_posts_per_page_save'] / 2 : 2;
				$base_query['posts_per_page'] = ( $per_page < 1 ) ? 1 : $per_page;
				$editor = VGSE()->helpers->get_provider_editor($this->post_type);
				VGSE()->current_provider = $editor->provider;
				$query = $editor->provider->get_items($base_query);
				$product_ids = $query->posts;
			}
			if (empty($product_ids)) {
				wp_send_json_error(array('message' => __('Target products not found.', VGSE()->textname)));
			}

			if ($data['vgse_variation_tool'] === 'copy') {
				$variations_count = $this->copy_variations($data, $product_ids);
			} else {
				foreach ($product_ids as $product_id) {

					// Link variations using WC ajax function
					if ($data['link_attributes'] === 'on') {
						$product = wc_get_product($product_id);
						if (!$product->is_type('variable')) {
							wp_set_object_terms($product_id, 'variable', 'product_type', false);
						}
						$variations = $this->link_all_variations($product_id);
						if (is_wp_error($variations)) {
							wp_send_json_error(array('message' => __('Request failed. Try again later.', VGSE()->textname)));
						}

						$variations_count = (int) $variations;
					} else {
						$variations_count = (int) $data['number'];

						$x = $variations_count;
						$api_request_data = array(
							'ID' => $product_id,
							'type' => 'variable',
							'variations' => array()
						);
						while ($x > 0) {
							$api_request_data['variations'][] = array('stock' => '');
							$x--;
						}

						VGSE()->WC->update_products_with_api($api_request_data);
					}
				}
			}

			// We don't retrieve the rows when using the search to reduce
			// memory usage because we might copy to a lot of products at once
			if (isset($data['vgse_variation_manager_source']) && $data['vgse_variation_manager_source'] === 'search') {
				$data = array();
			} else {
				$rows = VGSE()->helpers->get_rows(array(
					'nonce' => $data['nonce'],
					'post_type' => $this->post_type,
					'wp_query_args' => array(
						'post__in' => $product_ids,
					),
					'filters' => '&wc_display_variations=yes'
				));

				if (is_wp_error($rows)) {
					wp_send_json_error($rows->get_error_message());
				}
				$data = array_values($rows['rows']);
			}
			wp_send_json_success(array(
				'message' => sprintf(__('%s variations created.', VGSE()->textname), $variations_count),
				'data' => $data
			));
		}

		/**
		 * Create variations for every possible combination of attributes
		 * @param int $post_id
		 * @return \WP_Error|int
		 */
		function link_all_variations($post_id) {

			if (!defined('WC_MAX_LINKED_VARIATIONS')) {
				define('WC_MAX_LINKED_VARIATIONS', 49);
			}


			if (!current_user_can('edit_products')) {
				return new WP_Error(array('message' => __('User not allowed', VGSE()->textname)));
			}

			wc_set_time_limit(0);


			if (!$post_id) {
				return new WP_Error(array('message' => __('Data missing, try again later.', VGSE()->textname)));
			}

			$variations = array();
			$_product = wc_get_product($post_id, array('product_type' => 'variable'));

			// Put variation attributes into an array
			foreach ($_product->get_attributes() as $attribute) {

				if (!$attribute['is_variation']) {
					continue;
				}

				$attribute_field_name = 'attribute_' . sanitize_title($attribute['name']);

				if ($attribute['is_taxonomy']) {
					$options = wc_get_product_terms($post_id, $attribute['name'], array('fields' => 'slugs'));
				} else {
					$options = explode(WC_DELIMITER, $attribute['value']);
				}

				$options = array_map('trim', $options);

				$variations[$attribute_field_name] = $options;
			}

			// Quit out if none were found
			if (sizeof($variations) == 0) {
				return 0;
			}

			// Get existing variations so we don't create duplicates
			$available_variations = array();

			foreach ($_product->get_children() as $child_id) {
				$child = $_product->get_child($child_id);

				if (!empty($child->variation_id)) {
					$available_variations[] = $child->get_variation_attributes();
				}
			}

			// Created posts will all have the following data
			$variation_post_data = array(
				'post_title' => 'Product #' . $post_id . ' Variation',
				'post_content' => '',
				'post_status' => 'publish',
				'post_author' => get_current_user_id(),
				'post_parent' => $post_id,
				'post_type' => 'product_variation'
			);

			$variation_ids = array();
			$added = 0;
			$possible_variations = wc_array_cartesian($variations);

			foreach ($possible_variations as $variation) {

				// Check if variation already exists
				if (in_array($variation, $available_variations)) {
					continue;
				}

				$variation_id = wp_insert_post($variation_post_data);

				$variation_ids[] = $variation_id;

				foreach ($variation as $key => $value) {
					update_post_meta($variation_id, $key, $value);
				}

				// Save stock status
				update_post_meta($variation_id, '_stock_status', 'instock');

				$added++;

				do_action('product_variation_linked', $variation_id);

				if ($added > WC_MAX_LINKED_VARIATIONS) {
					break;
				}
			}

			delete_transient('wc_product_children_' . $post_id);

			return $added;
		}

		/**
		 * Render modal for creating variations
		 * @param str $post_type
		 * @return null
		 */
		function render_create_variation_modal($post_type) {
			if ($this->post_type !== $post_type) {
				return;
			}
			$nonce = wp_create_nonce('bep-nonce');
			$random_id = rand();
			?>
			<!--Save changes modal-->
			<div class="remodal create-variation-modal" data-remodal-id="create-variation-modal" data-remodal-options="closeOnOutsideClick: false, hashTracking: false">

				<div class="modal-content">
					<form class="create-variation-form vgse-modal-form " action="<?php echo admin_url('admin-ajax.php'); ?>" method="POST">
						<h3><?php _e('Variations Manager', VGSE()->textname); ?></h3>
						<div class="vgse-variations-tool-selectors">
							<button class="button vgse-variations-tool-selector" data-target=".vgse-create-variations"><?php _e('Create variations', VGSE()->textname); ?></button> - 
							<button class="button vgse-variations-tool-selector" data-target=".vgse-copy-variations"><?php _e('Copy variations', VGSE()->textname); ?></button>
						</div>
						<div class="vgse-variations-tool vgse-copy-variations">
							<input type="hidden" name="vgse_variation_tool" value="copy">
							<h3><?php _e('Copy variations', VGSE()->textname); ?></h3>
							<ul class="unstyled-list">
								<li>
									<label><?php _e('Copy variations and attributes from this product:', VGSE()->textname); ?> </label>
									<select name="copy_from_product" data-remote="true" data-min-input-length="4" data-action="vgse_find_post_by_name" data-post-type="<?php echo $post_type; ?>" data-nonce="<?php echo $nonce; ?>" data-placeholder="<?php _e('Select product...', VGSE()->textname); ?>" class="select2 vgse-copy-variation-from-product">
										<option></option>
									</select>
								</li>
								<li>
									<label><?php _e('The variations are for these products: ', VGSE()->textname); ?> </label>
									<select name="vgse_variation_manager_source">
										<option value="">- -</option>
										<option value="individual">Select individual products</option>
										<option value="search">Select multiple products</option>
									</select>
									<label class="use-search-query-container"><input type="checkbox" value="yes"  name="use_search_query"><?php _e('I understand it will update the posts from my search.', VGSE()->textname); ?> <a href="#" class="tipso tipso_style" data-tipso="<?php _e('For example, if you searched for posts by author = Mark using the search tool, we will update only posts with author Mark', VGSE()->textname); ?>">( ? )</a><input type="hidden" name="filters"></label>

									<select name="<?php echo $this->post_type; ?>[]" data-remote="true" data-min-input-length="4" data-action="vgse_find_post_by_name" data-post-type="<?php echo $post_type; ?>" data-nonce="<?php echo $nonce; ?>"  data-placeholder="<?php _e('Select product...', VGSE()->textname); ?> " class="select2 individual-product-selector" multiple>
										<option></option>
									</select>
								</li>
								<li>
									<label><input type="checkbox" class="show-advanced-options"> <?php _e('Show advanced options ', VGSE()->textname); ?></label>
									<div class="advanced-options">
										<label>
											<input type="checkbox" name="use_parent_product_price"> <?php _e('Use prices from simple product (parent) on the variations', VGSE()->textname); ?> <a href="#" class="tipso tipso_style" data-tipso="<?php _e('You can convert simple products into variable products. You can copy variations into a simple product and keep the prices from the simple product on the variations.', VGSE()->textname); ?>">( ? )</a>
										</label>
									</div>
								</li>								
							</ul>
							<div class="response">
							</div>
						</div>
						<div class="vgse-variations-tool vgse-create-variations">
							<input type="hidden" name="vgse_variation_tool" value="create">
							<h3><?php _e('Create variations', VGSE()->textname); ?> </h3>
							<ul class="unstyled-list">
								<li>
									<label><?php _e('The variations are for these products:', VGSE()->textname); ?> </label>
									<select name="<?php echo $this->post_type; ?>[]" data-remote="true" data-min-input-length="4" data-action="vgse_find_post_by_name" data-post-type="<?php echo $post_type; ?>" data-nonce="<?php echo $nonce; ?>"  data-placeholder="<?php _e('Select product...', VGSE()->textname); ?> " class="select2 individual-product-selector" multiple>
										<option></option>
									</select>
								</li>
								<li>
									<label>
										<input type="hidden" name="link_attributes" value="no" />
										<input type="checkbox" class="link-variations-attributes" name="link_attributes" /><?php _e('Create variations for every combination of attributes?', VGSE()->textname); ?></label>								
								</li>
								<li>
									<label><?php _e('Create this number of variations', VGSE()->textname); ?> <input type="number" class="link-variations-number" name="number" /></label>								
								</li>
							</ul>
						</div>

						<input type="hidden" value="vgse_create_variations" name="action">
						<input type="hidden" value=" <?php echo $nonce; ?>" name="nonce">
						<input type="hidden" value="<?php echo $post_type; ?>" name="post_type">
						<br>
						<button class="remodal-confirm" type="submit"><?php _e('Execute', VGSE()->textname); ?> </button>
						<button data-remodal-action="confirm" class="remodal-cancel"><?php _e('Close', VGSE()->textname); ?></button>
					</form>
				</div>
			</div>
			<?php
		}

		/**
		 * Save / get default product attributes
		 */
		function update_default_attributes() {

			$data = VGSE()->helpers->clean_data($_REQUEST);


			if (!wp_verify_nonce($data['nonce'], 'bep-nonce')) {
				wp_send_json_error(array('message' => __('You dont have enough permissions to view this page.', VGSE()->textname)));
			}
			$post_id = (int) $data['postId'];
			$post = get_post($post_id);
			$post_type = $post->post_type;


			$_product = wc_get_product($post_id);

			$attributes = $_product->get_attributes();

			// Is update
			if (isset($data['data'])) {

				if (!is_array($data['data'])) {
					$data['data'] = array($data['data']);
				}

				$new_data = array();

				foreach ($data['data'] as $default_attribute) {
					if (empty($default_attribute['name'])) {
						continue;
					}
					$sanitized_title = sanitize_title($default_attribute['name']);
					if (isset($attributes['pa_' . $sanitized_title])) {
						$key = 'pa_' . $sanitized_title;

						$new_data[] = wp_parse_args(array(
							'id' => wc_attribute_taxonomy_id_by_name($key)
								), $default_attribute);
					} else {
						$new_data[] = wp_parse_args($default_attribute, array(
							'id' => 0,
							'name' => $sanitized_title
						));
					}
				}

				$api_response = VGSE()->WC->update_products_with_api(array(
					'ID' => $post_id,
					'variations' => array(),
					'default_attributes' => $new_data
				));
			} else {
				// Is get
				$api_response = VGSE()->helpers->create_rest_request('GET', '/wc/v1/products/' . $post_id);
			}

			$api_data = $api_response->get_data();

			$default_attributes_out = $api_data['default_attributes'];

			$out = array(
				'data' => array()
			);
			foreach ($default_attributes_out as $default_attribute) {
				$sanitized_title = sanitize_title($default_attribute['name']);
				if (isset($attributes['pa_' . $sanitized_title])) {
					$out['data'][] = $default_attribute;
				} else {
					$out['data'][] = wp_parse_args(array(
						'name' => $attributes[$sanitized_title]['name']
							), $default_attribute);
				}
			}

			$out['custom_handsontable_args'] = array(
				'columns' => array(
					array(
						'data' => 'name',
						'type' => 'autocomplete',
						'source' => array_values(wp_list_pluck(wp_list_filter($api_data['attributes'], array(
							'variation' => true
										)), 'name'))
					),
					array(
						'data' => 'option',
						'type' => 'autocomplete',
						'source' => array_reduce(wp_list_pluck(wp_list_filter($api_data['attributes'], array(
							'variation' => true
										)), 'options'), 'array_merge', array())
					),
				)
			);
			wp_send_json_success($out);
		}

		/**
		 * Save variations rows using WC API
		 * @param array $data
		 * @param str $post_type
		 * @param array $spreadsheet_columns
		 * @param array $request
		 * @return array
		 */
		function maybe_save_variations($data, $post_type, $spreadsheet_columns, $request) {

			if (!$this->variations_enabled($post_type, $request) || empty($GLOBALS['be_wc_variations_rows'])) {
				return $data;
			}
			$data_with_post_type = VGSE()->helpers->add_post_type_to_rows($data);
			$variations_rows = $GLOBALS['be_wc_variations_rows'];

			if (empty($variations_rows)) {
				return $data;
			}

			// We save attributes without using the API because the documentation 
			// is not clear and it was too difficult to find the right parameters
			foreach ($variations_rows as $row_index => $row) {
				foreach ($row as $key => $attribute_name) {
					if (strpos($key, 'pa_') === false) {
						continue;
					}

					if ($attribute_name && $term = get_term_by('name', $attribute_name, $key)) {
						$value = $term->slug;
					} else {
						$value = '';
					}

					update_post_meta($row['ID'], 'attribute_' . $key, $value);
					unset($variations_rows[$row_index][$key]);
				}
			}

			$formatted_for_api = VGSE()->WC->convert_row_to_api_format($variations_rows);

			foreach ($formatted_for_api as $row_to_save) {
				$api_response = VGSE()->WC->update_products_with_api($row_to_save);
				do_action('vg_sheet_editor/woocommerce/variable_product_updated', $row_to_save, $request, $variations_rows);
			}

			return $data;
		}

		/**
		 * Remove variations rows from the posts list before saving
		 * @param array $data
		 * @param array $request
		 * @return array
		 */
		function exclude_variations_from_saving_list($data, $request) {
			if (!$this->variations_enabled($request['post_type'], $request) ||
					empty($data) || !is_array($data)) {
				return $data;
			}

			$data_with_post_type = VGSE()->helpers->add_post_type_to_rows($data);

			$general_products = wp_list_filter($data_with_post_type, array(
				'post_type' => $this->post_type
			));

			$variation_rows = wp_list_filter($data_with_post_type, array(
				'post_type' => 'product_variation'
			));

			$variations_to_save_without_wc_api = array();
			foreach ($variation_rows as $index => $variation_row) {
				$variations_to_save_without_wc_api[$index] = array(
					'ID' => $variation_row['ID']
				);

				foreach ($variation_row as $key => $value) {
					if (!in_array($key, $this->wc_core_variation_columns)) {
						$variations_to_save_without_wc_api[$index][$key] = $value;
					}
				}
			}

			$general_products = array_merge($general_products, $variations_to_save_without_wc_api);


			$GLOBALS['be_wc_variations_rows'] = $variation_rows;

			return $general_products;
		}

		/**
		 * Creates or returns an instance of this class.
		 *
		 * 
		 */
		static function get_instance() {
			if (null == WP_Sheet_Editor_WooCommerce_Variations::$instance) {
				WP_Sheet_Editor_WooCommerce_Variations::$instance = new WP_Sheet_Editor_WooCommerce_Variations();
				WP_Sheet_Editor_WooCommerce_Variations::$instance->init();
			}
			return WP_Sheet_Editor_WooCommerce_Variations::$instance;
		}

		function __set($name, $value) {
			$this->$name = $value;
		}

		function __get($name) {
			return $this->$name;
		}

	}

}


if (!function_exists('vgse_init_WooCommerce_Variations')) {

	function vgse_init_WooCommerce_Variations() {
		WP_Sheet_Editor_WooCommerce_Variations::get_instance();
	}

}

vgse_init_WooCommerce_Variations();






