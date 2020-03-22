<?php

// ini_set('display_errors',true);
// error_reporting(E_ERROR);

# WORDPRESS SETUP
####################################################################################################################

define( 'WP_USE_THEMES', false );

# require_once '../../wp-load.php';

/** @var $wp WP */
# global $wp;

/** @var $wpdb wpdb */
# global $wpdb;

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
// $insert = $conn->prepare("INSERT INTO redirects (legacy_url, permalink, post_id, title_used) VALUES (?,?,?,?)");
$search = $conn->prepare("SELECT post_id from wp_postmeta WHERE (meta_key = '_yoast_wpseo_title' AND meta_value LIKE ?) LIMIT 1");

# FILESYSTEM
####################################################################################################################

// This file will scan the CSV we have and try to pull information from each link
$text_file = "crawl-urls.txt";

// Open and read the CSV
$fh = fopen($text_file,'r+');
$rows = [];

while(!feof($fh))
{
    $rows[] = fgets($fh);
}

# EXECUTION
####################################################################################################################

foreach($rows as $url)
{
    $url = str_replace(" ","",$url);
    $url = str_replace("\n","",$url);
    $url = str_replace("\r","",$url);

    # Get the HTML content
    $html = file_get_contents( $url );

    # Load it into a DOMDocument
    $dom = new DOMDocument();
    $dom->preserveWhiteSpace = false;

    echo "Fetching URL ... " . $url . "\n";

    if($dom) {
        $dom->loadHTML($html);

        $title_tag = $dom->getElementsByTagName("title")->item(0);

        echo "Title would be ... " . $title_tag->nodeValue . "\n\n";

        $search->execute(["%".$title_tag->nodeValue."%"]);
        // $metas = $wpdb->get_results("SELECT post_id from wp_postmeta WHERE (meta_key = '_yoast_wpseo_title' AND meta_value LIKE '%".$title_tag->nodeValue."%') LIMIT 1");

        if(count($metas) > 0){
            $row = $search->fetch(\PDO::FETCH_OBJ);
            // $row = $metas[0];

            echo "Matching post [".$row->post_id."] found ... updating table ... \n";

            // $post = get_post($row['post_id']);

            // var_dump(get_the_permalink($row['post_id']));

            echo "\n";
        }else{
            echo "Could not find a matching post! \n";
        }
    }
    
}