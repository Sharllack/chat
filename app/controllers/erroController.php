<?php

namespace Name\Controllers;

use Name\Core\Controller;

class erroController extends Controller
{
    public function index()
    {
        $this->carregarView("erro");
    }
}
