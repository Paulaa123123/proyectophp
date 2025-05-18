<?php
session_start();
include 'connect_tienda.php';

$mensaje = "";

if (isset($_GET['tabla']) && isset($_GET['id'])) {
    $tabla = $_GET['tabla'];
    $id = $_GET['id'];

    $resultTablas = $conn_tienda->query("SHOW TABLES");
    $tablasValidas = [];
    while ($row = $resultTablas->fetch_array()) {
        $tablasValidas[] = $row[0];
    }

    if (in_array($tabla, $tablasValidas)) {
        $stmt = $conn_tienda->prepare("DELETE FROM `$tabla` WHERE id = ?");
        $stmt->bind_param("i", $id);

        if ($stmt->execute()) {
            $mensaje = "Producto eliminado correctamente.";
        } else {
            $mensaje = "Error al eliminar: " . $stmt->error;
        }
        $stmt->close();
    } else {
        $mensaje = "Tabla inválida.";
    }
} else {
    $mensaje = "Datos incompletos para eliminar.";
}

header("Location: formulario.php?tabla=" . urlencode($_GET['tabla']) . "&mensaje=" . urlencode($mensaje));
exit;
?>
