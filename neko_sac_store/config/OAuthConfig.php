<?php

/**
 * OAuthConfig.php - Configuración de autenticación OAuth
 * 
 * Para obtener credenciales:
 * - Google: https://console.developers.google.com/
 * - Facebook: https://developers.facebook.com/apps/
 * 
 * IMPORTANTE: Las credenciales deben configurarse en el archivo .env
 * Nunca incluir credenciales directamente en este archivo.
 */

namespace App\Config;

class OAuthConfig
{
    /**
     * Inicializar variables de entorno desde archivo .env
     */
    private static function loadEnv(): void
    {
        static $loaded = false;
        if ($loaded) return;

        $envFile = dirname(__DIR__, 2) . '/.env';
        if (file_exists($envFile)) {
            $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            foreach ($lines as $line) {
                $line = trim($line);
                // Ignorar comentarios
                if (strpos($line, '#') === 0) continue;
                // Parsear KEY=VALUE
                if (strpos($line, '=') !== false) {
                    list($key, $value) = explode('=', $line, 2);
                    $key = trim($key);
                    $value = trim($value);
                    // Solo establecer si no existe ya
                    if (!getenv($key)) {
                        putenv("$key=$value");
                        $_ENV[$key] = $value;
                    }
                }
            }
        }
        $loaded = true;
    }

    /**
     * Configuración de Google OAuth
     * 
     * 1. Ir a Google Cloud Console
     * 2. Crear proyecto o seleccionar existente
     * 3. Ir a "APIs & Services" > "Credentials"
     * 4. Crear "OAuth 2.0 Client ID" tipo "Web Application"
     * 5. Agregar URI de redirección autorizado
     * 6. Copiar Client ID y Secret al archivo .env
     */
    public static function getGoogle(): array
    {
        self::loadEnv();

        return [
            'client_id' => getenv('GOOGLE_CLIENT_ID') ?: '',
            'client_secret' => getenv('GOOGLE_CLIENT_SECRET') ?: '',
            'redirect_uri' => BASE_URL . '/auth/google/callback'
        ];
    }

    /**
     * Configuración de Facebook OAuth
     * 
     * 1. Ir a Facebook Developers
     * 2. Crear o seleccionar app
     * 3. Ir a "Facebook Login" > "Settings"
     * 4. Agregar URI de redirección OAuth válidos
     * 5. Copiar App ID y Secret al archivo .env
     */
    public static function getFacebook(): array
    {
        self::loadEnv();

        return [
            'app_id' => getenv('FACEBOOK_APP_ID') ?: '',
            'app_secret' => getenv('FACEBOOK_APP_SECRET') ?: '',
            'redirect_uri' => BASE_URL . '/auth/facebook/callback'
        ];
    }

    /**
     * Verificar si OAuth está configurado
     */
    public static function isGoogleConfigured(): bool
    {
        $config = self::getGoogle();
        return !empty($config['client_id']) && !empty($config['client_secret'])
            && !str_contains($config['client_id'], 'your_google');
    }

    public static function isFacebookConfigured(): bool
    {
        $config = self::getFacebook();
        return !empty($config['app_id']) && !empty($config['app_secret'])
            && !str_contains($config['app_id'], 'your_facebook');
    }
}
