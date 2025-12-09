<?php
// pega valor passado na URL (ex: pagamento.php?valor=120)
$valor = isset($_GET['valor']) ? floatval($_GET['valor']) : 0;
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
<meta charset="UTF-8">
<title>Pagamento</title>

<style>
    body {
        font-family: Arial, Helvetica, sans-serif;
        background: #f7f7f7;
        margin: 0;
        padding: 0;
    }

    .container {
        max-width: 600px;
        margin: 50px auto;
        background: #fff;
        border-radius: 12px;
        padding: 30px;
        box-shadow: 0 0 20px rgba(0,0,0,0.1);
    }

    h1 {
        text-align: center;
        margin-bottom: 30px;
        color: #333;
    }

    .info {
        font-size: 18px;
        margin-bottom: 10px;
    }

    .valor-box {
        margin: 25px 0;
        padding: 15px;
        background: #f0f0f0;
        border-radius: 10px;
    }

    button {
        width: 100%;
        padding: 14px;
        background: #059669;
        border: none;
        border-radius: 10px;
        color: #fff;
        font-size: 18px;
        cursor: pointer;
        transition: 0.3s;
    }

    button:hover {
        background: #047857;
    }

</style>
</head>

<body>

<div class="container">

    <h1>Resumo da Reserva</h1>

    <p class="info"><strong>Hospedagem:</strong> Pet</p>

    <div class="valor-box">
        <strong>Valor total:</strong> R$
        <span id="valor"><?php echo $valor; ?></span>
    </div>

    <button onclick="pagar()">Finalizar Pagamento</button>

</div>

<script>
function pagar() {
  const valor = document.getElementById("valor").innerText;

  const url =
    `https://checkout.infinitepay.io/maria-luiza-concei?items=[{"name":"Reserva","price":${valor*100},"quantity":1}]&redirect_url=https://isabellrsf.github.io/`;

  window.location.href = url;
}
</script>

</body>
</html>
