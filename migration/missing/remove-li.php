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

$raw_posts = $wpdb->get_results("SELECT ID,post_content,post_date FROM wp_posts WHERE post_type = 'post' AND post_status = 'publish'");

foreach($raw_posts as $raw)
{
    # Load it into a DOMDocument
    $dom = new DOMDocument();
    $dom->preserveWhiteSpace = false;

    # Load the content
    $dom->loadHTML( $raw->post_content );

    # Get a datetime going
    $post_date = new \DateTime();

    # Remove the weird LIs
    /*
     * <ul class="wp_navigation"></ul>
     */
    $ul_tags = $dom->getElementsByTagName("ul");

    /** @var DOMElement $ul_tag */
    foreach($ul_tags as $ul_tag)
    {
        if($ul_tag->getAttribute("class") == "wp_navigation"){
            $ul_tag->parentNode->removeChild($ul_tag);
        }
    }

    $newHtml = $dom->saveHTML($dom);

    # Clean up this garbage shit
    $newHtml = str_replace("<!DOCTYPE html PUBLIC \"-//W3C//DTD HTML 4.0 Transitional//EN\" \"http://www.w3.org/TR/REC-html40/loose.dtd\">","",$newHtml);
    $newHtml = str_replace("<html><body>","",$newHtml);
    $newHtml = str_replace("</body></html>","",$newHtml);

    $wpdb->update("wp_posts", [
        'post_content' => $newHtml,
        'post_date' => $post_date->format("Y-m-d H:i:s")
    ], ['ID' => $raw->ID]);

    echo "POST ID updated ... " . $raw->ID . "\n\n";
}