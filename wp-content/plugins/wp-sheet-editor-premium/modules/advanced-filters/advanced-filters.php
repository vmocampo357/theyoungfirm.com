<?php
if (!class_exists('WP_Sheet_Editor_Advanced_Filters')) {

	/**
	 * Filter rows in the spreadsheet editor.
	 */
	class WP_Sheet_Editor_Advanced_Filters {

		static private $instance = false;
		var $addon_helper = null;
		var $plugin_url = null;
		var $plugin_dir = null;

		private function __construct() {
			
		}

		function init() {

			$this->plugin_url = plugins_url('/', __FILE__);
			$this->plugin_dir = __DIR__;

			add_action('admin_enqueue_scripts', array($this, 'register_assets'));
			add_action('vg_sheet_editor/filters/after_fields', array($this, 'add_filters_fields'), 10, 2);
			add_action('vg_sheet_editor/filters/before_form_closing', array($this, 'add_advanced_filters_fields'), 10, 2);
			add_filter('vg_sheet_editor/load_rows/wp_query_args', array($this, 'filter_posts'), 10, 2);
			add_filter('vg_sheet_editor/filters/allowed_fields', array($this, 'register_filters'), 10, 2);
		}

		/**
		 * Register frontend assets
		 */
		function register_assets() {
			$pages_to_load_assets = VGSE()->frontend_assets_allowed_on_pages();
			if (!empty($_GET['page']) && in_array($_GET['page'], $pages_to_load_assets)) {
				$this->_enqueue_assets();
			}
		}

		function _enqueue_assets() {
			wp_enqueue_script('advanced-filters_js', $this->plugin_url . 'assets/js/init.js', array(), '0.1', false);
		}

		function register_filters($filters, $post_type) {
			$filters['keyword_exclude'] = array(
				'label' => __('NOT Contains this keyword', VGSE()->textname),
				'description' => __('Enter a keyword to exclude posts', VGSE()->textname)
			);
			$filters['taxonomy_term'] = array(
				'label' => '',
				'description' => '',
			);
			$filters['date'] = array(
				'label' => '',
				'description' => '',
			);
			if (post_type_supports($post_type, 'page-attributes') && $post_type !== 'attachment') {
				$filters['post_parent'] = array(
					'label' => __('Parent', VGSE()->textname),
					'description' => ''
				);
			}
			return $filters;
		}

		/**
		 * Apply filters to wp-query args
		 * @param array $query_args
		 * @param array $data
		 * @return array
		 */
		function filter_posts($query_args, $data) {

			if (!empty($data['filters'])) {
				$filters = WP_Sheet_Editor_Filters::get_instance()->get_raw_filters($data);

				foreach ($filters as $filter_key => $filter_value) {

					if (is_array($filter_value)) {
						$filters[$filter_key] = array_filter($filter_value);
					}
				}

				if (!empty($filters['apply_to']) && is_array($filters['apply_to'])) {
					$taxonomies_group = array();

					foreach ($filters['apply_to'] as $term) {
						$term_parts = explode('--', $term);
						if (count($term_parts) !== 2) {
							continue;
						}
						$taxonomy = $term_parts[0];
						$term = $term_parts[1];

						if (!isset($taxonomies_group[$taxonomy])) {
							$taxonomies_group[$taxonomy] = array();
						}
						$taxonomies_group[$taxonomy][] = $term;
					}

					$query_args['tax_query'] = array(
						'relation' => 'AND',
					);

					foreach ($taxonomies_group as $taxonomy_key => $terms) {
						$query_args['tax_query'][] = array(
							'taxonomy' => $taxonomy_key,
							'field' => 'slug',
							'terms' => $terms
						);
					}
				}

				if (!empty($filters['keyword_exclude'])) {
					$editor = VGSE()->helpers->get_provider_editor($query_args['post_type']);
					$post_id_exclude = $editor->provider->get_item_ids_by_keyword($filters['keyword_exclude'], $query_args['post_type'], 'NOT LIKE');
					$query_args['post__not_in'] = $post_id_exclude;
				}

				if (!empty($filters['post_parent'])) {
					$query_args['post_parent'] = (int) str_replace('page--', '', $filters['post_parent']);
				}
				if (!empty($filters['date_from']) || !empty($filters['date_to'])) {
					$query_args['date_query'] = array(
						'inclusive' => true
					);
				}
				if (!empty($filters['date_from'])) {
					$query_args['date_query']['after'] = $filters['date_from'];
				}
				if (!empty($filters['date_to'])) {
					$query_args['date_query']['before'] = $filters['date_to'];
				}
				if (!empty($filters['meta_query']) && is_array($filters['meta_query'])) {
					foreach ($filters['meta_query'] as $index => $meta_query) {
						if (is_array($meta_query['key'])) {
							$meta_query['key'] = array_filter($meta_query['key']);
						}
						if (empty($meta_query['key']) || empty($meta_query['compare'])) {
							unset($filters['meta_query'][$index]);
							continue;
						}
						if (in_array($meta_query['compare'], array('>', '>=', '<', '<='))) {
							$filters['meta_query'][$index]['type'] = 'NUMERIC';
						}
						if (empty($meta_query['value']) && in_array($meta_query['compare'], array('=', 'LIKE'))) {
							$not_exists = $meta_query;
							$not_exists['compare'] = 'NOT EXISTS';
							$filters['meta_query'][$index] = array(
								'relation' => 'OR',
								$meta_query,
								$not_exists
							);
						}
					}


					$query_args['meta_query'] = $filters['meta_query'];
				}
			}


			return $query_args;
		}

		function add_filters_fields($current_post_type, $filters) {
			?>

			<?php if (isset($filters['keyword_exclude'])) {
				?>
				<li>
					<label><?php echo $filters['keyword_exclude']['label']; ?>  <?php if (!empty($filters['keyword_exclude']['description'])) { ?><a href="#" class="tipso" data-tipso="<?php echo $filters['keyword_exclude']['description']; ?>">( ? )</a><?php } ?></label>
					<input type="text" name="keyword_exclude">
				</li>
				<?php
			}

			if (isset($filters['taxonomy_term'])) {
				?>
				<li class="<?php
				$labels = VGSE()->helpers->get_post_type_taxonomies_single_data($current_post_type, 'label');
				if (empty($labels)) {
					echo ' hidden';
				}

				$labels[count($labels) - 1] = ' or ' . end($labels);
				?>">
					<label><?php printf(__('Enter %s', VGSE()->textname), implode(', ', $labels)); ?> <a href="#" class="tipso" data-tipso="<?php _e('Enter the names of ' . implode(', ', $labels)); ?>">( ? )</a></label>
					<select data-placeholder="<?php _e('Category name...', VGSE()->textname); ?>" name="apply_to[]" class="select2"  multiple data-remote="true" data-action="vgse_search_taxonomy_terms" data-min-input-length="4">

					</select>
				</li>
			<?php } ?>

			<?php if (isset($filters['post_parent'])) { ?>
				<li>
					<label><?php echo $filters['post_parent']['label']; ?>  <?php if (!empty($filters['post_parent']['description'])) { ?><a href="#" class="tipso" data-tipso="<?php echo $filters['post_parent']['description']; ?>">( ? )</a><?php } ?></label>
					<select name="post_parent" data-remote="true" data-min-input-length="4" data-action="vgse_find_post_by_name" data-post-type="<?php echo $current_post_type; ?>" data-nonce="<?php echo wp_create_nonce('bep-nonce'); ?>" data-placeholder="<?php _e('Select...', VGSE()->textname); ?> " class="select2" multiple>
						<option></option>
					</select> 									
				</li>
			<?php } ?>
			<?php if (isset($filters['date'])) { ?>
				<li>
					<label><?php _e('Date range from', VGSE()->textname); ?> <a href="#" class="tipso" data-tipso="<?php _e('Show items published between these dates'); ?>">( ? )</a></label><input type="date" name="date_from" /><br/> <?php _e('to', VGSE()->textname); ?><br/> <input type="date" name="date_to" />
				</li>
				<?php
			}
		}

		function add_advanced_filters_fields($current_post_type, $filters) {
			?>

			<p><label><input type="checkbox" class="advanced-filters-toggle"> <?php _e('Enable advanced filters', VGSE()->textname); ?></label></p>
			<div class="advanced-filters"  style="display: none;">
				<h3><?php _e('Advanced search', VGSE()->textname); ?></h3>
				<p><?php _e('For example, to get all products with price higher than 100, use: _regular_price > 100', VGSE()->textname); ?></p>
				<ul class="unstyled-list">
					<li class="base" style="display: none;">
						<div class="fields-wrap">
							<div class="field-wrap">
								<label><?php _e('Field key', VGSE()->textname); ?></label>
								<select name="meta_query[][key]" data-placeholder="<?php _e('Select...', VGSE()->textname); ?>" class="select2">
									<option value="" selected>- -</option>
									<?php
									$all_meta_keys = apply_filters('vg_sheet_editor/advanced_filters/all_meta_keys', VGSE()->helpers->get_all_meta_keys($current_post_type), $current_post_type, $filters);

									if (!empty($all_meta_keys) && is_array($all_meta_keys)) {
										foreach ($all_meta_keys as $meta_key) {
											echo '<option value="' . $meta_key . '" ';
											echo '>' . $meta_key . '</option>';
										}
									}
									?>
								</select>
							</div>
							<div class="field-wrap">
								<label><?php _e('Operator', VGSE()->textname); ?></label>
								<select name="meta_query[][compare]" data-placeholder="<?php _e('Select...', VGSE()->textname); ?>" class="">
									<option value="=" selected>=</option>
									<option value="!=" >!=</option>
									<option value="<" ><</option>
									<option value="<=" ><=</option>
									<option value=">" >></option>
									<option value=">=" >>=</option>
									<option value="LIKE" >LIKE</option>
									<option value="NOT LIKE" >NOT LIKE</option>
								</select>
							</div>
							<div class="field-wrap">
								<label><?php _e('Value', VGSE()->textname); ?></label>
								<input name="meta_query[][value]" />
							</div>
						</div>
						<div class="fields-wrap"><a href="#" class="button remove-advanced-filter"><?php _e('X', VGSE()->textname); ?></a></div>
					</li>
					<?php
					do_action('vg_sheet_editor/filters/after_advanced_fields', $current_post_type);
					?>
					<li>
						<button type="submit" class="button primary hidden apply-formula-submit-inside"><?php _e('Apply filters', VGSE()->textname); ?></button>

					</li>
				</ul>

				<div class="fields-wrap"><a href="#" class="button new-advanced-filter"><?php _e('Add new', VGSE()->textname); ?></a></div>
			</div>
			<?php
		}

		/**
		 * Creates or returns an instance of this class.
		 */
		static function get_instance() {
			if (null == WP_Sheet_Editor_Advanced_Filters::$instance) {
				WP_Sheet_Editor_Advanced_Filters::$instance = new WP_Sheet_Editor_Advanced_Filters();
				WP_Sheet_Editor_Advanced_Filters::$instance->init();
			}
			return WP_Sheet_Editor_Advanced_Filters::$instance;
		}

		function __set($name, $value) {
			$this->$name = $value;
		}

		function __get($name) {
			return $this->$name;
		}

	}

	add_action('vg_sheet_editor/initialized', 'vgse_advanced_filters_init');

	function vgse_advanced_filters_init() {
		WP_Sheet_Editor_Advanced_Filters::get_instance();
	}

}
