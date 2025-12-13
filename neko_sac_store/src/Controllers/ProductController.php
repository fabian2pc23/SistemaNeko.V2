<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Models\Articulo;
use App\Models\Categoria;

class ProductController extends Controller
{
    public function index(): void
    {
        $page = isset($_GET['page']) ? max(1, (int) $_GET['page']) : 1;
        $idc  = isset($_GET['categoria']) ? (int) $_GET['categoria'] : null;
        $q    = isset($_GET['q']) ? trim((string) $_GET['q']) : null;

        $categorias = Categoria::activas();
        $paginado = Articulo::paginar($page, 12, $idc, $q);

        $this->view('products/index', [
            'categorias' => $categorias,
            'paginado'   => $paginado,
            'filtroCat'  => $idc,
            'filtroQ'    => $q,
        ]);
    }

    public function show(int $id): void
    {
        $producto = Articulo::find($id);

        if (!$producto) {
            http_response_code(404);
            echo 'Producto no encontrado';
            return;
        }

        $this->view('products/show', [
            'producto' => $producto,
        ]);
    }
}
