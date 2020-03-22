<?php
if (!class_exists('WP_Sheet_Editor_Filters')) {

	/**
	 * Filter rows in the spreadsheet editor.
	 */
	class WP_Sheet_Editor_Filters {

		static private $instance = false;
		var $addon_helper = null;
		var $plugin_url = null;
		var $plugin_dir = null;

		private function __construct() {
			
		}

		function init() {


			$this->plugin_url = plugins_url('/', __FILE__);
			$this->plugin_dir = __DIR__;

			add_action('vg_sheet_editor/editor_page/after_content', array($this, 'render_filters_form'));
			add_action('vg_sheet_editor/editor/before_init', array($this, 'register_toolbar_locate'), 999);
			add_action('vg_sheet_editor/editor/before_init', array($this, 'register_toolbar_search'), 8);
			add_action('admin_enqueue_scripts', array($this, 'register_assets'));
			add_filter('vg_sheet_editor/load_rows/wp_query_args', array($this, 'filter_posts'), 10, 2);
			add_filter('vg_sheet_editor/handsontable/custom_args', array($this, 'enable_cell_locator_js'));
		}

		function enable_cell_locator_js($args) {
			$args['search'] = true;
			return $args;
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
			wp_enqueue_script('filters_js', $this->plugin_url . 'assets/js/init.js', array(), '0.1', false);
		}

		/**
		 * Register toolbar items
		 */

		/**
		 * Register toolbar items
		 */
		function register_toolbar_locate($editor) {

			if ($editor->provider->is_post_type) {
				$post_types = $editor->args['enabled_post_types'];
			} elseif ($editor->provider->key === 'user') {
				$post_types = array(
					'user'
				);
			}

			foreach ($post_types as $post_type) {
				$editor->args['toolbars']->register_item('cell_locator', array(
					'type' => 'html',
					'help_tooltip' => __('Locate and highlight cell in the spreadsheet. It works on loaded posts only, if you want to search among all posts you need to load all posts first.', VGSE()->textname),
					'content' => '<input type="search" id="cell-locator-input" placeholder="Locate cell"/>',
					'label' => __('Locate cell.', VGSE()->textname),
						), $post_type);
			}
		}

		function register_toolbar_search($editor) {

			if ($editor->provider->is_post_type) {
				$post_types = $editor->args['enabled_post_types'];
			} elseif ($editor->provider->key === 'user') {
				$post_types = array(
					'user'
				);
			}
			foreach ($post_types as $post_type) {
				$editor->args['toolbars']->register_item('run_filters', array(
					'type' => 'button',
					'help_tooltip' => __('Make advanced searches and show only the matching rows in the spreadsheet. You can search by multiple conditions at once.', VGSE()->textname),
					'content' => __('Search', VGSE()->textname),
					'icon' => 'fa fa-search',
					'extra_html_attributes' => 'data-remodal-target="modal-filters"',
						), $post_type);
			}
		}

		function get_raw_filters($data = array()) {
			// We need to use $_REQUEST because all sanitization 
			// functions mess up the operators encoding. It´s fine, 
			// WP_Query sanitizes everything.
			$raw_filters = null;
			if (isset($_REQUEST['filters'])) {
				$raw_filters = $_REQUEST['filters'];
			} elseif (isset($_REQUEST['raw_form_data']['filters'])) {
				$raw_filters = $_REQUEST['raw_form_data']['filters'];
			}
			parse_str(urldecode(html_entity_decode($raw_filters)), $filters);
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
				$filters = $this->get_raw_filters($data);

				foreach ($filters as $filter_key => $filter_value) {

					if (is_array($filter_value)) {
						$filters[$filter_key] = array_filter($filter_value);
					}
				}

				if (!empty($filters['post_status'])) {
					$filters['post_status'] = array_filter($filters['post_status']);
					$query_args['post_status'] = $filters['post_status'];
				}

				if (!empty($filters['post_author']) && is_array($filters['post_author']) && current_user_can('edit_others_posts')) {
					$filters['post_author'] = array_filter($filters['post_author']);
					$query_args['author__in'] = array_map('intval', $filters['post_author']);
				}

				if (!empty($filters['keyword'])) {
					$editor = VGSE()->helpers->get_provider_editor($query_args['post_type']);
					$post_id_include = $editor->provider->get_item_ids_by_keyword($filters['keyword'], $query_args['post_type'], 'LIKE');
					$query_args['post__in'] = ( empty($post_id_include) ) ? array(time() * 2) : $post_id_include;
				}
			}

			return $query_args;
		}

		/**
		 * Render filters modal html
		 * @param string $current_post_type
		 */
		function render_filters_form($current_post_type) {

			$filters = apply_filters('vg_sheet_editor/filters/allowed_fields', array(
				'keyword' => array(
					'label' => __('Contains keyword', VGSE()->textname),
					'description' => __('It searches in the post title and post content.<br/>Search by multiple keywords separating keywords with a semicolon (;)'),
				),
				'post_status' => array(
					'label' => __('Status', VGSE()->textname),
					'description' => ''
				),
				'post_author' => array(
					'label' => __('Author', VGSE()->textname),
					'description' => ''
				),
					), $current_post_type);
			?>


			<div class="remodal remodal8" data-remodal-id="modal-filters" data-remodal-options="closeOnOutsideClick: false">

				<div class="modal-content">
					<form action="<?php echo admin_url(); ?>" method="GET" id="be-filters" >
						<h3><?php _e('Search', VGSE()->textname); ?></h3>
						<p><?php _e('This feature allows you to filter the items in the spreadsheet to display only the items you want to edit.<br/>If you want to edit ALL POSTS you can leave empty all the search fields.', VGSE()->textname); ?></p>

						<?php do_action('vg_sheet_editor/filters/above_form_fields', $filters, $current_post_type); ?>

						<ul class="unstyled-list basic-filters">
							<?php if (isset($filters['keyword'])) { ?>
								<li>
									<label><?php echo $filters['keyword']['label']; ?> <?php if (!empty($filters['keyword']['description'])) { ?><a href="#" class="tipso" data-tipso="<?php echo $filters['keyword']['description']; ?>">( ? )</a><?php } ?></label><input type="text" name="keyword" />
								</li>
							<?php } ?>
							<?php if (isset($filters['post_status'])) { ?>
								<li>
									<label><?php echo $filters['post_status']['label']; ?>  <?php if (!empty($filters['post_status']['description'])) { ?><a href="#" class="tipso" data-tipso="<?php echo $filters['post_status']['description']; ?>">( ? )</a><?php } ?></label>
									<select name="post_status[]" multiple data-placeholder="<?php _e('Select...', VGSE()->textname); ?>" class="select2">
										<?php
										$statuses = get_post_statuses();
										if (!empty($statuses) && is_array($statuses)) {
											foreach ($statuses as $item => $value) {
												echo '<option value="' . $item . '" ';
												echo '>' . $value . '</option>';
											}
										}
										?>
									</select>
								</li>
							<?php } ?>
							<?php if (isset($filters['post_author'])) { ?>
								<li>
									<label><?php echo $filters['post_author']['label']; ?>  <?php if (!empty($filters['post_author']['description'])) { ?><a href="#" class="tipso" data-tipso="<?php echo $filters['post_author']['description']; ?>">( ? )</a><?php } ?></label>
									<select name="post_author[]" multiple data-placeholder="<?php _e('Select...', VGSE()->textname); ?>" class="select2">
										<?php
										$authors = VGSE()->data_helpers->get_authors_list(null, true);
										if (!empty($authors) && is_array($authors)) {
											foreach ($authors as $item => $value) {
												echo '<option value="' . $item . '" ';
												echo '>' . $value . '</option>';
											}
										}
										?>
									</select>
								</li>
							<?php } ?>

							<?php
							do_action('vg_sheet_editor/filters/after_fields', $current_post_type, $filters);
							?>
						</ul>

						<?php
						do_action('vg_sheet_editor/filters/before_form_closing', $current_post_type, $filters);
						?>
						<button type="submit" class="remodal-confirm"><?php _e('Run search', VGSE()->textname); ?></button>
						<button data-remodal-action="confirm" class="remodal-cancel"><?php _e('Close', VGSE()->textname); ?></button>
					</form>
				</div>
				<br>
			</div>
			<?php
		}

		/**
		 * Creates or returns an instance of this class.
		 */
		static function get_instance() {
			if (null == WP_Sheet_Editor_Filters::$instance) {
				WP_Sheet_Editor_Filters::$instance = new WP_Sheet_Editor_Filters();
				WP_Sheet_Editor_Filters::$instance->init();
			}
			return WP_Sheet_Editor_Filters::$instance;
		}

		function __set($name, $value) {
			$this->$name = $value;
		}

		function __get($name) {
			return $this->$name;
		}

	}

	add_action('vg_sheet_editor/initialized', 'vgse_filters_init');

	function vgse_filters_init() {
		WP_Sheet_Editor_Filters::get_instance();
	}

}