<?php

namespace Name\Controllers;

use Name\Core\Controller;

class homeController extends Controller
{
    public function index()
    {
        $this->carregarTemplate("home");
    }
}
