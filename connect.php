<?php
$host = "localhost";    
$user = "root";           
$pass = "";               
$db   = "ccgc_lms";       

$conn = new mysqli($host, $user, $pass, $db);


try {
    $pdo = new PDO("mysql:host=$host;dbname=$db", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}
?>
