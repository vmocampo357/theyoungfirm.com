
<div data-remodal-id="modal-columns-visibility" data-remodal-options="closeOnOutsideClick: false" class="remodal remodal<?php echo $random_id; ?> modal-columns-visibility">

	<div class="modal-content">
		<form action="<?php echo admin_url('admin-ajax.php'); ?>" method="POST" class="vgse-modal-form" data-nonce="<?php echo wp_create_nonce('bep-nonce'); ?>">
			<h3><?php _e('Columns visibility', VGSE()->textname); ?></h3>
			<ul class="unstyled-list">
				<li>
					<label><?php _e('Select columns visibility and order:', VGSE()->textname); ?></label>
					<p><?php _e('Drag the columns to the right side to disable them. Drag the columns to the top or bottom to sort them.', VGSE()->textname); ?></p>

					<button class="button vgse-change-all-states" data-to="enabled"><?php _e('Enable all', VGSE()->textname); ?></button> - 
					<button class="button vgse-change-all-states" data-to="disabled"><?php _e('Disable all', VGSE()->textname); ?></button><?php
					if (class_exists('WP_Sheet_Editor_Custom_Columns')) {
						?> - 
						<a class="button button-secondary" href="<?php echo admin_url('admin.php?page=vg_sheet_editor_custom_columns'); ?>"><?php _e('Create new column', VGSE()->textname); ?></a>
					<?php } ?>

				</li>
				<li>

					<?php if (current_user_can('manage_options')) { ?>
						<a class="vgse-restore-removed-columns" href="javascript:void(0)"><?php _e('Restore deleted columns', VGSE()->textname); ?></a>
					<?php } ?>

					<?php if (!empty($options[$post_type])) { ?>
						<p class="alert alert-blue vg-refresh-needed"><?php _e('If you want to enable the columns marked with the "refresh" icon, you need to enable the option "Save these settings for future sessions" and reload the page.', VGSE()->textname); ?></p>
					<?php } ?>
					<div class="vgse-sorter-section">

						<h3><?php _e('Enabled', VGSE()->textname); ?></h3>
						<ul class="vgse-sorter columns-enabled" id="vgse-columns-enabled">
							<?php 
							if( empty($options[$post_type])){
								$options[$post_type] = array();
							}
							if( empty($options[$post_type]['enabled'])){
								$options[$post_type]['enabled'] = wp_list_pluck($filtered_columns, 'title', 'key');
							}
							foreach ($visible_columns as $column_key => $column) {
								if( in_array($column_key, $not_allowed_columns) ){
									continue;
								}
								$title = $column['title'];
								?>
								<li><span class="handle">::</span> <?php echo $title; ?> 
									<input type="hidden" name="columns[]" class="js-column-key" value="<?php echo $column_key; ?>" />
									<input type="hidden" name="columns_names[]" class="js-column-title" value="<?php echo $title; ?>" />
									<?php if (current_user_can('manage_options')) { ?>
										<button class="remove-column" title="<?php echo esc_attr(__('Remove column completely. If you want to use it later you can disable it by dragging and dropping to the right column', VGSE()->textname)); ?>"><i class="fa fa-remove"></i></button>
									<?php } ?>
								</li>
							<?php }
							?>
						</ul>
					</div>
					<div class="vgse-sorter-section">
						<h3><?php _e('Disabled', VGSE()->textname); ?></h3>
						<ul class="vgse-sorter columns-disabled" id="vgse-columns-disabled"><?php
							if (isset($options[$post_type]['disabled'])) {
								foreach ($options[$post_type]['disabled'] as $column_key => $column_title) {
									?>
									<li><span class="handle">::</span> <?php echo $column_title; ?>  <i class="fa fa-refresh"></i>
										<input type="hidden" name="columns[]" class="js-column-key" value="<?php echo $column_key; ?>" />
										<input type="hidden" name="columns_names[]" class="js-column-title" value="<?php echo $column_title; ?>" />
										<?php if (current_user_can('manage_options')) { ?>
											<button class="remove-column" title="<?php echo esc_attr(__('Remove column completely. If you want to use it later you can disable it by dragging and dropping to the right column', VGSE()->textname)); ?>"><i class="fa fa-remove"></i></button>
										<?php } ?>
									</li>
									<?php
								}
							}
							?></ul>
					</div>
				</li>
				<li class="vgse-allow-save-settings">
					<label><input type="checkbox" value="yes" name="save_post_type_settings" class="save_post_type_settings" /> <?php _e('Save these settings for future sessions?', VGSE()->textname); ?> <a href="#" class="tipso" data-tipso="If you enable this option, we will use these settings the next time you load the editor for this post type.">( ? )</a></label>

				</li>

				<li class="vgse-save-settings">
					<div class="alert alert-blue"><?php _e('<p>Note: When you hide columns we will remove those columns from the spreadsheet, so if you have unsaved changes in those columns you should save them before modifying this setting.</p>'); ?></div>

					<?php if (!$partial_form) { ?>
						<button type="submit" class="remodal-confirm"><?php _e('Apply settings', VGSE()->textname); ?></button>
						<button data-remodal-action="confirm" class="remodal-cancel"><?php _e('Close', VGSE()->textname); ?></button>
					<?php } ?>
				</li>
			</ul>
			<input type="hidden" value="<?php echo implode(',', $not_allowed_columns); ?>" class="not-allowed-columns">
			<input type="hidden" value="" class="all-allowed-columns" name="vgse_columns_enabled_all_keys">
			<?php if (!$partial_form) { ?>
				<input type="hidden" value="vgse_update_columns_visibility" name="action">
				<input type="hidden" value="<?php echo $nonce; ?>" name="nonce">
				<input type="hidden" value="<?php echo $post_type; ?>" name="post_type">
			<?php } ?>

		</form>
	</div>
	<br>
</div>