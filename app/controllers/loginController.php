<?php

namespace Name\Controllers;

use Name\Core\Controller;
use Name\Models\loginModels;

class loginController extends Controller
{
    public function index()
    {
        $this->carregarTemplate("login");
    }

    public function entrar()
    {
        $loginModels = new loginModels();
        $resposta = $loginModels->entrar();

        return $resposta;
    }
}