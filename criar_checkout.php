<?php
// criar_checkout.php
// Recebe ?valor= em reais (ex: 150)
// Retorna/Redireciona para o checkout da InfinitePay

// CONFIG
$handle = 'maria-luiza-concei';    // sua infinite tag
$order_nsu = 'reserva-' . time(); // ou pegue do seu sistema
$redirect_url = 'https://seusite.com/obrigado.php'; // opcional

// pega valor (em reais) e valida
$valor_reais = isset($_GET['valor']) ? floatval($_GET['valor']) : 0.0;
if ($valor_reais <= 0) {
  http_response_code(400);
  echo "Valor inválido.";
  exit;
}

// converte para centavos
$price_cents = intval(round($valor_reais * 100));

// monta body
$body = [
  "handle" => $handle,
  "order_nsu" => $order_nsu,
  "items" => [
    [
      "description" => "Reserva Pet",
      "quantity" => 1,
      "price" => $price_cents
    ]
  ]
];
// se quiser, adicione redirect_url/webhook_url:
// $body['redirect_url'] = $redirect_url;

$ch = curl_init("https://api.infinitepay.io/invoices/public/checkout/links");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($body));
$response = curl_exec($ch);
$err = curl_error($ch);
curl_close($ch);

if ($response === false || $err) {
  http_response_code(500);
  echo "Erro ao comunicar com InfinitePay: " . ($err ?: $response);
  exit;
}

$data = json_decode($response, true);
if (!$data || (!isset($data['url']) && !isset($data['data']['url']))) {
  http_response_code(500);
  echo "Resposta inesperada: " . $response;
  exit;
}

// suporta duas formas: data.url ou url direto
$checkoutUrl = $data['url'] ?? ($data['data']['url'] ?? null);
if (!$checkoutUrl) {
  http_response_code(500);
  echo "Não foi possível obter checkout URL.";
  exit;
}

// redireciona o usuário para o checkout
header("Location: " . $checkoutUrl);
exit;
