<?php
session_start();
include 'connect_tienda.php';
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
$producto = [];
$columnas = [];
$tabla = $_GET['tabla'] ?? '';
$id = $_GET['id'] ?? '';

if (!$tabla || !$id) {
    die("Parámetros incompletos.");
}

$tablas = [];
$resultTablas = $conn_tienda->query("SHOW TABLES");
while ($row = $resultTablas->fetch_array()) {
    $tablas[] = $row[0];
}
if (!in_array($tabla, $tablas)) {
    die("Tabla inválida.");
}

$resultCols = $conn_tienda->query("SHOW COLUMNS FROM `$tabla`");
while ($col = $resultCols->fetch_assoc()) {
    $columnas[] = $col;
}

$stmt = $conn_tienda->prepare("SELECT * FROM `$tabla` WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows === 1) {
    $producto = $result->fetch_assoc();
} else {
    die("Producto no encontrado.");
}
$stmt->close();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $campos = [];
    $valores = [];
    $tipos = "";

    foreach ($columnas as $col) {
        $campo = $col['Field'];
        if ($campo === 'id')
            continue;

        $valorRaw = $_POST[$campo] ?? '';
        if (preg_match('/int/i', $col['Type'])) {
            $tipos .= "i";
            $valor = intval($valorRaw);
        } elseif (preg_match('/float|double|decimal|real/i', $col['Type'])) {
            $tipos .= "d";
            $valor = floatval($valorRaw);
        } else {
            $tipos .= "s";
            $valor = trim($valorRaw);
        }
        $campos[] = "$campo = ?";
        $valores[] = $valor;
    }

    $sqlUpdate = "UPDATE `$tabla` SET " . implode(", ", $campos) . " WHERE id = ?";
    $stmt = $conn_tienda->prepare($sqlUpdate);

    if ($stmt === false) {
        $mensaje = "Error al preparar la consulta.";
    } else {
        $tipos .= "i";
        $valores[] = intval($id);
        $bind = [$tipos];
        foreach ($valores as &$v) {
            $bind[] = &$v;
        }
        call_user_func_array([$stmt, 'bind_param'], $bind);

        if ($stmt->execute()) {

            header("Location: formulario.php?tabla=" . urlencode($tabla));
            exit;
        } else {
            $mensaje = "Error al actualizar: " . $stmt->error;
        }
        $stmt->close();
    }
}
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Actualizar Producto</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.0.0/dist/css/bootstrap.min.css" />
</head>

<body>

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
        <h2>Actualizar Producto en <?php echo htmlspecialchars($tabla); ?></h2>
        <?php if ($mensaje): ?>
            <div class="alert alert-danger"><?php echo htmlspecialchars($mensaje); ?></div>
        <?php endif; ?>
        <form method="POST">
            <?php foreach ($columnas as $col):
                if ($col['Field'] === 'id')
                    continue;
                $campo = $col['Field'];
                $tipo = preg_match('/int|float|double|decimal|real/i', $col['Type']) ? 'number' : 'text';
                $step = preg_match('/float|double|decimal|real/i', $col['Type']) ? 'step="any"' : '';
                ?>
                <div class="form-group">
                    <label><?php echo ucfirst($campo); ?></label>
                    <input type="<?php echo $tipo; ?>" class="form-control" name="<?php echo $campo; ?>" required
                        value="<?php echo htmlspecialchars($producto[$campo]); ?>" <?php echo $step; ?>>
                </div>
            <?php endforeach; ?>
            <button type="submit" class="btn btn-primary">Guardar Cambios</button>
            <a href="formulario.php?tabla=<?php echo urlencode($tabla); ?>" class="btn btn-secondary">Volver</a>
        </form>
    </div>
</body>

</html>