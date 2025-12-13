<?php

/**
 * PaymentConfig.php - Configuración de Pasarelas de Pago
 * 
 * Configuración para integración con:
 * - MercadoPago (Internacional)
 * - Culqi (Perú)
 * - Yape (Perú - QR)
 * 
 * IMPORTANTE: En producción, mover credenciales a variables de entorno
 */

namespace App\Config;

class PaymentConfig
{
    /**
     * Modo de operación
     * 'sandbox' = Modo de pruebas
     * 'production' = Modo producción
     */
    const MODE = 'sandbox';

    /**
     * Pasarela de pago activa para tarjetas
     * Opciones: 'mercadopago', 'culqi', 'none'
     */
    const CARD_GATEWAY = 'culqi';

    /**
     * Configuración de MercadoPago
     * Obtener credenciales en: https://www.mercadopago.com.pe/developers
     */
    const MERCADOPAGO = [
        'sandbox' => [
            'public_key' => 'TEST-xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx',
            'access_token' => 'TEST-0000000000000000-000000-xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx-000000000'
        ],
        'production' => [
            'public_key' => 'APP_USR-xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx',
            'access_token' => 'APP_USR-0000000000000000-000000-xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx-000000000'
        ]
    ];

    /**
     * Configuración de Culqi (Perú)
     * Obtener credenciales en: https://www.culqi.com/
     */
    const CULQI = [
        'sandbox' => [
            'public_key' => 'pk_test_0b18bc03120d3f83',
            'secret_key' => 'sk_test_vbVGpz4RRBxxdlS2'
        ],
        'production' => [
            'public_key' => 'pk_live_xxxxxxxxxxxxxxxx',
            'secret_key' => 'sk_live_xxxxxxxxxxxxxxxx'
        ]
    ];

    /**
     * Configuración de Yape Business
     * Para recibir pagos con Yape QR
     */
    const YAPE = [
        'numero_celular' => '987654321',
        'nombre_titular' => 'NEKO SAC',
        'qr_image' => 'assets/img/yape-qr.png'
    ];

    /**
     * Moneda por defecto
     */
    const CURRENCY = 'PEN';

    /**
     * Obtiene las credenciales de MercadoPago según el modo
     */
    public static function getMercadoPagoCredentials(): array
    {
        return self::MERCADOPAGO[self::MODE];
    }

    /**
     * Obtiene las credenciales de Culqi según el modo
     */
    public static function getCulqiCredentials(): array
    {
        return self::CULQI[self::MODE];
    }

    /**
     * Obtiene la pasarela de tarjetas activa
     */
    public static function getCardGateway(): string
    {
        return self::CARD_GATEWAY;
    }

    /**
     * Verifica si está en modo sandbox
     */
    public static function isSandbox(): bool
    {
        return self::MODE === 'sandbox';
    }
}
