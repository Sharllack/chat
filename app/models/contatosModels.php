<?php

namespace Name\Models;

use Name\Models\Config\Conexao;
use PDO;

class contatosModels
{
    private $con;

    public function __construct()
    {
        $this->con = Conexao::getConexao();
    }

    public function getContatos()
    {
        $dados = array();
        $cmd = $this->con->prepare("SELECT * FROM usuario WHERE id != :id_usuario");
        $cmd->execute([':id_usuario' => $_SESSION['id_usuario']]);
        $dados = $cmd->fetchAll(PDO::FETCH_ASSOC);

        return $dados;
    }
}
