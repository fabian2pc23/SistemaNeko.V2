<?php
namespace App\Models;

use App\Core\Database;

class Categoria
{
    public static function activas(): array
    {
        $sql = "SELECT idcategoria, nombre
                FROM categoria
                WHERE condicion = 1
                ORDER BY nombre ASC";

        $pdo = Database::getConnection();
        return $pdo->query($sql)->fetchAll();
    }
}
