$("#lupa").on("click", function () {
  const $search = $("#search");
  const $lupaImg = $("#lupa_img");
  const $contatos = $("#contatos");

  if ($contatos.hasClass("open")) {
    // Estado "aberto" -> Recolher
    $contatos.removeClass("open");
    $contatos.css("transform", "translate(0, -19px)");
    $search.css("transform", "translate(0, -19px)");
    $lupaImg.attr("src", "assets/image/lupa.png"); // Voltar para a imagem original
  } else {
    // Estado "fechado" -> Expandir
    $contatos.addClass("open");
    $contatos.css("transform", "translate(0, 42.5px)");
    $search.css("transform", "translate(0, 42.5px)");
    $lupaImg.attr("src", "assets/image/close.png"); // Mostrar imagem de close
  }
});

function getContatos() {
  $("#contatos").empty();
  $.ajax({
    url: "contatos/getContatos",
    type: "GET",
    dataType: "json",
    success: function (dados) {
      dados.forEach((dado) => {
        $("#contatos").append(`
          <div id="contato" onclick="abrirConversa(${dado.id})">
            <div id="infos">
              <img src="assets/image_perfil/${dado.imagem}" alt="Foto de perfil" id="imagem_conversa">
              <p id="nome_Conversa">${dado.usuario}</p>
            </div>
          </div>
        `);
      });
    },
    error: function (xhr, status, error) {
      console.log("Status: " + status);
      console.log("Erro: " + error);
      console.log("Resposta do servidor: " + xhr.responseText);
    },
  });
}

function abrirConversa(id) {
  sessionStorage.setItem("id_usuario2", id);
  window.location.href = "home";
}

document.addEventListener("DOMContentLoaded", () => {
  getContatos();
});

document
  .getElementById("search_contato")
  .addEventListener("keyup", function () {
    const query = this.value.toLowerCase();
    const rows = document.querySelectorAll("#contato");

    rows.forEach((row) => {
      const cells = row.getElementsByTagName("p");
      const match = Array.from(cells).some((cell) =>
        cell.textContent.toLowerCase().includes(query)
      );

      row.style.display = match ? "" : "none";
    });
  });
