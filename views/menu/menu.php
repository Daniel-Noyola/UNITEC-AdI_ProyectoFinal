<?php
require_once __DIR__ . '/../../helpers/auth.php';
require_once __DIR__ . '/../../helpers/funciones.php';
require_once __DIR__ . '/../../helpers/layout.php';
require_once __DIR__ . '/../../db/platillos/acciones.php';
require_once __DIR__ . '/../../db/mesas/acciones.php';
require_once __DIR__ . '/../../db/pedidos/acciones.php';

requireLogin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $currentUser = getCurrentUser();
        $tableId = !empty($_POST['id_mesa']) ? (int)$_POST['id_mesa'] : null;
        $items = json_decode((string)($_POST['items_json'] ?? '[]'), true);

        if (!is_array($items) || empty($items)) {
            setFlash('danger', 'Debes seleccionar al menos un platillo.');
            header('Location: menu.php');
            exit;
        }

        $orderId = createOrder((int)$currentUser['id_usuario'], $tableId, $items);
        setFlash('success', "¡Pedido #$orderId registrado exitosamente!");
        header('Location: ../orders/orders.php?order_id=' . $orderId);
        exit;
    } catch (Exception $e) {
        setFlash('danger', $e->getMessage());
        header('Location: menu.php');
        exit;
    }
}

$selectedCategory = $_GET['categoria'] ?? null;
$dishes     = getDishes(true, $selectedCategory);
$categories = getCategories();
$tables     = getTables();

renderHeader('Menú del Restaurante', 'menu');
?>

<div class="segmented-tabs">
  <a href="menu.php" class="segmented-tabs__item <?= empty($selectedCategory) ? 'segmented-tabs__item--active' : '' ?>">
    Todos los Platillos
  </a>
  <?php foreach ($categories as $cat): ?>
    <a href="menu.php?categoria=<?= urlencode($cat) ?>" class="segmented-tabs__item <?= $selectedCategory === $cat ? 'segmented-tabs__item--active' : '' ?>">
      <?= e($cat) ?>
    </a>
  <?php endforeach; ?>
</div>

<div class="order-builder">
  <div class="order-builder__menu">
    <?php if (empty($dishes)): ?>
      <div class="card">
        <div class="empty-state">
          <div class="empty-state__icon"><?= renderIcon('menu') ?></div>
          <h3 class="empty-state__title">No hay platillos disponibles</h3>
          <p class="empty-state__desc">No se encontraron platillos activos en esta categoría.</p>
        </div>
      </div>
    <?php else: ?>
      <div class="dish-grid">
        <?php foreach ($dishes as $dish): ?>
          <div class="dish-card">
            <div class="dish-card__media">
              <?= renderDishImage($dish['imagen'], $dish['nombre']) ?>
              <span class="dish-card__category-pill"><?= e($dish['categoria']) ?></span>
            </div>

            <div class="dish-card__body">
              <div class="dish-card__header-row">
                <h3 class="dish-card__title"><?= e($dish['nombre']) ?></h3>
                <span class="dish-card__price">$<?= number_format((float)$dish['precio'], 2) ?></span>
              </div>
              <p class="dish-card__desc"><?= e($dish['descripcion'] ?? 'Delicioso platillo preparado al momento.') ?></p>
            </div>

            <div class="dish-card__footer">
              <button type="button"
                      class="btn btn--accent btn--sm btn--block"
                      data-add-to-cart
                      data-id="<?= (int)$dish['id_platillo'] ?>"
                      data-name="<?= e($dish['nombre']) ?>"
                      data-price="<?= (float)$dish['precio'] ?>">
                <?= renderIcon('plus') ?>
                <span>Agregar al Pedido</span>
              </button>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </div>

  <aside class="order-builder__cart">
    <div class="card order-ticket">
      <div class="card__header order-ticket__header">
        <div class="order-ticket__title-group">
          <span class="order-ticket__icon"><?= renderIcon('cart') ?></span>
          <div>
            <h2 class="card__title">Tu Comanda</h2>
            <span class="card__subtitle">Resumen de orden actual</span>
          </div>
        </div>
      </div>
      <div class="card__body">
        <form action="menu.php" method="POST">
          <input type="hidden" name="items_json" id="cartItemsJson" value="[]">

          <div class="form-group">
            <label for="id_mesa" class="form-label">Asignar Mesa (opcional)</label>
            <select name="id_mesa" id="id_mesa" class="form-control">
              <option value="">Para Llevar / Sin Mesa</option>
              <?php foreach ($tables as $t): ?>
                <?php if ($t['estado'] !== 'inactiva'): ?>
                  <option value="<?= (int)$t['id_mesa'] ?>" <?= $t['estado'] === 'ocupada' ? 'disabled' : '' ?>>
                    Mesa <?= (int)$t['numero_mesa'] ?> (<?= $t['estado'] === 'ocupada' ? 'Ocupada' : 'Cap: ' . (int)$t['capacidad'] . ' pers.' ?>)
                  </option>
                <?php endif; ?>
              <?php endforeach; ?>
            </select>
          </div>

          <div id="cartItemsContainer">
            <div class="empty-state">
              <p class="empty-state__desc">Aún no has agregado platillos a tu pedido.</p>
            </div>
          </div>

          <div class="cart-summary">
            <span class="cart-summary__label">Total a Pagar:</span>
            <span id="cartTotalDisplay" class="cart-summary__total">$0.00</span>
          </div>

          <button type="submit" id="cartSubmitBtn" class="btn btn--accent btn--block" disabled>
            Confirmar y Enviar Pedido
          </button>
        </form>
      </div>
    </div>
  </aside>
