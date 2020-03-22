<?php

ini_set('display_errors',true);
error_reporting(E_ERROR | E_PARSE);

# DATABASE
####################################################################################################################

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

    // display a message if connected to database successfully
    if($conn){
        echo "Connected to the <strong>$db</strong> database successfully! \r\n";
    }
}catch (PDOException $e){
    // report error message
    echo $e->getMessage();
    exit;
}

// Prepare a statement for inserting
$insert = $conn->prepare("INSERT INTO migration (title, description, keywords, url, links_inbound, links_outbound, html, summary, post_type) VALUES (?,?,?,?,?,?,?,?,?)");

# FILESYSTEM
####################################################################################################################

// This file will scan the CSV we have and try to pull information from each link
$csv_file_location = 'missing.csv';

// Open and read the CSV
$fh = fopen($csv_file_location,'r+');
$rows = [];

while($row = fgetcsv($fh)){
    $rows[] = $row;
}

# EXECUTION
####################################################################################################################

/*
 * 0 => Url of Pages Being Spidered
* 1 => Level Away from Home
* 2 => Status
* 3 => Internal Pages that link here
* 4 => Link text
* 5 => Internal links on that page
* 6 => External links on page
* 7 => Size of page
* 8 => Title Tag of Page
* 9 => Meta Description
* 10 => Meta Keywords
 */

// Wipe the table first
$conn->query("DELETE FROM migration");

foreach($rows as $page)
{
    if($page[0] == 'page'){
        continue;
    }
    # Get the post type, URL
    $post_type = $page[0];
    $url = $page[1];

    # We now need to figure out the title, keywords, descriptions, on our own
    # Get the HTML content
    $html = file_get_contents( $url );

    # Load it into a DOMDocument
    $dom = new DOMDocument();
    $dom->preserveWhiteSpace = false;

    if($dom){
        $dom->loadHTML( $html );

        # Load the Column Right tag (that's the only body tag we really want)
        $body = $dom->getElementById("column-right");

        if(!$body || !$body->ownerDocument){
            echo "Couldn't parse content for page ... " . $url . "\r\n";
            break;
        }

        # Need to grab the summary from the node, then remove it
        $summary = "";
        $summary_nodes = $body->getElementsByTagName("h1");
        if(!empty($summary_nodes)){
            $summary_node = $summary_nodes->item(0);
            $summary = $summary_node->nodeValue;
        }
        $body->removeChild($summary_node);

        # Extract just this node, and continue with insert
        $compiled = $body->ownerDocument->saveHTML($body);
        $compiled = str_replace("<div id=\"column-right\">","",$compiled);
        $compiled = substr($compiled, 0, -6);
        $compiled = str_replace("<div id=\"subhead\"></div>","",$compiled);

        echo "Processing page ... " . $url . "\r\n";
        
        $page_info = [
            'description' => '',
            'title' => '',
            'keywords' => '',
            'url' => $url,
            'html' => $compiled,
            'summary' => $summary
        ];
        
        # Load up all the meta-tags, and lets find the right ones
        $meta_tags = $dom->getElementsByTagName("meta");
        
        /** @var DOMElement $tag */
        foreach($meta_tags as $tag)
        {
            $attr_name = $tag->getAttribute('name');
            switch($attr_name){
                case('keywords'):
                    $page_info['keywords'] = $tag->getAttribute('content');
                    break;
                case('description'):
                    $page_info['description'] =  $tag->getAttribute('content');
                    break;
            }
        }

        # Load up the title tag
        $title_tags = $dom->getElementsByTagName("title");

        /** @var DOMElement $tag */
        foreach($title_tags as $tag)
        {
            $page_info['title'] = str_replace(' | www.theyoungfirm.com','',$tag->nodeValue);
        }

        # Insert what we've found so far
        try{
            $insert->execute([
                $page_info['title'],        //Title
                $page_info['description'],  //Description
                $page_info['keywords'],     //Keywords
                $url,                       //URL
                0,                          //inbound links,
                0,                          //outbound links
                $compiled,                  //html,
                $summary,                   //summary
                $post_type,                 // post type
            ]);

            echo "Page " . $page_info['title'] . " inserted successfully!";
        }catch (PDOException $e){
            echo "EXCEPTION: On page " . $page_info['title'] . " " . $e->getMessage() . "\r\n";
            echo $e->getTraceAsString();
            break;
        }
    }else{
        echo "Couldn't parse content for page ... " . $url . "\r\n";
    }
}