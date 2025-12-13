<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Models\Cliente;

class AuthController extends Controller
{
    /**
     * Mostrar formulario de login
     */
    public function loginForm(): void
    {
        if ($this->isLoggedIn()) {
            $this->redirect('/mi-cuenta');
            return;
        }

        $this->renderAuthView('login');
    }

    /**
     * Procesar login
     */
    public function login(): void
    {
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $remember = isset($_POST['remember']);

        if (empty($email) || empty($password)) {
            $_SESSION['auth_error'] = 'Por favor complete todos los campos';
            $this->redirect('/login');
            return;
        }

        $cliente = Cliente::findByEmail($email);

        if (!$cliente || !Cliente::verifyPassword($cliente, $password)) {
            $_SESSION['auth_error'] = 'Email o contraseña incorrectos';
            $this->redirect('/login');
            return;
        }

        if (!$cliente['activo']) {
            $_SESSION['auth_error'] = 'Tu cuenta está desactivada. Contacta soporte.';
            $this->redirect('/login');
            return;
        }

        // Login exitoso
        $this->setClienteSession($cliente);
        Cliente::updateLastAccess($cliente['idcliente']);

        $this->redirect('/mi-cuenta');
    }

    /**
     * Mostrar formulario de registro
     */
    public function registroForm(): void
    {
        if ($this->isLoggedIn()) {
            $this->redirect('/mi-cuenta');
            return;
        }

        $this->renderAuthView('registro');
    }

