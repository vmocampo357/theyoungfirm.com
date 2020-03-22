<?php
/**
 * Template used for the quick setup page.
 */
$nonce = wp_create_nonce('bep-nonce');

$current_post_type = VGSE()->helpers->get_provider_from_query_string();
update_option('vgse_disable_quick_setup', true);

$welcome_url = apply_filters('vg_sheet_editor/welcome_url', null);
if ($welcome_url) {
	?>
	<script>window.location.href = '<?php echo esc_url($welcome_url); ?>';</script>
	<?php
}
?>
<style>
	#setting-error-tgmpa {
		display: none;
	}
</style>
<div class="remodal-bg quick-setup-page-content" id="vgse-wrapper" data-nonce="<?php echo $nonce; ?>">
	<div class="">
		<div class="">
			<h2 class="hidden"><?php _e('Sheet Editor', VGSE()->textname); ?></h2>
			<a href="<?php echo VGSE()->get_buy_link('quick-setup-logo'); ?>" target="_blank"><img src="<?php echo VGSE()->plugin_url; ?>assets/imgs/logo-248x102.png" class="vg-logo"></a>
		</div>
		<div class="steps-container">
			<ul class="progressbar">
				<li class="active">Setup</li>
				<li>Enable modules</li>
				<li>Start editing</li>
			</ul>
		</div>
		<div class="setup-screen setup-step active">
			<h2><?php _e('Welcome to WP Sheet Editor', VGSE()->textname); ?></h2>
			<?php do_action('vg_sheet_editor/quick_setup_page/quick_setup_screen/before_content'); ?>
			<p><?php _e('You can start using the spreadsheet editor in just 5 minutes. Please follow these steps.', VGSE()->textname); ?></p>

			<?php
			ob_start();
			require 'post-types-form.php';
			$post_types_form = ob_get_clean();
			$steps = array();

			if (!class_exists('ReduxFramework')) {
				$steps['install_dependencies'] = '<p>' . __(sprintf('Install the Redux Framework plugin. <a href="%s" target="_blank" class="button">Click here</a>.<br/>It is required for the settings page. When you finish the installation please return to this page to continue.', VGSE()->get_plugin_install_url('redux-framework')), VGSE()->textname) . '</p>';
			}
			$steps['enable_post_types'] = '<p>' . __('Select the information that you want to edit with the spreadsheet editor.') . '</p>' . $post_types_form;

			$steps = apply_filters('vg_sheet_editor/quick_setup_page/setup_steps', $steps);

			if (!empty($steps)) {
				echo '<ol class="steps">';
				foreach ($steps as $key => $step_content) {
					?>
					<li><?php echo $step_content; ?></li>		
					<?php
				}

				echo '</ol>';
			}
			?>

			<button class="button button-primary button-primary save-all-trigger"><?php _e('Save and continue', VGSE()->textname); ?></button>

			<?php do_action('vg_sheet_editor/quick_setup_page/quick_setup_screen/after_content'); ?>
		</div>

		<div class="modules setup-step">
			<h2><?php _e('Available components', VGSE()->textname); ?></h2>
			<p><?php _e('The spreadsheet editor is very powerful and it has a lot of features. In this step you can enable the features that you need.', VGSE()->textname); ?></p>			

			<a class="button button-primary button-primary" href="#extensions-list">Enable Advanced Spreadsheet Features</a> - <button class="button save-all-trigger"><i class="fa fa-chevron-right"></i> Continue with the Basic Spreadsheet Now</button> - <a class="button help-button" href="<?php echo VGSE()->get_support_links('contact_us','url', 'quick-setup-step-2-help'); ?>" target="_blank" ><i class="fa fa-envelope"></i> <?php _e('Need help?', VGSE()->textname); ?></a>
			<hr/>
			<?php VGSE()->render_extensions_list();
			?>	
			<button class="button button-primary button-primary save-all-trigger"><?php _e('Continue', VGSE()->textname); ?></button> - 
			<button class="button step-back"><?php _e('Go back', VGSE()->textname); ?></button>
		</div>
		<div class="usage-screen setup-step">
			<h2><?php _e('The Spreadsheet is ready.', VGSE()->textname); ?></h2>
			<div class="post-types-enabled">
				<?php
				$post_types = VGSE()->helpers->get_enabled_post_types();

				if (!empty($post_types)) {
					foreach ($post_types as $key => $post_type_name) {
						if (is_numeric($key)) {
							$key = $post_type_name;
						}
						?>
						<a class="button post-type-<?php echo $key; ?>" href="<?php
						echo VGSE()->helpers->get_editor_url($key);
						?>"><?php _e('Edit ' . $post_type_name . 's', VGSE()->textname); ?></a>		
						   <?php
					   }
				   }
				   ?>
			</div>
			<hr>
			<?php if (class_exists('ReduxFramework')) { ?>
				<a class="button settings-button" href="<?php echo VGSE()->helpers->get_settings_page_url(); ?>"><i class="fa fa-cog"></i> <?php _e('Settings', VGSE()->textname); ?></a>
			<?php } ?>	
			<button class="button step-back"><?php _e('Go back', VGSE()->textname); ?></button>

			<?php do_action('vg_sheet_editor/quick_setup_page/usage_screen/after_content'); ?>
		</div>


		<div class="clear"></div>	
		<hr/>
		<p><?php _e('Tip. We can setup the plugin and do the content updates for you', VGSE()->textname); ?> <a class="button help-button" href="<?php echo VGSE()->get_support_links('contact_us','url', 'quick-setup-bottom'); ?>" target="_blank" ><i class="fa fa-envelope"></i> <?php _e('Need help? Contact us', VGSE()->textname); ?></a></p>

		<?php do_action('vg_sheet_editor/quick_setup_page/after_content'); ?>
	</div>
</div>
			<?php
		