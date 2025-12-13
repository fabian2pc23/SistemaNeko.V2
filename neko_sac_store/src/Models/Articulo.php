<?php

namespace App\Models;

use App\Core\Database;
use PDO;

class Articulo
{
    public static function destacados(int $limit = 8): array
    {
        $sql = "SELECT a.idarticulo, a.nombre, a.descripcion, a.precio_venta, a.imagen, a.stock,
                       c.nombre AS categoria
                FROM articulo a
                JOIN categoria c ON c.idcategoria = a.idcategoria
                WHERE a.condicion = 1 AND c.condicion = 1
                ORDER BY a.idarticulo DESC
                LIMIT :lim";

        $pdo = Database::getConnection();
        $stmt = $pdo->prepare($sql);
        $stmt->bindValue(':lim', $limit, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll();
    }

    public static function paginar(int $page = 1, int $perPage = 12, ?int $idCategoria = null, ?string $q = null): array
    {
        $offset = ($page - 1) * $perPage;
        $params = [];
        $where = ['a.condicion = 1'];

        if ($idCategoria !== null) {
            $where[] = 'a.idcategoria = :idc';
            $params[':idc'] = $idCategoria;
        }

        if ($q !== null && $q !== '') {
            $where[] = '(a.nombre LIKE :q OR a.descripcion LIKE :q)';
            $params[':q'] = '%' . $q . '%';
        }

        $whereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';

        $baseSql = "FROM articulo a
                    JOIN categoria c ON c.idcategoria = a.idcategoria
                    {$whereSql}";

        $sql = "SELECT a.idarticulo, a.nombre, a.descripcion, a.precio_venta, a.imagen, a.stock,
                       c.nombre AS categoria
                {$baseSql}
                ORDER BY a.idarticulo DESC
                LIMIT :off, :pp";

        $countSql = "SELECT COUNT(*) AS total {$baseSql}";

        $pdo = Database::getConnection();

        // Conteo
        $stmt = $pdo->prepare($countSql);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        $stmt->execute();
        $total = (int) $stmt->fetchColumn();

        // Datos
        $stmt = $pdo->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        $stmt->bindValue(':off', $offset, PDO::PARAM_INT);
        $stmt->bindValue(':pp', $perPage, PDO::PARAM_INT);
        $stmt->execute();
        $items = $stmt->fetchAll();

        return [
            'items' => $items,
            'total' => $total,
            'page' => $page,
            'per_page' => $perPage,
            'pages' => (int) ceil($total / $perPage),
        ];
    }

    public static function find(int $id): ?array
    {
        $sql = "SELECT a.*, c.nombre AS categoria
                FROM articulo a
                JOIN categoria c ON c.idcategoria = a.idcategoria
                WHERE a.idarticulo = :id AND a.condicion = 1";

        $pdo = Database::getConnection();
        $stmt = $pdo->prepare($sql);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();

        $row = $stmt->fetch();
        return $row ?: null;
    }
}
