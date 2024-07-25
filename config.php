<?php

$servername = "localhost";
$username = "rion_admin";
$password = "t5Bpcg^3qLBC2E#9";
$database = "rion_rionotes";

$conn = mysqli_connect($servername, $username, $password, $database);

if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}
