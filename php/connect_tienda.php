<?php
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "tiendaphp";

$conn_tienda = new mysqli($servername, $username, $password, $dbname);

if ($conn_tienda->connect_error) {
    die("Conexión a TIENDA fallida: " . $conn_tienda->connect_error);
}

?>
