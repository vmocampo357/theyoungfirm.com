<?php
	/**
	 * Plugin Name: Simple 301 Redirects - Addon - Bulk CSV Uploader
	 * Plugin URI: https://wordpress.org/support/plugin/simple-301-redirects-addon-bulk-uploader
	 * Description: Adds the ability to upload a CSV to populate the Simple 301 Redirects plugin
	 * Author: Webcraftic <wordpress.webraftic@gmail.com>, Ash Durham <plugins@kingpro.me>
	 * Version: 1.2.4
	 * Text Domain: s301r-bulk-uploader
	 * Domain Path: /languages/
	 * Author URI: https://clearfy.pro
	 */
	
	/*
		Copyright 2018  Webcraftic  (email : wordpress.webraftic@gmail.com)
		Copyright 2016  Ash Durham  (email : plugins@kingpro.me)

		This program is free software; you can redistribute it and/or modify
		it under the terms of the GNU General Public License, version 2, as
		published by the Free Software Foundation.

		This program is distributed in the hope that it will be useful,
		but WITHOUT ANY WARRANTY; without even the implied warranty of
		MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
		GNU General Public License for more details.

		You should have received a copy of the GNU General Public License
		along with this program; if not, write to the Free Software
		Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
	*/
	
	if ( ! class_exists( "Bulk301Uploader" ) ) {
		
		class Bulk301Uploader {
			
			public function __construct() {
				// Notifications about the extended version of the plugin with user interfaces.
				// After hide no longer bother
				
				require_once dirname( __FILE__ ) . '/admin-notices.php';
				S301B_Admin_Notices::instance( __FILE__ );
				
				// Localization plugin
				load_plugin_textdomain( 's301r-bulk-uploader', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );
			}
			
			
			/*
				check if Simple 301 Redirects exists and is active
			*/
			public function check_for_parent() {
				include_once( ABSPATH . 'wp-admin/includes/plugin.php' );
				if ( ! is_plugin_active( 'simple-301-redirects/wp-simple-301-redirects.php' ) ) {
					add_action( 'admin_notices', array( $this, 'bulk301_admin_notice' ) );
					
					return false;
				}
				
				return true;
			}
			
			public function bulk301_admin_notice() {
				?>
                <div class="error">
                    <p><?php _e( "301 Bulk Uploader requires the Simple 301 Redirects plugin to be installed and active in order to work.", 's301r-bulk-uploader' ); ?></p>
                </div>
				<?php
			}
			
			/*
				generate the link to the options page under settings
			*/
			public function create_bulk_menu() {
				add_options_page( __( '301 Bulk Redirects', 's301r-bulk-uploader' ), __( '301 Bulk Redirects', 's301r-bulk-uploader' ), 'manage_options', '301bulkoptions', array(
					$this,
					'bulk_options_page'
				) );
			}
			
			/*
				generate the options page in the wordpress admin
			*/
			public function bulk_options_page() {
				global $report;
				?>
                <div class="wrap">
                    <h2><?php _e( 'Simple 301 Redirects - Bulk Upload', 's301r-bulk-uploader' ) ?></h2>
                    <a href="<?= plugins_url( '301-example.csv', __FILE__ ); ?>"><?php _e( 'Example CSV', 's301r-bulk-uploader' ) ?></a>
                    -
                    <a href='<?= admin_url( 'admin.php?action=bulk301export' ) ?>'><?php _e( 'Export current 301\'s to CSV', 's301r-bulk-uploader' ) ?></a>
                    -
                    <a href="<?= admin_url( 'admin.php?action=bulk301clearlist' ) ?>" onclick="if(confirm('<?php _e( "Are you sure you want to do this? This will not be able to be retrieved. As a backup, please use the export feature to download a current copy before doing this.", 's301r-bulk-uploader' ) ?>')) {return true;
                            }else {return false;
                            }"><?php _e( 'Clear 301 Redirect List (DO SO WITH CAUTION!)', 's301r-bulk-uploader' ) ?></a>
                    <form method="post" action="options-general.php?page=301bulkoptions" enctype="multipart/form-data">
                        <input type="file" name="301_bulk_redirects"/>
                        <p>
                            <input type="hidden" name="auto_detect_end_line" value="0"/>
                            <input type="checkbox" name="auto_detect_end_line" value="1"/><?php _e( 'Auto Detect End of Line', 's301r-bulk-uploader' ) ?>
                            <br/>
                            <span style="font-size: 10px;"><?php _e( "If you are experiencing unusual results after uploading and/or you are using a Mac and have generated the CSV via excel, it is recommended to use this option to force PHP to detect the end of line automatically. By default, PHP has this turned off. This option changes the <strong>'auto_detect_line_endings'</strong> php.ini option temporarily while uploading and reading your CSV file.", 's301r-bulk-uploader' ) ?></span>
                        </p>
                        <p class="submit">
                            <input type="submit" name="submit_bulk_301" class="button-primary" value="<?php _e( 'Upload 301s', 's301r-bulk-uploader' ) ?>"/>
                        </p>
                    </form>
					<?php if ( isset( $report ) ) : echo $report; endif; ?>
                </div>
				<?php
			} // end of function options_page
			
			/*
				save the redirects from the options page to the database
			*/
			public function save_bulk_redirects( $data, $auto_detect = 0 ) {
				// Get Current Redirects
				$current_redirects = get_option( '301_redirects' );
				
				// Get CSV File
				$allowedExts = array( "csv" );
				$temp        = explode( ".", $data["name"] );
				$extension   = end( $temp );
				$report      = '';
				
				$mime_types = array(
					'application/csv',
					'application/excel',
					'application/ms-excel',
					'application/x-excel',
					'application/vnd.ms-excel',
					'application/vnd.msexcel',
					'application/octet-stream',
					'application/data',
					'application/x-csv',
					'application/txt',
					'plain/text',
					'text/anytext',
					'text/csv',
					'text/x-csv',
					'text/plain',
					'text/comma-separated-values'
				);
				
				if ( in_array( $data["type"], $mime_types ) && ( $data["size"] < 10000000 ) && in_array( $extension, $allowedExts ) ) {
					if ( $data["error"] > 0 ) {
						$report .= "Return Code: " . $data["error"] . "<br>";
					} else {
						$report .= "Upload: " . $data["name"] . "<br />";
						$report .= "Size: " . ( $data["size"] / 1024 ) . " kB<br /><br />";
						
						$row = 1;
						
						if ( $auto_detect == 1 ) {
							ini_set( 'auto_detect_line_endings', true );
						}
						
						if ( ( $handle = fopen( $data["tmp_name"], "r" ) ) !== false ) {
							while( ( $data = fgetcsv( $handle, 1000, "," ) ) !== false ) {
								$num = count( $data );
								$row ++;
								if ( ! isset( $current_redirects[ $data[0] ] ) && $data[1] !== '' ) {
									$current_redirects[ $data[0] ] = $data[1];
									$report                        .= "<strong>" . $data[0] . "</strong> " . __( 'was added to redirect to', 's301r-bulk-uploader' ) . $data[1] . "<br />";
								} elseif ( ! isset( $current_redirects[ $data[0] ] ) && $data[1] !== '' ) {
									$report .= "<span style='color: red'><strong>" . $data[0] . "</strong> " . __( 'is missing a corresponding URL to redirect to', 's301r-bulk-uploader' ) . ".</span><br />";
								} else {
									$report .= "<span style='color: red'><strong>" . $data[0] . "</strong> " . __( 'already exists and was not added', 's301r-bulk-uploader' ) . ".</span><br />";
								}
							}
							fclose( $handle );
							update_option( '301_redirects', $current_redirects );
						}
					}
				} else {
					$report .= __( '<strong>Invalid file</strong>. Use the below for debugging and when asking for support.', 's301r-bulk-uploader' ) . "<br /><br />";
					
					$report .= "<strong>" . __( 'File', 's301r-bulk-uploader' ) . "</strong>: " . print_r( $data, 1 );
					if ( ! in_array( $data["type"], $mime_types ) ) {
						$report .= "<br /><br /><strong>" . __( 'Approved Mime Types', 's301r-bulk-uploader' ) . "</strong>:<br />";
						foreach ( $mime_types as $mtype ) {
							$report .= $mtype . "<br />";
						}
						$report .= "<br />" . __( 'If you are certain that your filetype should be in this list, please let us know on the', 's301r-bulk-uploader' ) . " <a href='http://wordpress.org/support/plugin/simple-301-redirects-addon-bulk-uploader' target='_blank'>" . __( 'forums', 's301r-bulk-uploader' ) . "</a>.";
					}
				}
				
				if ( $auto_detect == 1 ) {
					ini_set( 'auto_detect_line_endings', false );
				}
				
				return $report;
			}
			
			/*
				Export redirects to CSV
			*/
			public function export_bulk_redirects() {
				// Get Current Redirects
				$current_redirects = get_option( '301_redirects' );
				
				header( 'Content-Type: application/excel' );
				header( 'Content-Disposition: attachment; filename="301_redirects.csv"' );
				$data = array();
				
				foreach ( $current_redirects as $old_url => $new_url ) {
					$data[] = array( $old_url, $new_url );
				}
				
				$fp = fopen( 'php://output', 'w' );
				foreach ( $data as $line ) {
					fputcsv( $fp, $line );
				}
				fclose( $fp );
			}
			
			/*
				Clear 301 Redirects list
			 */
			public function clear_301_redirects() {
				update_option( '301_redirects', '' );
				header( "Location: " . $_SERVER['HTTP_REFERER'] );
			}
		}
	}
	
	// instantiate
	$bulk_redirect_plugin = new Bulk301Uploader();
	
	if ( isset( $bulk_redirect_plugin ) && $bulk_redirect_plugin->check_for_parent() ) {
		
		// create the menu
		add_action( 'admin_menu', array( $bulk_redirect_plugin, 'create_bulk_menu' ) );
		
		// Create export action
		add_action( 'admin_action_bulk301export', array( $bulk_redirect_plugin, 'export_bulk_redirects' ) );
		
		// Create clear action
		add_action( 'admin_action_bulk301clearlist', array( $bulk_redirect_plugin, 'clear_301_redirects' ) );
		
		// if submitted, process the data
		if ( isset( $_POST['submit_bulk_301'] ) ) {
			$report = $bulk_redirect_plugin->save_bulk_redirects( $_FILES['301_bulk_redirects'], (int) $_POST['auto_detect_end_line'] );
		}
	}

