<?php
// auth.php — proteção básica para páginas PHP

// 1) Cookies de sessão um pouco mais seguros (antes do session_start)
if (session_status() === PHP_SESSION_NONE) {
  session_set_cookie_params([
    'lifetime' => 0,            // até fechar o navegador
    'path'     => '/',
    'secure'   => isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off', // só HTTPS
    'httponly' => true,         // JS não acessa
    'samesite' => 'Lax',
  ]);
  session_start();
}

// 2) Helpers
function is_uuid(string $v): bool {
  return (bool) preg_match('/^[0-9a-fA-F-]{36}$/', $v);
}
function current_url(): string {
  $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
  $host   = $_SERVER['HTTP_HOST'] ?? 'localhost';
  $uri    = $_SERVER['REQUEST_URI'] ?? '/';
  return $scheme . '://' . $host . $uri;
}

// 3) Timeout opcional (ex.: 30 min sem atividade)
$MAX_IDLE = 60 * 30;
$now = time();
if (!empty($_SESSION['__last_seen']) && ($now - $_SESSION['__last_seen'] > $MAX_IDLE)) {
  session_unset();
  session_destroy();
  header('Location: login.html?next=' . urlencode('/'));
  exit;
}
$_SESSION['__last_seen'] = $now;

// 4) Checagem de login (espera profile_id salvo no login)
$profileId = $_SESSION['profile_id'] ?? '';
if (!$profileId || !is_uuid($profileId)) {
  // Preserva a URL completa e evita open redirect (só permite relativo ao mesmo site)
  $next = $_SERVER['REQUEST_URI'] ?? '/';
  if (!str_starts_with($next, '/')) $next = '/';
  header('Location: login.html?next=' . urlencode($next));
  exit;
}

// 5) (opcional) Dados úteis do usuário na sessão
// $_SESSION['full_name'] ?? '(sem nome)';
// $_SESSION['role']      ?? 'tutor';
