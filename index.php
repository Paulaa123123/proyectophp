<!DOCTYPE html>

<html>
  <head>
    <meta charset="utf-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <title>Login Form</title>
    <meta name="description" content="" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <link
      rel="stylesheet"
      href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css"
    />
    <link rel="stylesheet" href="styles/style.css" />
    <script src="scripts/script.js" defer></script>
  </head>
  <body>
    <div class="container" id="container">
      <div class="form-container sign-up-container">
        <form action="php/register.php" method="POST">
          <h1>Registrate</h1>    

          <span>Usa tu email para registrate</span>
          <input
            type="text"
            name="nombre"
            placeholder="Ingresa tu nombre"
            required
          />
          <input
            type="text"
            name="apellidos"
            placeholder="Ingresa tus apellidos"
            required
          />
          <input
            type="text"
            name="email"
            placeholder="Ingresa tu email"
            required
          />
         <input type="password" name="contrasena" placeholder="Ingresa tu contraseña" required />

          <button type="submit">Registrarse</button>
        </form>
      </div>

      <div class="form-container sign-in-container">
        <form action="php/login.php" method="POST">
          <h1>Iniciar Sesion</h1>

          <span>Usa tu cuenta para iniciar sesion</span>
          <input type="text" name="email" placeholder="Ingresa tu email" />
          <input
            type="text"
            name="contrasena"
            placeholder="Ingresa tu contraseña"
          />
          <button type="submit">Iniciar sesion</button>
        </form>
      </div>
      <div class="overlay-container">
        <div class="overlay">
          <div class="overlay-panel overlay-left">
            <h1>Hola de nuevo</h1>
            <img
              src="img/foto1.png"
              alt=""
              style="width: 150px; margin-top: 20px"
            />
            <p>Inicia sesion para estar conectado</p>
            <button class="ghost" id="signIn">Iniciar sesion</button>
          </div>
          <div class="overlay-panel overlay-right">
            <h1>Bienvenido</h1>
            <img
              src="img/foto2.png"
              alt=""
              style="width: 150px; margin-top: 20px"
            />
            <p>Registrate para estar conetado</p>
            <button class="ghost" id="signUp">Registrate</button>
          </div>
        </div>
      </div>
    </div>
  </body>
</html>
