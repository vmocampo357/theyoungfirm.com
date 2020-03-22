<style>

	.vg-sheet-editor-usage-stats {
		text-align: center;
		overflow: auto;

	}
	.vg-sheet-editor-usage-stats p {
		font-size: 15px;
	}
	.vg-sheet-editor-usage-stats li {
		float: left;
	}
	.vg-sheet-editor-usage-stats .stats-list li {
		width: 100%;
		max-width: 165px;
		font-size: 16px;
		margin-bottom: 20px;
	}
	.vg-sheet-editor-usage-stats .count {
		font-size: 40px;
		color: green;
		line-height: 40px;
	}
	.vg-logo {
		display: block;
		margin: 0 auto;
	}
	.vg-sheet-editor-usage-stats hr {
		margin: 20px auto 0;
	}
	.vg-sheet-editor-usage-stats .post-types-enabled {
		margin-top: 10px;
	}
</style>
<div class="vg-sheet-editor-usage-stats">
	<a href="<?php echo VGSE()->get_buy_link('usage-widget-logo'); ?>" target="_blank"><img src="<?php echo VGSE()->plugin_url; ?>assets/imgs/logo-248x102.png" class="vg-logo"></a>

	<p><?php _e('Thank you for using our spreadsheet editor', VGSE()->textname); ?></p>

	<?php
	$editions_count = (int) get_option('vgse_editions_counter', 0);
	$processed_count = (int) get_option('vgse_processed_counter', 0);

	if ($processed_count > 0) {
		?>
		<h2><?php _e('Usage stats', VGSE()->textname); ?></h2>

		<?php
		$minutes_saved = ($editions_count * 4) / 3;
		$stats = array(
			'total_editions' => array(
				'label' => __('Modified posts', VGSE()->textname),
				'count' => $processed_count,
			),
			'time_saved' => array(
				'label' => __('Time saved <br/>(estimated)', VGSE()->textname),
				'count' => ( $minutes_saved > 60 ) ? intval($minutes_saved / 60) . ' hr' : intval($minutes_saved) . ' mins.',
			),
			'clicks_avoided' => array(
				'label' => __('Clicks avoided <br/>(estimated)', VGSE()->textname),
				'count' => $editions_count * 5,
			),
		);

		$stats = apply_filters('vg_sheet_editor/usage_stats/stats', $stats, $editions_count, $processed_count);

		if (!empty($stats)) {
			?>
			<ul class="stats-list">
				<?php foreach ($stats as $key => $stat) { ?>
					<li><div class="count"><?php echo $stat['count']; ?></div><div class="label"><?php echo $stat['label']; ?></div></li>
				<?php } ?>
			</ul>
		<?php
		}
	}
	?>
		<div class="clear"></div>
		<hr>
		<h2><?php _e('Extend the spreadsheet', VGSE()->textname); ?></h2>
		<p><?php _e('Edit WooCommerce products, WooCommerce Variations and Attributes.<br/>Edit hundreds of posts at once using formulas, copy information between posts,<br/>Edit custom post types and custom fields, Edit User Profiles, and More', VGSE()->textname); ?> </p>
		<a href="<?php echo admin_url('admin.php?page=vg_sheet_editor_extensions&vgse_only_inactive=1'); ?>" class="button button-primary" style="margin-bottom: 20px; display: inline-block;"><?php _e('View extensions', VGSE()->textname); ?></a>
		
	<div class="clear"></div>
	<hr>
	<h2><?php _e('Open the Spreadsheet Editor', VGSE()->textname); ?></h2>
	<div class="post-types-enabled">
		<?php
		$post_types = VGSE()->helpers->get_enabled_post_types();

		if (!empty($post_types)) {
			foreach ($post_types as $key => $post_type_name) {
				if (is_numeric($key)) {
					$key = $post_type_name;
				}
				?>
				<a class="button post-type-<?php echo $key; ?>" href="<?php echo VGSE()->helpers->get_editor_url($key);
				?>"><?php _e('Edit ' . $post_type_name . 's', VGSE()->textname); ?></a>		
				   <?php
			   }
		   }
		   ?>
	</div>
	<div class="clear"></div>
	<hr>
	<h2><?php _e('Help', VGSE()->textname); ?></h2>
	<?php
	$support_links = VGSE()->get_support_links(null, '', 'usage-stats-help');

	if (!empty($support_links)) {
		echo '<ul>';
		foreach ($support_links as $support_link) {
			?>
			<li><a class="button button-secondary button-secondary" target="_blank" href="<?php echo $support_link['url']; ?>"><?php echo $support_link['label']; ?></a></li> 
			<?php
		}
		echo '</ul>';
		?>
<?php } ?>


	<div class="clear"></div>

</div>

<script>
	if (typeof jQuery === 'function') {
		jQuery(document).ready(function () {
			// Equalize stats items height
			var $statsItem = jQuery('.stats-list li');
			var tallest = 0;
			$statsItem.each(function () {
				if (jQuery(this).height() > tallest) {
					tallest = jQuery(this).height();
				}
			});

			$statsItem.height(tallest);
		});
	}
</script>