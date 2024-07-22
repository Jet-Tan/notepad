<?php

$servername = "localhost";
$username = "dev_admin";
$password = "xyjPfqo%rDsJ^KJN";
$database = "dev_test";

$conn = mysqli_connect($servername, $username, $password, $database);

if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}
