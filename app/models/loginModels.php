<?php

namespace Name\Models;

use Name\Models\Config\Conexao;
use PDO;

class loginModels
{
    private $con;

    public function __construct()
    {
        $this->con = Conexao::getConexao();
    }

    public function entrar()
    {
        $cmd = $this->con->prepare("SELECT * FROM usuario WHERE usuario = :user AND senha = :pass");
        $cmd->execute([':user' => $_POST['user'], ':pass' => $_POST['pass']]);
        $resposta = $cmd->fetch(PDO::FETCH_ASSOC);

        if ($resposta) {
            $_SESSION['id_usuario'] = $resposta['id'];
            echo json_encode([
                "status" => "success",
                "id_usuario" => $_SESSION['id_usuario']
            ]);
        } else {
            echo json_encode(["status" => "error", "mensagem" => "Usuário ou senha inválidos"]);
        }
    }
}