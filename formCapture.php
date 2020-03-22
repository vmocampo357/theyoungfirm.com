<?php
/**
 * Created by PhpStorm.
 * User: VICTORMAYORGA
 * Date: 8/22/2019
 * Time: 9:26 PM
 */

/*
 * Database setup (PDO, etc)
 *
 */

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

/*
 * Capture ANY request, save it as a JSON object to the database
 *
 */
$capture = [
    'request_type' => $_SERVER['REQUEST_METHOD'],
    'request_body' => $_REQUEST
];

$insertQuery = $conn->prepare("INSERT INTO captures (form,dateCreated) VALUES (?, NOW())");

try {
    $insertQuery->execute([json_encode($capture)]);
} catch (\PDOException $e){
    //DO NOTHING
}

/*
 * Re-direct to the TYF homepage
 *
 */
header('Location: http://theyoungfirm.com/');