<?php
$host = "localhost";
$usuario = "root";
$contrasena = "";
$basedatos = "tiendaphp";

try {
    $conn_tienda = @new mysqli($host, $usuario, $contrasena, $basedatos);

    if ($conn_tienda->connect_error) {
        throw new Exception("Error de conexión a la base de datos 'tiendaphp': " . $conn_tienda->connect_error);
    }
} catch (Exception $e) {

    die("<div style='padding:20px; background-color:#f8d7da; color:#721c24; border:1px solid #f5c6cb; border-radius:5px;'>
            <strong>Error:</strong> " . $e->getMessage() . "<br>
            Por favor, asegúrate de que la base de datos <code>tiendaphp</code> existe.
        </div>");
}
?>
