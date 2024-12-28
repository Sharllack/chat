<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="assets/css/home.css">
    <title>Document</title>
</head>

<body>
    <div id="apagar_msg">
        <div id="apagar_msg2">
            <p>Apagar mensagem?</p>
            <div id="btns">

            </div>
        </div>
    </div>
    <div id="fundo">
        <div id="fundo_img" ondurationchange=""></div>
    </div>
    <section id="principal">
        <section id="usuario">
            <div id="foto">
                <img src="" alt="foto" id="foto_user">
            </div>
            <div id="nome">
                <p id="nome_user"></p>
                <div id="digi"></div>
            </div>
            <div id="status">
                <div id="status2"></div>
            </div>
        </section>
        <section id="mensagens">
        </section>
        <section id="mensagem">
            <input type="text" name="mensagem" id="msg" placeholder="Digite sua mensagem">
            <button type="button">
                <img src="assets/image/enviar.png" alt="Enviar" onclick="enviarMensagem()">
            </button>
        </section>
    </section>
    <script src="assets/js/home.js"></script>
</body>

</html>