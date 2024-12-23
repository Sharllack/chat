let socket;

function connectWebSocket() {
  // Substitua "ws://localhost:8080" pelo endereço do seu servidor WebSocket
  socket = new WebSocket("ws://localhost:8080");

  socket.onopen = function () {
    console.log("Conexão WebSocket estabelecida.");
    const idUsuario = sessionStorage.getItem("id_usuario");
    socket.send(JSON.stringify({ action: "connect", id_usuario: idUsuario }));
  };

  // Evento ao receber mensagens do servidor WebSocket
  socket.onmessage = function (event) {
    const mensagens = JSON.parse(event.data);
    updateMensagens(mensagens);
  };

  socket.onerror = function (error) {
    console.error("Erro WebSocket:", error);
  };

  socket.onclose = function () {
    console.warn("Conexão WebSocket encerrada. Tentando reconectar...");
    setTimeout(connectWebSocket, 5000); // Reconexão automática
  };
}

function updateMensagens(mensagens) {
  // Garante que 'mensagens' será um array, mesmo que não seja
  mensagens = Array.isArray(mensagens) ? mensagens : [mensagens];

  const idUsuario = parseInt(sessionStorage.getItem("id_usuario")); // Obtém o ID do usuário logado

  mensagens.forEach((mensagem) => {
    if (mensagem.id_usuario === idUsuario) {
      $("#mensagens").append(
        `<div class="msg_user2">${mensagem.mensagem}</div>`
      );
    } else {
      $("#foto_user").attr("src", "assets/image_perfil/" + mensagem.imagem);
      $("#nome_user").text(mensagem.usuario);
      $("#mensagens").append(
        `<div class="msg_user1">${mensagem.mensagem}</div>`
      );
    }
  });

  // Força o scroll para o final
  const container = document.getElementById("mensagens");
  container.scrollTop = container.scrollHeight;
}

function enviarMensagem() {
  const mensagem = $("#msg").val();
  if (mensagem.trim() === "") return;

  const idUsuario = sessionStorage.getItem("id_usuario"); // Obtém o ID do usuário logado
  const payload = {
    action: "send_message",
    mensagem: mensagem,
    id_usuario: idUsuario, // Envia o ID do usuário corretamente
  };

  // Envia a mensagem para o servidor WebSocket
  socket.send(JSON.stringify(payload));
  $("#msg").val(""); // Limpa o campo de mensagem
}

$(document).ready(function () {
  connectWebSocket();

  $("#send_button").click(() => {
    enviarMensagem();
  });

  $("#msg").keypress((event) => {
    if (event.which === 13) {
      enviarMensagem();
    }
  });
});
