<?php
require_once __DIR__ . '/../../helpers/auth.php';
require_once __DIR__ . '/../../helpers/funciones.php';
require_once __DIR__ . '/../../helpers/layout.php';
require_once __DIR__ . '/../../db/pedidos/acciones.php';

requireLogin();

$currentUser = getCurrentUser();
$isAdminUser = isAdmin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $orderId   = (int)($_POST['id_pedido'] ?? 0);
        $newStatus = trim($_POST['nuevo_estado'] ?? '');
        $order     = getOrderById($orderId);

        if (!$order) {
            setFlash('danger', 'El pedido no existe.');
        } elseif (!$isAdminUser && (int)$order['id_usuario'] !== (int)$currentUser['id_usuario']) {
            setFlash('danger', 'No tienes permisos para modificar este pedido.');
        } elseif (!$isAdminUser && !in_array($newStatus, ['cancelado', 'pagado'], true)) {
            setFlash('danger', 'Acción no permitida para clientes.');
        } elseif (!$isAdminUser && $newStatus === 'cancelado' && $order['estado'] !== 'pendiente') {
            setFlash('danger', 'Solo puedes cancelar pedidos que aún estén pendientes.');
        } elseif ($isAdminUser && $newStatus === 'pagado') {
            setFlash('danger', 'El pago del pedido debe realizarlo el cliente.');
        } else {
            updateOrderStatus($orderId, $newStatus);
            setFlash('success', "El estado del pedido #$orderId cambió a \"$newStatus\".");
        }
    } catch (Exception $e) {
        setFlash('danger', $e->getMessage());
    }

    header('Location: orders.php');
    exit;
}

$selectedOrderId = !empty($_GET['order_id']) ? (int)$_GET['order_id'] : null;
$statusFilter    = $_GET['estado'] ?? null;
$ordersList      = $isAdminUser ? getAllOrders($statusFilter) : getUserOrders((int)$currentUser['id_usuario']);

$activeOrder = null;
$canViewDetail = false;
$orderDetails = [];
if ($selectedOrderId) {
    $activeOrder = getOrderById($selectedOrderId);
    $canViewDetail = $activeOrder && ($isAdminUser || (int)$activeOrder['id_usuario'] === (int)$currentUser['id_usuario']);
    if ($canViewDetail) {
        $orderDetails = getOrderDetails($selectedOrderId);
    }
}

renderHeader('Historial y Seguimiento de Pedidos', 'orders');
?>

<?php if ($isAdminUser): ?>
  <div class="segmented-tabs">
    <a href="orders.php" class="segmented-tabs__item <?= empty($statusFilter) ? 'segmented-tabs__item--active' : '' ?>">Todos</a>
    <?php foreach (['pendiente', 'preparando', 'entregado', 'pagado', 'cancelado'] as $st): ?>
      <a href="orders.php?estado=<?= urlencode($st) ?>" class="segmented-tabs__item <?= $statusFilter === $st ? 'segmented-tabs__item--active' : '' ?>">
        <?= ucfirst($st) ?>
      </a>
    <?php endforeach; ?>
  </div>
<?php endif; ?>

