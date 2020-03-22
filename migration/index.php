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
        echo "Connected to the <strong>$db</strong> database successfully! \r\n <br />";
    }
}catch (PDOException $e){
    // report error message
    echo $e->getMessage();
    exit;
}

// Prepare a statement for inserting
$insert = $conn->prepare("INSERT INTO migration (title, description, keywords, url, links_inbound, links_outbound, html, summary) VALUES (?,?,?,?,?,?,?,?)");

# FILESYSTEM
####################################################################################################################

// This file will scan the CSV we have and try to pull information from each link
$csv_file_location = 'export.csv';

// Open and read the CSV
$fh = fopen($csv_file_location,'r+');
$rows = [];

while($row = fgetcsv($fh)){
    $rows[] = $row;
}

// Remove the first, use for titles
$titles = array_shift($rows);

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
    // Check for status 200 only, and not the origin page
    if($page[2] == 200){

        # Get the HTML content
        $html = file_get_contents( $page[0] );

        # Load it into a DOMDocument
        $dom = new DOMDocument();
        $dom->preserveWhiteSpace = false;

        if($dom){
            $dom->loadHTML( $html );

            # Load the Column Right tag (that's the only body tag we really want)
            $body = $dom->getElementById("column-right");

            if(!$body || !$body->ownerDocument){
                echo "Couldn't parse content for page ... " . $page[8] . "\r\n <br />";
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

            # Insert what we've found so far
            try{
                $insert->execute([
                    $page[8],   //Title
                    $page[9],   //Description
                    $page[10],  //Keywords
                    $page[0],   //URL
                    $page[3],   //inbound links,
                    $page[4],   //outbound links
                    $compiled,  //html,
                    $summary,   //summary
                ]);

                echo "Page " . $page[8] . " inserted successfully with content " . $page[0] . "\r\n <br />";
            }catch (PDOException $e){
                echo "EXCEPTION: On page " . $page[8] . " " . $e->getMessage() . "\r\n <br />";
                echo $e->getTraceAsString();
                break;
            }
        }else{
            echo "Couldn't parse content for page ... " . $page[8] . "\r\n <br />";
        }
    }
}