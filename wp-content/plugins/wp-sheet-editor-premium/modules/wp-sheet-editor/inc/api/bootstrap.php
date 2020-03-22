<?php

if (!class_exists('WP_Sheet_Editor_Bootstrap')) {

	/**
	 * Bootstrap post type spreadsheet.
	 * Use only for a post type. You can create new class 
	 * (extending this) for a custom bootstrap
	 */
	class WP_Sheet_Editor_Bootstrap {

		var $allowed_post_types = null;
		var $enabled_post_types = array();
		var $columns = null;
		var $toolbars = null;
		var $settings = array();

		function __construct($args = array()) {
			$defaults = array(
				'allowed_post_types' => VGSE()->helpers->get_allowed_post_types(),
				'enabled_post_types' => VGSE()->helpers->get_enabled_post_types(),
				'register_toolbars' => true,
				'register_columns' => true,
				'force_post_type_label' => null,
				'register_taxonomy_columns' => true,
				'register_admin_menus' => true,
				'register_spreadsheet_editor' => true,
				'only_allowed_spreadsheets' => false,
				'current_provider' => VGSE()->helpers->get_provider_from_query_string()
			);
			$this->settings = apply_filters('vg_sheet_editor/bootstrap/settings', wp_parse_args($args, $defaults));

			// Set allowed post types
			$this->allowed_post_types = $this->settings['allowed_post_types'];

			$this->enabled_post_types = $this->settings['enabled_post_types'];


			// Allow other plugins to skip post type bootstrapping
			if (!apply_filters('vg_sheet_editor/allow_to_bootstrap', true, $this->settings)) {
				return;
			}

			$current_post_type = $this->settings['current_provider'];
			if ($this->settings['only_allowed_spreadsheets'] && VGSE()->helpers->is_editor_page() && !empty($current_post_type) && !isset($this->allowed_post_types[$current_post_type])) {
				wp_die(__('Error 8391. You dont have enough permissions to view this page.', VGSE()->textname));
			}


			$this->columns = ( $this->settings['register_columns'] ) ? new WP_Sheet_Editor_Columns() : null;
			$this->toolbars = ( $this->settings['register_toolbars'] ) ? clone($this->_register_toolbars($this->enabled_post_types, new WP_Sheet_Editor_Toolbar())) : null;

			if (!empty($this->enabled_post_types) && $this->settings['register_spreadsheet_editor']) {
				$this->_register_columns();

				new WP_Sheet_Editor_Factory(array(
					'posts_per_page' => (!empty(VGSE()->options) && !empty(VGSE()->options['be_posts_per_page']) ) ? (int) VGSE()->options['be_posts_per_page'] : 10,
					'save_posts_per_page' => (!empty(VGSE()->options) && !empty(VGSE()->options['be_posts_per_page_save']) ) ? (int) VGSE()->options['be_posts_per_page_save'] : 4,
					'wait_between_batches' => (!empty(VGSE()->options) && !empty(VGSE()->options['be_timeout_between_batches']) ) ? (int) VGSE()->options['be_timeout_between_batches'] : 6,
					'fixed_columns_left' => (!empty(VGSE()->options['be_fix_first_columns']) ) ? 2 : false,
					'provider' => $current_post_type,
					'provider_key' => 'post_type',
					'admin_menu' => ( $this->settings['register_admin_menus'] ) ? $this->_register_admin_menu() : null,
					'columns' => $this->columns,
					'toolbars' => $this->toolbars,
					'enabled_post_types' => $this->enabled_post_types
				));
			}
		}

		function _register_admin_menu() {
			$admin_menu = array();

			if (!isset($GLOBALS['wpse_registered_menus'])) {
				$GLOBALS['wpse_registered_menus'] = array();
			}
			foreach ($this->enabled_post_types as $post_type_key) {
				if (in_array($post_type_key, $GLOBALS['wpse_registered_menus'])) {
					continue;
				}
				$GLOBALS['wpse_registered_menus'][] = $post_type_key;
				$page_slug = 'vgse-bulk-edit-' . $post_type_key;
				$post_type_label = (!empty($this->settings['force_post_type_label']) ) ? $this->settings['force_post_type_label'] : VGSE()->helpers->get_post_type_label($post_type_key);

				$admin_menu[] = array(
					'type' => 'submenu',
					'name' => __('Edit ' . $post_type_label, VGSE()->textname),
					'slug' => $page_slug,
				);
				if ($post_type_key === 'post') {
					$parent = 'edit.php';
				} elseif ($post_type_key === 'attachment') {
					$parent = 'upload.php';
				} else {
					$parent = 'edit.php?post_type=' . $post_type_key;
				}
				$admin_menu[] = array(
					'type' => 'submenu',
					'parent' => $parent,
					'name' => __('Sheet Editor', VGSE()->textname),
					'slug' => 'admin.php?page=' . $page_slug,
					'treat_as_url' => true,
				);
			}

			return $admin_menu;
		}

		/**
		 * Register core toolbar items
		 */
		function _register_toolbars($post_types = array(), $toolbars = null) {
			if (empty($toolbars)) {
				$toolbars = new WP_Sheet_Editor_Toolbar();
			}

			foreach ($post_types as $post_type) {
				// secondary
				$toolbars->register_item('settings', array(
					'type' => 'button',
					'content' => __('Settings', VGSE()->textname),
					'url' => 'javascript:void(0)',
					'toolbar_key' => 'secondary',
					'allow_in_frontend' => false,
						), $post_type);
				$toolbars->register_item('general_settings', array(
					'type' => 'button',
					'content' => __('Advanced settings', VGSE()->textname),
					'url' => VGSE()->helpers->get_settings_page_url(),
					'toolbar_key' => 'secondary',
					'allow_in_frontend' => false,
					'parent' => 'settings'
						), $post_type);
				$toolbars->register_item('support', array(
					'type' => 'button',
					'content' => __('Help', VGSE()->textname),
					'toolbar_key' => 'secondary',
					'extra_html_attributes' => 'data-remodal-target="modal-support"',
						), $post_type);

				if (apply_filters('vg_sheet_editor/extensions/is_toolbar_allowed', true)) {
					$toolbars->register_item('extensions', array(
						'type' => 'button',
						'content' => __('Extensions', VGSE()->textname),
						'toolbar_key' => 'secondary',
						'allow_in_frontend' => false,
						'extra_html_attributes' => 'data-remodal-target="modal-extensions"',
							), $post_type);
				}

				// primary
				$toolbars->register_item('save', array(
					'allow_to_hide' => false,
					'type' => 'button', // html | switch | button
					'icon' => 'fa fa-save', // Font awesome icon name , including font awesome prefix: fa fa-XXX. Only for type=button.
					'content' => __('Save', VGSE()->textname), // if type=button : button label | if type=html : html string.
					'css_class' => 'primary button-only-icon', // .button will be added to all items also.	
					'extra_html_attributes' => 'data-remodal-target="bulk-save"', // useful for adding data attributes
						), $post_type);
				$toolbars->register_item('add_rows', array(
					'type' => 'html', // html | switch | button
					'content' => '<button name="addrow" id="addrow" class="button button-only-icon"><i class="fa fa-plus"></i> ' . __('Add new', VGSE()->textname) . '</button><input type="number" min="1" value="1" class="number_rows" /> <input type="hidden" id="post_type_new_row" value="' . $post_type . '" />', // if type=button : button label | if type=html : html string.
					'help_tooltip' => __('Add new posts', VGSE()->textname),
						), $post_type);
				$toolbars->register_item('load', array(
					'allow_to_hide' => false,
					'type' => 'button', // html | switch | button
					'content' => __('Load', VGSE()->textname),
					'container_class' => 'hidden',
						), $post_type);
				$toolbars->register_item('cells_format', array(
					'type' => 'switch', // html | switch | button
					'content' => __('Cells as simple text', VGSE()->textname),
					'id' => 'formato',
					'toolbar_key' => ( defined('VGSE_WC_FILE') ) ? 'secondary' : 'primary',
					'help_tooltip' => __('When this is disabled dates will be displayed in a calendar and fields with multiple options as dropdowns , when enabled they will be displayed as simple text', VGSE()->textname),
					'default_value' => false,
					'parent' => 'settings',
						), $post_type);
				$toolbars->register_item('infinite_scroll', array(
					'type' => 'switch', // html | switch | button
					'content' => __('Load more on scroll', VGSE()->textname),
					'id' => 'infinito',
					'toolbar_key' => ( defined('VGSE_WC_FILE') ) ? 'secondary' : 'primary',
					'help_tooltip' => __('When this is enabled more items will be loaded to the bottom of the spreadsheet when you reach the end of the page', VGSE()->textname),
					'default_value' => VGSE()->options['be_load_items_on_scroll'] == true,
					'parent' => 'settings',
						), $post_type);
			}

			do_action('vg_sheet_editor/toolbar/core_items_registered');

			return $toolbars;
		}

		/**
		 * IMPORTANT. We copied the function from wp-admin/includes/post.php
		 * because we need the function before wp loads the file or in pages where 
		 * WP core doesn't load it.
		 * 
		 * We can't just require the post.php file because it causes error 500 when WP 
		 * or other plugins load the file later
		 * 
		 * Return whether a post type is compatible with the block editor.
		 *
		 * The block editor depends on the REST API, and if the post type is not shown in the
		 * REST API, then it won't work with the block editor.
		 *
		 * @since 5.0.0
		 *
		 * @param string $post_type The post type.
		 * @return bool Whether the post type can be edited with the block editor.
		 */
		function use_block_editor_for_post_type($post_type) {
			if (!post_type_exists($post_type)) {
				return false;
			}

			if (!post_type_supports($post_type, 'editor')) {
				return false;
			}

			$post_type_object = get_post_type_object($post_type);
			if ($post_type_object && !$post_type_object->show_in_rest) {
				return false;
			}

			/**
			 * Filter whether a post is able to be edited in the block editor.
			 *
			 * @since 5.0.0
			 *
			 * @param bool   $use_block_editor  Whether the post type can be edited or not. Default true.
			 * @param string $post_type         The post type being checked.
			 */
			return apply_filters('use_block_editor_for_post_type', true, $post_type);
		}

		/**
		 * Register core columns
		 */
		function _register_columns() {

			$post_types = $this->enabled_post_types;
			foreach ($post_types as $post_type) {
				$this->columns->register_item('ID', $post_type, array(
					'data_type' => 'post_data', //String (post_data,post_meta|meta_data)	
					'unformatted' => array('data' => 'ID', 'renderer' => 'html', 'readOnly' => true), //Array (Valores admitidos por el plugin de handsontable)
					'column_width' => 75, //int (Ancho de la columna)
					'title' => __('ID', VGSE()->textname), //String (Titulo de la columna)
					'type' => '', // String (Es para saber si serÃ¡ un boton que abre popup, si no dejar vacio) boton_tiny|boton_gallery|boton_gallery_multiple|(vacio)
					'supports_formulas' => false,
					'allow_to_hide' => false,
					'allow_to_save' => false,
					'allow_to_rename' => false,
					'formatted' => array('data' => 'ID', 'renderer' => 'html', 'readOnly' => true),
				));
				if ($post_type === 'attachment') {
					$this->columns->register_item('guid', $post_type, array(
						'data_type' => 'post_data',
						'unformatted' => array('data' => 'guid', 'renderer' => 'html', 'readOnly' => true),
						'column_width' => 150,
						'type' => 'inline_image',
						'supports_formulas' => false,
						'title' => __('Preview', VGSE()->textname), //String (Titulo de la columna)
						'allow_to_hide' => true,
						'allow_to_rename' => true,
						'allow_to_save' => false,
						'formatted' => array('data' => 'guid', 'renderer' => 'html', 'readOnly' => true),
					));
				}
				$this->columns->register_item('post_title', $post_type, array(
					'data_type' => 'post_data',
					'unformatted' => array('data' => 'post_title'),
					'column_width' => 300,
					'title' => __('Title', VGSE()->textname),
					'type' => '',
					'supports_formulas' => true,
					'formatted' => array('data' => 'post_title', 'renderer' => 'html'),
					'allow_to_hide' => true,
					'allow_to_rename' => true,
				));
				if ($post_type !== 'attachment') {
					$this->columns->register_item('post_name', $post_type, array(
						'data_type' => 'post_data', //String (post_data,post_meta|meta_data)	
						'unformatted' => array('data' => 'post_name', 'renderer' => 'html', 'readOnly' => (bool) !VGSE()->options['be_allow_edit_slugs']), //Array (Valores admitidos por el plugin de handsontable)
						'column_width' => 300, //int (Ancho de la columna)
						'title' => __('URL Slug', VGSE()->textname), //String (Titulo de la columna)
						'type' => '', // String (Es para saber si serÃ¡ un boton que abre popup, si no dejar vacio) boton_tiny|boton_gallery|boton_gallery_multiple|(vacio)
						'supports_formulas' => ( isset(VGSE()->options['be_allow_edit_slugs']) ) ? (bool) VGSE()->options['be_allow_edit_slugs'] : false,
						'allow_to_hide' => true,
						'allow_to_save' => ( isset(VGSE()->options['be_allow_edit_slugs']) ) ? (bool) VGSE()->options['be_allow_edit_slugs'] : false,
						'allow_to_rename' => true,
						'formatted' => array('data' => 'post_name', 'renderer' => 'html', 'readOnly' => ( isset(VGSE()->options['be_allow_edit_slugs']) ) ? (bool) !VGSE()->options['be_allow_edit_slugs'] : true),
					));
				}
				if ($post_type === 'attachment') {
					$this->columns->register_item('post_mime_type', $post_type, array(
						'data_type' => 'post_data',
						'unformatted' => array('data' => 'post_mime_type'),
						'column_width' => 150,
						'title' => __('Format', VGSE()->textname),
						'type' => '',
						'supports_formulas' => false,
						'formatted' => array('data' => 'post_mime_type', 'renderer' => 'text', 'readOnly' => true),
						'allow_to_hide' => true,
						'allow_to_save' => false,
						'allow_to_rename' => true,
					));
					$this->columns->register_item('_wp_attachment_image_alt', $post_type, array(
						'data_type' => 'meta_data',
						'unformatted' => array('data' => '_wp_attachment_image_alt'),
						'column_width' => 150,
						'title' => __('Alt text', VGSE()->textname),
						'type' => '',
						'supports_formulas' => true,
						'formatted' => array('data' => '_wp_attachment_image_alt', 'renderer' => 'text'),
						'allow_to_hide' => true,
						'allow_to_rename' => true,
					));
				}
				global $wp_version;
				if (version_compare($wp_version, '5.0', '>=') && $this->use_block_editor_for_post_type($post_type)) {
					$post_content_args = array(
						'data_type' => 'post_data',
						'unformatted' => array(
							'data' => 'post_content',
							'renderer' => 'html',
							'readOnly' => true
						),
						'column_width' => 75,
						'title' => __('Content', VGSE()->textname),
						'edit_button_label' => __('Edit', VGSE()->textname),
						'edit_modal_description' => __('Use this editor to edit the content only, other fields like tags and categories should be edited on the spreadsheet.', VGSE()->textname),
						'type' => 'metabox',
						'supports_formulas' => true,
						'formatted' => array(
							'data' => 'post_content',
							'renderer' => 'html',
							'readOnly' => true
						),
						'allow_to_hide' => true,
						'allow_to_save' => false,
						'allow_to_rename' => true,
						'edit_modal_save_action' => 'js_function_name:vgseCancelGutenbergEdit,vgse_save_gutenberg_content',
						'edit_modal_cancel_action' => 'js_function_name:vgseCancelGutenbergEdit',
						'metabox_show_selector' => '#wpcontent',
						'metabox_value_selector' => 'js_function_name:vgseGetGutenbergContent',
					);
					$this->columns->register_item('post_content', $post_type, $post_content_args);
				} else {
					if (post_type_supports($post_type, 'editor') || $post_type === 'attachment') {
						$post_content_args = array(
							'data_type' => 'post_data',
							'unformatted' => array('data' => 'post_content', 'renderer' => 'html', 'readOnly' => true),
							'column_width' => 75,
							'title' => ( $post_type !== 'attachment' ) ? __('Content', VGSE()->textname) : __('Description', VGSE()->textname),
							'type' => 'boton_tiny',
							'supports_formulas' => true,
							'formatted' => array('data' => 'post_content', 'renderer' => 'html', 'readOnly' => true),
							'allow_to_hide' => true,
							'allow_to_save' => false,
							'allow_to_rename' => true,
						);
						if (!empty(VGSE()->options) && !empty(VGSE()->options['be_post_content_as_plain_text'])) {
							$post_content_args['unformatted'] = array('data' => 'post_content',);
							$post_content_args['formatted'] = array('data' => 'post_content',);
							$post_content_args['column_width'] = 300;
							$post_content_args['type'] = '';
							$post_content_args['allow_to_save'] = true;
						}
						$this->columns->register_item('post_content', $post_type, $post_content_args);
					}
				}

				$this->columns->register_item('view_post', $post_type, array(
					'data_type' => 'post_data',
					'unformatted' => array('data' => 'view_post', 'renderer' => 'html', 'readOnly' => true),
					'column_width' => 85,
					'title' => __('View', VGSE()->textname),
					'type' => 'view_post',
					'supports_formulas' => false,
					'formatted' => array('data' => 'view_post', 'renderer' => 'html', 'readOnly' => true),
					'allow_to_hide' => true,
					'allow_to_save' => false,
					'allow_to_rename' => true,
				));
				$this->columns->register_item('post_date', $post_type, array(
					'data_type' => 'post_data',
					'unformatted' => array('data' => 'post_date'),
					'column_width' => 155,
					'title' => __('Date', VGSE()->textname),
					'type' => '',
					'supports_formulas' => true,
					'formatted' => array('data' => 'post_date', 'type' => 'date', 'dateFormat' => 'YYYY-MM-DD', 'correctFormat' => true, 'defaultDate' => date('Y-m-d'), 'datePickerConfig' => array('firstDay' => 0, 'showWeekNumber' => true, 'numberOfMonths' => 1)),
					'allow_to_hide' => true,
					'allow_to_rename' => true,
				));
				$this->columns->register_item('post_modified', $post_type, array(
					'data_type' => 'post_data',
					'unformatted' => array('data' => 'post_modified', 'renderer' => 'html', 'readOnly' => true),
					'column_width' => 155,
					'title' => __('Modified Date', VGSE()->textname),
					'type' => '',
					'supports_formulas' => false,
					'formatted' => array('data' => 'post_modified', 'renderer' => 'html', 'readOnly' => true),
					'allow_to_hide' => true,
					'allow_to_save' => false,
					'allow_to_rename' => true,
				));
				if (post_type_supports($post_type, 'author')) {
					$this->columns->register_item('post_author', $post_type, array(
						'data_type' => 'post_data',
						'unformatted' => array('data' => 'post_author'),
						'column_width' => 120,
						'title' => ( $post_type !== 'attachment' ) ? __('Author', VGSE()->textname) : __('Uploaded by', VGSE()->textname),
						'type' => '',
						'supports_formulas' => true,
						'formatted' => array('data' => 'post_author', 'editor' => 'select', 'selectOptions' => array(VGSE()->data_helpers, 'get_authors_list')),
						'allow_to_hide' => true,
						'allow_to_rename' => true,
					));
				}
				if (post_type_supports($post_type, 'excerpt') || $post_type === 'attachment') {
					$this->columns->register_item('post_excerpt', $post_type, array(
						'data_type' => 'post_data',
						'unformatted' => array('data' => 'post_excerpt'),
						'column_width' => 400,
						'title' => ( $post_type !== 'attachment' ) ? __('Excerpt', VGSE()->textname) : __('Caption', VGSE()->textname),
						'type' => '',
						'supports_formulas' => true,
						'formatted' => array('data' => 'post_excerpt'),
						'allow_to_hide' => true,
						'allow_to_rename' => true,
					));
				}

				$post_statuses = VGSE()->helpers->get_data_provider($post_type)->get_statuses();

				if (!isset($post_statuses['trash'])) {
					$post_statuses['trash'] = 'Trash';
				}
				if (( $post_type === 'page' && !current_user_can('publish_pages') ) || ( $post_type !== 'page' && !current_user_can('publish_posts'))) {
					unset($post_statuses['publish']);
				}
				if (($post_type === 'page' && !current_user_can('delete_pages')) || ($post_type !== 'page' && !current_user_can('delete_posts'))) {
					unset($post_statuses['trash']);
				}


				$this->columns->register_item('post_status', $post_type, array(
					'data_type' => 'post_data',
					'unformatted' => array('data' => 'post_status'),
					'column_width' => 100,
					'title' => __('Status', VGSE()->textname),
					'type' => '',
					'supports_formulas' => true,
					'formatted' => array('data' => 'post_status', 'editor' => 'select', 'selectOptions' => $post_statuses),
					'allow_to_hide' => true,
					'allow_to_rename' => true,
				));
				if (post_type_supports($post_type, 'comments')) {
					$this->columns->register_item('comment_status', $post_type, array(
						'data_type' => 'post_data',
						'unformatted' => array('data' => 'comment_status'),
						'column_width' => 100,
						'title' => __('Comments', VGSE()->textname),
						'type' => '',
						'supports_formulas' => true,
						'formatted' => array(
							'data' => 'comment_status',
							'type' => 'checkbox',
							'checkedTemplate' => 'open',
							'uncheckedTemplate' => 'closed',
						),
						'default_value' => 'open',
						'allow_to_hide' => true,
						'allow_to_rename' => true,
					));
				}

				if (post_type_supports($post_type, 'page-attributes') && $post_type !== 'attachment') {

					$this->columns->register_item('post_parent', $post_type, array(
						'data_type' => 'post_data',
						'unformatted' => array('data' => 'post_parent'),
						'column_width' => 210,
						'title' => __('Page Parent', VGSE()->textname),
						'type' => '',
						'supports_formulas' => true,
						'formatted' => array('data' => 'post_parent', 'type' => 'autocomplete', 'source' => array(VGSE()->data_helpers, 'get_all_post_titles_from_post_type'), 'callback_args' => array($post_type, ARRAY_N, true)),
						'allow_to_hide' => true,
						'allow_to_rename' => true,
					));
				}
				if (post_type_supports($post_type, 'thumbnail')) {
					$this->columns->register_item('_thumbnail_id', $post_type, array(
						'data_type' => 'meta_data',
						'unformatted' => array('data' => '_thumbnail_id', 'renderer' => 'html', 'readOnly' => true),
						'column_width' => 160,
						'supports_formulas' => true,
						'title' => __('Featured Image', VGSE()->textname),
						'type' => 'boton_gallery', //boton_gallery|boton_gallery_multiple (Multiple para galeria)
						'formatted' => array('data' => '_thumbnail_id', 'renderer' => 'html', 'readOnly' => true),
						'allow_to_hide' => true,
						'allow_to_save' => true,
						'allow_to_rename' => true,
					));
				}

				if ($this->settings['register_taxonomy_columns']) {
					$taxonomies = get_object_taxonomies($post_type, 'objects');

					if (!empty($taxonomies) && is_array($taxonomies)) {
						foreach ($taxonomies as $taxonomy) {

							if (!$taxonomy->show_ui) {
								continue;
							}
							$this->columns->register_item($taxonomy->name, $post_type, array(
								'data_type' => 'post_terms',
								'unformatted' => array('data' => $taxonomy->name),
								'column_width' => 150,
								'title' => $taxonomy->label,
								'type' => '',
								'supports_formulas' => true,
								'formatted' => array('data' => $taxonomy->name, 'type' => 'autocomplete', 'source' => array(VGSE()->data_helpers, 'get_taxonomy_terms'), 'callback_args' => array($taxonomy->name)),
								'allow_to_hide' => true,
								'allow_to_rename' => true,
							));
						}
					}
				}
			}

			do_action('vg_sheet_editor/core_columns_registered');
		}

		function __set($name, $value) {
			$this->$name = $value;
		}

		function __get($name) {
			return $this->$name;
		}

	}

}