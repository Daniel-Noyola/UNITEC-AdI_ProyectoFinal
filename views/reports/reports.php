<?php
require_once __DIR__ . '/../../helpers/auth.php';
require_once __DIR__ . '/../../helpers/funciones.php';
require_once __DIR__ . '/../../helpers/layout.php';
require_once __DIR__ . '/../../db/reportes/acciones.php';

requireAdmin();

$tabReport = $_GET['tab'] ?? '1';
$repFrom   = $_GET['from'] ?? null;
$repTo     = $_GET['to'] ?? null;

/**
 * Renderiza una fila de tarjetas de resumen en Flexbox para cualquier reporte.
 */
function renderReportSummary(array $items): void
{
    echo '<div class="report-summary">';
    foreach ($items as $item) {
        $toneClass = !empty($item['tone']) ? ' report-stat--' . e($item['tone']) : '';
        echo '<div class="report-stat' . $toneClass . '">';
        echo '<span class="report-stat__label">' . e($item['label']) . '</span>';
        echo '<strong class="report-stat__value">' . e($item['value']) . '</strong>';
        if (!empty($item['sub'])) {
            echo '<span class="report-stat__sub">' . e($item['sub']) . '</span>';
        }
        echo '</div>';
    }
    echo '</div>';
}

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
  <?php
    $salesData = getReportSalesByPeriod($repFrom, $repTo);
    $sumSales = 0.0;
    $sumOrders = 0;
    $maxDaySales = 0.0;
    $bestDayLabel = 'N/D';
    foreach ($salesData as $r) {
        $v = (float)$r['total_ventas'];
        $sumSales += $v;
        $sumOrders += (int)$r['total_pedidos'];
        if ($v > $maxDaySales) {
            $maxDaySales = $v;
            $bestDayLabel = date('d/m/Y', strtotime($r['periodo']));
        }
    }
    $avgTicketAll = $sumOrders > 0 ? ($sumSales / $sumOrders) : 0.0;

    renderReportSummary([
        ['label' => 'Venta Total del Periodo', 'value' => '$' . number_format($sumSales, 2), 'sub' => count($salesData) . ' días con ventas', 'tone' => 'accent'],
        ['label' => 'Pedidos Pagados', 'value' => (string)$sumOrders, 'sub' => 'Ticket promedio: $' . number_format($avgTicketAll, 2), 'tone' => 'info'],
        ['label' => 'Día con Mayor Venta', 'value' => $bestDayLabel, 'sub' => $maxDaySales > 0 ? '$' . number_format($maxDaySales, 2) . ' recaudados' : 'Sin registros', 'tone' => 'success'],
    ]);
  ?>
  <div class="card">
    <div class="card__header">
      <div>
        <h2 class="card__title">Reporte 1: Ventas por Periodo (Pedidos Pagados)</h2>
        <span class="card__subtitle">Desglose diario con participación relativa respecto al día pico</span>
      </div>
    </div>
    <div class="card__body">
      <div class="table-responsive">
        <table class="data-table data-table--orders">
          <thead>
            <tr>
              <th>Fecha / Periodo</th>
              <th class="num-cell">Pedidos Pagados</th>
              <th class="num-cell">Ticket Promedio</th>
              <th>Participación</th>
              <th class="num-cell">Venta Total</th>
            </tr>
          </thead>
          <tbody>
            <?php if (empty($salesData)): ?>
              <tr><td colspan="5">No hay ventas registradas en el periodo.</td></tr>
            <?php else: ?>
              <?php foreach ($salesData as $row): ?>
                <?php
                  $dayTotal = (float)$row['total_ventas'];
                  $pct = $maxDaySales > 0 ? min(100, round(($dayTotal / $maxDaySales) * 100)) : 0;
                ?>
                <tr>
                  <td><strong><?= e(date('d/m/Y', strtotime($row['periodo']))) ?></strong></td>
                  <td class="num-cell"><?= (int)$row['total_pedidos'] ?> pedidos</td>
                  <td class="num-cell">$<?= number_format((float)$row['ticket_promedio'], 2) ?></td>
                  <td>
                    <div class="report-bar">
                      <div class="report-bar__track"><div class="report-bar__fill" style="width: <?= $pct ?>%;"></div></div>
                      <span class="report-bar__label"><?= $pct ?>%</span>
                    </div>
                  </td>
                  <td class="num-cell"><strong>$<?= number_format($dayTotal, 2) ?></strong></td>
                </tr>
              <?php endforeach; ?>
            <?php endif; ?>
          </tbody>
          <?php if (!empty($salesData)): ?>
            <tfoot>
              <tr class="order-detail-total-row">
                <td><strong>Totales del Periodo</strong></td>
                <td class="num-cell"><strong><?= $sumOrders ?> pedidos</strong></td>
                <td class="num-cell"><strong>$<?= number_format($avgTicketAll, 2) ?></strong></td>
                <td></td>
                <td class="num-cell"><strong class="order-detail-total-row__amount">$<?= number_format($sumSales, 2) ?></strong></td>
              </tr>
            </tfoot>
          <?php endif; ?>
        </table>
      </div>
    </div>
  </div>

