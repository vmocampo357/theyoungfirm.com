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

# try to create a 301 file
$file = fopen("301_redirects.txt", "w+");
chmod("301_redirects.txt", 0777);

# get all the posts that need a 301 thing
$migration_posts = $conn->query("SELECT * FROM migration WHERE processed IS NOT NULL");

while($p = $migration_posts->fetch(\PDO::FETCH_OBJ)){
    # get the old url
    $old_url = str_replace("http://www.theyoungfirm.com","",$p->url);

    echo "Processing ... " . $p->permalink . "\r\n";

    # get the new url
    $new_url = str_replace("https://tyf.jonesactlaw.com","",$p->permalink);

    # write this
    $write = sprintf("Redirect 301 \"%s\" \"%s\"\n", $old_url, $new_url);
    
    fwrite($file, $write);
}

echo "COMPLETE \r\n";