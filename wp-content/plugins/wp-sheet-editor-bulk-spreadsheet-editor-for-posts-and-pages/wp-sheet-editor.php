<?php

/*
 Plugin Name: WP Sheet Editor
 Description: Bulk edit posts and pages easily using a beautiful spreadsheet inside WordPress.
 Version: 2.8.2
 Author: WP Sheet Editor
 Author URI: https://wpsheeteditor.com
 Plugin URI: https://wpsheeteditor.com/extensions/posts-pages-post-types-spreadsheet/
 @fs_premium_only /modules/user-path/send-user-path.php, /modules/acf/, /modules/advanced-filters/, /modules/columns-renaming/, /modules/custom-post-types/, /modules/formulas/, /modules/custom-columns/, /modules/spreadsheet-setup/, /modules/woocommerce/, /whats-new/
*/
if ( !defined( 'VGSE_MAIN_FILE' ) ) {
    define( 'VGSE_MAIN_FILE', __FILE__ );
}
if ( !defined( 'VGSE_DIST_DIR' ) ) {
    define( 'VGSE_DIST_DIR', __DIR__ );
}
if ( !class_exists( 'WP_Sheet_Editor_Dist' ) ) {
    class WP_Sheet_Editor_Dist
    {
        private static  $instance = false ;
        var  $modules_controller = null ;
        var  $sheets_bootstrap = null ;
        private function __construct()
        {
        }
        
        /**
         * Creates or returns an instance of this class.
         */
        static function get_instance()
        {
            
            if ( null == WP_Sheet_Editor_Dist::$instance ) {
                WP_Sheet_Editor_Dist::$instance = new WP_Sheet_Editor_Dist();
                WP_Sheet_Editor_Dist::$instance->init();
            }
            
            return WP_Sheet_Editor_Dist::$instance;
        }
        
        function notify_wrong_core_version()
        {
            $plugin_data = get_plugin_data( __FILE__, false, false );
            ?>
			<div class="notice notice-error">
				<p><?php 
            _e( 'Please update the WP Sheet Editor plugin and all its extensions to the latest version, the CORE plugin should be version 2.5.2 or higher. The plugin "' . $plugin_data['Name'] . '" requires that version.', VGSE()->textname );
            ?></p>
			</div>
			<?php 
        }
        
        function init()
        {
            require_once __DIR__ . '/modules/init.php';
            $this->modules_controller = new WP_Sheet_Editor_CORE_Modules_Init( __DIR__ );
            add_action( 'plugins_loaded', array( $this, 'late_init' ) );
            // After core has initialized
            add_filter( 'vg_sheet_editor/after_init', array( $this, 'after_core_init' ) );
        }
        
        /**
         * Redirect to welcome page after plugin activation
         */
        function redirect_to_welcome_page()
        {
            // Bail if no activation redirect
            $flag_key = 'vgse_welcome_redirect';
            $flag = get_option( $flag_key, '' );
            if ( $flag === 'no' ) {
                return;
            }
            update_option( $flag_key, 'no' );
            // Disable "whats new" redirect
            update_option( 'vgse_hide_whats_new_' . VGSE()->version, 'yes' );
            // Bail if activating from network, or bulk
            if ( is_network_admin() || isset( $_GET['activate-multi'] ) ) {
                return;
            }
            $welcome_url = esc_url( add_query_arg( array(
                'page' => 'vg_sheet_editor_setup',
            ), admin_url( 'admin.php' ) ) );
            wp_redirect( $welcome_url );
            exit;
        }
        
        function after_core_init()
        {
            
            if ( version_compare( VGSE()->version, '2.5.2' ) < 0 ) {
                $this->notify_wrong_core_version();
                return;
            }
            
            add_action( 'admin_init', array( $this, 'redirect_to_welcome_page' ) );
            // Set up posts editor.
            // Allow to bootstrap editor manually, later.
            if ( !apply_filters( 'vg_sheet_editor/bootstrap/manual_init', false ) ) {
                $this->sheets_bootstrap = new WP_Sheet_Editor_Bootstrap();
            }
        }
        
        function late_init()
        {
            if ( function_exists( 'vgse_freemius' ) ) {
            }
            add_filter( 'vg_sheet_editor/allowed_post_types', array( $this, 'enable_basic_post_types' ) );
            load_plugin_textdomain( VGSE()->textname, false, basename( dirname( __FILE__ ) ) . '/languages' );
        }
        
        function enable_basic_post_types( $post_types )
        {
            if ( !isset( $post_types['post'] ) ) {
                $post_types['post'] = 'Posts';
            }
            if ( !isset( $post_types['page'] ) ) {
                $post_types['page'] = 'Page';
            }
            return $post_types;
        }
        
        function __set( $name, $value )
        {
            $this->{$name} = $value;
        }
        
        function __get( $name )
        {
            return $this->{$name};
        }
    
    }
}
WP_Sheet_Editor_Dist::get_instance();