<?php elseif ($tabReport === '2'): ?>
  <?php
    $topDishes = getReportTopDishes($repFrom, $repTo);
    $totalUnits = 0;
    $totalDishRevenue = 0.0;
    $maxUnits = 0;
    foreach ($topDishes as $td) {
        $u = (int)$td['unidades_vendidas'];
        $totalUnits += $u;
        $totalDishRevenue += (float)$td['importe_total'];
        if ($u > $maxUnits) {
            $maxUnits = $u;
        }
    }
    $leaderDish = $topDishes[0] ?? null;

    renderReportSummary([
        ['label' => 'Platillo Líder', 'value' => $leaderDish ? $leaderDish['nombre'] : 'N/D', 'sub' => $leaderDish ? (int)$leaderDish['unidades_vendidas'] . ' unidades vendidas' : 'Sin ventas', 'tone' => 'accent'],
        ['label' => 'Unidades Vendidas', 'value' => $totalUnits . ' pzas', 'sub' => count($topDishes) . ' platillos distintos', 'tone' => 'info'],
        ['label' => 'Importe Acumulado', 'value' => '$' . number_format($totalDishRevenue, 2), 'sub' => 'En pedidos pagados', 'tone' => 'success'],
    ]);
  ?>
  <div class="card">
    <div class="card__header">
      <div>
        <h2 class="card__title">Reporte 2: Platillos Más Vendidos (Unidades e Importe)</h2>
        <span class="card__subtitle">Ranking de demanda por volumen e ingreso generado</span>
      </div>
    </div>
    <div class="card__body">
      <div class="table-responsive">
        <table class="data-table data-table--orders">
          <thead>
            <tr>
              <th>#</th>
              <th>Platillo</th>
              <th>Categoría</th>
              <th class="num-cell">Precio Base</th>
              <th class="num-cell">Unidades</th>
              <th>Demanda Relativa</th>
              <th class="num-cell">Importe</th>
            </tr>
          </thead>
          <tbody>
            <?php if (empty($topDishes)): ?>
              <tr><td colspan="7">No se encontraron platillos vendidos en pedidos pagados.</td></tr>
            <?php else: ?>
              <?php foreach ($topDishes as $idx => $td): ?>
                <?php
                  $u = (int)$td['unidades_vendidas'];
                  $pct = $maxUnits > 0 ? min(100, round(($u / $maxUnits) * 100)) : 0;
                ?>
                <tr>
                  <td><span class="folio-pill">#<?= $idx + 1 ?></span></td>
                  <td><strong><?= e($td['nombre']) ?></strong></td>
                  <td><span class="badge badge--info"><?= e($td['categoria']) ?></span></td>
                  <td class="num-cell">$<?= number_format((float)$td['precio'], 2) ?></td>
                  <td class="num-cell"><strong><?= $u ?></strong> pzas</td>
                  <td>
                    <div class="report-bar">
                      <div class="report-bar__track"><div class="report-bar__fill report-bar__fill--info" style="width: <?= $pct ?>%;"></div></div>
                      <span class="report-bar__label"><?= $pct ?>%</span>
                    </div>
                  </td>
                  <td class="num-cell"><strong>$<?= number_format((float)$td['importe_total'], 2) ?></strong></td>
                </tr>
              <?php endforeach; ?>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>

