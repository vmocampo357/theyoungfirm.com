<?php

if (!class_exists('WP_Sheet_Editor_Helpers')) {

	class WP_Sheet_Editor_Helpers {

		var $post_type;
		static private $instance = false;

		private function __construct() {
			
		}

		function get_enabled_post_types() {

			$post_types = VGSE()->post_type;
			if (empty($post_types)) {
				$post_types = array();
			}
			if (!is_array($post_types)) {
				$post_types = array($post_types);
			}

			// Every editor has its own settings regarding post types
			// because plugins can have custom spreadsheet bootstrap processes
			// so we merge all the enabled_post_types from the core settings and each
			// editor settings			
			foreach (VGSE()->editors as $editor) {
				$post_types = array_merge($post_types, $editor->args['enabled_post_types']);
			}

			return array_unique(apply_filters('vg_sheet_editor/get_enabled_post_types', $this->remove_disallowed_post_types(array_unique($post_types))));
		}

		/**
		 * Get all files in the folder
		 * @return array
		 */
		function get_files_list($directory_path, $file_format = '.php') {
			$files = glob(trailingslashit($directory_path) . '*' . $file_format);
			return $files;
		}

		function get_settings_page_url() {
			return add_query_arg(array('page' => VGSE()->options_key), admin_url('admin.php'));
		}

		function get_all_meta_keys($post_type = 'any') {
			global $wpdb;


			$transient_key = 'vgse_all_meta_keys_' . $post_type;
			$meta_keys = get_transient($transient_key);

			if (!$meta_keys) {

				$custom_where = '';
				if ($post_type === 'user') {
					$meta_table_name = $wpdb->usermeta;
					$post_table_name = $wpdb->users;
					$join_column_key = 'user_id';
					$post_types_keys = array();
				} else {
					$meta_table_name = $wpdb->postmeta;
					$post_table_name = $wpdb->posts;
					$join_column_key = 'post_id';

					if (!empty($post_type)) {
						$post_types_keys = array($post_type);
					} else {
						$post_types = VGSE()->post_type;
						$post_types_keys = array();
						if (!empty($post_types)) {
							foreach ($post_types as $key => $post_type_name) {
								if (is_numeric($key)) {
									$key = $post_type_name;
								}
								$post_types_keys[] = $key;
							}
						}
					}

					if (!empty($post_types_keys)) {
						$custom_where .= " AND $post_table_name.post_type IN ('" . implode("','", $post_types_keys) . "') ";
					}
				}

				$query = "
				SELECT $meta_table_name.meta_key 
				FROM $post_table_name 
				LEFT JOIN $meta_table_name 
				ON $post_table_name.ID = $meta_table_name.$join_column_key 
				WHERE $meta_table_name.meta_key != ''
			";
				$query .= $custom_where;
				$query .= "  GROUP BY  $meta_table_name.meta_key ";
				$meta_keys = $wpdb->get_col($query);

				set_transient($transient_key, $meta_keys, DAY_IN_SECONDS);
			}
			if (!$meta_keys) {
				$meta_keys = array();
			}
			return $meta_keys;
		}

		function is_settings_page() {
			return isset($_GET['page']) && $_GET['page'] === VGSE()->options_key;
		}

		function get_data_provider_class_key($provider) {
			$class_name = 'VGSE_Provider_' . ucwords($provider);

			if (!class_exists($class_name)) {
				$provider = 'post';
			}

			return $provider;
		}

		function get_current_provider() {
			if (empty(VGSE()->current_provider)) {
				VGSE()->current_provider = VGSE()->helpers->get_data_provider($this->get_provider_from_query_string());
			}
			return VGSE()->current_provider;
		}

		function get_data_provider($provider) {
			$provider_key = $this->get_data_provider_class_key($provider);
			$class_name = 'VGSE_Provider_' . ucwords($provider_key);

			return $class_name::get_instance();
		}

		function get_provider_editor($provider) {
			$provider_key = VGSE()->helpers->get_data_provider_class_key($provider);

			return (isset(VGSE()->editors[$provider_key])) ? VGSE()->editors[$provider_key] : false;
		}

		function get_provider_columns($post_type, $run_callbacks = false) {

			$current_editor = VGSE()->helpers->get_provider_editor($post_type);
			if (!$current_editor) {
				return array();
			}
			return $current_editor->args['columns']->get_provider_items($post_type, $run_callbacks);
		}

		function create_placeholder_posts($post_type, $rows = 1, $out_format = 'rows') {
			$data = array();
			VGSE()->current_provider = VGSE()->helpers->get_data_provider($post_type);
			$spreadsheet_columns = VGSE()->helpers->get_provider_columns($post_type);

			if (VGSE()->options['be_disable_post_actions']) {
				VGSE()->helpers->remove_all_post_actions($post_type);
			}

			$new_posts_ids = apply_filters('vg_sheet_editor/add_new_posts/create_new_posts', array(), $post_type, $rows, $spreadsheet_columns);


			if (empty($new_posts_ids)) {

				for ($i = 0; $i < $rows; $i++) {
					$my_post = array(
						'post_title' => __('...', VGSE()->textname),
						'post_type' => $post_type,
						'post_content' => ' ',
						'post_status' => 'draft',
						'post_author' => get_current_user_id(),
					);

					$my_post = apply_filters('vg_sheet_editor/add_new_posts/post_data', $my_post);
					$post_id = VGSE()->helpers->get_current_provider()->create_item($my_post);

					if (!$post_id || is_wp_error($post_id)) {
						return new WP_Error('vgse', __('The item could not be saved. Please try again in other moment.', VGSE()->textname));
					}

					do_action('vg_sheet_editor/add_new_posts/after', $post_id, $post_type, $rows, $spreadsheet_columns);

					$new_posts_ids[] = $post_id;
				}
			}
			do_action('vg_sheet_editor/add_new_posts/after_all_posts_created', $new_posts_ids, $post_type, $rows, $spreadsheet_columns);

			if ($out_format === 'ids') {
				$out = $new_posts_ids;
			} elseif (!empty($new_posts_ids)) {
				$get_rows_args = apply_filters('vg_sheet_editor/add_new_posts/get_rows_args', array(
					'nonce' => $_REQUEST['nonce'],
					'post_type' => $post_type,
					'wp_query_args' => array(
						'post__in' => $new_posts_ids,
					),
					'filters' => ''
				));
				$data = VGSE()->helpers->get_rows($get_rows_args);

				if (is_wp_error($data)) {
					return $data;
				}

				$out = $data['rows'];
			}
			VGSE()->helpers->increase_counter('editions', count($new_posts_ids));
			VGSE()->helpers->increase_counter('processed', count($new_posts_ids));

			$out = apply_filters('vg_sheet_editor/add_new_posts/output', $out, $post_type, $spreadsheet_columns);
			return array_values($out);
		}

		function save_rows($settings = array()) {
			if (!wp_verify_nonce($settings['nonce'], 'bep-nonce')) {
				return new WP_Error('vgse', __('You dont have enough permissions to view this page.', VGSE()->textname));
			}

//			global $wpdb;
//			Start Profiling to debug mysql queries and execution time.
//			vgse_start_save_rows_profiling();

			$data = apply_filters('vg_sheet_editor/save_rows/incoming_data', $settings['data'], $settings);
			$post_type = sanitize_text_field($settings['post_type']);
			VGSE()->current_provider = VGSE()->helpers->get_data_provider($post_type);
			$spreadsheet_columns = VGSE()->helpers->get_provider_columns($post_type);

			if (VGSE()->options['be_disable_post_actions']) {
				VGSE()->helpers->remove_all_post_actions($post_type);
			}

			do_action('vg_sheet_editor/save_rows/before_saving_rows', $data, $post_type, $spreadsheet_columns);

			$editions_count = 0;


			// Suspend cache invalidation to reduce mysql queries during saving
			// if the site doesn't use cache, only if post_names edit is disabled (post_names 
			// require cache invalidation, so wp can create the old_slug redirect)
			if (!empty(VGSE()->options['be_suspend_object_cache_invalidation']) && empty(VGSE()->options['be_allow_edit_slugs'])) {
				wp_suspend_cache_invalidation();
			}

			foreach ($data as $row_index => $item) {
				$post_id = $this->sanitize_integer($item['ID']);

				if (empty($post_id)) {
					continue;
				}
				$my_post = array();

				//Guarda los datos de la tabla en base a los datos del array de init_vars()
				foreach ($spreadsheet_columns as $key => $value) {

					if (!isset($item[$key])) {
						continue;
					}
					do_action('vg_sheet_editor/save_rows/before_saving_cell', $item, $post_type, $value, $key, $spreadsheet_columns, $post_id);
					if (!$value['allow_to_save']) {
						continue;
					}

					// If file cells, convert URLs to file IDs					
					if (in_array($value['value_type'], array('boton_gallery', 'boton_gallery_multiple')) && filter_var($item[$key], FILTER_VALIDATE_URL)) {

						$item[$key] = implode(',', array_filter(VGSE()->helpers->maybe_replace_urls_with_file_ids(explode(',', $item[$key]), $post_id)));
					}



					if ($value['data_type'] === 'post_data' && empty($value['type'])) {

						$final_key = $key;
						if (VGSE()->helpers->get_current_provider()->is_post_type) {
							if ($key !== 'ID' && $key !== 'comment_status' && strpos($key, 'post_') === false) {
								$final_key = 'post_' . $key;
							}
						}

						$my_post[$final_key] = VGSE()->data_helpers->set_post($key, $item[$key], $post_id);
					}
					// @todo Encontrar forma de sanitizar
					if ($value['data_type'] === 'meta_data' || $value['data_type'] === 'post_meta') {
						if (empty($value['type'])) {
							$result = VGSE()->helpers->get_current_provider()->update_item_meta($post_id, $key, $item[$key]);
						} elseif (in_array($value['type'], array('boton_gallery', 'boton_gallery_multiple'))) {

							$result = VGSE()->helpers->save_images(array(
								'post_id' => $post_id, // int
								'id' => $item[$key], // str
								'key' => $key, // str
							));
						}


						if ($result) {
							$editions_count++;
						}
					}
					if ($value['data_type'] === 'post_terms') {

						$terms_saved = VGSE()->data_helpers->prepare_post_terms_for_saving($item[$key], $key);
						VGSE()->helpers->get_current_provider()->set_object_terms($post_id, $terms_saved, $key);
					}

					$new_value = $item[$key];
					$post_id = $post_id;
					$cell_args = $value;
					do_action('vg_sheet_editor/save_rows/after_saving_cell', $post_type, $post_id, $key, $new_value, $cell_args, $spreadsheet_columns);
				}

				if (!empty($my_post)) {
					if (empty($my_post['ID'])) {
						$my_post['ID'] = $post_id;
					}
					if (!empty($my_post['post_title'])) {
						$my_post['post_title'] = wp_strip_all_tags($my_post['post_title']);
					}
					if (!empty($my_post['post_date'])) {
						$my_post['post_date_gmt'] = get_gmt_from_date($my_post['post_date']);
						$my_post['edit_date'] = true;
					}

					$original_post = VGSE()->helpers->get_current_provider()->get_item($my_post['ID'], ARRAY_A);

					// count how many fields were modified
					foreach ($original_post as $key => $original_value) {
						if (isset($my_post[$key]) && $my_post[$key] !== $original_value) {
							$editions_count++;
						}
					}

					$post_id = VGSE()->helpers->get_current_provider()->update_item_data($my_post, true);
				}
				do_action('vg_sheet_editor/save_rows/after_saving_post', $post_id, $item, $data, $post_type, $spreadsheet_columns, $settings);
			}
			do_action('vg_sheet_editor/save_rows/after_saving_rows', $data, $post_type, $spreadsheet_columns, $settings);


			// Enable cache invalidation to its original state
			// if the site doesn't use cache
			if (!empty(VGSE()->options['be_suspend_object_cache_invalidation'])) {
				wp_suspend_cache_invalidation(false);
			}

			VGSE()->helpers->increase_counter('editions', $editions_count);
			VGSE()->helpers->increase_counter('processed', count($data));

//			Finish Profiling to debug mysql queries and execution time.
//			vgse_end_save_rows_profiling( __FUNCTION__ );

			return true;
		}

		function sanitize_integer($integer) {
			if (is_string($integer)) {
				$out = (int) trim(wp_strip_all_tags($integer));
			} else {
				$out = (int) $integer;
			}
			return $out;
		}

		function prepare_query_params_for_retrieving_rows($clean_data, $settings) {

			$post_statuses = VGSE()->helpers->get_current_provider()->get_statuses();

			$qry = array(
				'post_type' => $clean_data['post_type'],
				'posts_per_page' => (!empty(VGSE()->options) && !empty(VGSE()->options['be_posts_per_page']) ) ? (int) VGSE()->options['be_posts_per_page'] : 10,
				'paged' => isset($clean_data['paged']) ? (int) $clean_data['paged'] : 1,
				'update_post_term_cache' => false,
				'update_post_meta_cache' => false,
			);

			if (( $qry['post_type'] === 'page' && !current_user_can('edit_others_pages') ) || ( $qry['post_type'] !== 'page' && !current_user_can('edit_others_posts') )) {
				$qry['author'] = get_current_user_id();
			}

			if (!empty($post_statuses)) {
				$post_statuses_keys = array_keys($post_statuses);
				$qry['post_status'] = $post_statuses_keys;
				if ($qry['post_type'] === 'attachment') {
					$qry['post_status'] = array_merge($post_statuses_keys, array('inherit'));
				}
				// Exclude published pages or posts if the user is not allowed to edit them
				if (( $qry['post_type'] === 'page' && !current_user_can('edit_published_pages') ) || ( $qry['post_type'] !== 'page' && !current_user_can('edit_published_posts') )) {
					if (!isset($qry['post_status'])) {
						$qry['post_status'] = $post_statuses_keys;
					}
					$qry['post_status'] = VGSE()->helpers->remove_array_item_by_value('publish', $qry['post_status']);
				}
				// Exclude private pages or posts if the user is not allowed to edit or read them
				if (( $qry['post_type'] === 'page' && !current_user_can('read_private_pages') ) || ( $qry['post_type'] !== 'page' && !current_user_can('read_private_posts') ) || ( $qry['post_type'] === 'page' && !current_user_can('edit_private_pages') ) || ( $qry['post_type'] !== 'page' && !current_user_can('edit_private_posts') )) {
					if (!isset($qry['post_status'])) {
						$qry['post_status'] = $post_statuses_keys;
					}
					$qry['post_status'] = VGSE()->helpers->remove_array_item_by_value('private', $qry['post_status']);
				}
			}



			// Exit if the user is not allowed to edit pages
			if ($qry['post_type'] === 'page' && !current_user_can('edit_pages')) {
				$message = __('User not allowed to edit pages', VGSE()->textname);
				return new WP_Error('vgse', $message);
			}



			if (!empty($settings['wp_query_args'])) {
				$qry = wp_parse_args($settings['wp_query_args'], $qry);
			}

			if (!empty(VGSE()->options['be_initial_rows_offset'])) {
				$initial_page = (int) ( (int) VGSE()->options['be_initial_rows_offset'] / $qry['posts_per_page'] );
				$qry['paged'] += $initial_page;
			}

			$qry = apply_filters('vg_sheet_editor/load_rows/wp_query_args', $qry, $clean_data);
			return $qry;
		}

		function get_rows($settings = array()) {
//			Start Profiling to debug mysql queries and execution time.
//			vgse_start_load_rows_profiling();

			VGSE()->helpers->profile_record("Start " . __FUNCTION__);
			$incoming_data = apply_filters('vg_sheet_editor/load_rows/raw_incoming_data', $settings);
			$clean_data = apply_filters('vg_sheet_editor/load_rows/sanitized_incoming_data', VGSE()->helpers->clean_data($incoming_data));
			$provider = $clean_data['post_type'];
			VGSE()->current_provider = $this->get_data_provider($provider);

			$qry = $this->prepare_query_params_for_retrieving_rows($clean_data, $settings);

			if (is_wp_error($qry)) {
				return $qry;
			}

			VGSE()->helpers->profile_record("After qry " . __FUNCTION__);
			$query = VGSE()->helpers->get_current_provider()->get_items($qry);
			$GLOBALS['wpse_main_query'] = $query;

			VGSE()->helpers->profile_record('After $query ' . __FUNCTION__);
			$data = array();

			if (!empty($query->posts)) {

				$count = 0;

				$spreadsheet_columns = VGSE()->helpers->get_provider_columns($clean_data['post_type']);

				VGSE()->helpers->profile_record('After $spreadsheet_columns ' . __FUNCTION__);
				$posts = apply_filters('vg_sheet_editor/load_rows/found_posts', $query->posts, $qry, $clean_data, $spreadsheet_columns);

				$data = apply_filters('vg_sheet_editor/load_rows/preload_data', $data, $posts, $qry, $clean_data, $spreadsheet_columns);

				VGSE()->helpers->profile_record('Before $posts foreach ' . __FUNCTION__);
				foreach ($posts as $post) {

					$GLOBALS['post'] = & $post;

					if (isset($post->post_title)) {
						setup_postdata($post);
					}

					$post_id = $post->ID;


					if (!apply_filters('vg_sheet_editor/load_rows/can_edit_item', true, $post, $qry, $spreadsheet_columns)) {
						continue;
					}

					$data[$post_id]['post_type'] = $post->post_type;
					$data[$post_id]['provider'] = $post->post_type;

					// Allow other plugins to filter the fields for every post, so we can optimize 
					// the process and avoid retrieving unnecessary data
					$allowed_columns_for_post = apply_filters('vg_sheet_editor/load_rows/allowed_post_columns', $spreadsheet_columns, $post, $qry);

					foreach ($allowed_columns_for_post as $item => $value) {

						if (isset($data[$post_id][$item])) {
							continue;
						}
						$item_custom_data = apply_filters('vg_sheet_editor/load_rows/get_cell_data', false, $post, $qry, $item, $value);

						if (!is_bool($item_custom_data)) {
							$data[$post_id][$item] = $item_custom_data;
							continue;
						}


						if (empty($value['type'])) {

							if ($value['data_type'] === 'post_data') {
								$data[$post_id][$item] = VGSE()->data_helpers->get_post_data($item, $post->ID);
							}
							if ($value['data_type'] === 'meta_data') {
								$data[$post_id][$item] = VGSE()->helpers->get_current_provider()->get_item_meta($post->ID, $item, true, 'read');
							}
							if ($value['data_type'] === 'post_terms') {
								$data[$post_id][$item] = VGSE()->data_helpers->get_post_terms($post->ID, $item);
							}
						} else {
							if ($value['type'] === 'boton_gallery') {
								$data[$post_id][$item] = VGSE()->helpers->get_gallery_cell_content($post->ID, $item, $value['data_type']);
							}
							if ($value['type'] === 'boton_gallery_multiple') {
								$data[$post_id][$item] = VGSE()->helpers->get_gallery_cell_content($post->ID, $item, $value['data_type'], true);
							}
							if ($value['type'] === 'boton_tiny') {
								$data[$post_id][$item] = VGSE()->helpers->get_tinymce_cell_content($post->ID, $item, $value['data_type']);
							}
							if ($value['type'] === 'view_post') {
								$data[$post_id][$item] = VGSE()->helpers->get_view_post_cell_content($post->ID, $item, $value['data_type']);
							}
							if ($value['type'] === 'inline_image') {
								$data[$post_id][$item] = VGSE()->helpers->get_inline_image_html($post->ID, $item, $value['data_type']);
							}
							if (in_array($value['type'], apply_filters('vg_sheet_editor/get_rows/cell_content/custom_modal_editor_types', array('handsontable', 'metabox')))) {
								$data[$post_id][$item] = VGSE()->helpers->get_custom_modal_editor_cell_content($post->ID, $item, $value);
							}
						}

						if ($item === 'post_name' && empty(VGSE()->options['be_allow_edit_slugs']) && class_exists('ReduxFrameworkInstances')) {
							$settings_page_instance = ReduxFrameworkInstances::get_instance(VGSE()->options_key);
							$tabNum = Redux_Helpers::tabFromField($settings_page_instance, 'be_allow_edit_slugs');
							$data[$post_id][$item] .= ' <a href="' . add_query_arg('tab', $tabNum, VGSE()->helpers->get_settings_page_url()) . '" target="_blank">' . __('(enable)', VGSE()->textname) . '</a>';
						}

						// Use default value if the field is empty, or if the cell is a 
						// checkbox and the value is not in the checkbox values.
						if ((empty($data[$post_id][$item]) && isset($value['default_value']) && $data[$post_id][$item] !== $value['default_value'] ) ||
								(!empty($data[$post_id][$item]) && !empty($value['formatted']['type']) && $value['formatted']['type'] === 'checkbox' && !in_array($data[$post_id][$item], array($value['formatted']['checkedTemplate'], $value['formatted']['uncheckedTemplate']), true) )) {
							$data[$post_id][$item] = $value['default_value'];
						}
					}
					$count++;
				}
				VGSE()->helpers->profile_record('After $posts foreach ' . __FUNCTION__);
			} else {

				$message = __('Posts not found.', VGSE()->textname);
				return new WP_Error('tvcv', $message);
			}


			wp_reset_postdata();
			wp_reset_query();


//			End Profiling to debug mysql queries and execution time.
//			vgse_end_load_rows_profiling(__FUNCTION__);

			VGSE()->helpers->profile_record('Before load_rows/output ' . __FUNCTION__);
			$data = apply_filters('vg_sheet_editor/load_rows/output', $data, $qry, $spreadsheet_columns, $clean_data);
			$out = array(
				'rows' => $data,
				'total' => (int) $query->found_posts,
			);
			VGSE()->helpers->profile_record('Before out ' . __FUNCTION__);

			VGSE()->helpers->profile_finish();
			return apply_filters('vg_sheet_editor/load_rows/full_output', $out, $qry, $spreadsheet_columns, $clean_data);
		}

		function get_editor_url($post_type) {
			$url_part = 'admin.php?page=vgse-bulk-edit-' . $post_type;
			return admin_url($url_part);
		}

		/**
		 * Get image html
		 * @param int $post_id
		 * @param string $key
		 * @param string $data_source
		 * @return string
		 */
		function get_inline_image_html($post_id, $key, $data_source) {

			$out = '';
			if ($data_source === 'post_data') {
				$post = VGSE()->helpers->get_current_provider()->get_item($post_id);

				if (!empty($post->$key)) {
					$url = $post->$key;

					if (strpos($url, WP_CONTENT_URL) === false) {
						$image_url = $url;
					} else {
						$image_id = VGSE()->helpers->get_attachment_id_from_url($url);
					}
				}
			} elseif ($data_source === 'meta_data') {
				$image_id = VGSE()->helpers->get_current_provider()->get_item_meta($post_id, $key, true);
			}

			if (empty($image_url) && !empty($image_id)) {

				$thumb_url_array = wp_get_attachment_image_src($image_id, array(100, 100), true);
				$image_url = $thumb_url_array[0];
			}

			if (!empty($image_url)) {
				$out = '<img src="' . $image_url . '" width="100px" height="100px" />';
			}
			return $out;
		}

		function remove_disallowed_post_types($post_types) {

			$out = array();

			if (empty($post_types) || !is_array($post_types)) {
				return $out;
			}

			foreach ($post_types as $post_type_key) {
				$post_type_object = get_post_type_object($post_type_key);
				if (is_object($post_type_object) && !current_user_can($post_type_object->cap->edit_posts)) {
					continue;
				}

				if (VGSE()->helpers->is_post_type_allowed($post_type_key)) {
					$out[$post_type_key] = $post_type_key;
				}
			}
			return $out;
		}

		/**
		 * Save images to WP gallery
		 * @param array $args
		 * @return boolean
		 */
		function save_images($args = array()) {
			$defaults = array(
				'post_id' => null, // int
				'id' => null, // str
				'key' => null, // str
			);

			$data = VGSE()->helpers->clean_data(wp_parse_args($args, $defaults));

			if (empty($data['id']) || empty($data['post_id']) || empty($data['key'])) {
				return false;
			}

			$post_id = (int) $data['post_id'];

			$attachment_ids = VGSE()->helpers->maybe_replace_urls_with_file_ids(explode(',', $data['id']), $post_id);

			if (empty($attachment_ids)) {
				return false;
			}

			$attachment_id = implode(',', $attachment_ids);
			$key = $data['key'];

			VGSE()->helpers->increase_counter('editions', count($attachment_ids));
			VGSE()->helpers->increase_counter('processed');

			VGSE()->helpers->get_current_provider()->update_item_meta($post_id, $key, $attachment_id);
			return true;
		}

// Call this when you're done and want to see the results
		function profile_finish() {
			global $prof_timing, $prof_names;
			if (!defined('WPSE_PROFILE') || !WPSE_PROFILE) {
				return;
			}

			ob_start();
			$size = count($prof_timing);
			echo "============" . PHP_EOL . $_SERVER['REQUEST_URI'] . PHP_EOL . "============" . PHP_EOL;
			for ($i = 0; $i < $size - 1; $i++) {
				echo $prof_names[$i] . PHP_EOL;
				echo sprintf("\t%f" . PHP_EOL, $prof_timing[$i + 1] - $prof_timing[$i]);
			}
			echo "{$prof_names[$size - 1]}" . PHP_EOL;

			$path = wp_normalize_path(WP_CONTENT_DIR . '/wp-sheet-editor-profiles/' . date('Y-m-d-H-i-s', current_time('timestamp')) . '.txt');
			wp_mkdir_p(dirname($path));
			$log = ob_get_clean();
			file_put_contents($path, $log);
		}

// Call this at each point of interest, passing a descriptive string
		function profile_record($str) {
			if (!defined('WPSE_PROFILE') || !WPSE_PROFILE) {
				return;
			}
			global $prof_timing, $prof_names;
			$prof_timing[] = microtime(true);
			$prof_names[] = $str;
		}

		/**
		 * Get current plugin mode. If it´s free or pro.
		 * @return str
		 */
		function get_plugin_mode() {
			$mode = ( defined('VGSE_ANY_PREMIUM_ADDON') && VGSE_ANY_PREMIUM_ADDON) ? 'pro' : 'free';

			return $mode . '-plugin';
		}

		/**
		 * Check if there is at least one paid addon active
		 * @return str
		 */
		function has_paid_addon_active() {
			$extensions = VGSE()->extensions;
			$has_paid_addon = wp_list_filter($extensions, array(
				'is_active' => true,
				'has_paid_offering' => true
			));

			return count($has_paid_addon);
		}

		/**
		 * Maybe replace urls in a list with wp media file ids.
		 * 
		 * @param str|array $ids
		 * @param int|null $post_id
		 * @return array
		 */
		function maybe_replace_urls_with_file_ids($ids = array(), $post_id = null) {
			if (!is_array($ids)) {
				$ids = array($ids);
			}

			$ids = array_map('trim', $ids);

			$out = array();
			foreach ($ids as $id) {
				if (is_numeric($id)) {
					$out[] = $id;
				} elseif (filter_var($id, FILTER_VALIDATE_URL)) {

					if (strpos($id, WP_CONTENT_URL) !== false) {
						$media_file_id = $this->get_attachment_id_from_url($id);
					} else {
						$media_file_id = $this->add_file_to_gallery_from_url($id, null, $post_id);
					}

					if ($media_file_id) {
						$out[] = $media_file_id;
					}
				}
			}

			return $out;
		}

		/**
		 * Add file to gallery from url
		 * Download a file from an external url and add it to 
		 * the wordpress gallery.		 
		 * @param str $url External file url
		 * @param str $save_as New file name
		 * @param int $post_id Append to the post ID
		 * @return mixed Attachment ID on success, false on failure
		 */
		function add_file_to_gallery_from_url($url, $save_as = null, $post_id = null) {
			if (!$url) {
				return false;
			}
			// Remove query strings, we accept only static files.
			$url = preg_replace('/\?.*/', '', $url);
			if (!$save_as) {
				$save_as = basename($url);
			}
			require_once(ABSPATH . 'wp-admin/includes/media.php');
			require_once(ABSPATH . 'wp-admin/includes/file.php');
			require_once(ABSPATH . 'wp-admin/includes/image.php');

			// build up array like PHP file upload
			$file = array();
			$file['name'] = $save_as;
			$file['tmp_name'] = download_url(esc_url($url));

			if (is_wp_error($file['tmp_name'])) {
				unlink($file['tmp_name']);
				return false;
			}

			$attachmentId = media_handle_sideload($file, $post_id);

			// If error storing permanently, unlink
			if (is_wp_error($attachmentId)) {
				unlink($file['tmp_name']);
				return false;
			}

			// create the thumbnails
			$attach_data = wp_generate_attachment_metadata($attachmentId, get_attached_file($attachmentId));

			wp_update_attachment_metadata($attachmentId, $attach_data);
			return $attachmentId;
		}

		/**
		 * Get column textual value.
		 * 
		 * @param str $column_key
		 * @param int $post_id
		 * @return boolean|string
		 */
		function get_column_text_value($column_key, $post_id, $data_type = null, $post_type = null) {

			if (empty($data_type)) {
				$spreadsheet_columns = VGSE()->helpers->get_provider_columns($post_type, false);

				$out = false;
				if (empty($spreadsheet_columns) || !is_array($spreadsheet_columns) || !isset($spreadsheet_columns[$column_key])) {
					return $out;
				}

				$column_settings = $spreadsheet_columns[$column_key];
				$data_type = $column_settings['data_type'];
			}

			if ($data_type === 'post_data') {
				$out = VGSE()->data_helpers->get_post_data($column_key, $post_id);
			} elseif ($data_type === 'meta_data' || $data_type === 'post_meta') {
				$out = VGSE()->helpers->get_current_provider()->get_item_meta($post_id, $column_key, true, 'read');
			} elseif ($data_type === 'post_terms') {
				$out = VGSE()->data_helpers->get_post_terms($post_id, $column_key);
			}

			return $out;
		}

		/**
		 * Get column settings
		 * 
		 * @param str $column_key
		 * @param int $post_id
		 * @return boolean|string
		 */
		function get_column_settings($column_key, $post_type = null) {

			if (!$post_type) {
				$post_type = VGSE()->helpers->get_current_provider()->key;
			}

			$spreadsheet_columns = VGSE()->helpers->get_provider_columns($post_type, false);

			$out = false;
			if (empty($spreadsheet_columns) || !is_array($spreadsheet_columns) || !isset($spreadsheet_columns[$column_key])) {
				return $out;
			}

			$column_settings = $spreadsheet_columns[$column_key];
			return $column_settings;
		}

		/**
		 * Remove keys from array
		 * @param array $array
		 * @param array $keys
		 * @return array
		 */
		public function remove_unlisted_keys($array, $keys = array()) {
			$out = array();
			foreach ($array as $key => $value) {
				if (in_array($key, $keys)) {
					$out[$key] = $value;
				}
			}
			return $out;
		}

		/**
		 * Rename array keys
		 * @param array $array Rest endpoint route
		 * @param array $keys_map Associative array of old keys => new keys.
		 * @return array
		 */
		function rename_array_keys($array, $keys_map) {

			foreach ($keys_map as $old => $new) {

				if ($old === $new) {
					continue;
				}
				if (isset($array[$old])) {
					$array[$new] = $array[$old];
					unset($array[$old]);
				} else {
					$array[$new] = '';
				}
			}
			return $array;
		}

		/**
		 * Add a post type element to posts rows.
		 * @param array $rows
		 * @return array
		 */
		public function add_post_type_to_rows($rows) {
			$new_data = array();
			foreach ($rows as $row) {
				if (isset($row['post_type'])) {
					$new_data[] = $row;
				}
				$post_id = $this->sanitize_integer($row['ID']);

				if (empty($post_id)) {
					continue;
				}
				$row['ID'] = $post_id;
				$post = VGSE()->helpers->get_current_provider()->get_item($post_id);
				$post_type = $post->post_type;

				$row['post_type'] = $post_type;
				$new_data[] = $row;
			}
			return $new_data;
		}

		/**
		 * Process array elements and replace old values with new values.
		 * @param array $array
		 * @param array $new_format
		 * @return array
		 */
		function change_values_format($array, $new_format) {
			$boolean_to_yes = array(array(
					'old' => true,
					'new' => 'yes'
				), array(
					'old' => false,
					'new' => 'no'
			));

			foreach ($array as $key => $value) {
				if (!isset($new_format[$key])) {
					continue;
				}

				if ($new_format[$key] === 'boolean_to_yes_no') {
					$new_format[$key] = $boolean_to_yes;
				}
				foreach ($new_format[$key] as $format) {
					if ($value === $format['old']) {
						$array[$key] = $format['new'];
						break;
					}
				}
			}
			return $array;
		}

		/**
		 * Make a rest request internally
		 * @param str $method Request method.
		 * @param str $route Rest endpoint route
		 * @param array $data Request arguments.
		 * @return obj
		 */
		function create_rest_request($method = 'GET', $route = '', $data = array()) {

			if (empty($route)) {
				return false;
			}
			$request = new WP_REST_Request($method, $route);

			// Add specified request parameters into the request.
			if (!empty($data)) {
				foreach ($data as $param_name => $param_value) {
					$request->set_param($param_name, $param_value);
				}
			}
			$response = rest_do_request($request);
			return $response;
		}

		/**
		 * Remove array item by value
		 * @param str $value
		 * @param array $array
		 * @return array
		 */
		function remove_array_item_by_value($value, $array) {
			$key = array_search($value, $array);
			if ($key) {
				unset($array[$key]);
			}
			return $array;
		}

		public function merge_arrays_by_value($array1, $array2, $value_key = '') {

			foreach ($array1 as $index => $item) {
				$filtered_array2 = wp_list_filter($array2, array(
					$value_key => $item[$value_key]
				));

				$first_match = current($filtered_array2);
				$array1[$index] = wp_parse_args($array1[$index], $first_match);
			}
			return $array1;
		}

		/**
		 * is plugin active?
		 * @return boolean
		 */
		function is_plugin_active($plugin_file) {
			if (empty($plugin_file)) {
				return false;
			}
			if (in_array($plugin_file, apply_filters('active_plugins', get_option('active_plugins')))) {
				return true;
			} else {
				return false;
			}
		}

		public function is_editor_page() {
			$out = false;
			if (isset($_GET['page']) && strpos($_GET['page'], 'vgse-bulk-edit-') !== false) {
				$out = true;
			}
			return apply_filters('vg_sheet_editor/is_editor_page', $out);
		}

		/**
		 * Get handsontable cell content (html)
		 * @param int $id
		 * @param string $key
		 * @param string $type
		 * @return string
		 */
		function get_custom_modal_editor_cell_content($id, $key, $cell_args) {
			$post = VGSE()->helpers->get_current_provider()->get_item($id);
			$type = $cell_args['type'];

			if ($type !== 'metabox') {
				$existing_value = apply_filters('vg_sheet_editor/' . $type . '_cell_content/existing_value', maybe_unserialize($this->get_column_text_value($key, $id, 'meta_data', $post->post_type)), $post, $key, $cell_args);
			}

			if (empty($existing_value)) {
				$existing_value = array();
			}

			$modal_settings = array_merge((array) $post, array('post_id' => $id), $cell_args);

			$out = '<a class="button button-' . $type . ' button-custom-modal-editor" data-existing="' . htmlentities(json_encode(array_values($existing_value)), ENT_QUOTES, 'UTF-8') . '" '
					. 'data-modal-settings="' . htmlentities(json_encode($modal_settings), ENT_QUOTES, 'UTF-8') . '"><i class="fa fa-edit"></i> ' . $modal_settings['edit_button_label'] . '</a>';

			return apply_filters('vg_sheet_editor/' . $type . '_cell_content/output', $out, $id, $key, $cell_args);
		}

		/**
		 * Get tinymce cell content (html)
		 * @param int $id
		 * @param string $key
		 * @param string $type
		 * @return string
		 */
		function get_tinymce_cell_content($id, $key, $type) {
			$out = '<a class="btn-popup-content button button-tinymce-' . $key . '" data-type="' . $type . '" data-key="' . $key . '" data-id="' . $id . '"><i class="fa fa-edit"></i> ' . __('Edit', VGSE()->textname) . '</a>';

			return apply_filters('vg_sheet_editor/tinymce_cell_content', $out, $id, $key, $type);
		}

		/**
		 * Get "view post" cell content (html)
		 * @param int $id
		 * @param string $key
		 * @param string $type
		 * @return string
		 */
		function get_view_post_cell_content($id, $key, $type) {
			// We use urls based on the ID instead of slugs because the slug can change while using the sheet
			$out = '<a target="_blank" href="' . home_url('?p=' . $id) . '" class="button"><i class="fa fa-external-link"></i> ' . __('View', VGSE()->textname) . '</a>';

			return apply_filters('vg_sheet_editor/view_post_cell_content', $out, $id, $key, $type);
		}

		/**
		 * Remove all post related actions.
		 * @param string $post_type
		 */
		function remove_all_post_actions($post_type) {

			foreach (array('transition_post_status', 'save_post', 'pre_post_update', 'add_attachment', 'edit_attachment', 'edit_post', 'post_updated', 'wp_insert_post', 'save_post_' . $post_type) as $act) {
				remove_all_actions($act);
			}
		}

		/**
		 * Get image gallery cell content (html)
		 * @param int $id
		 * @param string $key
		 * @param string $type
		 * @param bool $multiple
		 * @return string
		 */
		function get_gallery_cell_content($id, $key, $type, $multiple = false) {

			if ($type === 'post_data') {
				$current_value = VGSE()->data_helpers->get_post_data($key, (int) $id);
			} else {
				$current_value = VGSE()->helpers->get_current_provider()->get_item_meta((int) $id, $key, true);
			}

			$out = '';
//			$this->d( $id, $key, $type, $multiple, $current_value );
			$final_url = '';
			if (!empty($current_value)) {
				$url = wp_get_attachment_url($current_value);
				// Fix. Needed when using cloudflare flexible ssl
				$final_url = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ? str_replace('http://', 'https://', $url) : $url;
			}
			if (!$multiple) {
				$out .= '<div class="vi-inline-preview-wrapper"><img class="vi-preview-img" src="' . $final_url . '" width="25"></div>';
			}
			if (!empty($current_value)) {
				$out .= '<button class="set_custom_images ';
				if ($multiple) {
					$out .= 'multiple';
				}
				$out .= ' button" data-type="' . $type . '" data-file="' . (int) $current_value . '" data-key="' . $key . '" data-id="' . $id . '">' . __('Select Image', VGSE()->textname) . '</button>';
				if ($multiple) {
					$out .= ' <a href="#image" data-remodal-target="image" class="view_custom_images ';
					$out .= 'multiple';
					$out .= ' button" data-type="' . $type . '" data-key="' . $key . '" data-id="' . $id . '">' . __('View Image', VGSE()->textname) . '</a>';
				}
			} else {
				$out .= '<button class="set_custom_images ';
				if ($multiple) {
					$out .= 'multiple';
				}
				$out .= ' button" data-type="' . $type . '" data-key="' . $key . '" data-id="' . $id . '">' . __('Select Image', VGSE()->textname) . '</button>';
				if ($multiple) {
					$out .= '<a href="#image" data-remodal-target="image" class="view_custom_images ';
					$out .= 'multiple';
					$out .= ' button hidden" data-type="' . $type . '" data-key="' . $key . '" data-id="' . $id . '">' . __('View Image', VGSE()->textname) . '</a>';
				}
			}
			return apply_filters('vg_sheet_editor/gallery_cell_content', $out, $id, $key, $type, $multiple);
		}

		/**
		 * Initialize class
		 * @param string $post_type
		 */
		function init($post_type = null) {

			$this->post_type = (!empty($post_type) ) ? $post_type : $this->get_provider_from_query_string();
		}

		static function get_instance() {
			if (null == WP_Sheet_Editor_Helpers::$instance) {
				WP_Sheet_Editor_Helpers::$instance = new WP_Sheet_Editor_Helpers();
				WP_Sheet_Editor_Helpers::$instance->init();
			}
			return WP_Sheet_Editor_Helpers::$instance;
		}

		function get_allowed_post_types() {
			$post_types = apply_filters('vg_sheet_editor/allowed_post_types', array());
			return array_filter(array_unique($post_types));
		}

		/**
		 * Dump
		 * 
		 * Dump any variable
		 * .
		 * @param int|string|array|object $var
		 * 
		 */
		function d($var) {
			if (defined('VGSE_DEBUG') && !VGSE_DEBUG) {
				return;
			}
			if (count(func_get_args()) > 1) {
				foreach (func_get_args() as $arg) {
					$this->d($arg);
				}
				return $this;
			}
			echo '<pre>';
			var_dump($var);
			echo '</pre>';
			return $this;
		}

		/**
		 * Dump and Die
		 * 
		 * @param int|string|array|object $var
		 */
		function dd($var) {
			if (defined('VGSE_DEBUG') && !VGSE_DEBUG) {
				return;
			}
			if (count(func_get_args()) > 1) {
				foreach (func_get_args() as $arg) {
					$this->d($arg);
				}
				die();
			}
			$this->d($var);
			die();
		}

		/**
		 * Get attachment ID from URL
		 * 
		 * It accepts auto-generated thumbnails URLs.
		 * 
		 * @global type $wpdb
		 * @param type $attachment_url
		 * @return type
		 */
		function get_attachment_id_from_url($attachment_url = '') {
			global $wpdb;
			$attachment_id = false;
			// If there is no url, return.
			if ('' == $attachment_url)
				return;
			// Get the upload directory paths
			$upload_dir_paths = wp_upload_dir();
			// Make sure the upload path base directory exists in the attachment URL, to verify that we're working with a media library image
			if (false !== strpos($attachment_url, $upload_dir_paths['baseurl'])) {
				// If this is the URL of an auto-generated thumbnail, get the URL of the original image
				$attachment_url = preg_replace('/-\d+x\d+(?=\.(jpg|jpeg|png|gif)$)/i', '', $attachment_url);
				// Remove the upload path base directory from the attachment URL
				$attachment_url = str_replace($upload_dir_paths['baseurl'] . '/', '', $attachment_url);
				// Finally, run a custom database query to get the attachment ID from the modified attachment URL
				$attachment_id = $wpdb->get_var($wpdb->prepare("SELECT wposts.ID FROM $wpdb->posts wposts, $wpdb->postmeta wpostmeta WHERE wposts.ID = wpostmeta.post_id AND wpostmeta.meta_key = '_wp_attached_file' AND wpostmeta.meta_value = '%s' AND wposts.post_type = 'attachment'", $attachment_url));
			}
			return $attachment_id;
		}

		/**
		 * Get post type from query string
		 * @return string
		 */
		function get_provider_from_query_string() {
			$query_strings = $this->clean_data($_REQUEST);

			if (!empty($query_strings['page']) && strpos($query_strings['page'], 'vgse-bulk-edit-') !== false) {
				$current_post = str_replace('vgse-bulk-edit-', '', $query_strings['page']);
			} elseif (!empty($query_strings['post_type'])) {
				$current_post = $query_strings['post_type'];
			} else {
				$current_post = 'post';
			}
			return apply_filters('vg_sheet_editor/bootstrap/get_current_provider', $current_post);
		}

		/**
		 * Get post types as array
		 * @return array
		 */
		function post_type_array() {
			if (!is_array($this->post_type)) {
				$this->post_type = array($this->post_type);
			}
			return $this->post_type;
		}

		/**
		 * Is post type allowed?
		 * @param string $post_type
		 * @return boolean
		 */
		function is_post_type_allowed($post_type) {
			$allowed_post_types = VGSE()->helpers->get_allowed_post_types();
			return isset($allowed_post_types[$post_type]);
		}

		/*
		 * Clean $_POST or $_GET or $_REQUEST data
		 */

		/**
		 * Clean up data
		 * @param array $posts
		 * @return array
		 */
		function clean_data($posts) {

			$clean = array();
			if (is_array($posts)) {
				foreach ($posts as $post => $value) {
					if (!is_array($value)) {
						$clean[$post] = htmlspecialchars(rawurldecode(trim($value)), ENT_QUOTES, 'UTF-8');
					} else {
						$clean[$post] = $this->clean_data($value);
					}
				}
			} elseif (is_string($posts)) {
				$clean = strip_tags($posts);
			} else {
				$clean = $posts;
			}

			return $clean;
		}

		/**
		 * Get post type label from key
		 * @param string $post_type_key
		 * @return string
		 */
		function get_post_type_label($post_type_key) {

			// Get all post type *names*, that are shown in the admin menu
			$post_types = $this->get_all_post_types();
			$name = (isset($post_types[$post_type_key]) ) ? $post_types[$post_type_key]->label : $post_type_key;

			return $name;
		}

		/**
		 * Get taxonomies registered with a post type
		 * @param string $post_type
		 * @return array
		 */
		function get_post_type_taxonomies($post_type) {
			$taxonomies = VGSE()->helpers->get_provider_editor($post_type)->provider->get_object_taxonomies($post_type);

			$out = array();
			if (!empty($taxonomies) && is_array($taxonomies)) {
				foreach ($taxonomies as $taxonomy) {

					if (!$taxonomy->show_ui) {
						continue;
					}
					$out[] = $taxonomy;
				}
			}
			return $out;
		}

		/**
		 * Get all post types
		 * @return array
		 */
		function get_all_post_types($args = array(), $output = 'objects', $condition = 'OR') {
			$out = get_post_types($args, $output, $condition);
			$post_types = apply_filters('vg_sheet_editor/api/all_post_types', $out, $args, $output);

			$private_post_types = apply_filters('vg_sheet_editor/api/blacklisted_post_types', get_post_types(array('public' => false)), $post_types, $args, $output);

			foreach ($post_types as $index => $post_type) {
				$post_type_key = ( is_object($post_type) ) ? $post_type->name : $post_type;

				$post_types[$post_type_key] = $post_type;
				if (!empty($private_post_types)) {
					if (in_array($post_type_key, $private_post_types)) {
						unset($post_types[$index]);
					} elseif (in_array($post_type_key, $private_post_types)) {
						unset($post_types[$index]);
					}
				}
			}
			return $post_types;
		}

		/**
		 * Get all post types names
		 * @return array
		 */
		function get_all_post_types_names($include_private = true) {
			$args = array();

			if (!$include_private) {
				$args = array(
					'public' => true,
					'public_queryable' => true,
				);
			}

			$out = $this->get_all_post_types($args, 'names', 'OR');
			return $out;
		}

		/**
		 * Get single data from all taxonomies registered with a post type.
		 * @param string $post_type
		 * @param string $field_key
		 * @return mixed
		 */
		function get_post_type_taxonomies_single_data($post_type, $field_key) {

			$taxonomies = $this->get_post_type_taxonomies($post_type);
			$out = array();
			if (!empty($taxonomies) && is_array($taxonomies)) {
				foreach ($taxonomies as $taxonomy) {
					$out[] = $taxonomy->$field_key;
				}
			}
			return $out;
		}

		/**
		 * Convert multidimensional arrays to unidimensional
		 * @param array $array
		 * @param array $return
		 * @return array
		 */
		function array_flatten($array, $return) {

			if (!is_array($array) || empty($array)) {
				return $array;
			}

			for ($x = 0; $x <= count($array); $x++) {
				if (!empty($array[$x]) && is_array($array[$x])) {
					$return = $this->array_flatten($array[$x], $return);
				} else {
					if (isset($array[$x])) {
						$return[] = $array[$x];
					}
				}
			}
			return $return;
		}

		/**
		 * Get a list of <option> tags of all enabled columns from a post type
		 * @param string $post_type
		 * @param array $filters
		 * @return string
		 */
		function get_post_type_columns_options($post_type, $filters = array(), $formula_format = false) {

			$unfiltered_columns = WP_Sheet_Editor_Columns_Visibility::$unfiltered_columns;
			$spreadsheet_columns = isset($unfiltered_columns[$post_type]) ? $unfiltered_columns[$post_type] : array();
			$out = '';
			if (!empty($spreadsheet_columns) && is_array($spreadsheet_columns)) {
				if (!empty($filters)) {
					if (empty($filters['operator'])) {
						$filters['operator'] = 'AND';
					}
					$spreadsheet_columns = wp_list_filter($spreadsheet_columns, $filters['conditions'], $filters['operator']);
				}
				foreach ($spreadsheet_columns as $item => $value) {
					if (empty($value['value_type'])) {
						$value['value_type'] = 'text';
					}
					$name = $value['title'];
					$key = $item;
					if ($formula_format) {
						$name = '$' . $item . '$ (' . $value['title'] . ')';
						$key = '$' . $item . '$';
					}
					$out .= '<option value="' . $key . '" data-value-type="' . $value['value_type'] . '">' . $name . '</option>';
				}
			}

			return $out;
		}

		/**
		 * Increase editions counter. This is used to keep track of 
		 * how many posts have been edited using the spreadsheet editor.
		 * 
		 * This information is displayed on the dashboard widget.
		 */
		function increase_counter($key = 'editions', $count = 1) {
			$allowed_keys = array(
				'editions',
				'processed',
			);

			if (!in_array($key, $allowed_keys)) {
				return;
			}
			$counter = get_option('vgse_' . $key . '_counter', 0);

			$counter += $count;

			update_option('vgse_' . $key . '_counter', $counter);
		}

	}

}