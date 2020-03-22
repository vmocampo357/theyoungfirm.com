<?php
// Init apply formula in bulk controller
require_once 'apply-formula-bulk.php';
if (!class_exists('WP_Sheet_Editor_Formulas')) {

	/**
	 * Use formulas in the spreadsheet editor to update a lot of posts at once.
	 */
	class WP_Sheet_Editor_Formulas {

		static private $instance = false;
		var $addon_helper = null;
		var $plugin_url = null;
		var $plugin_dir = null;
		var $documentation_url = 'https://wpsheeteditor.com/documentation/how-to-use-the-formulas/?utm_source=product&utm_medium=pro-plugin&utm_campaign=formulas-field-help';
		static $regex_flag = '[::regex::]';
		static $textname = 'vg_sheet_editor';
		var $future_posts_formula_key = 'vgse_future_posts_formulas';

		private function __construct() {
			
		}

		function init() {


			$this->plugin_url = plugins_url('/', __FILE__);
			$this->plugin_dir = __DIR__;

			// Init wp hooks
			add_action('wp_ajax_vgse_bulk_edit_formula_big', array($this, 'bulk_execute_formula_ajax'));
			add_action('wp_ajax_vgse_delete_saved_formula', array($this, 'delete_formula'));
			add_action('wp_ajax_vgse_save_formula', array($this, 'save_formula'));

			add_action('vg_sheet_editor/editor_page/after_content', array($this, 'render_formulas_form'));
			add_action('vg_sheet_editor/editor/before_init', array($this, 'register_toolbar_items'));
			add_action('admin_enqueue_scripts', array($this, 'register_assets'));

			add_action('vg_sheet_editor/add_new_posts/after_all_posts_created', array($this, 'apply_formula_to_posts'), 10, 2);
			add_action('vg_sheet_editor/save_rows/after_saving_rows', array($this, 'apply_formula_after_saving_posts'), 10, 2);
			add_action('vg_sheet_editor/woocommerce/variable_product_updated', array($this, 'apply_formula_after_saving_variable_products'));
		}

		function apply_formula_after_saving_variable_products($modified_data) {
			$this->apply_formula_to_posts(array($modified_data['ID']), get_post_type($modified_data['ID']));
		}

		function apply_formula_after_saving_posts($rows, $post_type) {
			$post_ids = wp_list_pluck($rows, 'ID');

			$this->apply_formula_to_posts($post_ids, $post_type);
		}

		function apply_formula_to_posts($new_posts_ids, $post_type) {
			$future_posts_formulas = get_option($this->future_posts_formula_key, array());

			$post_type_formulas = wp_list_filter($future_posts_formulas, array('post_type' => $post_type));

			if (empty($post_type_formulas)) {
				return;
			}

			// Check if every single post matches any formula
			foreach ($new_posts_ids as $post_id) {
				$post_id = VGSE()->helpers->sanitize_integer($post_id);
				foreach ($post_type_formulas as $formula) {
					unset($formula['raw_form_data']['apply_to_future_posts']);
					$formula['custom_wp_query_params'] = array(
						'post__in' => array($post_id)
					);

					$result = $this->bulk_execute_formula($formula);
					clean_post_cache($post_id);
				}
			}
		}

		/**
		 * Register frontend assets
		 */
		function register_assets() {

			$current_post = VGSE()->helpers->get_provider_from_query_string();
			$pages_to_load_assets = VGSE()->frontend_assets_allowed_on_pages();
			if (empty($_GET['page']) || !in_array($_GET['page'], $pages_to_load_assets)) {
				return;
			}
			wp_enqueue_style('formulas_css', $this->plugin_url . 'assets/css/styles.css', '', '0.1', 'all');
			wp_enqueue_script('formulas_js', $this->plugin_url . 'assets/js/init.js', array(), '0.1', false);
			wp_localize_script('formulas_js', 'vgse_formulas_data', apply_filters('vg_sheet_editor/formulas/form_settings', array(
				'texts' => array(
					'formula_required' => __('The formula can´t be empty. Please use the formulas builder to set up the formula.', VGSE()->textname),
					'action_select_label' => __('Select type of edit', VGSE()->textname),
					'action_select_placeholder' => __('Select formula', VGSE()->textname),
					'wrong_formula' => __('It seems that you entered a wrong formula.', VGSE()->textname),
				),
				'default_actions' =>
				array(
					'math' =>
					array(
						'label' => __('Math operation', VGSE()->textname),
						'description' => __('Update existing value with the result of a math operation.', VGSE()->textname),
						'fields_relationship' => 'AND',
						'jsCallback' => 'vgseGenerateMathFormula',
						'input_fields' =>
						array(
							array(
								'tag' => 'input',
								'html_attrs' => array(
									'type' => 'text',
								),
								'label' => __('Math formula', VGSE()->textname),
								'description' => __('Example 1: $current_value$ + 2 * 5. <br/>Example 2: $_regular_price$ * 0.7 (Set regular price - 30%)', VGSE()->textname),
							),
						),
					),
					'decrease_by_percentage' =>
					array(
						'label' => __('Decrease by percentage', VGSE()->textname),
						'description' => __('Decrease the existing value by a percentage.', VGSE()->textname),
						'fields_relationship' => 'AND',
						'jsCallback' => 'vgseGenerateDecreasePercentageFormula',
						'input_fields' =>
						array(
							array(
								'tag' => 'input',
								'html_attrs' => array(
									'type' => 'number',
								),
								'label' => __('Decrease by', VGSE()->textname),
								'description' => __('Enter the percentage number.', VGSE()->textname),
							),
						),
					),
					'decrease_by_number' =>
					array(
						'label' => __('Decrease by number', VGSE()->textname),
						'description' => __('Decrease the existing value by a number.', VGSE()->textname),
						'fields_relationship' => 'AND',
						'jsCallback' => 'vgseGenerateDecreaseFormula',
						'input_fields' =>
						array(
							array(
								'tag' => 'input',
								'html_attrs' => array(
									'type' => 'number',
								),
								'label' => __('Decrease by', VGSE()->textname),
								'description' => __('Enter the number.', VGSE()->textname),
							),
						),
					),
					'increase_by_percentage' =>
					array(
						'label' => __('Increase by percentage', VGSE()->textname),
						'description' => __('Increase the existing value by a percentage.', VGSE()->textname),
						'fields_relationship' => 'AND',
						'jsCallback' => 'vgseGenerateIncreasePercentageFormula',
						'input_fields' =>
						array(
							array(
								'tag' => 'input',
								'html_attrs' => array(
									'type' => 'number',
								),
								'label' => __('Increase by', VGSE()->textname),
								'description' => __('Enter the percentage number.', VGSE()->textname),
							),
						),
					),
					'increase_by_number' =>
					array(
						'label' => __('Increase by number', VGSE()->textname),
						'description' => __('Increase the existing value by a number.', VGSE()->textname),
						'fields_relationship' => 'AND',
						'jsCallback' => 'vgseGenerateIncreaseFormula',
						'input_fields' =>
						array(
							array(
								'tag' => 'input',
								'html_attrs' => array(
									'type' => 'number',
								),
								'label' => __('Increase by', VGSE()->textname),
								'description' => __('Enter the number.', VGSE()->textname),
							),
						),
					),
					'set_value' =>
					array(
						'label' => __('Set value', VGSE()->textname),
						'description' => sprintf(__('Replace existing value with this value. <a href="%s" target="_blank">Read more</a>', VGSE()->textname), $this->documentation_url),
						'fields_relationship' => 'AND',
						'jsCallback' => 'vgseGenerateSetValueFormula',
						'input_fields' =>
						array(
							array(
								'tag' => 'textarea',
							),
						),
					),
					'replace' =>
					array(
						'label' => __('Replace', VGSE()->textname),
						'description' => sprintf(__('Replace a word, phrase, or number with a new value. <a href="%s" target="_blank">Read more</a>', VGSE()->textname), $this->documentation_url),
						'fields_relationship' => 'AND',
						'jsCallback' => 'vgseGenerateReplaceFormula',
						'input_fields' =>
						array(
							array(
								'tag' => 'textarea',
								'label' => __('Replace this', VGSE()->textname),
							),
							array(
								'tag' => 'textarea',
								'label' => __('With this', VGSE()->textname),
							),
						),
					),
					'append' =>
					array(
						'label' => __('Append', VGSE()->textname),
						'fields_relationship' => 'AND',
						'jsCallback' => 'vgseGenerateAppendFormula',
						'input_fields' =>
						array(
							array(
								'tag' => 'input',
								'html_attrs' => array(
									'type' => 'text',
								),
								'label' => __('Enter the value to append to the existing value.', VGSE()->textname),
							),
						),
					),
					'prepend' =>
					array(
						'label' => __('Prepend', VGSE()->textname),
						'fields_relationship' => 'AND',
						'jsCallback' => 'vgseGeneratePrependFormula',
						'input_fields' =>
						array(
							array(
								'tag' => 'input',
								'html_attrs' => array(
									'type' => 'text',
								),
								'label' => __('Enter the value to prepend to the existing value.', VGSE()->textname),
							),
						),
					),
					'custom' =>
					array(
						'label' => __('Custom formula', VGSE()->textname),
						'fields_relationship' => 'AND',
						'jsCallback' => 'vgseGenerateCustomFormula',
						'input_fields' =>
						array(
							array(
								'tag' => 'input',
								'html_attrs' => array(
									'type' => 'text',
								),
								'label' => sprintf(__('Only for advanced users. <a href="%s" target="_blank">Read more.</a>', VGSE()->textname), $this->documentation_url),
							),
						),
					),
					'merge_columns' =>
					array(
						'label' => __('Copy from other columns', VGSE()->textname),
						'fields_relationship' => 'OR',
						'jsCallback' => 'vgseGenerateMergeFormula',
						'description' => __('Copy the value of other fields into this field.<br/>Example, copy "sale price" into the "regular price" field.', VGSE()->textname),
						'input_fields' =>
						array(
							array(
								'tag' => 'select',
								'html_attrs' =>
								array(
									'multiple' => false,
									'class' => 'select2'
								),
								'options' => '<option value="">(none)</option>' . VGSE()->helpers->get_post_type_columns_options($current_post, array(
									'conditions' => array(
										'supports_formulas' => true
									),
										), true),
								'label' => __('Copy from this column', VGSE()->textname),
							),
							array(
								'tag' => 'textarea',
								'label' => __('Copy from multiple columns', VGSE()->textname),
								'description' => __("Example: 'Articles written by \$post_author\$ on \$post_date\$' = 'Articles written by Adam on 24-12-2017'.<br/>Another example: '\$category\$-\$_regular_price\$ EUR' would be 'Videos - 25 EUR'", VGSE()->textname),
							),
						),
					),
				),
				'columns_actions' =>
				array(
					'text' => array(
						"set_value" => 'default',
						"replace" => 'default',
						"append" => 'default',
						"prepend" => 'default',
						"merge_columns" => 'default',
						'custom' => 'default',
					),
					'boton_tiny' => array(
						"set_value" => 'default',
						"replace" => 'default',
						"append" => 'default',
						"prepend" => 'default',
						"merge_columns" => 'default',
						'custom' => 'default',
					),
					'boton_gallery_multiple' =>
					array(
						'set_value' =>
						array(
							'description' => __('We will replace the existing media file(s) with these file(s).', VGSE()->textname),
							'fields_relationship' => 'OR',
							'input_fields' =>
							array(
								array(
									'tag' => 'a',
									'html_attrs' =>
									array(
										'data-multiple' => true,
										'class' => 'wp-media button'
									),
									'label' => __('Upload the files', VGSE()->textname),
								),
								array(
									'tag' => 'input',
									'html_attrs' => array(
										'type' => 'url',
									),
									'label' => __('File URLs', VGSE()->textname),
									'description' => __('Enter the URLs separated by commas. They can be from your own site.', VGSE()->textname),
								),
							),
						),
						'replace' =>
						array(
							'description' => __('Replace a media file with other file', VGSE()->textname),
							'fields_relationship' => 'AND',
							'input_fields' =>
							array(
								array(
									'tag' => 'input',
									'html_attrs' => array(
										'type' => 'url',
									),
									'label' => __('Replace these files', VGSE()->textname),
									'description' => __('Enter the URLs separated by commas. They must be from your own site.', VGSE()->textname),
								),
								array(
									'tag' => 'input',
									'html_attrs' => array(
										'type' => 'url',
									),
									'label' => __('With these files', VGSE()->textname),
									'description' => __('Enter the URLs separated by commas. They must be from your own site.', VGSE()->textname),
								),
							),
						),
						'custom' => 'default',
					),
					'boton_gallery' =>
					array(
						'set_value' =>
						array(
							'description' => __('We will replace the existing media file with this file.', VGSE()->textname),
							'fields_relationship' => 'OR',
							'input_fields' =>
							array(
								array(
									'tag' => 'a',
									'html_attrs' =>
									array(
										'data-multiple' => false,
										'class' => 'wp-media button'
									),
									'label' => __('Upload the file', VGSE()->textname),
								),
								array(
									'tag' => 'input',
									'html_attrs' => array(
										'type' => 'url',
									),
									'label' => __('File URL', VGSE()->textname),
									'description' => __('Enter the URL. It can be an URL from your own site (Example http://site.com/wp-content/uploads/2016/01/file.jpg) or an external URL.', VGSE()->textname),
								),
							),
						),
						'replace' =>
						array(
							'label' => __('Replace', VGSE()->textname),
							'description' => __('Replace a media file with other file', VGSE()->textname),
							'fields_relationship' => 'AND',
							'input_fields' =>
							array(
								array(
									'tag' => 'input',
									'html_attrs' => array(
										'type' => 'url',
									),
									'label' => __('Replace this file', VGSE()->textname),
									'description' => __('Enter the URL. It must be an URL from your own site. Example: http://site.com/wp-content/uploads/2016/01/file.jpg', VGSE()->textname),
								),
								array(
									'tag' => 'input',
									'html_attrs' => array(
										'type' => 'url',
									),
									'label' => __('With this file', VGSE()->textname),
									'description' => __('Enter the URL. It must be an URL from your own site. Example: http://site.com/wp-content/uploads/2016/01/file.jpg', VGSE()->textname),
								),
							),
						),
						'custom' => 'default',
					),
					'number' =>
					array(
						'set_value' =>
						array(
							'input_fields' =>
							array(
								array(
									'tag' => 'input',
									'html_attrs' => array(
										'type' => 'number'
									),
								),
							),
						),
						'increase_by_number' => 'default',
						'increase_by_percentage' => 'default',
						'decrease_by_number' => 'default',
						'decrease_by_percentage' => 'default',
						'math' => 'default',
						"merge_columns" => 'default',
						'custom' => 'default',
					),
					'post_terms' =>
					array(
						"merge_columns" => 'default',
						'set_value' =>
						array(
							'description' => __('We will replace the existing terms with these terms.', VGSE()->textname),
							'input_fields' =>
							array(
								array(
									'tag' => 'select',
									'html_attrs' =>
									array(
										'multiple' => true,
										'data-remote' => true,
										'data-action' => 'vgse_search_taxonomy_terms',
										'data-output-format' => '%name%',
										'data-min-input-length' => 4,
										'data-placeholder' => 'My category name is...',
										'class' => 'select2'
									),
									'label' => __('Value', VGSE()->textname),
									'description' => __('Enter the new value.', VGSE()->textname),
								),
							),
						),
						'replace' =>
						array(
							'description' => sprintf(__('Replace some term(s) with new term(s). <a href="%s" target="_blank">Read more</a>', VGSE()->textname), $this->documentation_url),
							'fields_relationship' => 'AND',
							'input_fields' =>
							array(
								array(
									'tag' => 'select',
									'label' => __('Replace this', VGSE()->textname),
									'html_attrs' =>
									array(
										'multiple' => true,
										'data-remote' => true,
										'data-action' => 'vgse_search_taxonomy_terms',
										'data-output-format' => '%name%',
										'data-min-input-length' => 4,
										'data-placeholder' => 'My category name is...',
										'class' => 'select2'
									),
								),
								array(
									'tag' => 'select',
									'label' => __('With this', VGSE()->textname),
									'html_attrs' =>
									array(
										'multiple' => true,
										'data-remote' => true,
										'data-output-format' => '%name%',
										'data-action' => 'vgse_search_taxonomy_terms',
										'data-min-input-length' => 4,
										'data-placeholder' => 'My category name is...',
										'class' => 'select2'
									),
								),
							),
						),
						'append' =>
						array(
							'input_fields' =>
							array(
								array(
									'tag' => 'select',
									'html_attrs' =>
									array(
										'multiple' => true,
										'data-remote' => true,
										'data-action' => 'vgse_search_taxonomy_terms',
										'data-output-format' => '%name%',
										'data-min-input-length' => 4,
										'data-placeholder' => 'My category name is...',
										'class' => 'select2'
									),
									'label' => __('Terms', VGSE()->textname),
									'description' => __('Enter the term(s) to append to the existing term(s).', VGSE()->textname),
								),
							),
						),
						'custom' => 'default',
					),
				),
							), $current_post));
		}

		/**
		 * Register toolbar item
		 */
		function register_toolbar_items($editor) {

			if ($editor->provider->is_post_type) {
				$post_types = $editor->args['enabled_post_types'];
			} elseif ($editor->provider->key === 'user') {
				$post_types = array(
					'user'
				);
			}
			foreach ($post_types as $post_type) {
				$editor->args['toolbars']->register_item('run_formula', array(
					'type' => 'button',
					'allow_in_frontend' => false,
					'help_tooltip' => __('Edit thousands of rows at once in seconds', VGSE()->textname),
					'content' => __('Apply changes in bulk', VGSE()->textname),
					'icon' => 'fa fa-terminal',
					'extra_html_attributes' => 'data-remodal-target="modal-formula"',
						), $post_type);
			}
		}

		/**
		 * Render formulas modal html
		 */
		function render_formulas_form($current_post_type) {
			$nonce = wp_create_nonce('bep-nonce');
			?>


			<div class="remodal remodal4 modal-formula" data-remodal-id="modal-formula" data-remodal-options="closeOnOutsideClick: false, hashTracking: false">

				<div class="modal-content">
					<h3><?php _e('Bulk Edit', VGSE()->textname); ?></h3>
					<p><?php _e('Using this tool you can update thousands of posts at once', VGSE()->textname); ?></p>

					<ul class="vgse-simple-tabs">
						<li><a href="#vgse-create-formula" class="active">Create formula</a></li>
						<li><a href="#ongoing-formulas">Manage ongoing formulas</a></li>
					</ul>
					<form action="<?php
					echo add_query_arg(array(
						'page' => 'vgse_run_formulas',
						'post_type' => $current_post_type,
							), admin_url('admin.php'));
					?>" method="POST" class="vgse-modal-form be-formulas vgse-simple-tab-content active" onsubmit="setFormSubmitting();" id="vgse-create-formula">
						<p><?php _e('Tip. We can do the content updates for you', VGSE()->textname); ?> <a class="help-button" href="<?php echo VGSE()->get_support_links('contact_us','url', 'formulas-intro-help'); ?>" target="_blank" ><?php _e('Need help? Contact us', VGSE()->textname); ?></a></p>
						<ul class="unstyled-list">
							<li class="posts-query">
								<p><?php _e('1. Select the rows that you want to update.', VGSE()->textname); ?> <button class="wpse-formula-post-query button"><?php _e('Select rows', VGSE()->textname); ?></button></p>
								<label class="use-search-query-container"><input type="checkbox" value="yes" required name="use_search_query"><?php _e('I understand it will update the posts from my search.', VGSE()->textname); ?> <a href="#" class="tipso tipso_style" data-tipso="<?php _e('For example, if you searched for posts by author = Mark using the search tool, we will bulk edit only posts with author Mark', VGSE()->textname); ?>">( ? )</a>
									<input type="hidden" name="filters">
									<input type="hidden" name="filters_found_rows">
								</label>	 
							</li>
							<li class="column-selector">
								<label><?php _e('What field do you want to edit?', VGSE()->textname); ?></label>
								<select name="column" required data-placeholder="<?php _e('Select column...', VGSE()->textname); ?>" class="select2">
									<option></option>
									<?php
									echo VGSE()->helpers->get_post_type_columns_options($current_post_type, array(
										'conditions' => array(
											'supports_formulas' => true
										),
									));
									?>
								</select>
								<br/><span><small><?php _e('A column is missing? <a href="#" data-remodal-target="modal-columns-visibility">Enable it</a>', VGSE()->textname); ?></small></span>
							</li>
							<li class="formula-builder">
							</li>
							<li class="formula-field">
								<label><?php _e('Generated formula:', VGSE()->textname); ?> <a href="#" class="tipso" data-tipso="Formulas available:<br/>=REPLACE(&quot;&quot;Search&quot;&quot;, &quot;&quot;Replace&quot;&quot;) <br/>=MATH( &quot;5 + 6 - $current_value&quot; )">( ? )</a></label>								
								<input type="text" required class="be-txt-input" value="" name="be-formula" readonly="readonly" />
							</li>
							<li class="use-slower-execution-field">
								<label><input type="checkbox" value="yes" name="use_slower_execution"><?php _e('Use slower execution method?', VGSE()->textname); ?> <a href="#" class="tipso tipso_style" data-tipso="<?php _e('The default way uses a faster execution method, but it might not work in all the cases. Use this option when the default way doesn´t work or doesn´t update all the posts.', VGSE()->textname); ?>">( ? )</a></label>		
							</li>
							<li class="apply-to-future-posts-field">
								<label><input type="checkbox" value="yes" name="apply_to_future_posts"><?php _e('Execute formula on future posts automatically (Advanced users only)', VGSE()->textname); ?> <a href="#" class="tipso tipso_style" data-tipso="<?php _e('If you mark this option , when you create or update a post in the spreadsheet, we will check if the post matches the formula parameters and execute the formula automatically on that post. For example. When you create a product with category apples we can set the description automatically, or when you change the SKU we can update the downloadable files URLs automatically.', VGSE()->textname); ?>">( ? )</a></label>		
							</li>
							<?php do_action('vg_sheet_editor/formulas/after_form_fields', $current_post_type); ?>
							<li>
								<input type="hidden" value="apply_formula" name="action">		
								<input type="hidden" value="<?php echo $current_post_type; ?>" name="post_type">						
								<input type="hidden" value="<?php echo $nonce; ?>" name="nonce">
								<input type="hidden" value="" name="visibles">
							</li>
							<li class="vgse-formula-actions">
								<button type="submit" class="remodal-confirm submit"><?php _e('I have a database backup, Execute Now', VGSE()->textname); ?></button>								<br/>
								<button class="remodal-secundario save-formula"><?php _e('Execute on future posts only', VGSE()->textname); ?></button> <br/>
								<button data-remodal-action="confirm" class="remodal-cancel"><?php _e('Cancel', VGSE()->textname); ?></button>
								<br/>
								<div class="alert alert-blue"><?php _e('<p>1- When you execute this you will leave the page, so please save any unsaved changes in the spreadsheet.</p><p>2- Please backup your database before executing, the changes are not reversible.</p><p>3- Make sure the bulk edit settings are correct before executing.</p>'); ?></div>
							</li>
						</ul>
					</form>

					<div class="vgse-simple-tab-content" id="ongoing-formulas">

						<p><?php _e('Here you can view all the formulas saved for ongoing execution. These formulas will be executed on posts matching the filters when they are created or updated in the spreadsheet.', VGSE()->textname); ?></p>
						<p><?php _e('Note. If you want to modify saved formulas you have to delete the formula and create it again in the formulas builder.', VGSE()->textname); ?></p>
						<?php
						$saved_formulas = get_option($this->future_posts_formula_key, array());
						if (empty($saved_formulas)) {
							?>
							<p><?php _e('You haven´t saved formulas yet.', VGSE()->textname); ?></p>
							<?php
						} else {

							$spreadsheet_columns = VGSE()->helpers->get_provider_columns($current_post_type);
							?>

							<ul>
								<?php
								foreach ($saved_formulas as $formula_index => $formula) {
									if ($formula['post_type'] !== $current_post_type || empty($formula['raw_form_data']['action_name'])) {
										continue;
									}
									$column = (isset($spreadsheet_columns[$formula['column']])) ? $spreadsheet_columns[$formula['column']] : null;
									if (empty($column)) {
										continue;
									}
									?>
									<li>
										<button class="delete-saved-formula button" data-formula-index="<?php echo $formula_index; ?>">x</button>
										<b><?php _e('Field to update:', VGSE()->textname); ?></b> <?php echo $column['title']; ?>.<br/>
										<b><?php _e('Formula type:', VGSE()->textname); ?></b> <?php echo $formula['raw_form_data']['action_name']; ?>.<br/>
										<b><?php _e('Formula parameters:', VGSE()->textname); ?></b> <?php _e('Parameter:', VGSE()->textname); ?> <?php echo implode('. Parameter: ', $formula['raw_form_data']['formula_data']); ?>.<br/>
										<b><?php _e('Apply to:', VGSE()->textname); ?></b> <?php
										if (empty($formula['raw_form_data']['apply_to']) || (is_array($formula['raw_form_data']['apply_to']) && $formula['raw_form_data']['apply_to'][0] === 'all')) {
											_e('All', VGSE()->textname);
										} else {

											$taxonomy_labels = array();
											foreach ($formula['raw_form_data']['apply_to'] as $group) {
												$group_parts = explode('--', $group);
												$taxonomy_key = $group_parts[0];
												$term = get_term_by('slug', $group_parts[1], $taxonomy_key);

												if (!isset($taxonomy_labels[$taxonomy_key])) {
													$taxonomy = get_taxonomy($taxonomy_key);
													$taxonomy_labels[$taxonomy_key] = $taxonomy->label;
												}

												echo $term->name . ' (' . $taxonomy_labels[$taxonomy_key] . ')';
											}
										}
										?>.<br/>

										<input type="hidden" class="formula-full-settings" value="<?php
											   unset($formula['nonce']);
											   echo esc_attr(json_encode($formula));
											   ?>" />
									</li>
									<?php
								}
								?>

							</ul>
							<?php
						}
						?>
					</div>
				</div>
			</div>
			<?php
		}

		// Fix formula formatting
		function sanitize_formula($formula) {

			$formula = stripslashes($formula);
			$formula = html_entity_decode($formula);
			$formula = str_replace('&quot;', '"', $formula);

			return $formula;
		}

		function prepare_formula($formula, $original_formula) {
			$out = array(
				'type' => '', // REPLACE, MATH
				'set1' => '', // search, OR MATH formula
				'set2' => '', // replace
			);
			// if REPLACE formula
			if (strpos($formula, '=REPLACE(') !== false) {
				if (strpos($formula, ',') !== false) {
					$out['type'] = 'REPLACE';

					$regExp = '/=REPLACE\(""(.*)\"",""(.*)""\)/s';
					$matched = preg_match_all($regExp, str_replace('"", ""', '"",""', $formula), $result);
					$matched_original_formula = preg_match_all($regExp, str_replace('"", ""', '"",""', $original_formula), $result_original_formula);

					// make replacement
					// If the search is current_value, assign the replace string directly
					if (trim($result_original_formula[1][0]) === '$current_value$') {
						$replace = wp_kses_post($result[2][0]);

						$out['set1'] = '$current_value$';
						$out['set2'] = $replace;
					} else {
						if (empty($result[1][0])) {
							$result[1][0] = '';
						}
						if (empty($result[2][0])) {
							$result[2][0] = '';
						}
						$search = wp_kses_post($result[1][0]);
						$replace = wp_kses_post($result[2][0]);

						$out['set1'] = $search;
						$out['set2'] = $replace;
					}
				} else {
					return new WP_Error(VGSE()->options_key, __('Formula is not valid 1', VGSE()->textname));
				}
			} elseif (strpos($formula, '=MATH(') !== false) {
				$out['type'] = 'MATH';

				// if MATH formula
				if (strpos($formula, ' ') !== false) {
					$formula = str_replace(' ', '', $formula);
				}
				$regExp = '/\(\s*"(.*)"\s*\)/i';
				preg_match($regExp, $formula, $result);

				if (empty($result[1])) {
					return new WP_Error(VGSE()->options_key, __('Formula is not valid 2', VGSE()->textname));
				}
				$formula = $result[1];

				chdir(dirname(__DIR__));

				if (strpos($formula, ',') !== false) {
					return new WP_Error(VGSE()->options_key, __('Formula is not valid 3', VGSE()->textname));
				}


				$out['set1'] = $formula;
			} else {
				return new WP_Error(VGSE()->options_key, __('Formula is not valid 5', VGSE()->textname));
			}

			return $out;
		}

		function apply_formula_to_data($formula, $data, $post_id = null, $cell_args = array(), $post_type = null) {
			require_once 'math-calculator.php';

			// Fix formula formatting
			$formula = $this->sanitize_formula($formula);
			$original_formula = $formula;

			$original_data = $data;
			if (strpos($formula, '=MATH(') !== false) {
				$sanitized_data = trim($data);
				if (empty($sanitized_data)) {
					$data = 0;
				}
			}
			// Replacing placeholders with real values
			$formula = str_replace('$current_value$', $data, $formula);
			$formula = str_replace('$random_number$', mt_rand(10000, 999999), $formula);
			$formula = str_replace('$random_letters$', wp_generate_password(6, false), $formula);
			$formula = str_replace('$current_timestamp$', time(), $formula);
			$formula = str_replace('$current_time_friendly$', current_time('H:i:s', false), $formula);
			$formula = str_replace('$current_date$', date('d-m-Y'), $formula);

			// Replacing placeholders for columns names.
			// The column name must be in the format of $column_key$
			if (!empty($post_id)) {
				$columns_regex = '/\$([a-zA-Z0-9_\-]+)\$/';
				$columns_found = preg_match_all($columns_regex, $formula, $columns_matched);


				if ($columns_found && !empty($columns_matched[1]) && is_array($columns_matched[1])) {
					foreach ($columns_matched[1] as $column_key) {
						$column_value = VGSE()->helpers->get_column_text_value($column_key, $post_id, null, $post_type);

						if (strpos($formula, '=MATH') !== false) {
							$column_value = (float) $column_value;
						}
						$formula = str_replace('$' . $column_key . '$', $column_value, $formula);
					}
				}
			}

			$prepared_formula = $this->prepare_formula($formula, $original_formula);

			if (!$prepared_formula || is_wp_error($prepared_formula)) {
				return $prepared_formula;
			}

			if ($prepared_formula['type'] === 'REPLACE') {
				$search = $prepared_formula['set1'];
				$replace = $prepared_formula['set2'];
				if (empty($search) && empty($replace)) {
					return new WP_Error(VGSE()->options_key, __('Formula is not valid. The search and replace text must not be empty. At least one of them must have a value', VGSE()->textname));
				}

				// If search is empty it means we want to update only empty fields.
				// So we apply the replace only if the existing data is empty
				if (empty($search) && empty($data)) {
					$data = $replace;
				}

				if (trim($search) === '$current_value$') {
					$data = $replace;
				} else {
					// Use regex if search has wildcards
					$regex_flag = WP_Sheet_Editor_Formulas::$regex_flag;
					if (strpos($search, $regex_flag) !== false) {

						$search = untrailingslashit(ltrim(str_replace($regex_flag, '', $search), '/'));
						$data = preg_replace("$search", $replace, $data);
					} else {
						$data = str_replace($search, $replace, $data);
					}
				}
			} elseif ($prepared_formula['type'] === 'MATH') {

				$formula = $prepared_formula['set1'];
				// if existing field is empty, we assume a value of 0 to allow the math operation
				if (empty($data)) {
					$data = 0;
				}
				if (!is_numeric($data)) {
					return new WP_Error(VGSE()->options_key, __('The math formula can´t be applied. We found some existing data is not numeric.', VGSE()->textname));
				}
				// Execute math operation. It sanitizes the formula automatically.
				$parser = new VG_Math_Calculator();
				$data = $parser->calculate($formula);

				if ($data === $formula) {
					return new WP_Error(VGSE()->options_key, __('Formula is not valid 4', VGSE()->textname));
				}
			}

			return $data;
		}

		function can_execute_formula_as_sql($formula, $column, $post_type, $spreadsheet_columns, $raw_form_data) {
			$custom_check = apply_filters('vg_sheet_editor/formulas/sql_execution/can_execute', null, $formula, $column, $post_type, $spreadsheet_columns);
			if (is_bool($custom_check)) {
				return $custom_check;
			}

			if (!empty($raw_form_data['use_slower_execution'])) {
				return false;
			}

			// If formula is not replace, exit
			if (strpos($formula, '=REPLACE(') === false) {
				return false;
			}
			// If formula has wildcards, exit
			if (strpos($formula, WP_Sheet_Editor_Formulas::$regex_flag) !== false) {
				return false;
			}
			// If data type is not a post, exit
			if (!in_array($column['data_type'], array('post_data', 'post_meta', 'meta_data'))) {
				return false;
			}
			// If value_type is not supported, exit
			$unsupported_value_types = apply_filters('vg_sheet_editor/formulas/sql_execution/unsupported_value_types', array(), $formula, $column, $post_type, $spreadsheet_columns);
			if (!empty($unsupported_value_types) && in_array($column['value_type'], $unsupported_value_types)) {
				return false;
			}

			// If formula has placeholders besides $current_value$, exit
			$formula = str_replace('$current_value$', '', $formula);
			$columns_regex = '/\$([a-zA-Z0-9_\-]+)\$/';
			$columns_found = preg_match_all($columns_regex, $formula, $columns_matched);
			if ($columns_found) {
				return false;
			}

			return true;
		}

		function execute_formula_as_sql($post_ids, $formula, $column, $post_type, $spreadsheet_columns, $raw_form_data) {
			$allowed = $this->can_execute_formula_as_sql($formula, $column, $post_type, $spreadsheet_columns, $raw_form_data);
			if (!$allowed || empty($post_ids)) {
				return false;
			}
			global $wpdb;

			$editor = VGSE()->helpers->get_provider_editor($post_type);
			$table_name = $editor->provider->get_table_name_for_field($column['key'], $column);
			$meta_object_id_field = $editor->provider->get_meta_object_id_field($column['key'], $column);

			if (strpos($table_name, 'meta') === false) {
				$field_to_update = $column['key'];
				$object_id_field = 'ID';
				$extra_where = '';
			} else {
				$field_to_update = 'meta_value';
				$extra_where = " AND meta_key = '" . $column['key'] . "' ";
				$object_id_field = $meta_object_id_field;
			}


			$sanitized_formula = $this->sanitize_formula($formula);
			$prepared_formula = $this->prepare_formula($sanitized_formula, $sanitized_formula);

			if (!$prepared_formula || is_wp_error($prepared_formula) || (empty($prepared_formula['set1']) && empty($prepared_formula['set2']))) {
				return $prepared_formula;
			}


			$update_empty_fields_only = false;


			if (empty($prepared_formula['set1'])) {
				$update_empty_fields_only = true;
				$prepared_formula['set1'] = '$current_value$';

				if (strpos($table_name, 'meta') === false) {
					$extra_checks[] = esc_sql($field_to_update) . " REGEXP '^[0-9]+$' ";
				}
				$extra_checks = array(
					esc_sql($field_to_update) . " = '' ",
					esc_sql($field_to_update) . ' IS NULL ',
				);
				$extra_where .= " AND (" . implode(' OR ', $extra_checks) . " ) ";
			}


			if (strpos($prepared_formula['set1'], '$current_value$') !== false) {
				$search = esc_sql(str_replace('$current_value$', $field_to_update, $prepared_formula['set1']));
			} else {
				$search = "'" . esc_sql($prepared_formula['set1']) . "'";
			}

			if ($prepared_formula['set1'] === '$current_value$') {
				$set2_prepared = $this->_prepare_data_for_saving($prepared_formula['set2'], $column);
				if ($set2_prepared === false) {
					return new WP_Error(VGSE()->options_key, __('Value in the replace section is not valid', VGSE()->textname));
				}
				$prepared_formula['set2'] = $set2_prepared;
			}

			if (strpos($prepared_formula['set2'], '$current_value$') === false) {
				$replace = "'" . esc_sql($prepared_formula['set2']) . "'";
			} else {
				$concat_parts = array_map('esc_sql', array_filter(explode('$$$', preg_replace('/\$current_value\$/', '$$$' . $field_to_update . '$$$', $prepared_formula['set2']))));
				$replace = " CONCAT( ";

				$concat_parts_final = array();
				foreach ($concat_parts as $concat_part) {
					$quotes = $concat_part !== $field_to_update;

					if ($quotes) {
						$concat_parts_final[] = "'" . $concat_part . "'";
					} else {
						$concat_parts_final[] = $concat_part;
					}
				}
				$replace .= implode(',', $concat_parts_final) . ') ';
			}

			// Insert meta data for posts missing the meta key because the replace only updates existing meta data
			if (strpos($table_name, 'meta') !== false) {
				$existing_rows_sql = "SELECT $object_id_field FROM $table_name WHERE  $object_id_field IN (" . implode(',', $post_ids) . ") $extra_where;";
				$existing_rows = $wpdb->get_col($existing_rows_sql);
				$missing_rows = array_diff($post_ids, $existing_rows);

				foreach ($missing_rows as $missing_rows_object_id) {
					$wpdb->insert($table_name, array(
						$object_id_field => $missing_rows_object_id,
						$field_to_update => '',
						'meta_key' => $column['key'],
							), array(
						'%d',
						'%s',
						'%s',
					));
				}
			}


			$empty_wheres = array();
//			if (strpos($table_name, 'meta') === false) {
			$empty_wheres[] = " $field_to_update = '' ";
//			}
			$empty_wheres[] = " $field_to_update IS NULL ";

			$sql = "UPDATE $table_name SET $field_to_update = REPLACE($field_to_update, $search, $replace ) WHERE  $object_id_field IN (" . implode(',', $post_ids) . ") $extra_where;";
			$sql_empty_fields = "UPDATE $table_name SET $field_to_update = $replace WHERE  $object_id_field IN (" . implode(',', $post_ids) . ") AND (" . implode(' OR ', $empty_wheres) . ") $extra_where;";



			$total_updated = 0;
			$total_updated += $wpdb->query($sql);
			$total_updated += $wpdb->query($sql_empty_fields);

			return $total_updated;
		}

		function delete_formula() {

			if (empty($_REQUEST['nonce']) || !wp_verify_nonce($_REQUEST['nonce'], 'bep-nonce')) {
				wp_send_json_error(array('message' => __('You dont have enough permissions to view this page.', VGSE()->textname)));
			}

			$index = (int) $_REQUEST['formula_index'];

			$future_formulas = get_option($this->future_posts_formula_key, array());
			if (!is_array($future_formulas)) {
				$future_formulas = array();
			}
			unset($future_formulas[$index]);
			update_option($this->future_posts_formula_key, $future_formulas);

			wp_send_json_success();
		}

		function save_formula() {

			if (empty($_REQUEST['nonce']) || !wp_verify_nonce($_REQUEST['nonce'], 'bep-nonce')) {
				wp_send_json_error(array('message' => __('You dont have enough permissions to view this page.', VGSE()->textname)));
			}

			$future_formulas = get_option($this->future_posts_formula_key, array());
			if (!is_array($future_formulas)) {
				$future_formulas = array();
			}
			$future_formulas[] = VGSE()->helpers->clean_data($_REQUEST);
			update_option($this->future_posts_formula_key, $future_formulas);

			wp_send_json_success();
		}

		/**
		 * Controller - apply formula in bulk
		 */
		function bulk_execute_formula_ajax() {

			if (empty($_REQUEST['nonce']) || !wp_verify_nonce($_REQUEST['nonce'], 'bep-nonce')) {
				wp_send_json_error(array('message' => __('You dont have enough permissions to view this page.', VGSE()->textname)));
			}

			$result = $this->bulk_execute_formula($_REQUEST);

			if (is_wp_error($result)) {
				wp_send_json_error(array('message' => $result->get_error_message()));
			}

			wp_send_json_success($result);
		}

		function _get_initial_data_from_cell($cell_args, $post, $editor) {
			$cell_key = $cell_args['key'];

			if ($cell_args['data_type'] === 'post_data') {
				$data = VGSE()->data_helpers->get_post_data($cell_key, $post->ID);
			}
			if ($cell_args['data_type'] === 'meta_data' || $cell_args['data_type'] === 'post_meta') {
				$data = $editor->provider->get_item_meta($post->ID, $cell_key, true, 'read');
			}
			if ($cell_args['data_type'] === 'post_terms') {
				$data = VGSE()->data_helpers->get_post_terms($post->ID, $cell_key);
			}
			return $data;
		}

		function bulk_execute_formula($request_data = array()) {

			$query_strings = VGSE()->helpers->clean_data($request_data);

			$column = $query_strings['column'];
			$raw_form_data = $query_strings['raw_form_data'];
			$formula = $query_strings['formula'];
			$post_type = $query_strings['post_type'];
			$page = (int) $query_strings['page'];
			$per_page = (!empty(VGSE()->options) && !empty(VGSE()->options['be_posts_per_page_save']) ) ? (int) VGSE()->options['be_posts_per_page_save'] : 4;

			$editor = VGSE()->helpers->get_provider_editor($post_type);
			VGSE()->current_provider = $editor->provider;


			$unfiltered_columns = WP_Sheet_Editor_Columns_Visibility::$unfiltered_columns;
			$spreadsheet_columns = isset($unfiltered_columns[$post_type]) ? $unfiltered_columns[$post_type] : array();

			if (empty($spreadsheet_columns[$column])) {
				return new WP_Error('vgse', __('The column selected is not valid.', VGSE()->textname));
			}
			if (empty($raw_form_data['use_search_query'])) {
				return new WP_Error('vgse', __('You need to accept that we will update the rows from your search in the spreadsheet.', VGSE()->textname));
			}

			$can_execute_formula_as_sql = $this->can_execute_formula_as_sql($formula, $spreadsheet_columns[$column], $post_type, $spreadsheet_columns, $raw_form_data);

			if (VGSE()->options['be_disable_post_actions']) {
				VGSE()->helpers->remove_all_post_actions($post_type);
			}

			$updated_items = array();

			$get_rows_args = apply_filters('vg_sheet_editor/formulas/search_query/get_rows_args', array(
				'nonce' => $query_strings['nonce'],
				'post_type' => $post_type,
				'filters' => isset($raw_form_data['filters']) ? $raw_form_data['filters'] : ''
			));
			$base_query = VGSE()->helpers->prepare_query_params_for_retrieving_rows($get_rows_args, $get_rows_args);

			$base_query['posts_per_page'] = $per_page;
			$base_query['paged'] = $page;
			if ($can_execute_formula_as_sql) {
				$base_query['posts_per_page'] = -1;
				$base_query['fields'] = 'ids';
			}

			if (!empty($query_strings['custom_wp_query_params'])) {
				$base_query = wp_parse_args($query_strings['custom_wp_query_params'], $base_query);
				unset($query_strings['custom_wp_query_params']);
			}
			$base_query = apply_filters('vg_sheet_editor/formulas/execute/posts_query', $base_query, $query_strings);

			$query = $editor->provider->get_items($base_query);
			
			$total = $query->found_posts;
			
			if (!empty($raw_form_data['apply_to_future_posts'])) {
				$future_formulas = get_option($this->future_posts_formula_key, array());
				if (!is_array($future_formulas)) {
					$future_formulas = array();
				}
				$future_formulas[] = $query_strings;
				update_option($this->future_posts_formula_key, $future_formulas);
			}


			if (!empty($query->posts)) {
				$count = 0;
				do_action('vg_sheet_editor/formulas/execute_formula/before_execution', $column, $formula, $post_type, $spreadsheet_columns);


				$sql_updated = $this->execute_formula_as_sql($query->posts, $formula, $spreadsheet_columns[$column], $post_type, $spreadsheet_columns, $raw_form_data);
				if (is_int($sql_updated)) {
					$updated_items = $sql_updated;
					$editions_count = $sql_updated;
					VGSE()->helpers->increase_counter('editions', $updated_items);
					do_action('vg_sheet_editor/formulas/execute_formula/after_execution', $column, $formula, $post_type, $spreadsheet_columns);
				} else {

					$editions_count = 0;
					$file_urls_replaced = false;
					// Loop through all the posts
					foreach ($query->posts as $post) {

						$GLOBALS['post'] = & $post;

						if (isset($post->post_title)) {
							setup_postdata($post);
						}

						$post_id = $post->ID;

						do_action('vg_sheet_editor/formulas/execute_formula/before_execution_on_field', $post_id, $spreadsheet_columns[$column], $formula, $post_type, $spreadsheet_columns);

						if ($results = apply_filters('vg_sheet_editor/formulas/execute_formula/custom_formula_handler_executed', false, $post_id, $spreadsheet_columns[$column], $formula, $post_type, $spreadsheet_columns, $raw_form_data)) {
							do_action('vg_sheet_editor/formulas/execute_formula/after_execution_on_field', $post->ID, $results['initial_data'], $results['modified_data'], $column, $formula, $post_type, $spreadsheet_columns[$column], $spreadsheet_columns);

							if ($results['initial_data'] !== $results['modified_data']) {
								$editions_count++;
								$updated_items[] = $post->ID;
							}

							$count++;
							continue;
						}

						// loop through every column in the spreadsheet
						$cell_key = $column;
						$cell_args = $spreadsheet_columns[$column];
						$data = $this->_get_initial_data_from_cell($cell_args, $post, $editor);
						$initial_data = $data;


						// If file cells, convert URLs to file IDs before replacement
						if (!$file_urls_replaced && in_array($cell_args['type'], array('boton_gallery', 'boton_gallery_multiple')) && strpos($formula, '=REPLACE(') !== false && strpos($formula, ',') !== false) {
							$regExp = '/=REPLACE\(""(.*)\"",""(.*)""\)/s';
							$temp_formula = $this->sanitize_formula($formula);
							$matched = preg_match_all($regExp, str_replace('"", ""', '"",""', $temp_formula), $result);

							if ($matched) {
								$first_url = $result[1][0];
								$second_url = $result[2][0];
								if (filter_var($first_url, FILTER_VALIDATE_URL)) {
									$first_id = VGSE()->helpers->maybe_replace_urls_with_file_ids(explode(',', $first_url), $post_id);
									if (!empty($first_id)) {
										$formula = str_replace($first_url, current($first_id), $formula);
									}
								}
								if (filter_var($second_url, FILTER_VALIDATE_URL)) {
									$second_id = VGSE()->helpers->maybe_replace_urls_with_file_ids(explode(',', $second_url), $post_id);
									if (!empty($second_id)) {
										$formula = str_replace($second_url, current($second_id), $formula);
									}
								}
							}

							$file_urls_replaced = true;
						}

						$data = $this->apply_formula_to_data($formula, $data, $post->ID, $cell_args, $post_type);
						if (is_wp_error($data)) {
							return $data;
						}

						if ($initial_data !== $data) {
							
							do_action('vg_sheet_editor/save_rows/before_saving_cell', array(
								'ID' => $post->ID,
								$cell_key => $data,
									), $post_type, $cell_args, $cell_key, $spreadsheet_columns, $post_id);
							
							if ($cell_args['data_type'] === 'post_data') {

								// If the modified data is different, we save it
								$update = array();

								$final_key = $cell_key;
								if (VGSE()->helpers->get_current_provider()->is_post_type) {
									if ($cell_key !== 'ID' && $cell_key !== 'comment_status' && strpos($cell_key, 'post_') === false) {
										$final_key = 'post_' . $cell_key;
									}
								}
								$update[$final_key] = $this->_prepare_data_for_saving($data, $cell_args);

								if (empty($update['ID'])) {
									$update['ID'] = $post->ID;
								}
								$post_id = $editor->provider->update_item_data($update, true);

								$updated_items[] = $post->ID;

								$editions_count++;
							}
							if ($cell_args['data_type'] === 'meta_data' || $cell_args['data_type'] === 'post_meta') {
								$editions_count++;
								$data = $this->_prepare_data_for_saving($data, $cell_args);
								$update = $editor->provider->update_item_meta($post->ID, $cell_key, $data);
								$updated_items[] = $post->ID;
							}
							if ($cell_args['data_type'] === 'post_terms') {
								$editions_count++;
								$data = $this->_prepare_data_for_saving($data, $cell_args);
								$update = $editor->provider->set_object_terms($post->ID, $data, $cell_key);
								$updated_items[] = $post->ID;
							}
						} else {
							// if the data is the same after running the formula, we don´t save it.
							$post_id = true;
						}

						$modified_data = $data;
						do_action('vg_sheet_editor/formulas/execute_formula/after_execution_on_field', $post->ID, $initial_data, $modified_data, $column, $formula, $post_type, $cell_args, $spreadsheet_columns);
						$count++;
					}
				}
				VGSE()->helpers->increase_counter('editions', $editions_count);

				do_action('vg_sheet_editor/formulas/execute_formula/after_execution', $column, $formula, $post_type, $spreadsheet_columns);
			} else {

				if ($page === 1) {

					return new WP_Error('vgse', __('Formula not executed. No items found matching the criteria.', VGSE()->textname));
				} else {
					return array('message' => __('<p>Complete</p>.', VGSE()->textname));
				}
			}
			wp_reset_postdata();
			wp_reset_query();


			// Send final message indicating the number of posts updated.
			$processed = (!$can_execute_formula_as_sql && $total > ( $per_page * $page ) ) ? $per_page * $page : $total;
			VGSE()->helpers->increase_counter('processed', $processed);
			$total_updated = ( is_array($updated_items)) ? count($updated_items) : $updated_items;
			$message = sprintf(__('%d. of %d items have been processed. %d items have been updated.', VGSE()->textname), $processed, $total, $total_updated);

			if ($can_execute_formula_as_sql) {
				$message .= __('<p>Complete</p>.', VGSE()->textname);
			}
			return array(
				'message' => '<p>' . $message . '</p>',
				'total' => (int) $total,
				'processed' => (int) $processed,
				'updated' => $total_updated,
				'processed_posts' => ( !empty($base_query['fields']) && $base_query['fields'] === 'ids' ) ? $query->posts : wp_list_pluck($query->posts, 'ID'),
				'updated_posts' => $updated_items,
				'force_complete' => ( $can_execute_formula_as_sql ) ? true : false,
			);
		}

		function _prepare_data_for_saving($data, $cell_args) {
			if (is_wp_error($data)) {
				return $data;
			}

			$out = $data;

			$cell_key = $cell_args['key'];

			if ($cell_args['data_type'] === 'post_data') {
				if ($cell_key !== 'post_content') {
					$out = VGSE()->data_helpers->set_post($cell_key, $data);
				}
				if ($cell_key === 'post_title') {
					$out = wp_strip_all_tags($out);
				}
			}
			if ($cell_args['data_type'] === 'post_terms') {
				$out = VGSE()->data_helpers->prepare_post_terms_for_saving($data, $cell_key);
			}

			return $out;
		}

		/**
		 * Creates or returns an instance of this class.
		 */
		static function get_instance() {
			if (null == WP_Sheet_Editor_Formulas::$instance) {
				WP_Sheet_Editor_Formulas::$instance = new WP_Sheet_Editor_Formulas();
				WP_Sheet_Editor_Formulas::$instance->init();
			}
			return WP_Sheet_Editor_Formulas::$instance;
		}

		function __set($name, $value) {
			$this->$name = $value;
		}

		function __get($name) {
			return $this->$name;
		}

	}

	add_action('vg_sheet_editor/initialized', 'vgse_formulas_init');

	function vgse_formulas_init() {
		WP_Sheet_Editor_Formulas::get_instance();
	}

	require 'testing.php';
}