<?php elseif ($tabReport === '3'): ?>
  <?php
    $topClients = getReportTopClients($repFrom, $repTo);
    $totalClientSpent = 0.0;
    $maxClientSpent = 0.0;
    foreach ($topClients as $tc) {
        $g = (float)$tc['total_gastado'];
        $totalClientSpent += $g;
        if ($g > $maxClientSpent) {
            $maxClientSpent = $g;
        }
    }
    $topClient = $topClients[0] ?? null;

    renderReportSummary([
        ['label' => 'Cliente Frecuente', 'value' => $topClient ? $topClient['nombre'] : 'N/D', 'sub' => $topClient ? (int)$topClient['total_pedidos'] . ' pedidos realizados' : 'Sin registros', 'tone' => 'accent'],
        ['label' => 'Clientes con Compras', 'value' => (string)count($topClients), 'sub' => 'Con al menos 1 pedido pagado', 'tone' => 'info'],
        ['label' => 'Gasto Acumulado', 'value' => '$' . number_format($totalClientSpent, 2), 'sub' => 'Suma de clientes listados', 'tone' => 'success'],
    ]);
  ?>
  <div class="card">
    <div class="card__header">
      <div>
        <h2 class="card__title">Reporte 3: Clientes con Más Compras y Gasto Acumulado</h2>
        <span class="card__subtitle">Frecuencia de visita, contacto y aporte al ingreso</span>
      </div>
    </div>
    <div class="card__body">
      <div class="table-responsive">
        <table class="data-table data-table--orders">
          <thead>
            <tr>
              <th>Cliente y Contacto</th>
              <th class="num-cell">Pedidos</th>
              <th>Última Compra</th>
              <th>Aporte Relativo</th>
              <th class="num-cell">Gasto Acumulado</th>
            </tr>
          </thead>
          <tbody>
            <?php if (empty($topClients)): ?>
              <tr><td colspan="5">No se encontraron clientes con pedidos pagados.</td></tr>
            <?php else: ?>
              <?php foreach ($topClients as $tc): ?>
                <?php
                  $g = (float)$tc['total_gastado'];
                  $pct = $maxClientSpent > 0 ? min(100, round(($g / $maxClientSpent) * 100)) : 0;
                  $lastBuy = !empty($tc['ultima_compra']) ? date('d/m/Y · H:i', strtotime($tc['ultima_compra'])) : 'N/D';
                ?>
                <tr>
                  <td>
                    <div class="cell-stack">
                      <span class="cell-stack__main"><?= e($tc['nombre']) ?></span>
                      <span class="cell-stack__sub"><?= e($tc['correo']) ?><?= !empty($tc['telefono']) ? ' · ' . e($tc['telefono']) : '' ?></span>
                    </div>
                  </td>
                  <td class="num-cell"><strong><?= (int)$tc['total_pedidos'] ?></strong></td>
                  <td class="cell-stack__sub"><?= e($lastBuy) ?></td>
                  <td>
                    <div class="report-bar">
                      <div class="report-bar__track"><div class="report-bar__fill report-bar__fill--success" style="width: <?= $pct ?>%;"></div></div>
                      <span class="report-bar__label"><?= $pct ?>%</span>
                    </div>
                  </td>
                  <td class="num-cell"><strong>$<?= number_format($g, 2) ?></strong></td>
                </tr>
              <?php endforeach; ?>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>

