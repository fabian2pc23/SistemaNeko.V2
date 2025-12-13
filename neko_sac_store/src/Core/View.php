<?php
namespace App\Core;

class View
{
    public static function render(string $view, array $params = []): void
    {
        $viewFile = __DIR__ . '/../Views/' . $view . '.php';

        if (!file_exists($viewFile)) {
            throw new \RuntimeException("Vista no encontrada: {$view}");
        }

        extract($params, EXTR_SKIP);

        ob_start();
        require $viewFile;
        $content = ob_get_clean();

        // Layout principal
        require __DIR__ . '/../Views/layouts/main.php';
    }
}
