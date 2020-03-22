
<div class="remodal remodal-support" data-remodal-id="modal-support" data-remodal-options="closeOnOutsideClick: false">

	<div class="modal-content">
		<h3><?php _e('Help', VGSE()->textname); ?></h3>

		<?php
		$support_links = VGSE()->get_support_links(null, '', 'sheet-toolbar-help');

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

	</div>
	<br>
	<button data-remodal-action="confirm" class="remodal-cancel"><?php _e('Close', VGSE()->textname); ?></button>
</div>