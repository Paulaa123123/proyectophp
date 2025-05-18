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

$tablas = [];
$resultTablas = $conn_tienda->query("SHOW TABLES");
while ($row = $resultTablas->fetch_array()) {
    $tablas[] = $row[0];
}

$tablaSeleccionada = $_GET['tabla'] ?? ($tablas[0] ?? null);

$mensaje = "";
$columnasTabla = [];
$nuevoID = 1;

if ($tablaSeleccionada && in_array($tablaSeleccionada, $tablas)) {
    $resultCols = $conn_tienda->query("SHOW COLUMNS FROM `$tablaSeleccionada`");
    while ($col = $resultCols->fetch_assoc()) {
        $columnasTabla[] = $col;
    }

    $resultMaxId = $conn_tienda->query("SELECT MAX(id) as max_id FROM `$tablaSeleccionada`");
    if ($resultMaxId) {
        $rowMaxId = $resultMaxId->fetch_assoc();
        $nuevoID = ($rowMaxId['max_id'] ?? 0) + 1;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $tablaPost = $_POST['tabla'] ?? '';
    if (!in_array($tablaPost, $tablas)) {
        $mensaje = "<div class='alert alert-danger'> Tabla inválida seleccionada.</div>";
    } else {
        $tablaSeleccionada = $tablaPost;

        $columnasParaInsert = [];
        $valores = [];
        $tipos = [];

        $resultCols = $conn_tienda->query("SHOW COLUMNS FROM `$tablaSeleccionada`");
        while ($col = $resultCols->fetch_assoc()) {
            $columnasParaInsert[] = $col;
        }

        $campos = [];
        $placeholders = [];
        $datosParaCookie = [];

        foreach ($columnasParaInsert as $col) {
            $nombreCol = $col['Field'];
            $tipoCol = $col['Type'];
            $valorRaw = $_POST[$nombreCol] ?? null;

            $campos[] = $nombreCol;
            $placeholders[] = "?";

            if (preg_match('/int/i', $tipoCol)) {
                $tipos[] = "i";
                $valor = intval($valorRaw);
            } elseif (preg_match('/float|double|decimal|real/i', $tipoCol)) {
                $tipos[] = "d";
                $valor = floatval($valorRaw);
            } else {
                $tipos[] = "s";
                $valor = trim($valorRaw);
            }

            $valores[] = $valor;

            if ($nombreCol !== 'id') {
                $datosParaCookie[$nombreCol] = $valor;
            }
        }

        $validacionFallida = false;
        foreach ($valores as $v) {
            if ($v === null || $v === '') {
                $validacionFallida = true;
                break;
            }
        }

        if ($validacionFallida) {
            $mensaje = "<div class='alert alert-danger'>Por favor, rellena todos los campos.</div>";
        } else {
            $sqlInsert = "INSERT INTO `$tablaSeleccionada` (" . implode(", ", $campos) . ") VALUES (" . implode(", ", $placeholders) . ")";
            $stmtProd = $conn_tienda->prepare($sqlInsert);

            if ($stmtProd === false) {
                $mensaje = "<div class='alert alert-danger'>Error en la consulta: " . htmlspecialchars($conn_tienda->error) . "</div>";
            } else {
                $bind_names[] = implode('', $tipos);
                for ($i = 0; $i < count($valores); $i++) {
                    $bind_names[] = &$valores[$i];
                }

                call_user_func_array([$stmtProd, 'bind_param'], $bind_names);

                if ($stmtProd->execute()) {
                    
                    setcookie("ultimoProducto", json_encode($datosParaCookie), time() + 600, "/");

                    header("Location: ?tabla=" . urlencode($tablaSeleccionada));
                    exit;
                } else {
                    $mensaje = "<div class='alert alert-danger'>Error al guardar el producto: " . htmlspecialchars($stmtProd->error) . "</div>";
                }

                $stmtProd->close();
            }
        }
    }
}

$buscarNombre = $_GET['buscar_nombre'] ?? '';

$productos = [];
if ($tablaSeleccionada && in_array($tablaSeleccionada, $tablas)) {
    if ($buscarNombre !== '') {
        $stmtBuscar = $conn_tienda->prepare("SELECT * FROM `$tablaSeleccionada` WHERE nombre LIKE ?");
        $likeNombre = "%" . $buscarNombre . "%";
        $stmtBuscar->bind_param("s", $likeNombre);
        $stmtBuscar->execute();
        $resultadoBuscar = $stmtBuscar->get_result();
        while ($prod = $resultadoBuscar->fetch_assoc()) {
            $productos[] = $prod;
        }
        $stmtBuscar->close();
    } else {
        $sqlProd = "SELECT * FROM `$tablaSeleccionada`";
        $resultProd = $conn_tienda->query($sqlProd);
        if ($resultProd) {
            while ($prod = $resultProd->fetch_assoc()) {
                $productos[] = $prod;
            }
        }
    }
}

$valoresCookie = [];
if (isset($_COOKIE['ultimoProducto'])) {
    $valoresCookie = json_decode($_COOKIE['ultimoProducto'], true) ?? [];
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />
    <title>Agregar Producto - Mi Tienda</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.0.0/dist/css/bootstrap.min.css" />
    <script>
        function cambiarTabla(select) {
            const tabla = select.value;
            window.location.href = "?tabla=" + encodeURIComponent(tabla);
        }
    </script>
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
    <h2 class="mb-4">Agregar Producto</h2>
    <?php echo $mensaje; ?>

    <form method="POST" class="mb-4">
        <div class="form-group">
            <label>Selecciona la tabla</label>
            <select name="tabla" class="form-control" onchange="cambiarTabla(this)">
                <?php foreach ($tablas as $tabla): ?>
                    <option value="<?php echo htmlspecialchars($tabla); ?>" <?php if ($tabla == $tablaSeleccionada) echo 'selected'; ?>>
                        <?php echo htmlspecialchars($tabla); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <?php foreach ($columnasTabla as $col):
            $campo = $col['Field'];
            $tipo = $col['Type'];
            $inputType = (preg_match('/int|float|double|decimal|real/', $tipo)) ? 'number' : 'text';
            $step = (preg_match('/float|double|decimal|real/', $tipo)) ? 'step="any"' : '';

            $readonly = '';
            $valor = '';

            if ($campo === 'id') {
                $readonly = 'readonly';
                $valor = 'value="' . $nuevoID . '"';
            } elseif (isset($valoresCookie[$campo])) {
                $valor = 'value="' . htmlspecialchars($valoresCookie[$campo]) . '"';
            }
            ?>
            <div class="form-group">
                <label><?php echo ucfirst(htmlspecialchars($campo)); ?></label>
                <input
                    type="<?php echo $inputType; ?>"
                    class="form-control"
                    name="<?php echo htmlspecialchars($campo); ?>"
                    autocomplete="off"
                    <?php echo $step . ' ' . $readonly . ' ' . $valor; ?>
                />
            </div>
        <?php endforeach; ?>

        <button type="submit" class="btn btn-success">Guardar Producto</button>
    </form>

    <?php if ($tablaSeleccionada): ?>
        <hr />
        <h3>Productos en la tabla <b><?php echo htmlspecialchars($tablaSeleccionada); ?></b>:</h3>

        <form method="GET" class="form-inline mb-3">
            <input type="hidden" name="tabla" value="<?php echo htmlspecialchars($tablaSeleccionada); ?>" />
            <div class="form-group mr-2">
                <input
                    type="text"
                    name="buscar_nombre"
                    class="form-control"
                    placeholder="Buscar por nombre"
                    value="<?php echo htmlspecialchars($buscarNombre); ?>"
                />
            </div>
            <button type="submit" class="btn btn-primary">Buscar</button>
            <?php if ($buscarNombre !== ''): ?>
                <a href="?tabla=<?php echo htmlspecialchars($tablaSeleccionada); ?>" class="btn btn-secondary ml-2">Limpiar</a>
            <?php endif; ?>
        </form>

        <?php if (count($productos) > 0): ?>
            <div class="table-responsive">
                <table class="table table-hover table-dark mt-3">
                    <thead>
                    <tr>
                        <?php foreach (array_keys($productos[0]) as $columna): ?>
                            <th><?php echo htmlspecialchars(ucfirst($columna)); ?></th>
                        <?php endforeach; ?>
                        <th>Operaciones</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($productos as $producto): ?>
                        <tr>
                            <?php foreach ($producto as $valor): ?>
                                <td><?php echo htmlspecialchars($valor); ?></td>
                            <?php endforeach; ?>
                            <td>
                                <?php
                                $id = urlencode($producto['id'] ?? '');
                                $tabla = urlencode($tablaSeleccionada);
                                if ($id !== ''):
                                    ?>
                                    <a href="actualizar.php?tabla=<?php echo $tabla; ?>&id=<?php echo $id; ?>"
                                       class="btn btn-sm btn-primary mr-1">Actualizar</a>
                                    <a href="eliminar.php?tabla=<?php echo $tabla; ?>&id=<?php echo $id; ?>"
                                       class="btn btn-sm btn-danger"
                                       onclick="return confirm('¿Seguro que quieres eliminar este producto?');">Eliminar</a>
                                <?php else: ?>
                                    <em>No disponible</em>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <p>No hay productos en esta tabla<?php echo $buscarNombre ? " que coincidan con '" . htmlspecialchars($buscarNombre) . "'" : ""; ?>.</p>
        <?php endif; ?>
    <?php endif; ?>
</div>
</body>
</html>
