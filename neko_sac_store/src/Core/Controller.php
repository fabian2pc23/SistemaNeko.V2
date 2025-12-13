<?php
namespace App\Core;

abstract class Controller
{
    protected function view(string $view, array $params = []): void
    {
        View::render($view, $params);
    }

    protected function redirect(string $path): void
    {
        header('Location: ' . rtrim(BASE_URL, '/') . $path);
        exit;
    }
}
