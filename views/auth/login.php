<?php
require_once __DIR__ . '/../../helpers/auth.php';
require_once __DIR__ . '/../../helpers/funciones.php';
require_once __DIR__ . '/../../db/usuarios/acciones.php';

if (isLoggedIn()) {
    header('Location: ' . (isAdmin() ? '../dashboard/dashboard.php' : '../menu/menu.php'));
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = trim($_POST['correo'] ?? '');
    $password = trim($_POST['contrasena'] ?? '');

    if ($email === '' || $password === '') {
        setFlash('danger', 'Debes ingresar tu correo y contraseña.');
        header('Location: login.php');
        exit;
    }

    $user = authenticateUser($email, $password);
    if ($user) {
        setFlash('success', '¡Bienvenido(a), ' . $user['nombre'] . '!');
        header('Location: ' . ($user['rol'] === 'admin' ? '../dashboard/dashboard.php' : '../menu/menu.php'));
        exit;
    }

    setFlash('danger', 'Credenciales incorrectas. Verifica tu correo y contraseña.');
    header('Location: login.php');
    exit;
}

$flash = getFlash();
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Iniciar Sesión &bull; Food Manager</title>
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
        <span class="auth-showcase__tag">Hospitalidad &amp; Control</span>
        <h2 class="auth-showcase__title">Operación gastronómica con precisión editorial.</h2>
        <p class="auth-showcase__desc">
          Administra comandas en tiempo real, descuenta ingredientes por receta y consulta indicadores clave desde una sola interfaz.
        </p>

        <ul class="auth-showcase__features">
          <li>
            <span class="auth-showcase__feat-icon"><?= renderIcon('orders') ?></span>
            <div>
              <strong>Comandas y Mesas</strong>
              <span>Flujo continuo de cocina hasta cobro</span>
            </div>
          </li>
          <li>
            <span class="auth-showcase__feat-icon"><?= renderIcon('inventory') ?></span>
            <div>
              <strong>Inventario por Receta</strong>
              <span>Descuento automático y alertas de stock</span>
            </div>
          </li>
          <li>
            <span class="auth-showcase__feat-icon"><?= renderIcon('reports') ?></span>
            <div>
              <strong>KPIs y 5 Reportes</strong>
              <span>Visibilidad diaria de ventas y platillos</span>
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
          <h1 class="auth-card__title">Bienvenido de nuevo</h1>
          <p class="auth-card__subtitle">Ingresa tus credenciales para acceder a tu panel</p>
        </div>

        <div class="auth-card__body">
          <?php if ($flash): ?>
            <div class="alert alert--<?= e($flash['type']) ?>">
              <span><?= e($flash['message']) ?></span>
            </div>
          <?php endif; ?>

          <form action="login.php" method="POST">
            <div class="form-group">
              <label for="correo" class="form-label">Correo Electrónico</label>
              <input type="email" id="correo" name="correo" class="form-control" placeholder="ejemplo@correo.com" required autofocus>
            </div>

            <div class="form-group">
              <label for="contrasena" class="form-label">Contraseña</label>
              <input type="password" id="contrasena" name="contrasena" class="form-control" placeholder="&bull;&bull;&bull;&bull;&bull;&bull;" required>
            </div>

            <button type="submit" class="btn btn--accent btn--block btn--lg">
              <span>Iniciar Sesión</span>
              <?= renderIcon('arrow') ?>
            </button>
          </form>

          <div class="auth-card__switch">
            ¿No tienes una cuenta aún? <a href="register.php" class="auth-card__link">Regístrate aquí</a>
          </div>
        </div>
      </div>
    </section>
  </main>
</body>
</html>
