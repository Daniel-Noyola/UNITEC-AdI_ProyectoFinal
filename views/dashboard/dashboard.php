<?php
require_once __DIR__ . '/../../helpers/auth.php';
require_once __DIR__ . '/../../helpers/funciones.php';
require_once __DIR__ . '/../../helpers/layout.php';
require_once __DIR__ . '/../../db/reportes/acciones.php';
require_once __DIR__ . '/../../db/seeder/acciones.php';

requireAdmin();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'run_seeder') {
    try {
        $currentUser = getCurrentUser();
        resetDatabaseAndRunSeeder((int)$currentUser['id_usuario']);
        setFlash('success', 'Base de datos vaciada y seeder inicial cargado correctamente.');
    } catch (Exception $e) {
        setFlash('danger', 'Error al ejecutar el seeder: ' . $e->getMessage());
    }
    header('Location: dashboard.php');
    exit;
}

$dateFrom = $_GET['from'] ?? null;
$dateTo   = $_GET['to'] ?? null;
$kpis = getDashboardKpis($dateFrom, $dateTo);
$recentOrders = array_slice(getAllOrders(), 0, 5);

renderHeader('Dashboard de Administración', 'dashboard');
?>

<div class="page-toolbar">
  <div class="page-toolbar__intro">
    <h2 class="page-toolbar__title">Resumen Ejecutivo</h2>
    <p class="page-toolbar__subtitle">Monitoreo de ventas, demanda y operación en tiempo real.</p>
  </div>

  <form method="GET" action="dashboard.php" class="page-toolbar__filters">
    <div class="inline-field">
      <label class="inline-field__label" for="from">Desde</label>
      <input type="date" id="from" name="from" value="<?= e($dateFrom) ?>" class="form-control form-control--sm">
    </div>
    <div class="inline-field">
      <label class="inline-field__label" for="to">Hasta</label>
      <input type="date" id="to" name="to" value="<?= e($dateTo) ?>" class="form-control form-control--sm">
    </div>
    <button type="submit" class="btn btn--primary btn--sm">
      <?= renderIcon('filter') ?>
      <span>Filtrar</span>
    </button>
    <?php if ($dateFrom || $dateTo): ?>
      <a href="dashboard.php" class="btn btn--outline btn--sm">Limpiar</a>
    <?php endif; ?>
  </form>
</div>

<section class="kpi-bento">
  <div class="kpi-card kpi-card--hero">
    <div class="kpi-card__top">
      <span class="kpi-card__eyebrow">Ingresos Confirmados</span>
      <span class="kpi-card__icon kpi-card__icon--hero"><?= renderIcon('money') ?></span>
    </div>
    <div class="kpi-card__value kpi-card__value--hero">$<?= number_format($kpis['total_ventas'], 2) ?></div>
    <div class="kpi-card__meta">
      <span class="kpi-card__pill"><?= $kpis['pedidos_totales'] ?> pedidos pagados</span>
      <span class="kpi-card__subtext">Ticket prom. $<?= number_format($kpis['ticket_promedio'], 2) ?></span>
    </div>
  </div>

  <div class="kpi-bento__secondary">
    <div class="kpi-card kpi-card--metric kpi-card--accent">
      <div class="kpi-card__header">
        <span class="kpi-card__label">Producto Más Vendido</span>
        <div class="kpi-card__icon"><?= renderIcon('star') ?></div>
      </div>
      <div class="kpi-card__content">
        <span class="kpi-card__value"><?= e($kpis['platillo_top']['nombre'] ?? 'N/D') ?></span>
        <span class="kpi-card__subtext"><?= (int)($kpis['platillo_top']['total_unidades'] ?? 0) ?> unidades vendidas</span>
      </div>
    </div>

    <div class="kpi-card kpi-card--metric kpi-card--info">
      <div class="kpi-card__header">
        <span class="kpi-card__label">Cliente Frecuente</span>
        <div class="kpi-card__icon"><?= renderIcon('user') ?></div>
      </div>
      <div class="kpi-card__content">
        <span class="kpi-card__value"><?= e($kpis['cliente_top']['nombre'] ?? 'N/D') ?></span>
        <span class="kpi-card__subtext"><?= (int)($kpis['cliente_top']['total_pedidos'] ?? 0) ?> pedidos ($<?= number_format((float)($kpis['cliente_top']['total_gastado'] ?? 0), 2) ?>)</span>
      </div>
    </div>

    <div class="kpi-card kpi-card--metric kpi-card--success">
      <div class="kpi-card__header">
        <span class="kpi-card__label">Día con Más Ventas</span>
        <div class="kpi-card__icon"><?= renderIcon('calendar') ?></div>
      </div>
      <div class="kpi-card__content">
        <span class="kpi-card__value"><?= e($kpis['periodo_top']['fecha_dia'] ?? 'N/D') ?></span>
        <span class="kpi-card__subtext">Ingreso: $<?= number_format((float)($kpis['periodo_top']['total_dia'] ?? 0), 2) ?></span>
      </div>
    </div>

    <div class="kpi-card kpi-card--metric kpi-card--neutral">
      <div class="kpi-card__header">
        <span class="kpi-card__label">Ticket Promedio</span>
        <div class="kpi-card__icon"><?= renderIcon('receipt') ?></div>
      </div>
      <div class="kpi-card__content">
        <span class="kpi-card__value">$<?= number_format($kpis['ticket_promedio'], 2) ?></span>
        <span class="kpi-card__subtext">Base de <?= $kpis['pedidos_totales'] ?> cuentas cerradas</span>
      </div>
    </div>
  </div>
