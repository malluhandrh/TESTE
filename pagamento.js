function pagar() {
  const valor = parseFloat(document.getElementById("valor").innerText);

  const url =
    `https://checkout.infinitepay.io/maria-luiza-concei?items=[{"name":"Reserva","price":${valor*100},"quantity":1}]&redirect_url=https://isabellrsf.github.io/`;

  window.location.href = url;
}
