<?php

if ( !class_exists( 'WPSE_Sheet_Factory' ) ) {
    /**
     * Display woocommerce item in the toolbar to tease users of the free 
     * version into purchasing the premium plugin.
     */
    class WPSE_Sheet_Factory
    {
        var  $args = array() ;
        var  $sheets_bootstrap = null ;
        function __construct( $args = array() )
        {
            $defaults = array(
                'fs_object'          => null,
                'post_type'          => '',
                'post_type_label'    => '',
                'serialized_columns' => array(),
                'columns'            => array(),
                'allowed_columns'    => array(),
                'remove_columns'     => array(),
            );
            $this->args = wp_parse_args( $args, $defaults );
            if ( empty($this->get_prop( 'post_type' )) ) {
                return;
            }
            add_action( 'vg_sheet_editor/initialized', array( $this, 'init' ) );
            add_action( 'vg_sheet_editor/after_init', array( $this, 'after_full_core_init' ) );
        }
        
        function after_full_core_init()
        {
            // Set up spreadsheet.
            // Allow to bootstrap editor manually, later.
            if ( !apply_filters( 'vg_sheet_editor/bootstrap/manual_init', false ) ) {
                $this->sheets_bootstrap = new WP_Sheet_Editor_Bootstrap( array(
                    'allowed_post_types'          => array(),
                    'only_allowed_spreadsheets'   => false,
                    'enabled_post_types'          => array( $this->get_prop( 'post_type' ) ),
                    'register_toolbars'           => true,
                    'register_columns'            => true,
                    'register_taxonomy_columns'   => true,
                    'register_admin_menus'        => true,
                    'register_spreadsheet_editor' => true,
                    'current_provider'            => $this->get_prop( 'post_type' ),
                ) );
            }
        }
        
        function get_prop( $key, $default = null )
        {
            return ( isset( $this->args[$key] ) ? $this->args[$key] : $default );
        }
        
        function init()
        {
            if ( !is_admin() && !apply_filters( 'vg_sheet_editor/allowed_on_frontend', false ) ) {
                return;
            }
            add_filter(
                'vg_sheet_editor/load_rows/output',
                array( $this, 'lock_premium_columns' ),
                9,
                2
            );
            add_action(
                'vg_sheet_editor/columns/provider_items',
                array( $this, 'filter_columns_settings' ),
                10,
                4
            );
            add_action( 'vg_sheet_editor/editor/before_init', array( $this, 'register_columns' ) );
            add_filter(
                'vg_sheet_editor/custom_columns/teaser/allow_to_lock_column',
                array( $this, 'dont_lock_allowed_columns' ),
                99,
                2
            );
            add_filter( 'vg_sheet_editor/custom_post_types/get_all_post_types', array( $this, 'disable_from_custom_post_types_addon_object' ) );
            add_filter( 'vg_sheet_editor/custom_post_types/get_all_post_types_names', array( $this, 'disable_from_custom_post_types_addon_names' ) );
            add_action( 'vg_sheet_editor/editor_page/after_logo', array( $this, 'add_account_link' ) );
        }
        
        function disable_from_custom_post_types_addon_names( $post_types_names )
        {
            if ( $index = array_search( $this->get_prop( 'post_type' ), $post_types_names ) ) {
                unset( $post_types_names[$index] );
            }
            return $post_types_names;
        }
        
        function disable_from_custom_post_types_addon_object( $post_types_objects )
        {
            $indexed_post_types = wp_list_pluck( $post_types_objects, 'name' );
            if ( $index = array_search( $this->get_prop( 'post_type' ), $indexed_post_types ) ) {
                unset( $post_types_objects[$index] );
            }
            return $post_types_objects;
        }
        
        function add_account_link( $post_type )
        {
            if ( $post_type !== $this->get_prop( 'post_type' ) || !is_admin() ) {
                return;
            }
            ?>
			<a href="<?php 
            echo  $this->args['fs_object']->get_account_url() ;
            ?>" class="button" target="_blank"><i class="fa fa-user"></i> <?php 
            _e( 'My license', VGSE()->textname );
            ?></a>
			<?php 
        }
        
        function append_post_type_to_post_types_list( $post_types, $args, $output )
        {
            if ( isset( $post_types[$this->get_prop( 'post_type' )] ) || !empty(wp_list_filter( $post_types, array(
                'name' => $this->get_prop( 'post_type' ),
            ) )) ) {
                return $post_types;
            }
            
            if ( $output === 'names' ) {
                $post_types[$this->get_prop( 'post_type' )] = $this->get_prop( 'post_type_label' );
            } else {
                $post_types[$this->get_prop( 'post_type' )] = (object) array(
                    'label' => $this->get_prop( 'post_type_label' ),
                    'name'  => $this->get_prop( 'post_type' ),
                );
            }
            
            return $post_types;
        }
        
        function allow_post_type( $post_types )
        {
            $post_types[$this->get_prop( 'post_type' )] = $this->get_prop( 'post_type_label' );
            return $post_types;
        }
        
        function dont_lock_allowed_columns( $allowed_to_lock, $column_key )
        {
            if ( !empty($this->get_prop( 'allowed_columns' )) ) {
                $allowed_to_lock = !$this->is_column_allowed( $column_key );
            }
            return $allowed_to_lock;
        }
        
        /**
         * Modify spreadsheet columns settings.
         * 
         * It changes the names and settings of some columns.
         * @param array $spreadsheet_columns
         * @param string $post_type
         * @param bool $exclude_formatted_settings
         * @return array
         */
        function filter_columns_settings(
            $spreadsheet_columns,
            $post_type,
            $exclude_formatted_settings,
            $columns
        )
        {
            if ( $post_type !== $this->get_prop( 'post_type' ) ) {
                return $spreadsheet_columns;
            }
            $enabled = array();
            $disabled = array();
            foreach ( $this->get_prop( 'remove_columns' ) as $column_key ) {
                if ( isset( $spreadsheet_columns[$column_key] ) ) {
                    unset( $spreadsheet_columns[$column_key] );
                }
            }
            
            if ( !empty($this->get_prop( 'allowed_columns' )) ) {
                // Increase column width for disabled columns, so the "premium" message fits
                foreach ( $spreadsheet_columns as $key => $column ) {
                    
                    if ( $this->is_column_allowed( $key ) ) {
                        $enabled[$key] = $column;
                    } else {
                        $disabled[$key] = $column;
                        $disabled[$key]['column_width'] = 6.1 * strlen( $column['title'] ) + 75;
                        $disabled[$key]['unformatted']['readOnly'] = true;
                        $disabled[$key]['formatted']['readOnly'] = true;
                        $disabled[$key]['unformatted']['renderer'] = 'html';
                        $disabled[$key]['formatted']['renderer'] = 'html';
                        if ( isset( $disabled[$key]['formatted']['editor'] ) ) {
                            unset( $disabled[$key]['formatted']['editor'] );
                        }
                        if ( isset( $disabled[$key]['formatted']['type'] ) ) {
                            unset( $disabled[$key]['formatted']['type'] );
                        }
                    }
                
                }
                $new_columns = array_merge( $enabled, $disabled );
            } else {
                $new_columns = $spreadsheet_columns;
            }
            
            return $new_columns;
        }
        
        /**
         * Register spreadsheet columns
         */
        function register_columns( $editor )
        {
            $post_type = $this->get_prop( 'post_type' );
            if ( $editor->args['provider'] !== $post_type || empty($this->get_prop( 'columns' )) ) {
                return;
            }
            $columns = ( is_callable( $this->get_prop( 'columns' ) ) ? call_user_func( $this->get_prop( 'columns' ) ) : $this->get_prop( 'columns' ) );
            foreach ( $columns as $column_key => $column ) {
                $editor->args['columns']->register_item( $column_key, $post_type, $column );
            }
        }
        
        function lock_premium_columns( $rows, $wp_query )
        {
            if ( $wp_query['post_type'] !== $this->get_prop( 'post_type' ) || empty($this->get_prop( 'allowed_columns' )) ) {
                return $rows;
            }
            foreach ( $rows as $row_index => $row ) {
                foreach ( $row as $column_key => $value ) {
                    if ( strpos( $value, 'vg-cell-blocked' ) !== false ) {
                        continue;
                    }
                    
                    if ( !$this->is_column_allowed( $column_key ) ) {
                        // Remove buttons classes to disable cell popups
                        $value = str_replace( array(
                            'set_custom_images',
                            'view_custom_images',
                            'button-handsontable',
                            'data-remodal-target="image"'
                        ), '', $value );
                        $rows[$row_index][$column_key] = '<i class="fa fa-lock vg-cell-blocked vg-wc-teaser-lock"></i> ' . $value . ' <a href="' . VGSE()->get_buy_link( $this->get_prop( 'post_type' ) . '-locked-cell' ) . '" target="_blank">(Pro)</a>';
                    }
                
                }
            }
            return $rows;
        }
        
        function is_column_allowed( $column_key )
        {
            $allowed_columns = $this->get_prop( 'allowed_columns' );
            if ( empty($allowed_columns) ) {
                return true;
            }
            $allowed = false;
            foreach ( $allowed_columns as $allowed_column ) {
                
                if ( strpos( $column_key, $allowed_column ) !== false ) {
                    $allowed = true;
                    break;
                }
            
            }
            return $allowed;
        }
        
        function __set( $name, $value )
        {
            $this->args[$name] = $value;
        }
        
        function __get( $name )
        {
            return $this->get_prop( $name );
        }
    
    }
}