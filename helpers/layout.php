<?php
/**
 * Plantilla de encabezado y pie de página compartida por las vistas autenticadas.
 */

function renderHeader(string $pageTitle, string $activePage): void
{
    $currentUser = getCurrentUser();
    $flash = getFlash();
    $isAdminUser = isAdmin();
    $initial = strtoupper(substr($currentUser['nombre'] ?? 'U', 0, 1));
    ?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= e($pageTitle) ?> &bull; Food Manager</title>
  <link rel="stylesheet" href="../../styles/style.css">
</head>
<body>
  <div class="app-layout">
    <aside class="app-layout__sidebar" id="appSidebar">
      <div class="sidebar__header">
        <div class="sidebar__logo-icon"><?= renderIcon('brand') ?></div>
        <div class="sidebar__brand">Food <span>Manager</span></div>
      </div>

      <nav class="sidebar__nav">
        <span class="sidebar__section-label">Operación</span>

        <?php if ($isAdminUser): ?>
          <a href="../dashboard/dashboard.php" class="sidebar__link <?= $activePage === 'dashboard' ? 'sidebar__link--active' : '' ?>">
            <span class="sidebar__link-icon"><?= renderIcon('dashboard') ?></span>
            <span>Dashboard KPI</span>
          </a>
        <?php endif; ?>

        <a href="../menu/menu.php" class="sidebar__link <?= $activePage === 'menu' ? 'sidebar__link--active' : '' ?>">
          <span class="sidebar__link-icon"><?= renderIcon('menu') ?></span>
          <span>Menú Digital</span>
        </a>

        <a href="../orders/orders.php" class="sidebar__link <?= $activePage === 'orders' ? 'sidebar__link--active' : '' ?>">
          <span class="sidebar__link-icon"><?= renderIcon('orders') ?></span>
          <span><?= $isAdminUser ? 'Gestión de Pedidos' : 'Mis Pedidos' ?></span>
        </a>

        <?php if ($isAdminUser): ?>
          <span class="sidebar__section-label">Administración</span>

          <a href="../tables/tables.php" class="sidebar__link <?= $activePage === 'tables' ? 'sidebar__link--active' : '' ?>">
            <span class="sidebar__link-icon"><?= renderIcon('tables') ?></span>
            <span>Mesas</span>
          </a>
          <a href="../dishes/dishes.php" class="sidebar__link <?= $activePage === 'dishes' ? 'sidebar__link--active' : '' ?>">
            <span class="sidebar__link-icon"><?= renderIcon('dishes') ?></span>
            <span>Platillos y Recetas</span>
          </a>
          <a href="../inventory/inventory.php" class="sidebar__link <?= $activePage === 'inventory' ? 'sidebar__link--active' : '' ?>">
            <span class="sidebar__link-icon"><?= renderIcon('inventory') ?></span>
            <span>Inventario</span>
          </a>
          <a href="../reports/reports.php" class="sidebar__link <?= $activePage === 'reports' ? 'sidebar__link--active' : '' ?>">
            <span class="sidebar__link-icon"><?= renderIcon('reports') ?></span>
            <span>Reportes</span>
          </a>
        <?php endif; ?>
      </nav>

      <div class="sidebar__footer">
        <div class="sidebar__user-info">
          <div class="sidebar__avatar"><?= e($initial) ?></div>
          <div class="sidebar__user-details">
            <span class="sidebar__user-name"><?= e($currentUser['nombre']) ?></span>
            <span class="sidebar__user-role"><?= $isAdminUser ? 'Administrador' : 'Cliente' ?></span>
          </div>
        </div>
        <div class="sidebar__footer-actions">
          <?php if ($isAdminUser && $activePage === 'dashboard'): ?>
            <form method="POST" action="../dashboard/dashboard.php" class="sidebar__seeder-form" data-confirm="¿Estás seguro de borrar todo el contenido de la base de datos y cargar el seeder? Esta acción no se puede deshacer.">
              <input type="hidden" name="action" value="run_seeder">
              <button type="submit" class="btn btn--danger btn--sm btn--block" title="Vaciar base de datos y cargar datos semilla">
                <span>Cargar Seeder</span>
              </button>
            </form>
          <?php endif; ?>
          <a href="../auth/logout.php" class="btn btn--outline btn--sm btn--block sidebar__logout-btn">
            <?= renderIcon('logout') ?>
            <span>Cerrar Sesión</span>
          </a>
        </div>
      </div>
    </aside>

    <div class="app-layout__main">
      <header class="topbar">
        <div class="topbar__left">
          <button type="button" class="topbar__toggle-btn" id="sidebarToggle" aria-label="Abrir Menú">&#9776;</button>
          <div class="topbar__heading">
            <span class="topbar__eyebrow">Food Manager &bull; <?= $isAdminUser ? 'Panel Ejecutivo' : 'Experiencia Comensal' ?></span>
            <h1 class="topbar__title"><?= e($pageTitle) ?></h1>
          </div>
        </div>
        <div class="topbar__right">
          <span class="badge <?= $isAdminUser ? 'badge--accent' : 'badge--info' ?>">
            <?= $isAdminUser ? 'Admin' : 'Cliente' ?>
          </span>
          <span class="topbar__email"><?= e($currentUser['correo']) ?></span>
        </div>
      </header>

      <main class="app-layout__content">
        <?php if ($flash): ?>
          <div class="alert alert--<?= e($flash['type']) ?>">
            <span><?= e($flash['message']) ?></span>
            <button type="button" class="alert__close">&times;</button>
          </div>
        <?php endif; ?>
    <?php
}

function renderFooter(): void
{
    ?>
      </main>
    </div>
  </div>

  <!-- JavaScript mínimo para interactividad de interfaz (menú móvil, alertas, modales y confirmaciones) -->
  <script>
    document.addEventListener('DOMContentLoaded', () => {
      const toggleBtn = document.getElementById('sidebarToggle');
      const sidebar = document.getElementById('appSidebar');
      if (toggleBtn && sidebar) {
        toggleBtn.addEventListener('click', () => sidebar.classList.toggle('app-layout__sidebar--open'));
        document.addEventListener('click', (e) => {
          if (window.innerWidth <= 768 && sidebar.classList.contains('app-layout__sidebar--open') &&
              !sidebar.contains(e.target) && !toggleBtn.contains(e.target)) {
            sidebar.classList.remove('app-layout__sidebar--open');
          }
        });
      }

      document.querySelectorAll('.alert__close').forEach((btn) => {
        btn.addEventListener('click', () => btn.closest('.alert')?.remove());
      });

      document.querySelectorAll('[data-modal-target]').forEach((btn) => {
        btn.addEventListener('click', () => {
          document.getElementById(btn.getAttribute('data-modal-target'))?.classList.add('modal-overlay--open');
        });
      });

      document.querySelectorAll('[data-modal-close]').forEach((btn) => {
        btn.addEventListener('click', () => btn.closest('.modal-overlay')?.classList.remove('modal-overlay--open'));
      });

      document.querySelectorAll('.modal-overlay').forEach((overlay) => {
        overlay.addEventListener('click', (e) => {
          if (e.target === overlay) overlay.classList.remove('modal-overlay--open');
        });
      });

      document.querySelectorAll('[data-confirm]').forEach((form) => {
        form.addEventListener('submit', (e) => {
          if (!confirm(form.getAttribute('data-confirm') || '¿Estás seguro?')) {
            e.preventDefault();
          }
        });
      });
    });
  </script>
</body>
</html>
    <?php
}