<?php if ($canViewDetail): ?>
  <section class="card order-detail-card" aria-label="Detalle del pedido seleccionado">
    <div class="card__header order-detail-card__header">
      <div>
        <span class="order-detail-card__eyebrow">Detalle del Pedido Seleccionado</span>
        <div class="order-detail-card__title-row">
          <h2 class="card__title">Pedido #<?= (int)$activeOrder['id_pedido'] ?></h2>
          <span class="badge <?= getStatusBadgeClass($activeOrder['estado']) ?>"><?= e(ucfirst($activeOrder['estado'])) ?></span>
        </div>
      </div>
      <a href="orders.php<?= $statusFilter ? '?estado=' . urlencode($statusFilter) : '' ?>" class="btn btn--outline btn--sm">
        <?= renderIcon('close') ?>
        <span>Cerrar Detalle</span>
      </a>
    </div>
    <div class="card__body">
      <div class="order-detail-summary">
        <div class="order-detail-summary__item">
          <span class="order-detail-summary__label">Cliente</span>
          <strong class="order-detail-summary__value"><?= e($activeOrder['usuario_nombre']) ?></strong>
        </div>
        <div class="order-detail-summary__item">
          <span class="order-detail-summary__label">Ubicación / Modalidad</span>
          <strong class="order-detail-summary__value">
            <?= $activeOrder['numero_mesa'] ? 'Mesa ' . (int)$activeOrder['numero_mesa'] : 'Para Llevar' ?>
          </strong>
        </div>
        <div class="order-detail-summary__item">
          <span class="order-detail-summary__label">Fecha de Solicitud</span>
          <strong class="order-detail-summary__value"><?= e($activeOrder['fecha_pedido']) ?></strong>
        </div>
        <div class="order-detail-summary__item order-detail-summary__item--total">
          <span class="order-detail-summary__label">Monto Final del Pedido</span>
          <strong class="order-detail-summary__total">$<?= number_format((float)$activeOrder['total'], 2) ?></strong>
        </div>
      </div>
      <?php
        $steps = ['pendiente' => 'Pendiente', 'preparando' => 'Preparando', 'entregado' => 'Entregado', 'pagado' => 'Pagado'];
        $stepKeys = array_keys($steps);
        $currentStepIdx = array_search($activeOrder['estado'], $stepKeys, true);
      ?>
      <?php if ($activeOrder['estado'] === 'cancelado'): ?>
        <div class="alert alert--danger">
          <span><?= renderIcon('alert') ?> Este pedido fue cancelado.</span>
        </div>
      <?php else: ?>
        <div class="order-stepper">
          <?php $isOrderPaid = ($activeOrder['estado'] === 'pagado'); ?>
          <?php foreach ($stepKeys as $idx => $key): ?>
            <?php
              $isDone = $currentStepIdx !== false && ($isOrderPaid ? $idx <= $currentStepIdx : $idx < $currentStepIdx);
              $isActive = !$isOrderPaid && $currentStepIdx !== false && $idx === $currentStepIdx;
            ?>
            <div class="order-stepper__step <?= $isDone ? 'order-stepper__step--done' : '' ?> <?= $isActive ? 'order-stepper__step--active' : '' ?>">
              <span class="order-stepper__dot"><?= $isDone ? renderIcon('check') : ($idx + 1) ?></span>
              <span class="order-stepper__label"><?= e($steps[$key]) ?></span>
            </div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>

      <div class="table-responsive">
        <table class="data-table">
          <thead>
            <tr>
              <th>Platillo</th>
              <th>Categoría</th>
              <th>Cantidad</th>
              <th>Precio Unitario</th>
              <th>Subtotal</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($orderDetails as $item): ?>
              <tr>
                <td><strong><?= e($item['platillo_nombre']) ?></strong></td>
                <td><span class="badge badge--info"><?= e($item['categoria']) ?></span></td>
                <td class="num-cell"><span class="badge badge--neutral"><?= (int)$item['cantidad'] ?> pza(s)</span></td>
                <td class="num-cell">$<?= number_format((float)$item['precio_unitario'], 2) ?></td>
                <td class="num-cell"><strong>$<?= number_format((float)$item['subtotal'], 2) ?></strong></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
          <tfoot>
            <tr class="order-detail-total-row">
              <td colspan="4"><strong>Total Final del Pedido</strong></td>
              <td class="num-cell"><strong class="order-detail-total-row__amount">$<?= number_format((float)$activeOrder['total'], 2) ?></strong></td>
            </tr>
          </tfoot>
        </table>
      </div>
    </div>
  </section>
<?php endif; ?>

