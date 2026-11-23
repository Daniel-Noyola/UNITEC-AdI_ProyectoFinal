<?php
require_once __DIR__ . '/../../helpers/auth.php';
require_once __DIR__ . '/../../helpers/funciones.php';
require_once __DIR__ . '/../../helpers/layout.php';
require_once __DIR__ . '/../../db/platillos/acciones.php';
require_once __DIR__ . '/../../db/ingredientes/acciones.php';
require_once __DIR__ . '/../../db/recetas/acciones.php';

requireAdmin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $action = $_POST['action'] ?? '';

        if ($action === 'save_dish') {
            $dishId      = !empty($_POST['id_platillo']) ? (int)$_POST['id_platillo'] : null;
            $name        = trim($_POST['nombre'] ?? '');
            $description = trim($_POST['descripcion'] ?? '');
            $price       = (float)($_POST['precio'] ?? 0);
            $category    = trim($_POST['categoria'] ?? '');
            $available   = isset($_POST['disponible']) ? 1 : 0;

            if ($name === '' || $category === '' || $price < 0) {
                setFlash('danger', 'Nombre, categoría y un precio no negativo son obligatorios.');
            } else {
                $uploadedFile = isset($_FILES['imagen']) ? uploadDishImage($_FILES['imagen']) : null;
                saveDish($dishId, $name, $description, $price, $category, $available, $uploadedFile);
                setFlash('success', 'Platillo guardado satisfactoriamente.');
            }
        } elseif ($action === 'toggle_dish') {
            $dishId = (int)($_POST['id_platillo'] ?? 0);
            if ($dishId > 0) {
                toggleDishAvailability($dishId);
                setFlash('success', 'Disponibilidad del platillo actualizada.');
            }
        } elseif ($action === 'save_recipe') {
            $dishId = (int)($_POST['id_platillo'] ?? 0);
            $rawIngredients = $_POST['receta'] ?? [];
            if ($dishId > 0 && is_array($rawIngredients)) {
                saveRecipe($dishId, $rawIngredients);
                setFlash('success', 'Receta configurada satisfactoriamente.');
                header('Location: dishes.php?dish_id=' . $dishId);
                exit;
            }
        }
    } catch (Exception $e) {
        setFlash('danger', $e->getMessage());
    }

    header('Location: dishes.php');
    exit;
}

$activeDishId    = !empty($_GET['dish_id']) ? (int)$_GET['dish_id'] : null;
$allDishes       = getDishes(false);
$ingredientsList = getIngredients();

renderHeader('Catálogo de Platillos y Recetas', 'dishes');
?>

