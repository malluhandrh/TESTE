<?php
// supabase.php

// -------------- Leitura de variáveis do .env --------------
function env($key) {
  static $vars = null;
  if ($vars === null) {
    $vars = [];
    $path = __DIR__ . '/.env';
    if (file_exists($path)) {
      foreach (file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        if ($line === '' || $line[0] === '#') continue;
        [$k, $v] = array_map('trim', explode('=', $line, 2));
        $vars[$k] = $v;
      }
    }
  }
  return $vars[$key] ?? null;
}

// -------------- Util: configurações SSL para cURL --------------
function apply_curl_ssl_opts($ch) {
  $cafile = env('SUPABASE_CA_FILE') ?: env('CURL_CA_BUNDLE');
  if ($cafile && is_file($cafile)) {
    curl_setopt($ch, CURLOPT_CAINFO, $cafile);
  } else {
    if (env('DEV_NO_SSL_VERIFY') === '1') {
      curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
      curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
    }
  }
}

// -------------- Chamada REST genérica ao Supabase (server-side) --------------
function sb_request($method, $path, $json = null, array $extraHeaders = []) {
  $base = rtrim(env('SUPABASE_URL'), '/');
  $url  = $base . $path;

  $headers = array_merge([
    'Authorization: Bearer ' . env('SUPABASE_SERVICE_KEY'),
    'apikey: ' . env('SUPABASE_SERVICE_KEY'),
    'Content-Type: application/json',
    'Prefer: return=representation',
  ], $extraHeaders);

  $ch = curl_init($url);
  curl_setopt_array($ch, [
    CURLOPT_CUSTOMREQUEST  => $method,
    CURLOPT_HTTPHEADER     => $headers,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POSTFIELDS     => ($json !== null ? json_encode($json) : null),
    CURLOPT_TIMEOUT        => 30,
  ]);

  apply_curl_ssl_opts($ch);

  $body   = curl_exec($ch);
  $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
  $err    = curl_error($ch);
  curl_close($ch);

  if ($body === false) {
    return [$status ?: 0, ['error' => $err]];
  }

  $decoded = json_decode($body, true);
  if ($decoded === null && json_last_error() !== JSON_ERROR_NONE) {
    $decoded = $body;
  }

  return [$status, $decoded];
}

/**
 * ---------- AUTH ADMIN: CRIAR USUÁRIO NO SUPABASE ----------
 * Usa a Service Key para criar um usuário no Supabase Auth.
 * É isso que o cadastro do tutor/anfitrião usa para o login funcionar.
 */
function sb_auth_admin_create_user(string $email, string $password, array $user_metadata = []) {
  $base = rtrim(env('SUPABASE_URL'), '/');
  $url  = $base . '/auth/v1/admin/users';

  $payload = [
    'email'          => $email,
    'password'       => $password,
    'email_confirm'  => true,
    'user_metadata'  => $user_metadata,
  ];

  $headers = [
    'Authorization: Bearer ' . env('SUPABASE_SERVICE_KEY'),
    'apikey: ' . env('SUPABASE_SERVICE_KEY'),
    'Content-Type: application/json',
  ];

  $ch = curl_init($url);
  curl_setopt_array($ch, [
    CURLOPT_CUSTOMREQUEST  => 'POST',
    CURLOPT_HTTPHEADER     => $headers,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POSTFIELDS     => json_encode($payload),
    CURLOPT_TIMEOUT        => 30,
  ]);

  apply_curl_ssl_opts($ch);

  $body   = curl_exec($ch);
  $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
  $err    = curl_error($ch);
  curl_close($ch);

  if ($body === false) {
    return [$status ?: 0, ['error' => $err]];
  }

  $decoded = json_decode($body, true);
  if ($decoded === null && json_last_error() !== JSON_ERROR_NONE) {
    $decoded = $body;
  }

  return [$status, $decoded];
}

/**
 * ---------- AUTH ADMIN: DELETAR USUÁRIO NO SUPABASE ----------
 * Vamos usar essa função depois para o "excluir conta".
 */
function sb_auth_admin_delete_user(string $user_id) {
  $base = rtrim(env('SUPABASE_URL'), '/');
  $url  = $base . '/auth/v1/admin/users/' . $user_id;

  $headers = [
    'Authorization: Bearer ' . env('SUPABASE_SERVICE_KEY'),
    'apikey: ' . env('SUPABASE_SERVICE_KEY'),
    'Content-Type: application/json',
  ];

  $ch = curl_init($url);
  curl_setopt_array($ch, [
    CURLOPT_CUSTOMREQUEST  => 'DELETE',
    CURLOPT_HTTPHEADER     => $headers,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_TIMEOUT        => 30,
  ]);

  apply_curl_ssl_opts($ch);

  $body   = curl_exec($ch);
  $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
  $err    = curl_error($ch);
  curl_close($ch);

  if ($body === false) {
    return [$status ?: 0, ['error' => $err]];
  }

  $decoded = json_decode($body, true);
  if ($decoded === null && json_last_error() !== JSON_ERROR_NONE) {
    $decoded = $body;
  }

  return [$status, $decoded];
}

// -------------- Upload para Storage (bucket público) --------------
function upload_to_bucket($bucket, $destPath, $localTmpPath) {
  $base = rtrim(env('SUPABASE_URL'), '/');
  $url  = "$base/storage/v1/object/$bucket/$destPath";

  $ch = curl_init($url);
  $headers = [
    'Authorization: Bearer ' . env('SUPABASE_SERVICE_KEY'),
    'apikey: ' . env('SUPABASE_SERVICE_KEY'),
    'Content-Type: application/octet-stream',
  ];
  curl_setopt_array($ch, [
    CURLOPT_CUSTOMREQUEST   => 'POST',
    CURLOPT_HTTPHEADER      => $headers,
    CURLOPT_RETURNTRANSFER  => true,
    CURLOPT_POSTFIELDS      => file_get_contents($localTmpPath),
    CURLOPT_TIMEOUT         => 60,
  ]);

  apply_curl_ssl_opts($ch);

  $res  = curl_exec($ch);
  $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
  $err  = curl_error($ch);
  curl_close($ch);

  if ($code >= 300) {
    throw new Exception("Falha no upload ($code): " . ($res ?: $err));
  }

  return "$base/storage/v1/object/public/$bucket/$destPath";
}
