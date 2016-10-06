<?php
$servername = "localhost";
$username = "root";
$password = "root";
$dbName = "mobilequizDB";

// Create connection
/*$conn = mysql_connect($servername, $username, $password);
if (!$conn) {
    die('Verbindung nicht möglich : ' . mysql_error());
}

// Check connection
$db_selected = mysql_select_db($dbName, $conn);
if (!$db_selected) {
    die ('Kann ' . $dbName . ' nicht benutzen : ' . mysql_error());
}*/
$dbh = new PDO("mysql:host=".$servername.";dbname=" . $dbName, $username, $password);
$dbh->exec("set names utf8");
?>