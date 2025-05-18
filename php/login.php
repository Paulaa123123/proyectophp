<?php
session_start();
include "connect.php";

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $contrasena = mysqli_real_escape_string($conn, $_POST['contrasena']);

    $sql = "SELECT * FROM usuario WHERE email = '$email'";
    $result = $conn->query($sql);

    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();

        if (password_verify($contrasena, $user['contrasena'])) {
        
            $_SESSION['email'] = $email;
            $_SESSION['nombre'] = $user['nombre'];

            
            header("Location: dashboard.php");
            exit(); 

        } else {
            echo "Contraseña incorrecta.";
        }

    } else {
        echo "No existe un usuario con ese email.";
    }
}
?>
