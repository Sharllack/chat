<?php

namespace Name\Core;

class Controller
{
    public $dados;

    public function __construct()
    {
        $this->dados = array();
    }

    //Chamado para todos os CONTROLLERS
    public function carregarTemplate($nomeView, $dadosModel = array())
    {
        $this->dados = $dadosModel;

        require 'assets/views/template.php';
    }

    public function carregarView($nomeView, $dadosModel = array())
    {
        extract($dadosModel);

        require "assets/views/" . $nomeView . ".php";
    }
}