</div>

<!-- JavaScript mínimo para armar el carrito en pantalla antes de enviarlo por POST -->
<script>
  document.addEventListener('DOMContentLoaded', () => {
    const container = document.getElementById('cartItemsContainer');
    const totalEl   = document.getElementById('cartTotalDisplay');
    const inputJson = document.getElementById('cartItemsJson');
    const submitBtn = document.getElementById('cartSubmitBtn');
    const cart = {};

    function renderCart() {
      const items = Object.values(cart);
      container.innerHTML = '';

      if (items.length === 0) {
        container.innerHTML = '<div class="empty-state"><p class="empty-state__desc">Aún no has agregado platillos a tu pedido.</p></div>';
        totalEl.textContent = '$0.00';
        inputJson.value = '[]';
        submitBtn.disabled = true;
        return;
      }

      let total = 0;
      const payload = [];

      items.forEach((item) => {
        const subtotal = item.price * item.qty;
        total += subtotal;
        payload.push({ id_platillo: item.id, cantidad: item.qty });

        const row = document.createElement('div');
        row.className = 'cart-item';
        row.innerHTML = `
          <div>
            <div class="cart-item__title"></div>
            <div class="cart-item__price">$${item.price.toFixed(2)} c/u &bull; <strong>$${subtotal.toFixed(2)}</strong></div>
          </div>
          <div class="cart-item__controls">
            <button type="button" class="cart-item__qty-btn" data-op="dec" data-id="${item.id}">&minus;</button>
            <span><strong>${item.qty}</strong></span>
            <button type="button" class="cart-item__qty-btn" data-op="inc" data-id="${item.id}">&#43;</button>
            <button type="button" class="cart-item__qty-btn" data-op="del" data-id="${item.id}">&times;</button>
          </div>
        `;
        row.querySelector('.cart-item__title').textContent = item.name;
        container.appendChild(row);
      });

      totalEl.textContent = `$${total.toFixed(2)}`;
      inputJson.value = JSON.stringify(payload);
      submitBtn.disabled = false;
    }

    document.querySelectorAll('[data-add-to-cart]').forEach((btn) => {
      btn.addEventListener('click', () => {
        const id = parseInt(btn.dataset.id, 10);
        if (cart[id]) {
          cart[id].qty += 1;
        } else {
          cart[id] = { id, name: btn.dataset.name, price: parseFloat(btn.dataset.price), qty: 1 };
        }
        renderCart();
      });
    });

    container.addEventListener('click', (e) => {
      const btn = e.target.closest('[data-op]');
      if (!btn) return;
      const id = parseInt(btn.dataset.id, 10);
      if (!cart[id]) return;

      if (btn.dataset.op === 'inc') cart[id].qty += 1;
      if (btn.dataset.op === 'dec') cart[id].qty -= 1;
      if (btn.dataset.op === 'del' || cart[id].qty <= 0) delete cart[id];
      renderCart();
    });
  });
</script>

<?php renderFooter(); ?>
