<?php

if (!class_exists('WP_Sheet_Editor_Data')) {

	class WP_Sheet_Editor_Data {

		static private $instance = false;

		private function __construct() {
			
		}

		/**
		 * Get individual post field.
		 * @param string $item
		 * @param int $id
		 * @return mixed
		 */
		function get_post_data($item, $id) {
			$post = VGSE()->helpers->get_current_provider()->get_item($id);

			$out = '';
			if ($item === 'ID') {
				$out = $id;
			} elseif ($item === 'post_title') {
				$post_title = $post->post_title;
				if ($post->post_type === 'attachment' && empty($post_title)) {
					$out = basename($post->guid);
				} else {
					$out = $post_title;
				}
			} elseif ($item === 'post_date') {
				$out = get_the_date('Y-m-d H:i:s', $id);
			} elseif ($item === 'modified') {
				$out = get_the_modified_date('Y-m-d H:i:s', $id);
			} elseif ($item === 'post_author') {
				$author = get_userdata($post->post_author);				
				$out = ( $author ) ? $author->user_login : '';
			} elseif ($item === 'post_status') {

				// If the post status is found in the public post statuses we return it directly,
				// otherwise we return it with a lock icon because the cell will be read-only
				$statuses = VGSE()->helpers->get_current_provider()->get_statuses();
				if (!isset($statuses['trash'])) {
					$statuses['trash'] = 'Trash';
				}
				$out = ( isset($statuses[$post->post_status]) ) ? $post->post_status : '<i class="fa fa-lock vg-cell-blocked"></i> ' . $post->post_status;
			} elseif ($item === 'post_parent') {
				$out = (!empty($post->post_parent) ) ? get_the_title($post->post_parent) : '';
			} else {
				$out = VGSE()->helpers->get_current_provider()->get_item_data($id, $item);
			}

			return $out;
		}

		/**
		 * Prepare individual post field for saving
		 * @param string $key
		 * @param mixed $item
		 * @param int $id
		 * @return mixed
		 */
		function set_post($key, $item, $id = null) {

			if (!VGSE()->helpers->get_current_provider()->is_post_type) {
				return $item;
			}
			$out = false;

			if ($key === 'ID') {
				$out = (int) $item;
			} elseif (!empty(VGSE()->options) && !empty(VGSE()->options['be_post_content_as_plain_text']) && ($key === 'content' || $key === 'post_content')) {
				$out = wp_kses_post($item);
			} elseif ($key === 'post_date') {
				$out = $this->change_date_format_for_saving($item);
			} elseif ($key === 'post_modified') {
				$out = current_time('mysql');
			} elseif ($key === 'post_author') {
				$out = $this->get_author_id_from_username($item);
			} elseif ($key === 'post_parent') {
				$out = $this->get_post_id_from_title($item);
			} elseif ($key === 'post_status') {
				$statuses_raw = get_post_stati(null, 'objects');
				$statuses = wp_list_pluck($statuses_raw, 'label', 'name');

				if (isset($statuses[$item])) {
					$out = $item;
				} elseif ($status_key = array_search($item, $statuses)) {
					$out = $status_key;
				}
			} else {
				$out = $item;
			}

			return $out;
		}

		/**
		 * Get post terms by taxonomy as friendly text.
		 * 
		 * List of terms separated by commas.
		 * 
		 * @param int $id
		 * @param string $taxonomy
		 * @return boolean|string
		 */
		function get_post_terms($id = null, $taxonomy) {
			if ($id === null) {
				global $post;
				if (is_object($post)) {
					$id = $post->ID;
				}
			}
			if (empty($id)) {
				return false;
			}
			$current_terms = VGSE()->helpers->get_current_provider()->get_item_terms($id, $taxonomy);

			if (empty($current_terms) || is_wp_error($current_terms)) {
				return '';
			}
			$names = wp_list_pluck($current_terms, 'name');

			return implode(', ', $names);
		}

		/**
		 * Get all terms in taxonomy
		 * @param string $taxonomy
		 * @return array|bool
		 */
		function get_taxonomy_terms($taxonomy) {
			$terms = get_terms(array('taxonomy' => $taxonomy, 'hide_empty' => false, 'fields' => 'names'));

			return $terms;
		}

		/**
		 * Get users
		 * @param int $first Display first a specific user
		 * @param bool $with_keys include user ID as array keys.
		 * @return array
		 */
		function get_authors_list($first = null, $with_keys = false) {
			global $wpdb;


			if (!VGSE()->helpers->is_editor_page() || !post_type_supports(VGSE()->helpers->get_provider_from_query_string(), 'author')) {
				return array();
			}

			$cache_key = 'wpse_authors';
			$list = wp_cache_get($cache_key);

			if (!$list) {
				// We use a custom query for performance reasons
				$blogusers = $wpdb->get_results("SELECT ID,user_login FROM $wpdb->users WHERE 1=1
ORDER BY user_login ASC", OBJECT);
				$list = array();

				if (!empty($blogusers)) {
					foreach ($blogusers as $user) {
						if (is_numeric($first) && (int) $first === $user->ID) {

							if ($with_keys) {
								$list = array_merge(array($user->ID => $user->user_login), $list);
							} else {
								array_unshift($list, $user->user_login);
							}
						}

						if ($with_keys) {
							$list[$user->ID] = $user->user_login;
						} else {
							$list[] = $user->user_login;
						}
					}
				}
				wp_cache_set($cache_key, $list);
			}

			return array_map('esc_html', $list);
		}

		/**
		 * Prepare modified date for saving.
		 * 
		 * Changes date to Y-d-m H:i:s format
		 * @param string $date
		 * @param int $post_id
		 * @return string
		 */
		function prepare_modified_date_for_saving($date = null, $post_id) {
			$current_time = get_the_modified_date('Y-m-d H:i:s', $post_id);
			return $current_time;
		}

		/**
		 * Get user ID from username
		 * @param string $author username
		 * @return int
		 */
		function get_author_id_from_username($author) {
			$autor = get_user_by('login', $author);

			if (!$autor) {
				return false;
			}
			return $autor->ID;
		}

		/**
		 * Prepare date format for saving
		 * @param string $date
		 * @param int $post_id
		 * @return string
		 */
		function change_date_format_for_saving($date) {
			// note, we had some logic related to product dates. I removed it because it seemed unnecessary.
			// Keep in mind a possible rollback in case users report issues.
			// The date must always come in Y-m-d format, so we can easily change the format here.
			$date_timestamp = ( empty($date)) ? time() : strtotime($date);
			$savedate = date('Y-m-d H:i:s', $date_timestamp);
			return $savedate;
		}

		/**
		 * Save single post data, either post data or metadata.
		 * @param int $id
		 * @param mixed $content
		 * @param string $key
		 * @param string $type
		 * @return boolean
		 */
		function save_single_post_data($id, $content, $key, $type) {

			if ($type === 'post_data') {
				$my_post['ID'] = $id;
				if (strpos($key, 'post_') === false) {
					$my_post['post_' . $key] = $content;
				} else {
					$my_post[$key] = $content;
				}

				if (!empty($my_post['post_title'])) {
					$my_post['post_title'] = wp_strip_all_tags($my_post['post_title']);
				}
				$post_id = VGSE()->helpers->get_current_provider()->update_item_data($my_post, true);
				if (is_wp_error($post_id)) {
					return $post_id;
				}
			} else if ($type === 'meta_data' || $type === 'post_meta') {
				VGSE()->helpers->get_current_provider()->update_item_meta($id, $key, $content);
			}
			return true;
		}

		/**
		 * Get all post titles from post type
		 * @global type $wpdb
		 * @param string $post_type
		 * @param array $output ARRAY_N or ARRAY_A
		 * @param bool $flatten
		 * @return array
		 */
		function get_all_post_titles_from_post_type($post_type, $output = ARRAY_N, $flatten = false) {

			global $wpdb;
			$results = $wpdb->get_results("SELECT post_title FROM $wpdb->posts WHERE post_type = '" . esc_sql($post_type) . "' AND post_status IN ('" . implode("','", array_keys(VGSE()->helpers->get_current_provider()->get_statuses())) . "') ", $output);

			if ($flatten) {
				$results = VGSE()->helpers->array_flatten($results, array());
			}

			return $results;
		}

		/**
		 * Prepare post terms for saving.
		 * 
		 * Convert a string of terms separated by commas to a terms IDs array.
		 * If the term doesn´t exist, it creates it automatically.
		 * 
		 * @param string $categories
		 * @param string $taxonomy
		 * @return array
		 */
		function prepare_post_terms_for_saving($categories, $taxonomy) {
			$cats = array_map('trim', explode(',', sanitize_text_field($categories)));
			if (!$cats) {
				$cats = array($categories);
			}
			$cate = array();
			$created = 0;
			foreach ($cats as $cat) {
				if (empty($cat)) {
					break;
				}

				$term = get_term_by('name', $cat, $taxonomy);
				if (!$term) {
					$insert = wp_insert_term($cat, $taxonomy);

					if (is_wp_error($insert) || empty($insert['term_id'])) {
						continue;
					}
					$cate[] = (int) $insert['term_id'];
					$created++;
				} else {
					$cate[] = (int) $term->term_id;
				}
			}

			VGSE()->helpers->increase_counter('editions', $created);
			return $cate;
		}

		/**
		 * Get posts count by post type
		 * @global obj $wpdb
		 * @param string $current_post post type
		 * @return int
		 */
		function total_posts($current_post) {
			$provider = VGSE()->helpers->get_data_provider($current_post);
			return $provider->get_total($current_post);
		}

		/**
		 * Get post status key from friendly name
		 * @param string $status
		 * @return boolean|string
		 */
		function get_status_key_from_name($status) {

			$statuses = VGSE()->helpers->get_current_provider()->get_statuses();

			if (!in_array($status, $statuses)) {
				return false;
			}

			$status_key = array_search($status, $statuses);

			return $status_key;
		}

		/*
		 * Devuelve el parent de cada post si lo tiene
		 */

		/**
		 * Get post ID from title
		 * @global obj $wpdb
		 * @param string $page_title
		 * @param string $output OBJECT , ARRAY_N , or ARRAY_A.
		 * @return ID
		 */
		function get_post_id_from_title($page_title, $post_type = null ) {
			global $wpdb;
			
			if( empty($page_title)){
				return null;
			}
			
			if( ! $post_type ){
			$post_type = (isset($_REQUEST['post_type'])) ? sanitize_text_field($_REQUEST['post_type']) : 'post';
			}
			$post_id = $wpdb->get_var($wpdb->prepare("SELECT ID FROM $wpdb->posts WHERE post_title = %s AND post_type= %s", $page_title, esc_sql($post_type)));
			if ($post_id) {
				return $post_id;
			}
			return null;
		}

		/**
		 * Get post statuses by friendly names.
		 * @return array
		 */
		function get_post_statuses() {

			$status = VGSE()->helpers->get_current_provider()->get_statuses();
			$list = array();

			foreach ($status as $item) {
				$list[] = esc_html($item);
			}

			return $list;
		}

		/**
		 * Creates or returns an instance of this class.
		 *
		 */
		static function get_instance() {
			if (null == WP_Sheet_Editor_Data::$instance) {
				WP_Sheet_Editor_Data::$instance = new WP_Sheet_Editor_Data();
			}
			return WP_Sheet_Editor_Data::$instance;
		}

		function __set($name, $value) {
			$this->$name = $value;
		}

		function __get($name) {
			return $this->$name;
		}

	}

}