<div class="card">
  <div class="card__header">
    <div>
      <h2 class="card__title">Catálogo Completo de Platillos</h2>
      <span class="card__subtitle">Administración de menú, precios y recetas por ingrediente</span>
    </div>
    <button type="button" class="btn btn--accent btn--sm" data-modal-target="modalAddDish">
      <?= renderIcon('plus') ?>
      <span>Nuevo Platillo</span>
    </button>
  </div>
  <div class="card__body">
    <div class="table-responsive">
      <table class="data-table">
        <thead>
          <tr>
            <th>Imagen</th>
            <th>Nombre</th>
            <th>Categoría</th>
            <th>Precio</th>
            <th>Disponibilidad</th>
            <th>Receta</th>
            <th>Acciones</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($allDishes as $d): ?>
            <tr>
              <td><?= renderDishImage($d['imagen'], $d['nombre'], 'dish-thumb') ?></td>
              <td>
                <strong><?= e($d['nombre']) ?></strong>
                <div><small><?= e($d['descripcion'] ?? '') ?></small></div>
              </td>
              <td><span class="badge badge--info"><?= e($d['categoria']) ?></span></td>
              <td class="num-cell"><strong>$<?= number_format((float)$d['precio'], 2) ?></strong></td>
              <td>
                <span class="badge <?= (int)$d['disponible'] === 1 ? 'badge--success' : 'badge--neutral' ?>">
                  <?= (int)$d['disponible'] === 1 ? 'Disponible' : 'Desactivado' ?>
                </span>
              </td>
              <td>
                <?php $rec = getRecipeForDish((int)$d['id_platillo']); ?>
                <a href="dishes.php?dish_id=<?= (int)$d['id_platillo'] ?>" class="btn btn--outline btn--sm">
                  <?= renderIcon('orders') ?>
                  <span><?= count($rec) ?> ingredientes</span>
                </a>
              </td>
              <td>
                <form action="dishes.php" method="POST">
                  <input type="hidden" name="action" value="toggle_dish">
                  <input type="hidden" name="id_platillo" value="<?= (int)$d['id_platillo'] ?>">
                  <button type="submit" class="btn btn--outline btn--sm">
                    <?= (int)$d['disponible'] === 1 ? 'Desactivar' : 'Activar' ?>
                  </button>
                </form>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<?php if ($activeDishId): ?>
  <?php
    $selectedDish  = getDishById($activeDishId);
    $currentRecipe = getRecipeForDish($activeDishId);
  ?>
  <?php if ($selectedDish): ?>
    <div class="card">
      <div class="card__header">
        <h3 class="card__title">Configurar Receta: <?= e($selectedDish['nombre']) ?></h3>
        <a href="dishes.php" class="btn btn--outline btn--sm">Cerrar Receta</a>
      </div>
      <form action="dishes.php" method="POST">
        <input type="hidden" name="action" value="save_recipe">
        <input type="hidden" name="id_platillo" value="<?= $activeDishId ?>">

        <div class="card__body">
          <p>Define qué ingredientes del inventario se descontarán al pedir este platillo.</p>

          <div id="recipeRowsContainer">
            <?php if (empty($currentRecipe)): ?>
              <div class="form-row">
                <div class="form-group">
                  <label class="form-label">Ingrediente</label>
                  <select name="receta[0][id_ingrediente]" class="form-control" required>
                    <option value="">Selecciona ingrediente</option>
                    <?php foreach ($ingredientsList as $ing): ?>
                      <option value="<?= (int)$ing['id_ingrediente'] ?>">
                        <?= e($ing['nombre']) ?> (<?= e($ing['unidad_medida']) ?>)
                      </option>
                    <?php endforeach; ?>
                  </select>
                </div>
                <div class="form-group">
                  <label class="form-label">Cantidad Requerida</label>
                  <input type="number" step="0.01" min="0.01" name="receta[0][cantidad_requerida]" class="form-control" required>
                </div>
              </div>
            <?php else: ?>
              <?php foreach ($currentRecipe as $idx => $rItem): ?>
                <div class="form-row">
                  <div class="form-group">
                    <label class="form-label">Ingrediente</label>
                    <select name="receta[<?= $idx ?>][id_ingrediente]" class="form-control" required>
                      <?php foreach ($ingredientsList as $ing): ?>
                        <option value="<?= (int)$ing['id_ingrediente'] ?>" <?= (int)$ing['id_ingrediente'] === (int)$rItem['id_ingrediente'] ? 'selected' : '' ?>>
                          <?= e($ing['nombre']) ?> (<?= e($ing['unidad_medida']) ?>)
                        </option>
                      <?php endforeach; ?>
                    </select>
                  </div>
                  <div class="form-group">
                    <label class="form-label">Cantidad Requerida</label>
                    <input type="number" step="0.01" min="0.01" name="receta[<?= $idx ?>][cantidad_requerida]" value="<?= (float)$rItem['cantidad_requerida'] ?>" class="form-control" required>
                  </div>
                  <div class="form-group">
                    <button type="button" class="btn btn--danger btn--sm remove-recipe-row-btn">&times; Eliminar</button>
                  </div>
                </div>
              <?php endforeach; ?>
            <?php endif; ?>
          </div>

          <button type="button" id="addRecipeIngredientBtn" class="btn btn--outline btn--sm">
            &#43; Añadir Ingrediente a la Receta
          </button>
        </div>

        <div class="card__footer">
          <button type="submit" class="btn btn--accent">Guardar Receta</button>
        </div>
      </form>
    </div>

    <template id="recipeRowTemplate">
      <div class="form-row">
        <div class="form-group">
          <label class="form-label">Ingrediente</label>
          <select name="receta[__IDX__][id_ingrediente]" class="form-control" required>
            <option value="">Selecciona ingrediente</option>
            <?php foreach ($ingredientsList as $ing): ?>
              <option value="<?= (int)$ing['id_ingrediente'] ?>">
                <?= e($ing['nombre']) ?> (<?= e($ing['unidad_medida']) ?>)
              </option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="form-group">
          <label class="form-label">Cantidad Requerida</label>
          <input type="number" step="0.01" min="0.01" name="receta[__IDX__][cantidad_requerida]" class="form-control" required>
        </div>
        <div class="form-group">
          <button type="button" class="btn btn--danger btn--sm remove-recipe-row-btn">&times; Eliminar</button>
        </div>
      </div>
    </template>

    <!-- JavaScript mínimo para agregar o quitar filas de ingredientes en la receta -->
    <script>
      document.addEventListener('DOMContentLoaded', () => {
        const addBtn = document.getElementById('addRecipeIngredientBtn');
        const container = document.getElementById('recipeRowsContainer');
        const template = document.getElementById('recipeRowTemplate');
        let nextIndex = <?= max(1, count($currentRecipe)) ?>;

        if (addBtn && container && template) {
          addBtn.addEventListener('click', () => {
            const html = template.innerHTML.replace(/__IDX__/g, String(nextIndex++));
            container.insertAdjacentHTML('beforeend', html);
          });

          container.addEventListener('click', (e) => {
            const btn = e.target.closest('.remove-recipe-row-btn');
            if (btn) btn.closest('.form-row')?.remove();
          });
        }
      });
    </script>
  <?php endif; ?>