</section>

<div class="dashboard-split">
  <div class="card dashboard-split__main">
    <div class="card__header">
      <div>
        <h2 class="card__title">Últimos Pedidos Registrados</h2>
        <span class="card__subtitle">Actividad reciente de salón y para llevar</span>
      </div>
      <a href="../orders/orders.php" class="btn btn--outline btn--sm">
        <span>Ver Todos</span>
        <?= renderIcon('arrow') ?>
      </a>
    </div>
    <div class="card__body">
      <?php if (empty($recentOrders)): ?>
        <div class="empty-state">
          <div class="empty-state__icon"><?= renderIcon('orders') ?></div>
          <h3 class="empty-state__title">Aún no hay pedidos</h3>
          <p class="empty-state__desc">Los pedidos registrados aparecerán en esta tabla.</p>
        </div>
      <?php else: ?>
        <div class="table-responsive">
          <table class="data-table">
            <thead>
              <tr>
                <th># Pedido</th>
                <th>Cliente</th>
                <th>Mesa</th>
                <th>Fecha</th>
                <th>Estado</th>
                <th>Total</th>
                <th>Acción</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($recentOrders as $ro): ?>
                <tr>
                  <td class="num-cell"><strong>#<?= (int)$ro['id_pedido'] ?></strong></td>
                  <td><?= e($ro['usuario_nombre']) ?></td>
                  <td><?= $ro['numero_mesa'] ? 'Mesa ' . (int)$ro['numero_mesa'] : 'Para Llevar' ?></td>
                  <td class="num-cell"><?= e($ro['fecha_pedido']) ?></td>
                  <td><span class="badge <?= getStatusBadgeClass($ro['estado']) ?>"><?= e($ro['estado']) ?></span></td>
                  <td class="num-cell"><strong>$<?= number_format((float)$ro['total'], 2) ?></strong></td>
                  <td><a href="../orders/orders.php?order_id=<?= (int)$ro['id_pedido'] ?>" class="btn btn--outline btn--sm">Detalle</a></td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      <?php endif; ?>
    </div>
  </div>

  <aside class="card dashboard-split__aside">
    <div class="card__header">
      <div>
        <h2 class="card__title">Centro de Control</h2>
        <span class="card__subtitle">Accesos operativos frecuentes</span>
      </div>
    </div>
    <div class="card__body">
      <div class="quick-actions">
        <a href="../menu/menu.php" class="quick-action-item quick-action-item--primary">
          <span class="quick-action-item__icon"><?= renderIcon('menu') ?></span>
          <div class="quick-action-item__text">
            <strong>Nueva Comanda</strong>
            <span>Abrir menú digital y tomar pedido</span>
          </div>
          <?= renderIcon('arrow', 'ui-icon quick-action-item__arrow') ?>
        </a>

        <a href="../tables/tables.php" class="quick-action-item">
          <span class="quick-action-item__icon"><?= renderIcon('tables') ?></span>
          <div class="quick-action-item__text">
            <strong>Estado de Mesas</strong>
            <span>Consultar ocupación del salón</span>
          </div>
          <?= renderIcon('arrow', 'ui-icon quick-action-item__arrow') ?>
        </a>

        <a href="../inventory/inventory.php" class="quick-action-item">
          <span class="quick-action-item__icon"><?= renderIcon('inventory') ?></span>
          <div class="quick-action-item__text">
            <strong>Almacén e Insumos</strong>
            <span>Revisar existencias y alertas</span>
          </div>
          <?= renderIcon('arrow', 'ui-icon quick-action-item__arrow') ?>
        </a>

        <a href="../reports/reports.php" class="quick-action-item">
          <span class="quick-action-item__icon"><?= renderIcon('reports') ?></span>
          <div class="quick-action-item__text">
            <strong>Reportes Gerenciales</strong>
            <span>Exportar y analizar 5 vistas</span>
          </div>
          <?= renderIcon('arrow', 'ui-icon quick-action-item__arrow') ?>
        </a>
      </div>
    </div>
  </aside>
</div>

<?php renderFooter(); ?>