    /**
     * Procesar registro
     */
    public function registro(): void
    {
        $nombre = trim($_POST['nombre'] ?? '');
        $apellido = trim($_POST['apellido'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $telefono = trim($_POST['telefono'] ?? '');
        $password = $_POST['password'] ?? '';
        $passwordConfirm = $_POST['password_confirm'] ?? '';

        // Validaciones
        if (empty($nombre) || empty($email) || empty($password)) {
            $_SESSION['auth_error'] = 'Por favor complete los campos obligatorios';
            $this->redirect('/registro');
            return;
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $_SESSION['auth_error'] = 'Email no válido';
            $this->redirect('/registro');
            return;
        }

        if (strlen($password) < 6) {
            $_SESSION['auth_error'] = 'La contraseña debe tener al menos 6 caracteres';
            $this->redirect('/registro');
            return;
        }

        if ($password !== $passwordConfirm) {
            $_SESSION['auth_error'] = 'Las contraseñas no coinciden';
            $this->redirect('/registro');
            return;
        }

        // Verificar si el email ya existe
        $existente = Cliente::findByEmail($email);
        if ($existente) {
            $_SESSION['auth_error'] = 'Este email ya está registrado. <a href="' . BASE_URL . '/login">Inicia sesión</a>';
            $this->redirect('/registro');
            return;
        }

        // Crear cliente
        $idCliente = Cliente::create([
            'nombre' => $nombre,
            'apellido' => $apellido,
            'email' => $email,
            'telefono' => $telefono ?: null,
            'password_hash' => Cliente::hashPassword($password)
        ]);

        if (!$idCliente) {
            $_SESSION['auth_error'] = 'Error al crear la cuenta. Intenta nuevamente.';
            $this->redirect('/registro');
            return;
        }

        // Login automático después del registro
        $cliente = Cliente::find($idCliente);
        $this->setClienteSession($cliente);

        $_SESSION['auth_success'] = '¡Bienvenido! Tu cuenta ha sido creada exitosamente.';
        $this->redirect('/mi-cuenta');
    }

    /**
     * Cerrar sesión
     */
    public function logout(): void
    {
        unset($_SESSION['cliente']);
        $_SESSION['auth_success'] = 'Has cerrado sesión correctamente';
        $this->redirect('/login');
    }

    /**
     * Redirección a Google OAuth
     */
    public function googleRedirect(): void
    {
        require_once __DIR__ . '/../../config/OAuthConfig.php';
        $config = \App\Config\OAuthConfig::getGoogle();

        $params = http_build_query([
            'client_id' => $config['client_id'],
            'redirect_uri' => $config['redirect_uri'],
            'response_type' => 'code',
            'scope' => 'email profile',
            'access_type' => 'online',
            'prompt' => 'select_account'
        ]);

        header('Location: https://accounts.google.com/o/oauth2/v2/auth?' . $params);
        exit;
    }

    /**
     * Callback de Google OAuth
     */
    public function googleCallback(): void
    {
        require_once __DIR__ . '/../../config/OAuthConfig.php';
        $config = \App\Config\OAuthConfig::getGoogle();

        $code = $_GET['code'] ?? '';

        if (empty($code)) {
            $_SESSION['auth_error'] = 'Error al autenticar con Google';
            $this->redirect('/login');
            return;
        }

        try {
            // Intercambiar código por token
            $tokenData = $this->exchangeGoogleCode($code, $config);

            if (!$tokenData || !isset($tokenData['access_token'])) {
                throw new \Exception('No se pudo obtener token de acceso');
            }

            // Obtener datos del usuario
            $userData = $this->getGoogleUserData($tokenData['access_token']);

            if (!$userData || !isset($userData['email'])) {
                throw new \Exception('No se pudieron obtener datos del usuario');
            }

            // Buscar o crear cliente
            $cliente = $this->findOrCreateOAuthClient('google', $userData['id'], [
                'email' => $userData['email'],
                'nombre' => $userData['given_name'] ?? $userData['name'] ?? '',
                'apellido' => $userData['family_name'] ?? '',
                'avatar_url' => $userData['picture'] ?? null
            ]);

            $this->setClienteSession($cliente);
            Cliente::updateLastAccess($cliente['idcliente']);

            $this->redirect('/mi-cuenta');
        } catch (\Exception $e) {
            $_SESSION['auth_error'] = 'Error al autenticar con Google: ' . $e->getMessage();
            $this->redirect('/login');
        }
    }

    /**
     * Redirección a Facebook OAuth
     */
    public function facebookRedirect(): void
    {
        require_once __DIR__ . '/../../config/OAuthConfig.php';
        $config = \App\Config\OAuthConfig::getFacebook();

        $params = http_build_query([
            'client_id' => $config['app_id'],
            'redirect_uri' => $config['redirect_uri'],
            'response_type' => 'code',
            'scope' => 'email,public_profile'
        ]);

        header('Location: https://www.facebook.com/v18.0/dialog/oauth?' . $params);
        exit;
    }

    /**
     * Callback de Facebook OAuth
     */
    public function facebookCallback(): void
    {
        require_once __DIR__ . '/../../config/OAuthConfig.php';
        $config = \App\Config\OAuthConfig::getFacebook();

        $code = $_GET['code'] ?? '';

        if (empty($code)) {
            $_SESSION['auth_error'] = 'Error al autenticar con Facebook';
            $this->redirect('/login');
            return;
        }

        try {
            // Intercambiar código por token
            $tokenData = $this->exchangeFacebookCode($code, $config);

            if (!$tokenData || !isset($tokenData['access_token'])) {
                throw new \Exception('No se pudo obtener token de acceso');
            }

            // Obtener datos del usuario
            $userData = $this->getFacebookUserData($tokenData['access_token']);

            if (!$userData || !isset($userData['id'])) {
                throw new \Exception('No se pudieron obtener datos del usuario');
            }

            // Separar nombre
            $nombres = explode(' ', $userData['name'] ?? '', 2);

            // Buscar o crear cliente
            $cliente = $this->findOrCreateOAuthClient('facebook', $userData['id'], [
                'email' => $userData['email'] ?? 'fb_' . $userData['id'] . '@facebook.local',
                'nombre' => $nombres[0] ?? '',
                'apellido' => $nombres[1] ?? '',
                'avatar_url' => $userData['picture']['data']['url'] ?? null
            ]);

            $this->setClienteSession($cliente);
            Cliente::updateLastAccess($cliente['idcliente']);

            $this->redirect('/mi-cuenta');
        } catch (\Exception $e) {
            $_SESSION['auth_error'] = 'Error al autenticar con Facebook: ' . $e->getMessage();
            $this->redirect('/login');
        }
    }

    /**
     * Página Mi Cuenta
     */
    public function miCuenta(): void
    {
        if (!$this->isLoggedIn()) {
            $this->redirect('/login');
            return;
        }

        $cliente = Cliente::find($_SESSION['cliente']['id']);

        $this->view('auth/mi-cuenta', [
            'cliente' => $cliente,
            'success' => $_SESSION['auth_success'] ?? null,
            'error' => $_SESSION['auth_error'] ?? null
        ]);

        unset($_SESSION['auth_success'], $_SESSION['auth_error']);
    }

    /**
     * Actualizar perfil
     */
    public function actualizarPerfil(): void
    {
        if (!$this->isLoggedIn()) {
            $this->redirect('/login');
            return;
        }

        $data = [
            'nombre' => trim($_POST['nombre'] ?? ''),
            'apellido' => trim($_POST['apellido'] ?? ''),
            'telefono' => trim($_POST['telefono'] ?? ''),
            'direccion' => trim($_POST['direccion'] ?? '')
        ];

        if (empty($data['nombre'])) {
            $_SESSION['auth_error'] = 'El nombre es obligatorio';
            $this->redirect('/mi-cuenta');
            return;
        }

        Cliente::update($_SESSION['cliente']['id'], $data);

        // Actualizar sesión
        $_SESSION['cliente']['nombre'] = $data['nombre'];
        $_SESSION['cliente']['apellido'] = $data['apellido'];

        $_SESSION['auth_success'] = 'Perfil actualizado correctamente';
        $this->redirect('/mi-cuenta');
    }

    // ===== MÉTODOS AUXILIARES =====

    /**
     * Renderiza la vista unificada de autenticación
     */
    private function renderAuthView(string $mode): void
    {
        $error = $_SESSION['auth_error'] ?? null;
        $success = $_SESSION['auth_success'] ?? null;
        unset($_SESSION['auth_error'], $_SESSION['auth_success']);

        // Incluir directamente el archivo para usar layout standalone
        require __DIR__ . '/../Views/auth/auth-unified.php';
        exit;
    }

    private function isLoggedIn(): bool
    {
        return isset($_SESSION['cliente']) && !empty($_SESSION['cliente']['id']);
    }

    private function setClienteSession(array $cliente): void
    {
        $_SESSION['cliente'] = [
            'id' => $cliente['idcliente'],
            'nombre' => $cliente['nombre'],
            'apellido' => $cliente['apellido'] ?? '',
            'email' => $cliente['email'],
            'avatar' => $cliente['avatar_url'] ?? null
        ];
    }

    private function findOrCreateOAuthClient(string $provider, string $providerId, array $userData): array
    {
        // Buscar por OAuth
        $cliente = Cliente::findByOAuth($provider, $providerId);

        if ($cliente) {
            return $cliente;
        }

        // Buscar por email
        $cliente = Cliente::findByEmail($userData['email']);

        if ($cliente) {
            // Vincular cuenta existente con OAuth
            $pdo = \App\Core\Database::getConnection();
            $stmt = $pdo->prepare("UPDATE cliente_online SET oauth_provider = ?, oauth_id = ?, avatar_url = COALESCE(avatar_url, ?) WHERE idcliente = ?");
            $stmt->execute([$provider, $providerId, $userData['avatar_url'], $cliente['idcliente']]);
            return Cliente::find($cliente['idcliente']);
        }

        // Crear nuevo cliente
        $idCliente = Cliente::create([
            'nombre' => $userData['nombre'],
            'apellido' => $userData['apellido'],
            'email' => $userData['email'],
            'oauth_provider' => $provider,
            'oauth_id' => $providerId,
            'avatar_url' => $userData['avatar_url']
        ]);

        return Cliente::find($idCliente);
    }

    private function exchangeGoogleCode(string $code, array $config): ?array
    {
        $ch = curl_init('https://oauth2.googleapis.com/token');
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => http_build_query([
                'code' => $code,
                'client_id' => $config['client_id'],
                'client_secret' => $config['client_secret'],
                'redirect_uri' => $config['redirect_uri'],
                'grant_type' => 'authorization_code'
            ])
        ]);
        $response = curl_exec($ch);
        curl_close($ch);
        return json_decode($response, true);
    }

    private function getGoogleUserData(string $accessToken): ?array
    {
        $ch = curl_init('https://www.googleapis.com/oauth2/v2/userinfo');
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => ['Authorization: Bearer ' . $accessToken]
        ]);
        $response = curl_exec($ch);
        curl_close($ch);
        return json_decode($response, true);
    }

    private function exchangeFacebookCode(string $code, array $config): ?array
    {
        $url = 'https://graph.facebook.com/v18.0/oauth/access_token?' . http_build_query([
            'client_id' => $config['app_id'],
            'client_secret' => $config['app_secret'],
            'redirect_uri' => $config['redirect_uri'],
            'code' => $code
        ]);

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $response = curl_exec($ch);
        curl_close($ch);
        return json_decode($response, true);
    }

    private function getFacebookUserData(string $accessToken): ?array
    {
        $url = 'https://graph.facebook.com/me?fields=id,name,email,picture.type(large)&access_token=' . $accessToken;

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $response = curl_exec($ch);
        curl_close($ch);
        return json_decode($response, true);
    }
}
