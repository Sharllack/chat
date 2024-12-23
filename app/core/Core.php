<?php

namespace Name\Core;

/**
 * Responsável por criar URL amigável
 */
class Core
{
    public function __construct()
    {
        session_start();
        $this->run();
    }

    public function run()
    {
        $param = array();

        if (isset($_GET['pag'])) {
            $url = $_GET['pag'];
        }

        if (!empty($url)) //possui informacao após o domínio
        {
            $url = explode('/', $url); // $url vira um array
            $controller = $url[0] . "Controller";
            //retira o primeiro valor do array
            array_shift($url);

            //Se o usuário mandou classe e funcao
            if (isset($url[0]) && !empty($url[0])) {
                $metodo = $url[0];
                array_shift($url);
            } else { //enviou somente a classe
                $metodo = 'index';
            }
            //verifica se ainda existe coisas a serem passadas pela url
            if (count($url) > 0) {
                $param = $url;
            }
        } else { //não tem informação após o domínio
            $controller = "homeController";
            $metodo = "index";
        }

        $caminho = "app/controllers/" . $controller . ".php";

        if (!file_exists($caminho) && !method_exists($controller, $metodo)) {
            $controller = "erroController";
            $metodo = "index";
        }

        $controller = "Name\\Controllers\\" . $controller;
        $c = new $controller;


        call_user_func_array(array($c, $metodo), $param);
    }
}
