<?php
require_once __DIR__ . '/../../helpers/auth.php';
require_once __DIR__ . '/../../helpers/funciones.php';
require_once __DIR__ . '/../../helpers/layout.php';
require_once __DIR__ . '/../../db/mesas/acciones.php';

requireAdmin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $action = $_POST['action'] ?? '';

        if ($action === 'save_table') {
            $number   = (int)($_POST['numero_mesa'] ?? 0);
            $capacity = (int)($_POST['capacidad'] ?? 4);
            $status   = trim($_POST['estado'] ?? 'disponible');

            if ($number <= 0 || $capacity <= 0) {
                setFlash('danger', 'El número de mesa y la capacidad deben ser mayores a 0.');
            } else {
                saveTable(null, $number, $capacity, $status);
                setFlash('success', 'Mesa guardada correctamente.');
            }
        } elseif ($action === 'toggle_table') {
            $tableId = (int)($_POST['id_mesa'] ?? 0);
            if ($tableId > 0) {
                toggleTableStatus($tableId);
                setFlash('success', 'Estado de la mesa actualizado.');
            }
        }
    } catch (Exception $e) {
        setFlash('danger', $e->getMessage());
    }

    header('Location: tables.php');
    exit;
}

$tables = getTables();

renderHeader('Gestión de Mesas', 'tables');
?>

<div class="card">
  <div class="card__header">
    <div>
      <h2 class="card__title">Distribución y Estado de Mesas</h2>
      <span class="card__subtitle">Vista rápida de disponibilidad y capacidad en salón</span>
    </div>
    <button type="button" class="btn btn--accent btn--sm" data-modal-target="modalAddTable">
      <?= renderIcon('plus') ?>
      <span>Nueva Mesa</span>
    </button>
  </div>
  <div class="card__body">
    <div class="table-grid">
      <?php foreach ($tables as $table): ?>
        <div class="table-card table-card--<?= e($table['estado']) ?>">
          <div class="table-card__header">
            <div class="table-card__identity">
              <span class="table-card__num-pill"><?= str_pad((string)(int)$table['numero_mesa'], 2, '0', STR_PAD_LEFT) ?></span>
              <span class="table-card__number">Mesa <?= (int)$table['numero_mesa'] ?></span>
            </div>
            <span class="badge <?= getStatusBadgeClass($table['estado']) ?>">
              <?= e($table['estado']) ?>
            </span>
          </div>
          <div class="table-card__capacity">
            <?= renderIcon('users') ?>
            <span>Capacidad: <strong><?= (int)$table['capacidad'] ?></strong> comensales</span>
          </div>
          <form action="tables.php" method="POST" class="table-card__actions">
            <input type="hidden" name="action" value="toggle_table">
            <input type="hidden" name="id_mesa" value="<?= (int)$table['id_mesa'] ?>">
            <button type="submit" class="btn btn--outline btn--sm btn--block">
              <?= $table['estado'] === 'inactiva' ? 'Habilitar' : 'Desactivar' ?>
            </button>
          </form>
        </div>
      <?php endforeach; ?>
    </div>
  </div>
</div>

<div class="modal-overlay" id="modalAddTable">
  <div class="modal">
    <div class="modal__header">
      <h3 class="modal__title">Agregar Nueva Mesa</h3>
      <button type="button" class="modal__close" data-modal-close>&times;</button>
    </div>
    <form action="tables.php" method="POST">
      <input type="hidden" name="action" value="save_table">
      <div class="modal__body">
        <div class="form-group">
          <label for="table_num" class="form-label">Número de Mesa</label>
          <input type="number" id="table_num" name="numero_mesa" min="1" class="form-control" required>
        </div>
        <div class="form-group">
          <label for="table_cap" class="form-label">Capacidad de Personas</label>
          <input type="number" id="table_cap" name="capacidad" min="1" max="50" value="4" class="form-control" required>
        </div>
        <div class="form-group">
          <label for="table_status" class="form-label">Estado Inicial</label>
          <select id="table_status" name="estado" class="form-control">
            <option value="disponible">Disponible</option>
            <option value="inactiva">Inactiva</option>
          </select>
        </div>
      </div>
      <div class="card__footer">
        <button type="button" class="btn btn--outline" data-modal-close>Cancelar</button>
        <button type="submit" class="btn btn--accent">Guardar Mesa</button>
      </div>
    </form>
  </div>
</div>

<?php renderFooter(); ?>