<?php elseif ($tabReport === '4'): ?>
  <?php
    $invReport = getReportInventoryStatus();
    $lowStockCount = 0;
    $okStockCount = 0;
    foreach ($invReport as $inv) {
        if ((int)$inv['bajo_stock'] === 1) {
            $lowStockCount++;
        } else {
            $okStockCount++;
        }
    }

    renderReportSummary([
        ['label' => 'Total de Insumos', 'value' => (string)count($invReport), 'sub' => 'Ingredientes registrados', 'tone' => 'info'],
        ['label' => 'Requieren Reabastecimiento', 'value' => (string)$lowStockCount, 'sub' => $lowStockCount > 0 ? 'Igual o menor al stock mínimo' : 'Sin alertas activas', 'tone' => $lowStockCount > 0 ? 'danger' : 'success'],
        ['label' => 'Existencia Suficiente', 'value' => (string)$okStockCount, 'sub' => 'Por encima del mínimo', 'tone' => 'success'],
    ]);
  ?>
  <div class="card">
    <div class="card__header">
      <div>
        <h2 class="card__title">Reporte 4: Inventario Actual y Productos Bajo Stock Mínimo</h2>
        <span class="card__subtitle">Nivel de existencias respecto al umbral mínimo configurado</span>
      </div>
    </div>
    <div class="card__body">
      <div class="table-responsive">
        <table class="data-table data-table--orders">
          <thead>
            <tr>
              <th>Ingrediente</th>
              <th class="num-cell">Existencia</th>
              <th class="num-cell">Mínimo</th>
              <th>Nivel Visual</th>
              <th class="num-cell">Diferencia</th>
              <th>Estado de Alerta</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($invReport as $inv): ?>
              <?php
                $stock = (float)$inv['cantidad_stock'];
                $min   = (float)$inv['stock_minimo'];
                $diff  = (float)$inv['diferencia'];
                $isLow = (int)$inv['bajo_stock'] === 1;
                $ratio = $min > 0 ? min(100, round(($stock / ($min * 2)) * 100)) : 100;
                $meterTone = $isLow ? 'danger' : ($stock <= $min * 1.4 ? 'warning' : 'success');
              ?>
              <tr>
                <td>
                  <div class="cell-stack">
                    <span class="cell-stack__main"><?= e($inv['nombre']) ?></span>
                    <span class="cell-stack__sub">Unidad: <?= e($inv['unidad_medida']) ?></span>
                  </div>
                </td>
                <td class="num-cell"><strong><?= number_format($stock, 2) ?></strong></td>
                <td class="num-cell"><?= number_format($min, 2) ?></td>
                <td>
                  <div class="stock-meter">
                    <div class="stock-meter__fill stock-meter__fill--<?= $meterTone ?>" style="width: <?= $ratio ?>%;"></div>
                  </div>
                </td>
                <td class="num-cell">
                  <strong class="<?= $diff <= 0 ? 'text-danger-strong' : 'text-success-strong' ?>">
                    <?= ($diff > 0 ? '+' : '') . number_format($diff, 2) ?>
                  </strong>
                </td>
                <td>
                  <?php if ($isLow): ?>
                    <span class="badge badge--danger">Requiere Reabastecimiento</span>
                  <?php else: ?>
                    <span class="badge badge--success">Existencia Suficiente</span>
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
  <?php
    $history = getReportOrdersHistory(null, $repFrom, $repTo);
    $paidCount = 0;
    $paidSum = 0.0;
    $activeCount = 0;
    $cancelledCount = 0;
    foreach ($history as $h) {
        if ($h['estado'] === 'pagado') {
            $paidCount++;
            $paidSum += (float)$h['total'];
        } elseif ($h['estado'] === 'cancelado') {
            $cancelledCount++;
        } else {
            $activeCount++;
        }
    }

    renderReportSummary([
        ['label' => 'Total de Pedidos', 'value' => (string)count($history), 'sub' => $activeCount . ' en curso · ' . $cancelledCount . ' cancelados', 'tone' => 'info'],
        ['label' => 'Pedidos Pagados', 'value' => (string)$paidCount, 'sub' => 'Órdenes cerradas con cobro', 'tone' => 'success'],
        ['label' => 'Monto Cobrado', 'value' => '$' . number_format($paidSum, 2), 'sub' => 'Suma de pedidos pagados', 'tone' => 'accent'],
    ]);
  ?>
  <div class="card">
    <div class="card__header">
      <div>
        <h2 class="card__title">Reporte 5: Historial Completo de Pedidos</h2>
        <span class="card__subtitle">Registro cronológico de órdenes y acceso directo al detalle</span>
      </div>
    </div>
    <div class="card__body">
      <div class="table-responsive">
        <table class="data-table data-table--orders">
          <thead>
            <tr>
              <th>Folio</th>
              <th>Cliente y Fecha</th>
              <th>Modalidad</th>
              <th>Estado</th>
              <th class="num-cell">Total</th>
              <th class="num-cell">Acción</th>
            </tr>
          </thead>
          <tbody>
            <?php if (empty($history)): ?>
              <tr><td colspan="6">No se encontraron pedidos en el rango seleccionado.</td></tr>
            <?php else: ?>
              <?php foreach ($history as $h): ?>
                <?php $isClosed = in_array($h['estado'], ['pagado', 'cancelado'], true); ?>
                <tr class="<?= $isClosed ? 'order-row--closed' : '' ?>">
                  <td><a href="../orders/orders.php?order_id=<?= (int)$h['id_pedido'] ?>" class="folio-pill">#<?= (int)$h['id_pedido'] ?></a></td>
                  <td>
                    <div class="cell-stack">
                      <span class="cell-stack__main"><?= e($h['usuario_nombre']) ?></span>
                      <span class="cell-stack__sub"><?= e(date('d/m/Y · H:i', strtotime($h['fecha_pedido']))) ?></span>
                    </div>
                  </td>
                  <td>
                    <?php if ($h['numero_mesa']): ?>
                      <span class="place-pill place-pill--table">Mesa <?= str_pad((string)(int)$h['numero_mesa'], 2, '0', STR_PAD_LEFT) ?></span>
                    <?php else: ?>
                      <span class="place-pill place-pill--takeout">Para Llevar</span>
                    <?php endif; ?>
                  </td>
                  <td><span class="badge <?= getStatusBadgeClass($h['estado']) ?>"><?= e($h['estado']) ?></span></td>
                  <td class="num-cell"><strong>$<?= number_format((float)$h['total'], 2) ?></strong></td>
                  <td class="num-cell"><a href="../orders/orders.php?order_id=<?= (int)$h['id_pedido'] ?>" class="btn btn--outline btn--sm">Detalle</a></td>
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
