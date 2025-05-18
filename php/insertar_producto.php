<?php
include "connect_tienda.php"; 

if ($_SERVER["REQUEST_METHOD"] == "POST") {
  
    $tabla = preg_replace('/[^a-zA-Z0-9_]/', '', $_POST['tabla']);
    $nombre = mysqli_real_escape_string($conn_tienda, $_POST['nombre']);
    $precio = floatval($_POST['precio']);
    $stock = intval($_POST['stock']);

    $sql = "INSERT INTO `$tabla` (nombre, precio, stock) VALUES ('$nombre', $precio, $stock)";
    
    if ($conn_tienda->query($sql) === TRUE) {
        echo "Producto insertado correctamente en la tabla <strong>$tabla</strong>.";
    } else {
        echo "Error al insertar el producto: " . $conn_tienda->error;
    }

    $conn_tienda->close();
}
?>
