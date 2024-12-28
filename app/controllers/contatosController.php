<?php

namespace Name\Controllers;

use Name\Core\Controller;
use Name\Models\loginModels;

class contatosController extends Controller
{
    public function index()
    {
        $this->carregarTemplate("contatos");
    }

    // public function abrir()
    // {
    //     $loginModels = new loginModels();
    //     $resposta = $loginModels->abrir();

    //     return $resposta;
    // }
}