<div class="card">
  <div class="card__header">
    <div>
      <h2 class="card__title">
        <?= $isAdminUser ? 'Registro Completo de Pedidos' : 'Mis Pedidos Solicitados' ?>
      </h2>
      <span class="card__subtitle">Seguimiento de estado y detalle de consumo</span>
    </div>
  </div>
  <div class="card__body">
    <?php if (empty($ordersList)): ?>
      <div class="empty-state">
        <div class="empty-state__icon"><?= renderIcon('orders') ?></div>
        <h3 class="empty-state__title">No hay pedidos para mostrar</h3>
        <p class="empty-state__desc">Aún no se han generado pedidos con este filtro.</p>
      </div>
    <?php else: ?>
      <div class="table-responsive">
        <table class="data-table data-table--orders">
          <thead>
            <tr>
              <th>Folio</th>
              <th><?= $isAdminUser ? 'Cliente y Fecha' : 'Fecha de Solicitud' ?></th>
              <th>Modalidad</th>
              <th>Estado</th>
              <th class="num-cell">Total</th>
              <th class="num-cell">Acciones</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($ordersList as $ord): ?>
              <?php
                $isRowSelected = $canViewDetail && (int)$ord['id_pedido'] === (int)$activeOrder['id_pedido'];
                $isClosedOrder = in_array($ord['estado'], ['pagado', 'cancelado'], true);
                $detailUrl = 'orders.php?order_id=' . (int)$ord['id_pedido'] . ($statusFilter ? '&estado=' . urlencode($statusFilter) : '');
                $formattedDate = date('d/m/Y · H:i', strtotime($ord['fecha_pedido']));
              ?>
              <tr class="<?= $isRowSelected ? 'order-row--selected' : '' ?> <?= $isClosedOrder ? 'order-row--closed' : '' ?>">
                <td>
                  <a href="<?= e($detailUrl) ?>" class="folio-pill" title="Ver detalle del pedido #<?= (int)$ord['id_pedido'] ?>">
                    #<?= (int)$ord['id_pedido'] ?>
                  </a>
                </td>
                <td>
                  <div class="cell-stack">
                    <?php if ($isAdminUser): ?>
                      <span class="cell-stack__main"><?= e($ord['usuario_nombre']) ?></span>
                      <span class="cell-stack__sub"><?= e($formattedDate) ?></span>
                    <?php else: ?>
                      <span class="cell-stack__main"><?= e($formattedDate) ?></span>
                    <?php endif; ?>
                  </div>
                </td>
                <td>
                  <?php if ($ord['numero_mesa']): ?>
                    <span class="place-pill place-pill--table">Mesa <?= str_pad((string)(int)$ord['numero_mesa'], 2, '0', STR_PAD_LEFT) ?></span>
                  <?php else: ?>
                    <span class="place-pill place-pill--takeout">Para Llevar</span>
                  <?php endif; ?>
                </td>
                <td><span class="badge <?= getStatusBadgeClass($ord['estado']) ?>"><?= e($ord['estado']) ?></span></td>
                <td class="num-cell"><strong>$<?= number_format((float)$ord['total'], 2) ?></strong></td>
                <td>
                  <div class="actions-clean">
                    <a href="<?= e($detailUrl) ?>" class="btn <?= $isRowSelected ? 'btn--accent' : 'btn--outline' ?> btn--sm">
                      <?= $isRowSelected ? 'Viendo' : 'Detalle' ?>
                    </a>

                    <?php if ($isAdminUser): ?>
                      <?php if ($ord['estado'] === 'pendiente'): ?>
                        <form action="orders.php" method="POST">
                          <input type="hidden" name="id_pedido" value="<?= (int)$ord['id_pedido'] ?>">
                          <input type="hidden" name="nuevo_estado" value="preparando">
                          <button type="submit" class="btn btn--primary btn--sm"><?= renderIcon('dishes') ?><span>Preparar</span></button>
                        </form>
                      <?php elseif ($ord['estado'] === 'preparando'): ?>
                        <form action="orders.php" method="POST">
                          <input type="hidden" name="id_pedido" value="<?= (int)$ord['id_pedido'] ?>">
                          <input type="hidden" name="nuevo_estado" value="entregado">
                          <button type="submit" class="btn btn--primary btn--sm"><?= renderIcon('check') ?><span>Entregar</span></button>
                        </form>
                      <?php endif; ?>

                      <?php if (in_array($ord['estado'], ['pendiente', 'preparando', 'entregado'], true)): ?>
                        <form action="orders.php" method="POST" data-confirm="¿Cancelar este pedido? Los ingredientes serán devueltos al almacén.">
                          <input type="hidden" name="id_pedido" value="<?= (int)$ord['id_pedido'] ?>">
                          <input type="hidden" name="nuevo_estado" value="cancelado">
                          <button type="submit" class="btn-icon-danger" title="Cancelar pedido" aria-label="Cancelar pedido #<?= (int)$ord['id_pedido'] ?>">&times;</button>
                        </form>
                      <?php endif; ?>
                    <?php elseif ($ord['estado'] === 'pendiente'): ?>
                      <form action="orders.php" method="POST" data-confirm="¿Seguro que deseas cancelar tu pedido?">
                        <input type="hidden" name="id_pedido" value="<?= (int)$ord['id_pedido'] ?>">
                        <input type="hidden" name="nuevo_estado" value="cancelado">
                        <button type="submit" class="btn-icon-danger" title="Cancelar pedido" aria-label="Cancelar pedido #<?= (int)$ord['id_pedido'] ?>">&times;</button>
                      </form>
                    <?php elseif ($ord['estado'] === 'entregado'): ?>
                      <form action="orders.php" method="POST" data-confirm="¿Confirmar el pago del pedido #<?= (int)$ord['id_pedido'] ?> por $<?= number_format((float)$ord['total'], 2) ?>?">
                        <input type="hidden" name="id_pedido" value="<?= (int)$ord['id_pedido'] ?>">
                        <input type="hidden" name="nuevo_estado" value="pagado">
                        <button type="submit" class="btn btn--accent btn--sm"><?= renderIcon('money') ?><span>Pagar Pedido</span></button>
                      </form>
                    <?php endif; ?>
                  </div>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    <?php endif; ?>
  </div>
</div>

<?php renderFooter(); ?>
