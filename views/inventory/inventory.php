<?php
require_once __DIR__ . '/../../helpers/auth.php';
require_once __DIR__ . '/../../helpers/funciones.php';
require_once __DIR__ . '/../../helpers/layout.php';
require_once __DIR__ . '/../../db/ingredientes/acciones.php';

requireAdmin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $ingId    = !empty($_POST['id_ingrediente']) ? (int)$_POST['id_ingrediente'] : null;
        $name     = trim($_POST['nombre'] ?? '');
        $stock    = (float)($_POST['cantidad_stock'] ?? 0);
        $unit     = trim($_POST['unidad_medida'] ?? 'kg');
        $minStock = (float)($_POST['stock_minimo'] ?? 0);

        if ($name === '' || $unit === '') {
            setFlash('danger', 'El nombre y la unidad de medida son obligatorios.');
        } else {
            saveIngredient($ingId, $name, $stock, $unit, $minStock);
            setFlash('success', 'Ingrediente e inventario actualizados con éxito.');
        }
    } catch (Exception $e) {
        setFlash('danger', $e->getMessage());
    }

    header('Location: inventory.php');
    exit;
}

$ingredients = getIngredients();

renderHeader('Control de Inventario e Ingredientes', 'inventory');
?>

<div class="card">
  <div class="card__header">
    <div>
      <h2 class="card__title">Existencias de Ingredientes en Almacén</h2>
      <span class="card__subtitle">Monitoreo de nivel de stock frente al mínimo operativo</span>
    </div>
    <button type="button" class="btn btn--accent btn--sm" data-modal-target="modalAddIngredient">
      <?= renderIcon('plus') ?>
      <span>Nuevo Ingrediente</span>
    </button>
  </div>
  <div class="card__body">
    <div class="table-responsive">
      <table class="data-table">
        <thead>
          <tr>
            <th>ID</th>
            <th>Ingrediente</th>
            <th>Nivel de Stock</th>
            <th>Unidad</th>
            <th>Stock Mínimo</th>
            <th>Estado</th>
            <th>Acción</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($ingredients as $ing): ?>
            <?php
              $stockVal = (float)$ing['cantidad_stock'];
              $minVal   = (float)$ing['stock_minimo'];
              $isLow    = $stockVal <= $minVal;
              $isWarn   = !$isLow && $minVal > 0 && $stockVal <= ($minVal * 1.5);
              $pct      = $minVal > 0 ? min(100, max(6, round(($stockVal / ($minVal * 2.5)) * 100))) : 100;
              $meterMod = $isLow ? 'stock-meter__fill--danger' : ($isWarn ? 'stock-meter__fill--warning' : 'stock-meter__fill--success');
            ?>
            <tr>
              <td class="num-cell">#<?= (int)$ing['id_ingrediente'] ?></td>
              <td><strong><?= e($ing['nombre']) ?></strong></td>
              <td>
                <div class="stock-cell">
                  <strong class="num-cell"><?= number_format($stockVal, 2) ?></strong>
                  <div class="stock-meter" title="Nivel respecto al mínimo">
                    <div class="stock-meter__fill <?= $meterMod ?>" style="width: <?= $pct ?>%"></div>
                  </div>
                </div>
              </td>
              <td><?= e($ing['unidad_medida']) ?></td>
              <td class="num-cell"><?= number_format($minVal, 2) ?></td>
              <td>
                <?php if ($isLow): ?>
                  <span class="badge badge--danger"><?= renderIcon('alert') ?> Bajo Stock Mínimo</span>
                <?php elseif ($isWarn): ?>
                  <span class="badge badge--warning">Próximo al Mínimo</span>
                <?php else: ?>
                  <span class="badge badge--success">Stock Óptimo</span>
                <?php endif; ?>
              </td>
              <td>
                <button type="button"
                        class="btn btn--outline btn--sm"
                        data-modal-target="modalEditIngredient"
                        data-id="<?= (int)$ing['id_ingrediente'] ?>"
                        data-nombre="<?= e($ing['nombre']) ?>"
                        data-stock="<?= (float)$ing['cantidad_stock'] ?>"
                        data-unidad="<?= e($ing['unidad_medida']) ?>"
                        data-minimo="<?= (float)$ing['stock_minimo'] ?>">
                  Actualizar Existencias
                </button>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<div class="modal-overlay" id="modalEditIngredient">
  <div class="modal">
    <div class="modal__header">
      <h3 class="modal__title" id="editModalTitle">Ajustar Ingrediente</h3>
      <button type="button" class="modal__close" data-modal-close>&times;</button>
    </div>
    <form action="inventory.php" method="POST">
      <input type="hidden" name="id_ingrediente" id="edit_ing_id" value="">
      <div class="modal__body">
        <div class="form-group">
          <label for="edit_ing_nombre" class="form-label">Nombre</label>
          <input type="text" id="edit_ing_nombre" name="nombre" class="form-control" required>
        </div>
        <div class="form-row">
          <div class="form-group">
            <label for="edit_ing_stock" class="form-label">Cantidad en Stock</label>
            <input type="number" step="0.01" min="0" id="edit_ing_stock" name="cantidad_stock" class="form-control" required>
          </div>
          <div class="form-group">
            <label for="edit_ing_unidad" class="form-label">Unidad de Medida</label>
            <select id="edit_ing_unidad" name="unidad_medida" class="form-control" required>
              <?php foreach (INGREDIENT_UNITS as $unitCode => $unitLabel): ?>
                <option value="<?= e($unitCode) ?>"><?= e($unitLabel) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>
        <div class="form-group">
          <label for="edit_ing_minimo" class="form-label">Stock Mínimo</label>
          <input type="number" step="0.01" min="0" id="edit_ing_minimo" name="stock_minimo" class="form-control" required>
        </div>
      </div>
      <div class="card__footer">
        <button type="button" class="btn btn--outline" data-modal-close>Cancelar</button>
        <button type="submit" class="btn btn--accent">Guardar Cambios</button>
      </div>
    </form>
  </div>
