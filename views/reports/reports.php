<?php
require_once __DIR__ . '/../../helpers/auth.php';
require_once __DIR__ . '/../../helpers/funciones.php';
require_once __DIR__ . '/../../helpers/layout.php';
require_once __DIR__ . '/../../db/reportes/acciones.php';

requireAdmin();

$tabReport = $_GET['tab'] ?? '1';
$repFrom   = $_GET['from'] ?? null;
$repTo     = $_GET['to'] ?? null;

renderHeader('Reportes del Restaurante', 'reports');
?>

<div class="segmented-tabs">
  <a href="reports.php?tab=1" class="segmented-tabs__item <?= $tabReport === '1' ? 'segmented-tabs__item--active' : '' ?>">1. Ventas por Periodo</a>
  <a href="reports.php?tab=2" class="segmented-tabs__item <?= $tabReport === '2' ? 'segmented-tabs__item--active' : '' ?>">2. Platillos Más Vendidos</a>
  <a href="reports.php?tab=3" class="segmented-tabs__item <?= $tabReport === '3' ? 'segmented-tabs__item--active' : '' ?>">3. Clientes y Gasto Acumulado</a>
  <a href="reports.php?tab=4" class="segmented-tabs__item <?= $tabReport === '4' ? 'segmented-tabs__item--active' : '' ?>">4. Inventario y Stock Mínimo</a>
  <a href="reports.php?tab=5" class="segmented-tabs__item <?= $tabReport === '5' ? 'segmented-tabs__item--active' : '' ?>">5. Historial Completo de Pedidos</a>
</div>

<?php if (in_array($tabReport, ['1', '2', '3', '5'], true)): ?>
  <div class="card">
    <div class="card__body">
      <form method="GET" action="reports.php" class="form-row">
        <input type="hidden" name="tab" value="<?= e($tabReport) ?>">
        <div class="form-group">
          <label class="form-label" for="from">Fecha Desde</label>
          <input type="date" id="from" name="from" value="<?= e($repFrom) ?>" class="form-control">
        </div>
        <div class="form-group">
          <label class="form-label" for="to">Fecha Hasta</label>
          <input type="date" id="to" name="to" value="<?= e($repTo) ?>" class="form-control">
        </div>
        <div class="form-group filter-bar">
          <button type="submit" class="btn btn--primary">Generar Reporte</button>
          <?php if ($repFrom || $repTo): ?>
            <a href="reports.php?tab=<?= e($tabReport) ?>" class="btn btn--outline">Limpiar</a>
          <?php endif; ?>
        </div>
      </form>
    </div>
  </div>
<?php endif; ?>

<?php if ($tabReport === '1'): ?>
  <?php $salesData = getReportSalesByPeriod($repFrom, $repTo); ?>
  <div class="card">
    <div class="card__header">
      <h2 class="card__title">Reporte 1: Ventas por Periodo (Pedidos Pagados)</h2>
    </div>
    <div class="card__body">
      <div class="table-responsive">
        <table class="data-table">
          <thead>
            <tr>
              <th>Fecha / Periodo</th>
              <th>Pedidos Pagados</th>
              <th>Venta Total</th>
              <th>Ticket Promedio</th>
            </tr>
          </thead>
          <tbody>
            <?php if (empty($salesData)): ?>
              <tr><td colspan="4">No hay ventas registradas en el periodo.</td></tr>
            <?php else: ?>
              <?php foreach ($salesData as $row): ?>
                <tr>
                  <td><strong><?= e($row['periodo']) ?></strong></td>
                  <td><?= (int)$row['total_pedidos'] ?> pedidos</td>
                  <td><strong>$<?= number_format((float)$row['total_ventas'], 2) ?></strong></td>
                  <td>$<?= number_format((float)$row['ticket_promedio'], 2) ?></td>
                </tr>
              <?php endforeach; ?>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>

<?php elseif ($tabReport === '2'): ?>
  <?php $topDishes = getReportTopDishes($repFrom, $repTo); ?>
  <div class="card">
    <div class="card__header">
      <h2 class="card__title">Reporte 2: Platillos Más Vendidos (Unidades e Importe)</h2>
    </div>
    <div class="card__body">
      <div class="table-responsive">
        <table class="data-table">
          <thead>
            <tr>
              <th>Platillo</th>
              <th>Categoría</th>
              <th>Precio Base</th>
              <th>Unidades Vendidas</th>
              <th>Importe Acumulado</th>
            </tr>
          </thead>
          <tbody>
            <?php if (empty($topDishes)): ?>
              <tr><td colspan="5">No se encontraron platillos vendidos en pedidos pagados.</td></tr>
            <?php else: ?>
              <?php foreach ($topDishes as $td): ?>
                <tr>
                  <td><strong><?= e($td['nombre']) ?></strong></td>
                  <td><span class="badge badge--info"><?= e($td['categoria']) ?></span></td>
                  <td>$<?= number_format((float)$td['precio'], 2) ?></td>
                  <td><strong><?= (int)$td['unidades_vendidas'] ?></strong> unidades</td>
                  <td><strong>$<?= number_format((float)$td['importe_total'], 2) ?></strong></td>
                </tr>
              <?php endforeach; ?>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>

