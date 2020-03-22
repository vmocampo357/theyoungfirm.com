<?php
/**
 * Template used for the extensions page.
 */
$nonce = wp_create_nonce('bep-nonce');
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
			<a href="<?php echo VGSE()->get_buy_link('extensions-page-logo'); ?>" target="_blank"><img src="<?php echo VGSE()->plugin_url; ?>assets/imgs/logo-248x102.png" class="vg-logo"></a>
		</div>		
		<h2><?php _e('What component do you need?', VGSE()->textname); ?></h2>

		<?php do_action('vg_sheet_editor/extensions_page/before_content'); ?>
		<?php VGSE()->render_extensions_list(); ?>		


		<?php do_action('vg_sheet_editor/extensions_page/after_content'); ?>
	</div>
</div>
			<?php
		