</div>

<div class="modal-overlay" id="modalAddIngredient">
  <div class="modal">
    <div class="modal__header">
      <h3 class="modal__title">Registrar Nuevo Ingrediente</h3>
      <button type="button" class="modal__close" data-modal-close>&times;</button>
    </div>
    <form action="inventory.php" method="POST">
      <div class="modal__body">
        <div class="form-group">
          <label for="new_ing_nom" class="form-label">Nombre del Ingrediente</label>
          <input type="text" id="new_ing_nom" name="nombre" class="form-control" placeholder="Ej. Queso Mozzarella" required>
        </div>
        <div class="form-row">
          <div class="form-group">
            <label for="new_ing_stock" class="form-label">Cantidad Inicial</label>
            <input type="number" step="0.01" min="0" id="new_ing_stock" name="cantidad_stock" class="form-control" value="0.00" required>
          </div>
          <div class="form-group">
            <label for="new_ing_unit" class="form-label">Unidad de Medida</label>
            <select id="new_ing_unit" name="unidad_medida" class="form-control" required>
              <?php foreach (INGREDIENT_UNITS as $unitCode => $unitLabel): ?>
                <option value="<?= e($unitCode) ?>"><?= e($unitLabel) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>
        <div class="form-group">
          <label for="new_ing_min" class="form-label">Stock Mínimo de Alerta</label>
          <input type="number" step="0.01" min="0" id="new_ing_min" name="stock_minimo" class="form-control" value="5.00" required>
        </div>
      </div>
      <div class="card__footer">
        <button type="button" class="btn btn--outline" data-modal-close>Cancelar</button>
        <button type="submit" class="btn btn--accent">Guardar Ingrediente</button>
      </div>
    </form>
  </div>
</div>

<script>
  document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('[data-modal-target="modalEditIngredient"]').forEach((btn) => {
      btn.addEventListener('click', () => {
        document.getElementById('editModalTitle').textContent = `Ajustar: ${btn.dataset.nombre || ''}`;
        document.getElementById('edit_ing_id').value     = btn.dataset.id || '';
        document.getElementById('edit_ing_nombre').value = btn.dataset.nombre || '';
        document.getElementById('edit_ing_stock').value  = btn.dataset.stock || '0';
        document.getElementById('edit_ing_unidad').value = btn.dataset.unidad || '';
        document.getElementById('edit_ing_minimo').value = btn.dataset.minimo || '0';
      });
    });
  });
</script>

<?php renderFooter(); ?>
