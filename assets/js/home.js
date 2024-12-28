let socket;

function connectWebSocket() {
  // Substitua "ws://localhost:8080" pelo endereço do seu servidor WebSocket
  const id_usuario2 = sessionStorage.getItem("id_usuario2");
  socket = new WebSocket(
    `wss://730de984d0d1.ngrok.app/ws?id_usuario2=${id_usuario2}`
  );

  socket.onopen = function () {
    console.log("Conexão WebSocket estabelecida.");
    const idUsuario = sessionStorage.getItem("id_usuario");
    socket.send(
      JSON.stringify({
        action: "connect",
        id_usuario: idUsuario,
      })
    );
    $("#fundo").css("display", "none");
  };

  // Evento ao receber mensagens do servidor WebSocket
  let typingTimeout;

  function notifyTyping(isTyping) {
    const idUsuario = sessionStorage.getItem("id_usuario");
    socket.send(
      JSON.stringify({
        action: isTyping ? "typing" : "stop_typing",
        id_usuario: idUsuario,
      })
    );
  }

  // Detectar início e fim da digitação
  $("#msg").on("input", () => {
    clearTimeout(typingTimeout);
    notifyTyping(true);

    // Parar de enviar "digitando" após 3 segundos de inatividade
    typingTimeout = setTimeout(() => {
      notifyTyping(false);
    }, 3000);
  });

  // Tratamento de mensagens do WebSocket
  socket.onmessage = function (event) {
    const data = JSON.parse(event.data);

    if (data.action === "exclude_message") {
      updateMensagens({ mensagens: [{ id_mensagem: data.id_mensagem }] }, true);
    } else if (data.action === "online_users") {
      const onlineUsers = data.users;
      onlineUsers.forEach((userId) => {
        // Atualize a interface para mostrar os usuários online
        console.log(`Usuário online: ${userId}`);
        $("#status").css("background-color", "rgb(0, 153, 0)");
      });
    } else if (data.action === "user_joined") {
      console.log(`Usuário entrou: ${data.user_id}`);
      $("#status").css("background-color", "rgb(0, 153, 0)");
    } else if (data.action === "user_left") {
      console.log(`Usuário saiu: ${data.user_id}`);
      $("#status").css("background-color", "rgb(153, 0, 0)");
    } else if (data.action === "typing" || data.action === "stop_typing") {
      showTypingStatus(data.user_id, data.action === "typing");
    } else {
      const mensagens = data;
      updateMensagens(mensagens);
    }
  };

  function showTypingStatus(userId, isTyping) {
    const typingElement = $(`#typing-status-${userId}`);

    if (isTyping) {
      if (typingElement.length === 0) {
        $("#digi").append(
          `<p id="typing-status-${userId}" class="typing-status">
          Digitando...
        </p>`
        );
      }
    } else {
      typingElement.remove();
    }

    const container = document.getElementById("mensagens");
    container.scrollTop = container.scrollHeight;
  }

  socket.onerror = function (error) {
    console.error("Erro WebSocket:", error);
  };

  socket.onclose = function () {
    console.warn("Conexão WebSocket encerrada. Tentando reconectar...");
    setTimeout(connectWebSocket, 5000); // Reconexão automática
  };
}

function updateMensagens(data, excluir = false) {
  const { usuario2, mensagens = [] } = data; // Define mensagens como array vazio por padrão

  // Atualize as informações do usuário 2 (foto e nome)
  if (usuario2) {
    $("#foto_user").attr("src", `assets/image_perfil/${usuario2.imagem}`);
    $("#nome_user").text(usuario2.usuario);
  }

  // Se for uma exclusão de mensagens
  if (excluir) {
    mensagens.forEach((mensagem) => {
      $(`[data-id='${mensagem.id_mensagem}']`).remove();
    });
    return;
  }

  // Adicionar mensagens ao DOM
  mensagens.forEach((mensagem) => {
    if (!document.querySelector(`[data-id='${mensagem.id_mensagem}']`)) {
      const msgClass =
        mensagem.id_usuario == sessionStorage.getItem("id_usuario")
          ? "msg_user2"
          : "msg_user1";
      $("#mensagens").append(
        `<div class="${msgClass}" data-id="${mensagem.id_mensagem}">${mensagem.mensagem}</div>`
      );
    }
  });

  // Scroll para o final
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

  // Notifica o servidor que o usuário parou de digitar
  socket.send(
    JSON.stringify({
      action: "stop_typing",
      id_usuario: idUsuario,
    })
  );

  // Remove o status de "Digitando..." na interface
  $(".typing-status").remove();

  // Limpa o campo de mensagem, mas mantém o foco nele
  $("#msg").val("").focus(); // Limpa o campo e mantém o foco
}

function mostrarBotaoApagar(idMensagem) {
  // Limpa os botões anteriores
  $("#btns").empty();

  // Adiciona os botões de apagar
  $("#btns").append(`
    <button type="button" onclick="apagarMensagem(${idMensagem})" id="sim">Sim</button>
    <button type="button" onclick="cancelarApagar()" id="nao">Não</button>
  `);

  // Exibe o modal para confirmação de exclusão
  $("#apagar_msg").css("display", "flex");
}

document.addEventListener("DOMContentLoaded", () => {
  const mensagensContainer = document.querySelector("#mensagens");
  const apagar = document.querySelector("#apagar_msg");
  let pressTimer;

  if (!mensagensContainer || !apagar) {
    console.error("Elemento(s) necessário(s) não encontrado(s) no DOM.");
    return;
  }

  // Detecta o clique longo na mensagem para exibir a opção de apagar
  mensagensContainer.addEventListener("mousedown", (event) => {
    const target = event.target;
    if (
      target.classList.contains("msg_user1") ||
      target.classList.contains("msg_user2")
    ) {
      iniciarTemporizador(target);
    }
  });

  mensagensContainer.addEventListener("touchstart", (event) => {
    const target = event.target;
    if (
      target.classList.contains("msg_user1") ||
      target.classList.contains("msg_user2")
    ) {
      iniciarTemporizador(target);
    }
  });

  mensagensContainer.addEventListener("mouseup", cancelarTemporizador);
  mensagensContainer.addEventListener("mouseleave", cancelarTemporizador);
  mensagensContainer.addEventListener("touchend", cancelarTemporizador);

  function iniciarTemporizador(target) {
    pressTimer = setTimeout(() => {
      // Obtenha o ID da mensagem a partir do atributo 'data-id'
      const mensagemId = target.getAttribute("data-id");
      mostrarBotaoApagar(mensagemId); // Passa o id da mensagem para a função de exibição dos botões
    }, 2000); // 2 segundos
  }

  function cancelarTemporizador() {
    clearTimeout(pressTimer);
  }
});

function cancelarApagar() {
  $("#apagar_msg").css("display", "none");
}

function apagarMensagem(id) {
  const idUsuario = sessionStorage.getItem("id_usuario");
  const payload = {
    action: "exclude_message",
    id_mensagem: id,
    id_usuario: idUsuario,
  };

  socket.send(JSON.stringify(payload));
  $("#apagar_msg").css("display", "none");
}

jQuery(function () {
  connectWebSocket();

  $("#send_button").on("click", () => {
    enviarMensagem();
  });

  $("#msg").on("keypress", (event) => {
    if (event.which === 13) {
      enviarMensagem();
    }
  });
});
