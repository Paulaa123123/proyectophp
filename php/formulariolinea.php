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

include 'connect_tienda.php';

$mensaje = "";

if (isset($_GET['delete_table'])) {
    $tablaEliminar = $_GET['delete_table'];


    if (preg_match('/^[a-zA-Z_][a-zA-Z0-9_]*$/', $tablaEliminar)) {

        $sqlDelete = "DROP TABLE IF EXISTS `$tablaEliminar`";
        if ($conn_tienda->query($sqlDelete) === TRUE) {
            $mensaje = "<div class='alert alert-success'>Tabla '$tablaEliminar' eliminada correctamente.</div>";
        } else {
            $mensaje = "<div class='alert alert-danger'>Error al eliminar tabla '$tablaEliminar': " . htmlspecialchars($conn_tienda->error) . "</div>";
        }
    } else {
        $mensaje = "<div class='alert alert-danger'>Nombre de tabla no válido para eliminar.</div>";
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombreTabla = trim($_POST['nombre_tabla'] ?? '');
    $columnas = $_POST['columnas'] ?? [];

    if ($nombreTabla === '') {
        $mensaje = "<div class='alert alert-danger'>Debes poner un nombre para la tabla.</div>";
    } elseif (empty($columnas)) {
        $mensaje = "<div class='alert alert-danger'>Debes definir al menos una columna.</div>";
    } else {

        if (!preg_match('/^[a-zA-Z_][a-zA-Z0-9_]*$/', $nombreTabla)) {
            $mensaje = "<div class='alert alert-danger'>Nombre de tabla no válido.</div>";
        } else {

            $result = $conn_tienda->query("SHOW TABLES LIKE '$nombreTabla'");
            if ($result && $result->num_rows > 0) {
                $mensaje = "<div class='alert alert-danger'>La tabla '$nombreTabla' ya existe.</div>";
            } else {
                $colsSql = [];
                $hayError = false;

                foreach ($columnas as $col) {
                    $colName = trim($col['nombre'] ?? '');
                    $colTipo = $col['tipo'] ?? '';
                    $colLong = trim($col['longitud'] ?? '');

                    if ($colName === '' || $colTipo === '') {
                        $mensaje = "<div class='alert alert-danger'>Cada columna debe tener nombre y tipo.</div>";
                        $hayError = true;
                        break;
                    }

                    if (!preg_match('/^[a-zA-Z_][a-zA-Z0-9_]*$/', $colName)) {
                        $mensaje = "<div class='alert alert-danger'>Nombre de columna '$colName' no válido.</div>";
                        $hayError = true;
                        break;
                    }

                    if (in_array($colTipo, ['VARCHAR', 'CHAR'])) {
                        if (!is_numeric($colLong) || intval($colLong) <= 0) {
                            $mensaje = "<div class='alert alert-danger'>Longitud inválida para la columna '$colName'.</div>";
                            $hayError = true;
                            break;
                        }
                        $colsSql[] = "`$colName` $colTipo($colLong)";
                    } elseif (in_array($colTipo, ['INT', 'FLOAT', 'DOUBLE', 'DATE', 'TEXT'])) {
                        $colsSql[] = "`$colName` $colTipo";
                    } else {
                        $mensaje = "<div class='alert alert-danger'>Tipo no soportado para la columna '$colName'.</div>";
                        $hayError = true;
                        break;
                    }
                }

                if (!$hayError) {

                    $colsSql[0] .= " PRIMARY KEY";

                    $sql = "CREATE TABLE `$nombreTabla` (" . implode(", ", $colsSql) . ") ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

                    if ($conn_tienda->query($sql) === TRUE) {
                        $mensaje = "<div class='alert alert-success'>Tabla '$nombreTabla' creada correctamente.</div>";
                    } else {
                        $mensaje = "<div class='alert alert-danger'>Error al crear tabla: " . htmlspecialchars($conn_tienda->error) . "</div>";
                    }
                }
            }
        }
    }
}

$tablas = [];
$resTablas = $conn_tienda->query("SHOW TABLES");
if ($resTablas) {
    while ($row = $resTablas->fetch_array()) {
        $tablas[] = $row[0];
    }
}
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8" />
    <title>Crear / Eliminar Tablas</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.0.0/dist/css/bootstrap.min.css" />
    <style>
        .columna-row {
            margin-bottom: 10px;
        }

        .tabla-existente {
            margin-bottom: 20px;
        }
    </style>
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
        <h2>Tablas Existentes</h2>
        <?php if (count($tablas) === 0): ?>
            <p>No hay tablas creadas aún.</p>
        <?php else: ?>
            <table class="table table-bordered tabla-existente">
                <thead>
                    <tr>
                        <th>Nombre tabla</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($tablas as $tabla): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($tabla); ?></td>
                            <td>
                                <a href="?delete_table=<?php echo urlencode($tabla); ?>"
                                    onclick="return confirm('¿Seguro que quieres eliminar la tabla <?php echo htmlspecialchars($tabla); ?>? Esta acción es irreversible.');"
                                    class="btn btn-danger btn-sm">Eliminar</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>

        <hr />

        <h2>Crear Tabla Nueva</h2>

        <?php echo $mensaje; ?>

        <form method="POST" id="formTabla">
            <div class="form-group">
                <label>Nombre de la tabla</label>
                <input type="text" name="nombre_tabla" class="form-control" required pattern="[a-zA-Z_][a-zA-Z0-9_]*"
                    title="Solo letras, números y guiones bajos, no puede empezar por número" />
            </div>

            <h4>Columnas</h4>
            <div id="columnas-container">

                <div class="row columna-row">
                    <div class="col-md-5">
                        <input type="text" value="id" class="form-control" readonly />
                        <input type="hidden" name="columnas[0][nombre]" value="id" />
                    </div>
                    <div class="col-md-4">
                        <select class="form-control" disabled>
                            <option selected>INT</option>
                        </select>
                        <input type="hidden" name="columnas[0][tipo]" value="INT" />
                    </div>
                    <div class="col-md-2">
                        <input type="text" value="AUTO_INCREMENT PRIMARY KEY" class="form-control" readonly />
                        <input type="hidden" name="columnas[0][longitud]" value="" />
                        <input type="hidden" name="columnas[0][atributos]" value="AUTO_INCREMENT PRIMARY KEY" />
                    </div>
                    <div class="col-md-1">
                        <button type="button" class="btn btn-danger btn-sm btn-remove-column" disabled>&times;</button>
                    </div>
                </div>


                <div class="row columna-row">
                    <div class="col-md-5">
                        <input type="text" name="columnas[1][nombre]" placeholder="Nombre columna" class="form-control"
                            required pattern="[a-zA-Z_][a-zA-Z0-9_]*"
                            title="Solo letras, números y guiones bajos, no puede empezar por número" />
                    </div>
                    <div class="col-md-4">
                        <select name="columnas[1][tipo]" class="form-control tipo-select" required>
                            <option value="">Tipo de dato</option>
                            <option value="INT">INT</option>
                            <option value="VARCHAR">VARCHAR</option>
                            <option value="CHAR">CHAR</option>
                            <option value="TEXT">TEXT</option>
                            <option value="DATE">DATE</option>
                            <option value="FLOAT">FLOAT</option>
                            <option value="DOUBLE">DOUBLE</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <input type="number" name="columnas[1][longitud]" placeholder="Longitud"
                            class="form-control longitud-input" disabled />
                    </div>
                    <div class="col-md-1">
                        <button type="button" class="btn btn-danger btn-sm btn-remove-column">&times;</button>
                    </div>
                </div>
            </div>

            <button type="button" id="add-column" class="btn btn-primary mb-3">+ Añadir Columna</button>
            <br />
            <button type="submit" class="btn btn-success">Crear Tabla</button>

        </form>
    </div>

    <script>
        let contadorColumnas = 2;


        function crearFilaColumna(indice) {
            const divRow = document.createElement('div');
            divRow.classList.add('row', 'columna-row');

            divRow.innerHTML = `
                <div class="col-md-5">
                    <input type="text" name="columnas[${indice}][nombre]" placeholder="Nombre columna" class="form-control" required pattern="[a-zA-Z_][a-zA-Z0-9_]*" title="Solo letras, números y guiones bajos, no puede empezar por número" />
                </div>
                <div class="col-md-4">
                    <select name="columnas[${indice}][tipo]" class="form-control tipo-select" required>
                        <option value="">Tipo de dato</option>
                        <option value="INT">INT</option>
                        <option value="VARCHAR">VARCHAR</option>
                        <option value="CHAR">CHAR</option>
                        <option value="TEXT">TEXT</option>
                        <option value="DATE">DATE</option>
                        <option value="FLOAT">FLOAT</option>
                        <option value="DOUBLE">DOUBLE</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <input type="number" name="columnas[${indice}][longitud]" placeholder="Longitud" class="form-control longitud-input" disabled />
                </div>
                <div class="col-md-1">
                    <button type="button" class="btn btn-danger btn-sm btn-remove-column">&times;</button>
                </div>
            `;

            return divRow;
        }

        document.getElementById('add-column').addEventListener('click', () => {
            const contenedor = document.getElementById('columnas-container');
            const nuevaFila = crearFilaColumna(contadorColumnas++);
            contenedor.appendChild(nuevaFila);
        });


        document.getElementById('columnas-container').addEventListener('click', e => {
            if (e.target.classList.contains('btn-remove-column')) {
                const filas = document.querySelectorAll('.columna-row');

                if (filas.length <= 2) {
                    alert("Debe haber al menos una columna además del ID.");
                    return;
                }
                e.target.closest('.columna-row').remove();
            }
        });

        document.getElementById('columnas-container').addEventListener('change', e => {
            if (e.target.classList.contains('tipo-select')) {
                const fila = e.target.closest('.columna-row');
                const longitudInput = fila.querySelector('.longitud-input');
                if (e.target.value === "VARCHAR" || e.target.value === "CHAR") {
                    longitudInput.disabled = false;
                    longitudInput.required = true;
                } else {
                    longitudInput.disabled = true;
                    longitudInput.required = false;
                    longitudInput.value = "";
                }
            }
        });
    </script>
</body>

</html>