<?php elseif ($tabReport === '3'): ?>
  <?php $topClients = getReportTopClients($repFrom, $repTo); ?>
  <div class="card">
    <div class="card__header">
      <h2 class="card__title">Reporte 3: Clientes con Más Compras y Gasto Acumulado</h2>
    </div>
    <div class="card__body">
      <div class="table-responsive">
        <table class="data-table">
          <thead>
            <tr>
              <th>Cliente</th>
              <th>Correo</th>
              <th>Teléfono</th>
              <th>Total de Pedidos</th>
              <th>Gasto Acumulado</th>
              <th>Última Compra</th>
            </tr>
          </thead>
          <tbody>
            <?php if (empty($topClients)): ?>
              <tr><td colspan="6">No se encontraron clientes con pedidos pagados.</td></tr>
            <?php else: ?>
              <?php foreach ($topClients as $tc): ?>
                <tr>
                  <td><strong><?= e($tc['nombre']) ?></strong></td>
                  <td><?= e($tc['correo']) ?></td>
                  <td><?= e($tc['telefono'] ?? 'N/D') ?></td>
                  <td><strong><?= (int)$tc['total_pedidos'] ?></strong> pedidos</td>
                  <td><strong>$<?= number_format((float)$tc['total_gastado'], 2) ?></strong></td>
                  <td><?= e($tc['ultima_compra'] ?? 'N/D') ?></td>
                </tr>
              <?php endforeach; ?>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>

<?php elseif ($tabReport === '4'): ?>
  <?php $invReport = getReportInventoryStatus(); ?>
  <div class="card">
    <div class="card__header">
      <h2 class="card__title">Reporte 4: Inventario Actual y Productos Bajo Stock Mínimo</h2>
    </div>
    <div class="card__body">
      <div class="table-responsive">
        <table class="data-table">
          <thead>
            <tr>
              <th>Ingrediente</th>
              <th>Existencia Actual</th>
              <th>Unidad</th>
              <th>Stock Mínimo</th>
              <th>Diferencia</th>
              <th>Estado de Alerta</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($invReport as $inv): ?>
              <?php $diff = (float)$inv['diferencia']; ?>
              <tr>
                <td><strong><?= e($inv['nombre']) ?></strong></td>
                <td><?= number_format((float)$inv['cantidad_stock'], 2) ?></td>
                <td><?= e($inv['unidad_medida']) ?></td>
                <td><?= number_format((float)$inv['stock_minimo'], 2) ?></td>
                <td><?= ($diff > 0 ? '+' : '') . number_format($diff, 2) ?></td>
                <td>
                  <?php if ((int)$inv['bajo_stock'] === 1): ?>
                    <span class="badge badge--danger">&#9888; Requiere Reabastecimiento</span>
                  <?php else: ?>
                    <span class="badge badge--success">&#10003; Existencia Suficiente</span>
                  <?php endif; ?>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>

<?php elseif ($tabReport === '5'): ?>
  <?php $history = getReportOrdersHistory(null, $repFrom, $repTo); ?>
  <div class="card">
    <div class="card__header">
      <h2 class="card__title">Reporte 5: Historial Completo de Pedidos</h2>
    </div>
    <div class="card__body">
      <div class="table-responsive">
        <table class="data-table">
          <thead>
            <tr>
              <th># Pedido</th>
              <th>Fecha</th>
              <th>Cliente</th>
              <th>Mesa</th>
              <th>Estado</th>
              <th>Total</th>
              <th>Acción</th>
            </tr>
          </thead>
          <tbody>
            <?php if (empty($history)): ?>
              <tr><td colspan="7">No se encontraron pedidos en el rango seleccionado.</td></tr>
            <?php else: ?>
              <?php foreach ($history as $h): ?>
                <tr>
                  <td><strong>#<?= (int)$h['id_pedido'] ?></strong></td>
                  <td><?= e($h['fecha_pedido']) ?></td>
                  <td><?= e($h['usuario_nombre']) ?></td>
                  <td><?= $h['numero_mesa'] ? 'Mesa ' . (int)$h['numero_mesa'] : 'Para Llevar' ?></td>
                  <td><span class="badge <?= getStatusBadgeClass($h['estado']) ?>"><?= e($h['estado']) ?></span></td>
                  <td><strong>$<?= number_format((float)$h['total'], 2) ?></strong></td>
                  <td><a href="../orders/orders.php?order_id=<?= (int)$h['id_pedido'] ?>" class="btn btn--outline btn--sm">Detalle</a></td>
                </tr>
              <?php endforeach; ?>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
<?php endif; ?>

<?php renderFooter(); ?>
