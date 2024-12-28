<?php

namespace Name\Controllers;

use Name\Core\Controller;
use Name\Models\contatosModels;
use Name\Models\loginModels;

class contatosController extends Controller
{
    public function index()
    {
        $this->carregarTemplate("contatos");
    }

    public function getContatos()
    {
        $contatosModels = new contatosModels();
        $dados = $contatosModels->getContatos();

        echo json_encode($dados);
    }
}
