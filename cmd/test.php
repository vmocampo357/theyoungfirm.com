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

$select = $conn->query("SELECT * FROM migration");

?>

<!doctype html>
<html class="no-js" lang="">

<head>
    <meta charset="utf-8">
    <title>TESTING LOADED CONTENT</title>
    <meta name="description" content="">
    <meta name="viewport" content="width=device-width, initial-scale=1">
</head>

<body>

    <?php while($row = $select->fetch(\PDO::FETCH_OBJ)): ?>

        <h1><?php echo $row->url; ?></h1>

        <hr />

        <p><?php echo $row->summary; ?></p>

        <?php echo $row->html; ?>

        <hr />

    <?php endwhile; ?>

</body>

</html>