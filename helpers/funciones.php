<?php
/**
 * Funciones utilitarias compartidas entre vistas y acciones.
 */

define('UPLOADS_DIR', __DIR__ . '/../uploads/');
define('UPLOADS_URL', '../../uploads/');

function e(?string $value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function setFlash(string $type, string $message): void
{
    $_SESSION['flash'] = [
        'type'    => $type, // 'success' o 'danger'
        'message' => $message,
    ];
}

function getFlash(): ?array
{
    if (!empty($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $flash;
    }
    return null;
}

function getStatusBadgeClass(string $status): string
{
    switch ($status) {
        case 'pagado':
        case 'disponible':
            return 'badge--success';
        case 'preparando':
        case 'ocupada':
            return 'badge--warning';
        case 'entregado':
            return 'badge--info';
        case 'cancelado':
            return 'badge--danger';
        default:
            return 'badge--neutral';
    }
}

/**
 * Sube una imagen a la carpeta uploads/ y devuelve el nombre generado.
 */
function uploadDishImage(array $file): ?string
{
    if (empty($file['name']) || $file['error'] !== UPLOAD_ERR_OK) {
        return null;
    }

    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $allowed = ['jpg', 'jpeg', 'png', 'webp'];

    if (!in_array($ext, $allowed, true)) {
        throw new RuntimeException('Formato de imagen no permitido. Usa JPG, PNG o WEBP.');
    }

    if ((int)$file['size'] > 3 * 1024 * 1024) {
        throw new RuntimeException('La imagen es muy pesada. El tamaño máximo es de 3 MB.');
    }

    if (!is_dir(UPLOADS_DIR)) {
        mkdir(UPLOADS_DIR, 0755, true);
    }

    $newFileName = 'dish_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
    if (!move_uploaded_file($file['tmp_name'], UPLOADS_DIR . $newFileName)) {
        throw new RuntimeException('No se pudo guardar la imagen en el servidor.');
    }

    return $newFileName;
}

/**
 * Muestra la imagen del platillo o un icono SVG de respaldo si no tiene foto.
 */
function renderDishImage(?string $imagePath, string $dishName, string $extraClass = ''): string
{
    if (!empty($imagePath)) {
        // Normalizar separadores y evitar traversal fuera de uploads/
        $relativePath = ltrim(str_replace('\\', '/', $imagePath), '/');
        if (strpos($relativePath, '..') === false && file_exists(UPLOADS_DIR . $relativePath)) {
            return sprintf(
                '<div class="dish-card__image-container %s"><img src="%s" alt="%s" class="dish-card__img" loading="lazy"></div>',
                e($extraClass),
                e(UPLOADS_URL . $relativePath),
                e($dishName)
            );
        }
    }

    $svg = '<svg class="dish-card__placeholder-icon" viewBox="0 0 64 64" fill="none" xmlns="http://www.w3.org/2000/svg">'
         . '<circle cx="32" cy="34" r="18" stroke="#CBD5E1" stroke-width="2" stroke-dasharray="4 3"/>'
         . '<path d="M14 44H50" stroke="#F97316" stroke-width="2.5" stroke-linecap="round"/>'
         . '<path d="M18 40C18 32.268 24.268 26 32 26C39.732 26 46 32.268 46 40" stroke="#94A3B8" stroke-width="2.2" stroke-linecap="round"/>'
         . '<circle cx="32" cy="22" r="2.5" fill="#F97316"/>'
         . '</svg>';

    return sprintf(
        '<div class="dish-card__image-container dish-card__image-container--placeholder %s" title="Imagen no asignada">%s<span class="dish-card__placeholder-label">Sin fotografía</span></div>',
        e($extraClass),
        $svg
    );
}

/**
 * Devuelve un icono SVG lineal uniforme para evitar emojis dependientes del sistema.
 */
function renderIcon(string $name, string $class = 'ui-icon'): string
{
    $icons = [
        'brand'     => '<path d="M6 13.87A4 4 0 0 1 7.41 6a5.11 5.11 0 0 1 1.05-1.54 5 5 0 0 1 7.08 0A5.11 5.11 0 0 1 16.59 6 4 4 0 0 1 18 13.87V21H6Z"/><line x1="6" y1="17" x2="18" y2="17"/>',
        'dashboard' => '<rect x="3" y="3" width="7" height="9" rx="1.5"/><rect x="14" y="3" width="7" height="5" rx="1.5"/><rect x="14" y="12" width="7" height="9" rx="1.5"/><rect x="3" y="16" width="7" height="5" rx="1.5"/>',
        'menu'      => '<path d="M4 19h16"/><path d="M5 15a7 7 0 0 1 14 0H5Z"/><path d="M12 5v3"/><circle cx="12" cy="4" r="1"/>',
        'orders'    => '<path d="M9 5H7a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2h-2"/><rect x="9" y="3" width="6" height="4" rx="1"/><path d="M9 12h6"/><path d="M9 16h4"/>',
        'tables'    => '<path d="M4 10h16"/><path d="M6 10v9"/><path d="M18 10v9"/><path d="M9 10V7a3 3 0 0 1 6 0v3"/>',
        'dishes'    => '<path d="M3 2v7c0 1.1.9 2 2 2h4a2 2 0 0 0 2-2V2"/><path d="M7 2v20"/><path d="M21 15V2a5 5 0 0 0-5 5v6c0 1.1.9 2 2 2h3Zm0 0v7"/>',
        'inventory' => '<path d="M21 8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16Z"/><path d="m3.3 7 8.7 5 8.7-5"/><path d="M12 22V12"/>',
        'reports'   => '<path d="M3 3v18h18"/><path d="m19 9-5 5-4-4-3 3"/>',
        'money'     => '<circle cx="12" cy="12" r="9"/><path d="M14.5 9.5a2.5 2.5 0 0 0-5 0c0 1.5 2.5 2 2.5 2s2.5.5 2.5 2a2.5 2.5 0 0 1-5 0"/><path d="M12 6v12"/>',
        'star'      => '<polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/>',
        'user'      => '<path d="M19 21v-2a4 4 0 0 0-4-4H9a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/>',
        'users'     => '<path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/>',
        'calendar'  => '<rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4"/><path d="M8 2v4"/><path d="M3 10h18"/>',
        'receipt'   => '<path d="M4 2v20l2-1 2 1 2-1 2 1 2-1 2 1 2-1 2 1V2l-2 1-2-1-2 1-2-1-2 1-2-1-2 1Z"/><path d="M8 7h8"/><path d="M8 11h8"/><path d="M8 15h5"/>',
        'plus'      => '<path d="M12 5v14"/><path d="M5 12h14"/>',
        'check'     => '<polyline points="20 6 9 17 4 12"/>',
        'close'     => '<path d="M18 6 6 18"/><path d="m6 6 12 12"/>',
        'alert'     => '<path d="m21.73 18-8-14a2 2 0 0 0-3.48 0l-8 14A2 2 0 0 0 4 21h16a2 2 0 0 0 1.73-3Z"/><path d="M12 9v4"/><path d="M12 17h.01"/>',
        'cart'      => '<circle cx="8" cy="21" r="1"/><circle cx="19" cy="21" r="1"/><path d="M2.05 2.05h2l2.66 12.42a2 2 0 0 0 2 1.58h9.78a2 2 0 0 0 1.95-1.57l1.65-7.43H5.12"/>',
        'logout'    => '<path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/>',
        'filter'    => '<polygon points="22 3 2 3 10 12.46 10 19 14 21 14 12.46 22 3"/>',
        'clock'     => '<circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/>',
        'arrow'     => '<path d="M5 12h14"/><path d="m12 5 7 7-7 7"/>',
        'edit'      => '<path d="M17 3a2.85 2.83 0 1 1 4 4L7.5 20.5 2 22l1.5-5.5Z"/>',
    ];

    $path = $icons[$name] ?? '<circle cx="12" cy="12" r="8"/>';
    return sprintf(
        '<svg class="%s" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">%s</svg>',
        e($class),
        $path
    );
}