<?php endif; ?>

<div class="modal-overlay" id="modalAddDish">
  <div class="modal">
    <div class="modal__header">
      <h3 class="modal__title">Crear Nuevo Platillo</h3>
      <button type="button" class="modal__close" data-modal-close>&times;</button>
    </div>
    <form action="dishes.php" method="POST" enctype="multipart/form-data">
      <input type="hidden" name="action" value="save_dish">
      <div class="modal__body">
        <div class="form-group">
          <label for="dish_nom" class="form-label">Nombre del Platillo</label>
          <input type="text" id="dish_nom" name="nombre" class="form-control" required>
        </div>
        <div class="form-row">
          <div class="form-group">
            <label for="dish_cat" class="form-label">Categoría</label>
            <select id="dish_cat" name="categoria" class="form-control" required>
              <option value="">Selecciona una categoría...</option>
              <?php foreach (DISH_CATEGORIES as $catOption): ?>
                <option value="<?= e($catOption) ?>"><?= e($catOption) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="form-group">
            <label for="dish_pre" class="form-label">Precio ($)</label>
            <input type="number" step="0.01" min="0" id="dish_pre" name="precio" class="form-control" required>
          </div>
        </div>
        <div class="form-group">
          <label for="dish_desc" class="form-label">Descripción</label>
          <textarea id="dish_desc" name="descripcion" rows="3" class="form-control"></textarea>
        </div>
        <div class="form-group">
          <label for="dish_img" class="form-label">Fotografía del Platillo (opcional, JPG/PNG/WEBP)</label>
          <input type="file" id="dish_img" name="imagen" accept="image/jpeg,image/png,image/webp" class="form-control">
        </div>
        <div class="form-group">
          <label class="form-label">
            <input type="checkbox" name="disponible" value="1" checked> Platillo disponible en el menú
          </label>
        </div>
      </div>
      <div class="card__footer">
        <button type="button" class="btn btn--outline" data-modal-close>Cancelar</button>
        <button type="submit" class="btn btn--accent">Guardar Platillo</button>
      </div>
    </form>
  </div>
</div>

<?php renderFooter(); ?>
