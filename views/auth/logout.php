<?php
require_once __DIR__ . '/../../helpers/auth.php';
require_once __DIR__ . '/../../helpers/funciones.php';

logoutUser();
setFlash('success', 'Has cerrado sesión correctamente. ¡Hasta pronto!');
header('Location: login.php');
exit;
