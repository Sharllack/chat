<?php

namespace Name\Controllers;

use Name\Core\Controller;
use Name\Models\homeModels;

class homeController extends Controller
{
    public function index()
    {
        $this->carregarTemplate("home");
    }

    public function enviarMensagem()
    {
        $homeModels = new homeModels;
        $resposta = $homeModels->enviarMensagem();

        return $resposta;
    }

    public function getMensagens()
    {
        $homeModels = new homeModels;
        $mensagens = $homeModels->getMensagens();

        return $mensagens;
    }
}