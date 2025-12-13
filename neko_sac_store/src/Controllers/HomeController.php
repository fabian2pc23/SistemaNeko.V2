<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Models\Articulo;
use App\Models\Categoria;

class HomeController extends Controller
{
    public function index(): void
    {
        $destacados = Articulo::destacados(8);
        $categorias = Categoria::activas();

        $this->view('home/index', [
            'destacados' => $destacados,
            'categorias' => $categorias,
        ]);
    }
}
