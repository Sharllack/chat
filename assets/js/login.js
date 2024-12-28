function entrar() {
  var user = $("#user").val().trim();
  var pass = $("#pass").val().trim();

  if (!user || !pass) {
    alert("Por favor, preencha todos os campos.");
    return;
  }

  var formData = new FormData();
  formData.append("user", user);
  formData.append("pass", pass);

  $.ajax({
    url: "login/entrar",
    type: "POST",
    data: formData,
    processData: false,
    contentType: false,
    dataType: "json",
    success: function (response) {
      if (response.status == "success") {
        // Armazena o id_usuario na sessão ou localStorage
        sessionStorage.setItem("id_usuario", response.id_usuario);
        window.location.href = "contatos"; // Redireciona para a página inicial
      } else {
        alert(response.mensagem);
      }
    },
    error: function (xhr, status, error) {
      console.log("Status: " + status);
      console.log("Erro: " + error);
      console.log("Resposta do servidor: " + xhr.responseText);
    },
  });
}
