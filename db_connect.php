<?php
/* Database connection start */
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "cleaning_db";

// Create connection
$conn = mysqli_connect($servername, $username, $password, $dbname);

// Check connection
if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}
