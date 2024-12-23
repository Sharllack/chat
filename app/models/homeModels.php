<?php

namespace Name\Models;

use Name\Models\Config\Conexao;
use PDO;

class homeModels
{
    private $con;

    public function __construct()
    {
        $this->con = Conexao::getConexao();
    }

    public function enviarMensagem()
    {
        $cmd = $this->con->prepare("INSERT INTO mensagens (mensagem, id_usuario) VALUES (:msg, :id_usuario)");
        $cmd->execute([':msg' => $_POST['msg'], ':id_usuario' => $_SESSION['id_usuario']]);

        echo json_encode(["status" => "success"]);
    }

    public function getMensagens()
    {
        // Consulta todas as mensagens (do usuário e de outros usuários)
        $cmd = $this->con->prepare("
        SELECT mensagens.*, usuario.*
        FROM mensagens
        JOIN usuario ON mensagens.id_usuario = usuario.id
        WHERE mensagens.id_usuario = :id_usuario
           OR mensagens.id_usuario != :id_usuario
        ORDER BY mensagens.data ASC
    ");
        $cmd->execute([":id_usuario" => $_SESSION['id_usuario']]);
        $mensagens = $cmd->fetchAll(PDO::FETCH_ASSOC);

        // Retorna as mensagens ordenadas
        echo json_encode($mensagens);
    }
}