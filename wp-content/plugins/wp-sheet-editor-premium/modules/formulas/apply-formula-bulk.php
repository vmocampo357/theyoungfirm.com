<?php
if (!class_exists('WPSE_Bulk_Apply_Formula')) {

	/**
	 * This class renders the apply formula in bulk page.
	 */
	class WPSE_Bulk_Apply_Formula {

		static private $instance = false;

		private function __construct() {
			
		}

		/**
		 * Creates or returns an instance of this class.
		 */
		static function get_instance() {
			if (null == WPSE_Bulk_Apply_Formula::$instance) {
				WPSE_Bulk_Apply_Formula::$instance = new WPSE_Bulk_Apply_Formula();
				WPSE_Bulk_Apply_Formula::$instance->init();
			}
			return WPSE_Bulk_Apply_Formula::$instance;
		}

		function init() {

			add_action('admin_menu', array($this, 'register_menu_page'));
			add_action('admin_enqueue_scripts', array($this, 'register_scripts'), 999);
		}

		/**
		 * Register menu page
		 */
		function register_menu_page() {
			add_submenu_page(null, __('Sheet Editor Formula', VGSE()->textname), __('Sheet Editor Formula', VGSE()->textname), 'edit_posts', 'vgse_run_formulas', array($this, 'render_run_formula_page'));
		}

		/**
		 * Register frontend assets
		 */
		function register_scripts() {

			if (empty($_GET['page']) || $_GET['page'] !== 'vgse_run_formulas') {
				return;
			}
			$current_post = (!empty($_GET['post_type'])) ? $_GET['post_type'] : 'post';
			VGSE()->_register_scripts_lite($current_post);
		}

		function __set($name, $value) {
			$this->$name = $value;
		}

		function __get($name) {
			return $this->$name;
		}

		/**
		 * Render apply formula in bulk page html
		 */
		function render_run_formula_page() {
			if (!current_user_can('edit_posts')) {
				wp_die(__('You dont have enough permissions to view this page.', VGSE()->textname));
			}

			$query_strings = VGSE()->helpers->clean_data($_REQUEST);


			if (empty($query_strings['filters_found_rows']) || empty($query_strings['column']) || empty($query_strings['be-formula'])) {
				echo '<div><h3>' . __('No data to process...', VGSE()->textname) . '</h3></div>';
				return;
			}

			$column = $query_strings['column'];
			$apply_to = (!isset($query_strings['apply_to'])) ? array('all') : $query_strings['apply_to'];
			$formula = $_REQUEST['be-formula'];
			$post_type = $query_strings['post_type'];
			$total = (int) $query_strings['filters_found_rows'];
			$nonce = wp_create_nonce('bep-nonce');
			?>

			<div class="vgse-bulk-run-formula">
				<div id="ohsnap"></div>
				<div class="">
					<h2 class="hidden"><?php _e('Sheet Editor', VGSE()->textname); ?></h2>
					<img src="<?php echo VGSE()->plugin_url; ?>assets/imgs/logo-248x102.png" class="vg-logo">
				</div>
				<p><?php _e('The formula is being applied. Please dont close this window until the process has finished.'); ?></p>
				<p><?php printf(__('<b>Tip:</b> The formula execution is too slow? <a href="%s" target="_blank">Save <b>more posts</b> per batch</a><br/>Are you getting errors when executing the formula? <a href="%s" target="_blank">Save <b>less posts</b> per batch</a>', VGSE()->textname), VGSE()->helpers->get_settings_page_url(), VGSE()->helpers->get_settings_page_url()); ?></p>

				<p><a href="#" class="button pause-formula-execution button-secondary" data-action="pause"><i class="fa fa-pause"></i> Pause</a></p>
				<div class="be-response">
					<p><?php _e('Processing...', VGSE()->textname); ?></p>
				</div>
				<script>
					jQuery(document).ready(function () {

						var nonce = '<?php echo $nonce; ?>';
						var total = <?php echo $total; ?>;
						var column = '<?php echo $column; ?>';
			<?php if (!empty($apply_to)) { ?>
							var apply_to = ['<?php echo implode("','", $apply_to); ?>'];
			<?php } else { ?>
							var apply_to = 'all';
			<?php } ?>
						var formula = '<?php echo $formula; ?>';
						var post_type = '<?php echo $post_type; ?>';

						var $response = jQuery('.be-response');
						var loop = beAjaxLoop({
							totalCalls: Math.ceil(total / vgse_editor_settings.save_posts_per_page),
							url: ajaxurl,
							dataType: 'json',
							method: 'POST',
							data: {
								action: 'vgse_bulk_edit_formula_big',
								total: total,
								column: column,
								apply_to: apply_to,
								formula: formula,
								nonce: nonce,
								post_type: post_type,
								raw_form_data: <?php echo json_encode($query_strings); ?>
							},
							onSuccess: function (res, settings) {
								console.log('success cb');

								if (!res.success) {
									$response.append('<p>' + res.data.message + '</p>');
									$response.scrollTop($response[0].scrollHeight);
									return false;
								}


								settings.totalCalls = Math.ceil(parseInt(res.data.total) / vgse_editor_settings.save_posts_per_page);

								$response.append(res.data.message);
								$response.scrollTop($response[0].scrollHeight);

								if (res.data.force_complete) {
									return false;
								}

								if (settings.current === settings.totalCalls) {
									$response.append('<?php _e('The formula has been applied completely. You can close this window.', VGSE()->textname); ?>');
									$response.scrollTop($response[0].scrollHeight);
									return false;
								}
								return true;
							},
							onError: function (jqXHR, textStatus, settings) {
								console.log('error cb');

								var goNext = confirm('<?php _e('Your server was not able to process this batch. Do you want to try again?', VGSE()->textname); ?>');

								if (!goNext) {
									$response.append('<?php _e('<p>The formula was not applied completely. The process was canceled due to an error.</p><p>You can close this window.</p>', VGSE()->textname); ?>');
									$response.scrollTop($response[0].scrollHeight);
									return false;
								}

								// le restamos 1 al pointer para que la función le 
								// incremente 1 y repita la misma tanda
								settings.current--;
								return true;
							}
						});


						jQuery('.pause-formula-execution').click(function (e) {
							e.preventDefault();
							var $button = jQuery(this);
							if ($button.data('action') === 'pause') {
								$button.data('action', 'play').addClass('button-primary').removeClass('button-secondary').html('<i class="fa fa-play"></i> Resume');
								loop.pause();
							} else {
								$button.data('action', 'pause').addClass('button-secondary').removeClass('button-primary').html('<i class="fa fa-pause"></i> Pause');
								loop.resume();
							}
						});

					});
				</script>
			</div>
			<?php
		}

	}

	WPSE_Bulk_Apply_Formula::get_instance();
}
