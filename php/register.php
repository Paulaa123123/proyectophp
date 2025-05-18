<?php
include "connect.php";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $nombre = mysqli_real_escape_string($conn, $_POST['nombre']);
    $apellidos = mysqli_real_escape_string($conn, $_POST['apellidos']);
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $contrasena = mysqli_real_escape_string($conn, $_POST['contrasena']);

    $checkEmail = "SELECT * FROM usuario WHERE email='$email'";
    $result = $conn->query($checkEmail);

    if ($result->num_rows > 0) {
        echo "El email que has introducido ya está asociado a otra cuenta.";
    } else {
        $hashed_password = password_hash($contrasena, PASSWORD_BCRYPT);
        $sql = "INSERT INTO usuario(nombre, apellidos, email, contrasena) 
                VALUES ('$nombre', '$apellidos', '$email', '$hashed_password')";

        if ($conn->query($sql) === TRUE) {
            echo "<script>alert('Cuenta creada correctamente.');
            window.location.href = 'index.html'; </script>";
        } else {
            echo "Error al crear la cuenta: " . $conn->error;
        }
    }

    $conn->close();
}
?>
