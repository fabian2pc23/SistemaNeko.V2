<?php

namespace App\Models;

use App\Core\Database;
use PDO;

class Cliente
{
    /**
     * Buscar cliente por ID
     */
    public static function find(int $id): ?array
    {
        $sql = "SELECT * FROM cliente_online WHERE idcliente = :id";
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare($sql);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        $row = $stmt->fetch();
        return $row ?: null;
    }

    /**
     * Buscar cliente por email
     */
    public static function findByEmail(string $email): ?array
    {
        $sql = "SELECT * FROM cliente_online WHERE email = :email";
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare($sql);
        $stmt->bindValue(':email', $email);
        $stmt->execute();
        $row = $stmt->fetch();
        return $row ?: null;
    }

    /**
     * Buscar cliente por proveedor OAuth
     */
    public static function findByOAuth(string $provider, string $providerId): ?array
    {
        $sql = "SELECT * FROM cliente_online WHERE oauth_provider = :provider AND oauth_id = :oauth_id";
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare($sql);
        $stmt->bindValue(':provider', $provider);
        $stmt->bindValue(':oauth_id', $providerId);
        $stmt->execute();
        $row = $stmt->fetch();
        return $row ?: null;
    }

    /**
     * Crear nuevo cliente
     */
    public static function create(array $data): int
    {
        $pdo = Database::getConnection();

        $sql = "INSERT INTO cliente_online (
            nombre, apellido, email, telefono, password_hash,
            oauth_provider, oauth_id, avatar_url,
            fecha_registro, ultimo_acceso, activo
        ) VALUES (
            :nombre, :apellido, :email, :telefono, :password_hash,
            :oauth_provider, :oauth_id, :avatar_url,
            NOW(), NOW(), 1
        )";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':nombre' => $data['nombre'] ?? '',
            ':apellido' => $data['apellido'] ?? '',
            ':email' => $data['email'],
            ':telefono' => $data['telefono'] ?? null,
            ':password_hash' => $data['password_hash'] ?? null,
            ':oauth_provider' => $data['oauth_provider'] ?? null,
            ':oauth_id' => $data['oauth_id'] ?? null,
            ':avatar_url' => $data['avatar_url'] ?? null
        ]);

        return (int) $pdo->lastInsertId();
    }

    /**
     * Actualizar último acceso
     */
    public static function updateLastAccess(int $id): void
    {
        $sql = "UPDATE cliente_online SET ultimo_acceso = NOW() WHERE idcliente = :id";
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':id' => $id]);
    }

    /**
     * Actualizar perfil
     */
    public static function update(int $id, array $data): bool
    {
        $pdo = Database::getConnection();

        $fields = [];
        $params = [':id' => $id];

        foreach (['nombre', 'apellido', 'telefono', 'direccion'] as $field) {
            if (isset($data[$field])) {
                $fields[] = "$field = :$field";
                $params[":$field"] = $data[$field];
            }
        }

        if (empty($fields)) {
            return false;
        }

        $sql = "UPDATE cliente_online SET " . implode(', ', $fields) . " WHERE idcliente = :id";
        $stmt = $pdo->prepare($sql);
        return $stmt->execute($params);
    }

    /**
     * Verificar contraseña
     */
    public static function verifyPassword(array $cliente, string $password): bool
    {
        if (empty($cliente['password_hash'])) {
            return false;
        }
        return password_verify($password, $cliente['password_hash']);
    }

    /**
     * Hashear contraseña
     */
    public static function hashPassword(string $password): string
    {
        return password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
    }
}
