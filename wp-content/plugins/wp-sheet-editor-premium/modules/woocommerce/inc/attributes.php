<?php

if (!class_exists('WP_Sheet_Editor_WooCommerce_Attrs')) {

	/**
	 * Display woocommerce item in the toolbar to tease users of the free 
	 * version into purchasing the premium plugin.
	 */
	class WP_Sheet_Editor_WooCommerce_Attrs {

		static private $instance = false;
		var $post_type = null;
		var $post_type_variation = 'product_variation';
		var $cell_key = '_vgse_create_attribute';

		private function __construct() {
			
		}

		function init() {
			$this->post_type = apply_filters('vg_sheet_editor/woocommerce/product_post_type_key', 'product');
			add_filter('vg_sheet_editor/handsontable_cell_content/existing_value', array($this, 'filter_cell_value'), 10, 4);
			add_filter('wp_ajax_vgse_wc_save_attributes', array($this, 'save_attributes'));
			add_action('vg_sheet_editor/editor/before_init', array($this, 'register_columns'));
		}

		/**
		 * Filter "edit attributes" cell html
		 * @param str $value
		 * @param obj $post WP_Post object
		 * @param str $key
		 * @param array $cell_args
		 * @return str
		 */
		function filter_cell_value($value, $post, $key, $cell_args) {
			if ($key !== $this->cell_key) {
				return $value;
			}


			$boolean_fields = array(
				'is_visible',
				'is_taxonomy',
				'is_variation'
			);


			// @todo Obtener attrs. de variaciones con API DE WC.
			// Hacer un merge de los datos de la API con _product_attributes
			// para enviar los datos faltantes en la respuesta de la API.

			if ($post->post_type === $this->post_type) {
				$attributes = maybe_unserialize(get_post_meta($post->ID, '_product_attributes', true));

				if (!empty($attributes) && is_array($attributes)) {
					$i = 0;

					foreach ($attributes as $index => $attribute) {
						if ($attribute['is_taxonomy'] && taxonomy_exists($index)) {
							$attributes[$index]['taxonomy_key'] = $index;
							$taxonomy = get_taxonomy($index);
							$attributes[$index]['name'] = $taxonomy->label;

							$terms = get_the_terms($post->ID, $index);
							if (empty($terms) || is_wp_error($terms)) {
								$terms = array();
							}
							$attributes[$index]['value'] = implode(', ', wp_list_pluck($terms, 'name'));
						}
						if (empty($attributes[$index]['position'])) {
							$attributes[$index]['position'] = $i;
						}
						foreach ($boolean_fields as $boolean_field) {
							if (empty($attributes[$index][$boolean_field])) {
								$attributes[$index][$boolean_field] = 0;
							}
						}

						$i++;
					}
				}
			} elseif ($post->post_type === 'product_variation') {


				// @todo Obtener attrs. de variaciones con API DE WC.

				$parent_attributes = maybe_unserialize(get_post_meta($post->post_parent, '_product_attributes', true));
				$variation_meta = get_post_meta($post->ID);
				$attributes = array();
				foreach ($variation_meta as $key => $value) {
					if (strpos($key, 'attribute_') === false) {
						continue;
					}

					$attribute_key = sanitize_title(str_replace('attribute_', '', $key));

					if (!isset($parent_attributes[$attribute_key])) {
						continue;
					}
					$attributes[$attribute_key] = $parent_attributes[$attribute_key];
					$attributes[$attribute_key]['value'] = (is_array($value) ) ? current($value) : $value;
					if ($parent_attributes[$attribute_key]['is_taxonomy'] && taxonomy_exists($attribute_key)) {
						$taxonomy = get_taxonomy($attribute_key);
						$attributes[$attribute_key]['name'] = $taxonomy->label;
					}
				}
			}
			$custom_attributes = $attributes;

			if (!is_array($custom_attributes)) {
				$custom_attributes = array($custom_attributes);
			}


			return $custom_attributes;
		}

		/**
		 * Register spreadsheet columns
		 */
		function register_columns($editor) {
			$post_type = $this->post_type;

			if ($editor->provider->key === 'user') {
				return;
			}

			$attribute_taxonomies = wc_get_attribute_taxonomies();
			if (empty($attribute_taxonomies)) {
				$attribute_taxonomies = array();
			}
			$editor->args['columns']->register_item('_vgse_create_attribute', $post_type, array(
				'data_type' => 'meta_data',
				'unformatted' => array('data' => '_vgse_create_attribute', 'renderer' => 'html', 'readOnly' => true),
				'column_width' => 150,
				'title' => __('Edit attributes', VGSE()->textname),
				'supports_formulas' => false,
				'formatted' => array('data' => '_vgse_create_attribute', 'renderer' => 'html', 'readOnly' => true),
				'allow_to_hide' => true,
				'allow_to_save' => false,
				'allow_to_rename' => true,
				'type' => 'handsontable',
				'edit_button_label' => __('Edit attributes', VGSE()->textname),
				'edit_modal_id' => 'vgse-edit-attributes',
				'edit_modal_title' => __('Edit attributes', VGSE()->textname),
				'edit_modal_description' => sprintf(__('Note: Separate values with the character %s<br/>Global attributes can be edited in the general spreadsheet as individual columns as well.<br/><span class="vg-only-variations-enabled">If you are editing the attributes of variations, the variation must be enabled, otherwise the attributes won´t be saved.</spán>'), WC_DELIMITER),
				'edit_modal_save_action' => 'vgse_wc_save_attributes',
				'edit_modal_get_action' => 'vgse_wc_save_attributes',
				'handsontable_columns' => array(
					$this->post_type => array(
						array(
							'data' => 'name',
							'type' => 'autocomplete',
							'source' => wp_list_pluck($attribute_taxonomies, 'attribute_label')
						),
						array(
							'data' => 'options'
						),
						array(
							'data' => 'visible',
							'type' => 'checkbox',
							'checkedTemplate' => true,
							'uncheckedTemplate' => false
						),
						array(
							'data' => 'variation',
							'type' => 'checkbox',
							'checkedTemplate' => true,
							'uncheckedTemplate' => false
						),
					),
					'product_variation' => array(
						array(
							'data' => 'name'
						),
						array(
							'data' => 'options'
						))
				),
				'handsontable_column_names' => array(
					$this->post_type => array(__('Name', VGSE()->textname), __('Value', VGSE()->textname), __('Is visible?', VGSE()->textname), __('Used for variation?', VGSE()->textname)),
					'product_variation' => array(__('Name', VGSE()->textname), __('Value', VGSE()->textname)),
				),
				'handsontable_column_widths' => array(
					$this->post_type => array(150, 240, 90, 130),
					'product_variation' => array(150, 240),
				),
			));
		}

		/**
		 * Save / get attributes via ajax
		 */
		function save_attributes() {
			$data = VGSE()->helpers->clean_data($_REQUEST);


			if (!wp_verify_nonce($data['nonce'], 'bep-nonce')) {
				wp_send_json_error(array('message' => __('You dont have enough permissions to view this page.', VGSE()->textname)));
			}
			$post_id = (int) $data['postId'];
			$post = get_post($post_id);
			$post_type = $post->post_type;

			if ($post_type === 'product_variation') {
				$product_id = $post->post_parent;
			} else {
				$product_id = $post_id;
			}
			$_product = wc_get_product($product_id);

			$attributes = $_product->get_attributes();


			if (isset($data['data']) && !is_array($data['data'])) {
				$data['data'] = array($data['data']);
			}

			// Save variation attributes
			if ($post_type === 'product_variation') {

				// update
				if (isset($data['data'])) {

					$new_data = array();

					foreach ($data['data'] as $attribute) {
						if (empty($attribute['name'])) {
							continue;
						}
						$sanitized_title = sanitize_title($attribute['name']);
						if (isset($attributes['pa_' . $sanitized_title])) {
							$key = 'pa_' . $sanitized_title;

							$new_data[] = wp_parse_args(array(
								'id' => wc_attribute_taxonomy_id_by_name($key)
									), $attribute);
						} else {
							$new_data[] = wp_parse_args($attribute, array(
								'id' => 0,
								'name' => $sanitized_title
							));
						}
					}

					$api_response = VGSE()->WC->update_products_with_api(array(
						'ID' => $product_id,
						'variations' => array(array(
								'id' => $post_id,
								'attributes' => $new_data
							))
					));
				} else {
					// view
					$api_response = VGSE()->helpers->create_rest_request('GET', '/wc/v1/products/' . $product_id);
				}
				$product_data = $api_response->get_data();

				$variation = current(wp_list_filter($product_data['variations'], array(
					'id' => $post_id
				)));

				$attributes_out = $variation['attributes'];

				$out = array(
					'data' => $attributes_out
				);
				$out['custom_handsontable_args'] = array(
					'columns' => array(
						array(
							'data' => 'name',
							'type' => 'autocomplete',
							'source' => array_values(wp_list_pluck(wp_list_filter($product_data['attributes'], array(
								'variation' => true
											)), 'name'))
						),
						array(
							'data' => 'option',
							'type' => 'autocomplete',
							'source' => array_reduce(wp_list_pluck(wp_list_filter($product_data['attributes'], array(
								'variation' => true
											)), 'options'), 'array_merge', array())
						),
					)
				);
				wp_send_json_success($out);
			}

			// Products
// update
			if (isset($data['data'])) {

				$new_data = array();

				$attribute_taxonomies_keys = wp_list_pluck(wc_get_attribute_taxonomies(), 'attribute_name');

				foreach ($data['data'] as $attribute) {
					if (empty($attribute['name'])) {
						continue;
					}
					// WC uses "position" to determine if it´s custom attr.
					// we want to determine it by attr. name.
					unset($attribute['position']);

					$sanitized_title = sanitize_title($attribute['name']);
					if (in_array($sanitized_title, $attribute_taxonomies_keys)) {
						$key = 'pa_' . $sanitized_title;

						$prepared_attribute = wp_parse_args(array(
							'id' => wc_attribute_taxonomy_id_by_name($key)
								), $attribute);
					} else {
						$prepared_attribute = wp_parse_args($attribute, array(
							'id' => 0,
							'name' => $sanitized_title
						));
					}
					
					if( is_string($prepared_attribute['options']) ){
						$prepared_attribute['options'] = array_map('trim', explode( WC_DELIMITER, $prepared_attribute['options']) );
					}

					$new_data[] = wp_parse_args(array(
						'visible' => VGSE()->WC->_do_booleable($prepared_attribute['visible']),
						'variation' => VGSE()->WC->_do_booleable($prepared_attribute['variation']),
							), $prepared_attribute);
				}
				
				$api_response = VGSE()->WC->update_products_with_api(array(
					'ID' => $product_id,
					'attributes' => $new_data
				));
			} else {
				// view
				$api_response = VGSE()->helpers->create_rest_request('GET', '/wc/v1/products/' . $product_id);
			}

			$product_data = $api_response->get_data();

			$out = $product_data['attributes'];

			foreach ($out as $out_index => $item) {
				$out[$out_index]['options'] = implode(' ' . WC_DELIMITER . ' ', $item['options']);
			}
			wp_send_json_success($out);
		}

		/**
		 * Creates or returns an instance of this class.
		 *
		 * 
		 */
		static function get_instance() {
			if (null == WP_Sheet_Editor_WooCommerce_Attrs::$instance) {
				WP_Sheet_Editor_WooCommerce_Attrs::$instance = new WP_Sheet_Editor_WooCommerce_Attrs();
				WP_Sheet_Editor_WooCommerce_Attrs::$instance->init();
			}
			return WP_Sheet_Editor_WooCommerce_Attrs::$instance;
		}

		function __set($name, $value) {
			$this->$name = $value;
		}

		function __get($name) {
			return $this->$name;
		}

	}

}


if (!function_exists('vgse_init_WooCommerce_Attrs')) {

	function vgse_init_WooCommerce_Attrs() {
		WP_Sheet_Editor_WooCommerce_Attrs::get_instance();
	}

}

vgse_init_WooCommerce_Attrs();
