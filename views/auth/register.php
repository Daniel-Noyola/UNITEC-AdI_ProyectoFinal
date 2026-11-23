<?php
require_once __DIR__ . '/../../helpers/auth.php';
require_once __DIR__ . '/../../helpers/funciones.php';
require_once __DIR__ . '/../../db/usuarios/acciones.php';

if (isLoggedIn()) {
    header('Location: ' . (isAdmin() ? '../dashboard/dashboard.php' : '../menu/menu.php'));
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name     = trim($_POST['nombre'] ?? '');
    $email    = trim($_POST['correo'] ?? '');
    $password = trim($_POST['contrasena'] ?? '');
    $phone    = trim($_POST['telefono'] ?? '');

    if ($name === '' || $email === '' || $password === '') {
        setFlash('danger', 'Nombre, correo y contraseña son obligatorios.');
        header('Location: register.php');
        exit;
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        setFlash('danger', 'El correo electrónico no tiene un formato válido.');
        header('Location: register.php');
        exit;
    }

    if (strlen($password) < 6) {
        setFlash('danger', 'La contraseña debe contener al menos 6 caracteres.');
        header('Location: register.php');
        exit;
    }

    if (emailExists($email)) {
        setFlash('danger', 'El correo electrónico ya se encuentra registrado.');
        header('Location: register.php');
        exit;
    }

    registerUser($name, $email, $password, $phone, 'cliente');
    authenticateUser($email, $password);
    setFlash('success', '¡Cuenta creada con éxito! Ahora puedes realizar tus pedidos.');
    header('Location: ../menu/menu.php');
    exit;
}

$flash = getFlash();
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Crear Cuenta &bull; Food Manager</title>
  <link rel="stylesheet" href="../../styles/style.css">
</head>
<body>
  <main class="auth-split">
    <section class="auth-split__showcase">
      <div class="auth-showcase__brand">
        <div class="sidebar__logo-icon"><?= renderIcon('brand') ?></div>
        <div class="sidebar__brand">Food <span>Manager</span></div>
      </div>

      <div class="auth-showcase__hero">
        <span class="auth-showcase__tag">Experiencia Comensal</span>
        <h2 class="auth-showcase__title">Tu mesa y tus platillos favoritos, a un clic.</h2>
        <p class="auth-showcase__desc">
          Crea tu cuenta de cliente para explorar el menú digital, asignar tu mesa y dar seguimiento en vivo al estado de tu pedido.
        </p>

        <ul class="auth-showcase__features">
          <li>
            <span class="auth-showcase__feat-icon"><?= renderIcon('menu') ?></span>
            <div>
              <strong>Menú Digital Actualizado</strong>
              <span>Disponibilidad en tiempo real</span>
            </div>
          </li>
          <li>
            <span class="auth-showcase__feat-icon"><?= renderIcon('clock') ?></span>
            <div>
              <strong>Seguimiento Transparente</strong>
              <span>De cocina hasta tu mesa</span>
            </div>
          </li>
        </ul>
      </div>

      <div class="auth-showcase__footer">
        Sistema Integral de Restaurante &bull; Food Manager
      </div>
    </section>

    <section class="auth-split__form-area">
      <div class="auth-card">
        <div class="auth-card__header">
          <h1 class="auth-card__title">Crear cuenta</h1>
          <p class="auth-card__subtitle">Regístrate como cliente en segundos</p>
        </div>

        <div class="auth-card__body">
          <?php if ($flash): ?>
            <div class="alert alert--<?= e($flash['type']) ?>">
              <span><?= e($flash['message']) ?></span>
            </div>
          <?php endif; ?>

          <form action="register.php" method="POST">
            <div class="form-group">
              <label for="reg_nombre" class="form-label">Nombre Completo</label>
              <input type="text" id="reg_nombre" name="nombre" class="form-control" placeholder="Ej. Daniel Noyola" required autofocus>
            </div>

            <div class="form-group">
              <label for="reg_correo" class="form-label">Correo Electrónico</label>
              <input type="email" id="reg_correo" name="correo" class="form-control" placeholder="tu@correo.com" required>
            </div>

            <div class="form-row">
              <div class="form-group">
                <label for="reg_pass" class="form-label">Contraseña</label>
                <input type="password" id="reg_pass" name="contrasena" class="form-control" placeholder="Mínimo 6 caracteres" minlength="6" required>
              </div>
              <div class="form-group">
                <label for="reg_tel" class="form-label">Teléfono (opcional)</label>
                <input type="tel" id="reg_tel" name="telefono" class="form-control" placeholder="55 1234 5678">
              </div>
            </div>

            <button type="submit" class="btn btn--accent btn--block btn--lg">
              <span>Crear mi Cuenta</span>
              <?= renderIcon('arrow') ?>
            </button>
          </form>

          <div class="auth-card__switch">
            ¿Ya tienes una cuenta? <a href="login.php" class="auth-card__link">Inicia sesión</a>
          </div>
        </div>
      </div>
    </section>
  </main>
</body>
</html>
