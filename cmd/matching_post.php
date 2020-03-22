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

# WORDPRESS SETUP
####################################################################################################################

error_reporting(-1);
ini_set('display_errors', true);

define( 'WP_USE_THEMES', false );

require_once '../wp-load.php';

/** @var $wp WP */
global $wp;

/** @var $wpdb wpdb */
global $wpdb;


# Find the matching post for that tile
$matching_post = $conn->prepare("SELECT wpp.post_id FROM wp_postmeta wpp LEFT JOIN wp_posts p ON p.ID = wpp.post_id WHERE ( wpp.meta_key = '_yoast_wpseo_title' AND wpp.meta_value LIKE ? ) AND p.post_status = 'publish' LIMIT 1");

# Update the migration record
$update = $conn->prepare("UPDATE migration SET processed = ?, permalink = ? WHERE id = ?");

# Get all the objects in MIGRATION
$migration_posts = $conn->query("SELECT * FROM migration");

while($p = $migration_posts->fetch(\PDO::FETCH_OBJ))
{
    $matching_post->execute([$p->title]);
    if($matching_post->rowCount() > 0){
        $match = $matching_post->fetch(\PDO::FETCH_OBJ);
        echo "Matching post id ... " . $match->post_id . "\r\n";
        try{
            $update->execute([$match->post_id, get_the_permalink($match->post_id), $p->id]);
        }catch (\PDOException $p){
            echo "Exception ... " . $p->getMessage() . "\r\n";
        }
    }else{
        echo "Couldn't find post for ... " . $p->title . "\r\n";
    }
}