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

    # Load up all the P tags first, we want to find the one with the date
    $p_tags = $dom->getElementsByTagName("p");

    # Get a datetime going
    $post_date = new \DateTime();

    /** @var DOMElement $tag */
    foreach($p_tags as $tag)
    {
        $content = $tag->nodeValue;
        if(strpos($content, 'By') !== false){
            // we want to remove this tag, but first, lets get the date out
            $date_arr = explode("on", $content);
            $new_date = strtotime($date_arr[1]);
            $post_date->setTimestamp($new_date);

            // ok, now remove the tag
            $tag->parentNode->removeChild($tag);
        }

        if($tag->getAttribute("class") == "postmetadata"){
            $tag->parentNode->removeChild($tag);
        }
    }

    # Remove the fieldset tag too
    $fieldset_tags = $dom->getElementsByTagName("fieldset");

    /** @var DOMElement $tag */
    foreach($fieldset_tags as $tag)
    {
        $tag->parentNode->removeChild($tag);
    }

    # Remove the reply thing
    $reply_tag = $dom->getElementById("respond");
    if($reply_tag){
        $reply_tag->parentNode->removeChild($reply_tag);
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