<?php

ini_set('display_errors',true);
error_reporting(E_ERROR | E_PARSE);
// error_reporting(-1);

# DATABASE
########################################################################################################################

// Make a connection to PDO

$username   = "jonesact";
# -----------------------------------
$password   = "Joneslaw2017!";
# -----------------------------------
$host       = "localhost";
# -----------------------------------
$db         = "tyfsite_dev";
# -----------------------------------

$dsn= "mysql:host=$host;dbname=$db";

try{
    // create a PDO connection with the configuration data
    $conn = new PDO($dsn, $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
}catch (PDOException $e){
    // report error message
    echo $e->getMessage();
    exit;
}

$insert = $conn->prepare("
    INSERT INTO migration (
        title,
        description,
        keywords,
        url,
        html,
        summary,
        post_type,
        date_published
    )
    VALUES (?,?,?,?,?,?,?,?)
");

# FILESYSTEM
####################################################################################################################

// This file will scan the CSV we have and try to pull information from each link
$text_file = "links.txt";

// Open and read the CSV
$fh = fopen($text_file,'r+');
$rows = [];

while(!feof($fh))
{
    $rows[] = fgets($fh);
}

# EXECUTION
####################################################################################################################

$conn->query("DELETE FROM migration");

foreach($rows as $url)
{
    # Cleanup URL
    $url = str_replace(" ","",$url);
    $url = str_replace("\n","",$url);
    $url = str_replace("\r","",$url);

    /*
     * REMOTE DOWNLOAD HTML
     *
     * This will download the HTML content from the server (File_get_contents)
     */
    $html = file_get_contents( $url );
    $dom = new DOMDocument();
    $dom->preserveWhiteSpace = false;

    /*
     * DOM DOCUMENT SETUP
     *
     * Create a DOM Document Object that we'll use to navigate
     */
    if( $dom->loadHTML( $html ) ){

        # Preparing insert stuff
        $today = new \DateTime();
        $page_info = [
            'title' => '', // done
            'description' => '', // done
            'keywords' => '', // done
            'url' => $url,
            'html' => '',
            'summary' => '',
            'post_type' => 'page',
            'publish_date' => $today->format("Y-m-d H:i:s")
        ];

        /**
         * POST TYPE DETERMINATION
         */
        if(strpos($url,"/blog") !== false){
            $page_info['post_type'] = 'post';
        }

        /*
         * METADATA: TITLE EXTRACTION
         *
         * Grab the title from the DOM document
         */
        $title_tag = "The Young Firm";

        if( $dom->getElementsByTagName("title")->length > 0){
            $title_tag = $dom->getElementsByTagName("title")->item(0);
            $title_tag = str_replace(" | www.theyoungfirm.com","",$title_tag->nodeValue);
            $page_info['title'] = clean($title_tag);
        }

        /*
         * METADATA: DESCRIPTION, KEYWORDS EXTRACTION
         *
         * Grab the description from the DOM document
         */
        $description = "";

        if($dom->getElementsByTagName("meta")->length > 0){
            /** @var DOMElement $tag */
            foreach($dom->getElementsByTagName("meta") as $tag){
                if($tag->getAttribute("name") == "description"){
                    $page_info['description'] = clean($tag->getAttribute("content"));
                }
                if($tag->getAttribute("name") == "keywords"){
                    $page_info['keywords'] = clean($tag->getAttribute("content"));
                }
            }
        }

        /*
         * CONTENT: SUMMARY EXTRACTION
         *
         * This will grab the 'summary' -- which is basically the h1 on the page
         */
        $body = $dom->getElementById("column-right");

        $summary = "";
        $summary_nodes = $body->getElementsByTagName("h1");
        if(!empty($summary_nodes)){
            $summary_node = $summary_nodes->item(0);
            $summary = $summary_node->nodeValue;
            $body->removeChild($summary_node);
        }
        $page_info['summary'] = $summary;

        /*
         * BLOG: DATETIME EXTRACTION
         *
         * This will get us the publish date for the post
         */
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

            if($tag->getAttribute("class") == "back-to-top"){
                $tag->parentNode->removeChild($tag);
            }
        }

        $page_info['publish_date'] = $post_date->format('Y-m-d H:i:s');

        /*
         * BLOG: OTHER REMOVAL
         *
         * Remove other junk tags from the body
         */

        # Remove the fieldset tag
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

        /*
         * BLOG: LIST REMOVAL
         *
         * Remove the weird lis that show up at the bottom of the page
         */
        $ul_tags = $dom->getElementsByTagName("ul");

        /** @var DOMElement $ul_tag */
        foreach($ul_tags as $ul_tag)
        {
            if($ul_tag->getAttribute("class") == "wp_navigation"){
                $ul_tag->parentNode->removeChild($ul_tag);
            }
        }

        /*
         * CONTENT: BODY EXTRACTION
         *
         * This will extract the main body html for us
         */
        $compiled = $body->ownerDocument->saveHTML($body);
        $compiled = str_replace("<div id=\"column-right\">","",$compiled);
        $compiled = substr($compiled, 0, -6);
        $compiled = str_replace("<div id=\"subhead\"></div>","",$compiled);
        $compiled = clean($compiled);
        $page_info['html'] = $compiled;

        /*
         * PDO INSERTION
         *
         * This will try to insert the stuff
         */
        try{
            $insert->execute([
                $page_info['title'],
                $page_info['description'],
                $page_info['keywords'],
                $url,
                $page_info['html'],
                $page_info['summary'],
                $page_info['post_type'],
                $page_info['publish_date']
            ]);
        }catch (\PDOException $e){
            echo "There was an issue inserting \n";
            echo $e->getMessage() . "\n";
        }

    }else{
        echo "Could not load HTML from remote source: " . $url . " \n";
    }
}

/**
 * @param $str
 * @return string
 *
 * This function will return a clean, DATABASE ready version of whatever
 */
function clean($str)
{
    return mb_convert_encoding($str,"HTML-ENTITIES","UTF-8");
}