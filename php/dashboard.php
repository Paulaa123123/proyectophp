<?php

session_start();

include 'connect.php';

$nombre = "Invitado";
if (isset($_SESSION['email'])) {
    $email = $_SESSION['email'];
    $stmt = $conn->prepare("SELECT nombre FROM usuario WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $stmt->bind_result($nombreUsuario);
    if ($stmt->fetch()) {
        $nombre = $nombreUsuario;
    }
    $stmt->close();
}


$mensaje = "";

try {
  
    $pdo = new PDO("mysql:host=localhost", "root", "", [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    ]);
} catch (PDOException $e) {
    die("Conexión fallida: " . $e->getMessage());
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['crear_tienda'])) {
       
        $stmt = $pdo->query("SHOW DATABASES LIKE 'tiendaphp'");
        if ($stmt->rowCount() > 0) {
            $mensaje = "<div class='alert alert-warning'>La base de datos 'tiendaphp' ya existe.</div>";
        } else {
           
            $pdo->exec("CREATE DATABASE tiendaphp");
            $mensaje = "<div class='alert alert-success'>Base de datos 'tiendaphp' creada correctamente.</div>";
        }
    } elseif (isset($_POST['borrar_tienda'])) {
      
        $stmt = $pdo->query("SHOW DATABASES LIKE 'tiendaphp'");
        if ($stmt->rowCount() > 0) {
            $pdo->exec("DROP DATABASE tiendaphp");
            $mensaje = "<div class='alert alert-success'>Base de datos 'tiendaphp' eliminada correctamente.</div>";
        } else {
            $mensaje = "<div class='alert alert-warning'>La base de datos 'tiendaphp' no existe o ya fue eliminada.</div>";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Dashboard - TiendaPHP</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css">
</head>

<body class="bg-light">

    <header>
        <nav class="navbar navbar-expand-lg navbar-light bg-light">
            <p class="navbar-brand text-dark fw-bold mb-0">Mi Tienda</p>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav">
                    <li class="nav-item"><a class="nav-link" href="dashboard.php">Tienda</a></li>
                    <li class="nav-item"><a class="nav-link" href="formulariolinea.php">Línea de Productos</a></li>
                    <li class="nav-item"><a class="nav-link" href="formulario.php">Productos</a></li>
                </ul>
                <span class="navbar-text ml-auto">
                    Hola, <?php echo htmlspecialchars($nombre); ?>
                    <a href="logout.php" class="btn btn-outline-danger btn-sm ml-3">Logout</a>
                </span>
            </div>
        </nav>
    </header>


    <div class="container mt-5">
        <h1 class="mb-4">TiendaPHP</h1>

        <?php echo $mensaje; ?>

        <div class="mb-4">
            <form method="POST" class="d-inline">
                <input class="btn btn-primary" type="submit" name="crear_tienda" value="Crear Tienda">
            </form>

            <form method="POST" class="d-inline">
                <input class="btn btn-danger" type="submit" name="borrar_tienda" value="Borrar Tienda">
            </form>
        </div>

        <div class="mb-4">
            <a class="btn btn-primary" href="formulariolinea.php" role="button">Crear Línea de Productos</a>
            <a class="btn btn-primary" href="formulario.php" role="button">Crear Producto</a>
        </div>

        <hr>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>