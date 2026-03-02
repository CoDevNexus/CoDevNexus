<?php
namespace App\Controllers;

use Core\Controller;

class HomeController extends Controller
{
    public function index(): void
    {
        $this->render('home.index', [], 'main');
    }
}
