<?php

# WORDPRESS SETUP
####################################################################################################################

error_reporting(-1);
ini_set('display_errors', true);

define( 'WP_USE_THEMES', false );

require_once '../../wp-load.php';

/** @var $wp WP */
global $wp;

/** @var $wpdb wpdb */
global $wpdb;

# RESULTS
####################################################################################################################

$raw_posts = $wpdb->get_results("SELECT * FROM migration WHERE processed IS NULL");

foreach($raw_posts as $raw)
{
    # This will be useful, because we will update its status (processed, etc.)
    $id = $raw->id;

    # We need to get a POST title first
    $candidate = str_replace("http://www.theyoungfirm.com/","",$raw->url);
    $options = explode("/", $candidate);

    // if($options[0] == "html"){

        # The second part is the link we can use sorta
        $potential = str_replace(".html","",$options[1]);
        $final_title = ucwords(str_replace("-"," ",$potential));

        # Start by putting together a POST for insert
        $post_arr = [
            'post_title'    => wp_strip_all_tags( $final_title ),
            'post_content'  => $raw->html,
            'post_status'   => 'publish',
            'post_author'   => 1,
            'post_type'     => ($raw->post_type == 'blog') ? 'post' : 'page'
        ];

        # Insert, and get the POST ID
        $post_id = wp_insert_post( $post_arr );

        # If we have a post_id, start adding meta
        if( $post_id > 0 ){

            # Add YOAST metadata
            update_post_meta( $post_id, '_yoast_wpseo_title', $raw->title );
            update_post_meta( $post_id, '_yoast_wpseo_focuskw', str_replace(","," ",$raw->keywords) );
            update_post_meta( $post_id, '_yoast_wpseo_metadesc', $raw->description );

            if($raw->post_type == 'page'){
                update_post_meta( $post_id, 'page_summary', $raw->summary );    
            }

            # Continue to update processing piece now
            $wpdb->update('migration', ['processed' => $post_id, 'permalink' => get_the_permalink($post_id)], ['id' => $id]);

            echo "Inserted page succesfully! " . $raw->url . "<br />";
        }else{
            echo "Could not insert this page " . $raw->url . "<br />";
            continue;
        }
    /*}else{
        echo "Will not auto insert homepage or otherwise ... <br />";
    }*/
}