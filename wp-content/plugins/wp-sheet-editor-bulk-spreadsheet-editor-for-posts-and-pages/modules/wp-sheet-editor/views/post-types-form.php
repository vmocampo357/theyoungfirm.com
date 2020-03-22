<?php
$allowed_post_types = VGSE()->helpers->get_allowed_post_types();
$enabled_post_types = VGSE()->helpers->get_enabled_post_types();
$post_types = VGSE()->helpers->get_all_post_types(array(
	'show_in_menu' => true,
		));

if (empty($post_types)) {
	return;
}
?>

<form action="<?php echo admin_url('admin-ajax.php'); ?>" method="POST" class="post-types-form">

	<p><?php _e('Available post types', VGSE()->textname); ?></p>

	<?php
	foreach ($post_types as $post_type) {
		$key = $post_type->name;
		$post_type_name = $post_type->label;
		$disabled = !isset($allowed_post_types[$key]) ? ' disabled ' : '';
		$buy_link = $key === 'users' ? VGSE()->bundles['users']['inactive_action_url'] : VGSE()->bundles['custom_post_types']['inactive_action_url'];
		$maybe_go_premium = !empty($disabled) ? '<small><a href="' . VGSE()->get_buy_link('setup-post-type-selector', $buy_link) . '" target="_blank">' . __('(Go premium)', VGSE()->textname) . '</a></small>' : '';
		?>
		<div class="post-type-field post-type-<?php echo $key; ?>"><input type="checkbox" name="post_types[]" value="<?php echo $key; ?>" id="<?php echo $key; ?>" <?php echo $disabled; ?> <?php checked(in_array($key, $enabled_post_types)); ?>> <label for="<?php echo $key; ?>"><?php echo $post_type_name; ?> <?php echo $maybe_go_premium; ?></label></div>
	<?php } ?>
	<input type="hidden" name="action" value="vgse_save_post_types_setting">
	<input type="hidden" name="append" value="no">
	<input type="hidden" name="nonce" value="<?php echo wp_create_nonce('bep-nonce'); ?>">
	<button class="button button-primary hidden save-trigger button-primary"><?php _e('Save', VGSE()->textname); ?></button>
</form>