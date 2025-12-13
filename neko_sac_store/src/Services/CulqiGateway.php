<?php

/**
 * CulqiGateway.php - Integración con Culqi para pagos con tarjeta
 * 
 * Culqi es una de las pasarelas de pago más populares en Perú
 * Documentación: https://docs.culqi.com/
 */

namespace App\Services;

class CulqiGateway
{
    private string $publicKey;
    private string $secretKey;
    private string $apiUrl = 'https://api.culqi.com/v2';

    public function __construct()
    {
        $config = \App\Config\PaymentConfig::getCulqiCredentials();
        $this->publicKey = $config['public_key'];
        $this->secretKey = $config['secret_key'];
    }

    /**
     * Obtiene la public key para el frontend
     */
    public function getPublicKey(): string
    {
        return $this->publicKey;
    }

    /**
     * Crear un cargo (cobro) con un token de tarjeta
     * 
     * @param string $tokenId Token generado por Culqi.js en el frontend
     * @param float $amount Monto en soles (se convierte a céntimos automáticamente)
     * @param string $email Email del cliente
     * @param string $description Descripción del pago
     * @param array $metadata Datos adicionales
     * @return array Respuesta del cargo
     */
    public function createCharge(
        string $tokenId,
        float $amount,
        string $email,
        string $description = '',
        array $metadata = []
    ): array {
        // Culqi requiere el monto en céntimos
        $amountCents = (int) round($amount * 100);

        $data = [
            'amount' => $amountCents,
            'currency_code' => 'PEN',
            'email' => $email,
            'source_id' => $tokenId,
            'description' => $description ?: 'Compra en NEKO SAC Store',
            'capture' => true, // Captura inmediata
            'metadata' => array_merge([
                'store' => 'NEKO SAC Store',
                'date' => date('Y-m-d H:i:s')
            ], $metadata)
        ];

        return $this->makeRequest('/charges', $data);
    }

    /**
     * Verificar un cargo existente
     */
    public function getCharge(string $chargeId): array
    {
        return $this->makeRequest('/charges/' . $chargeId, null, 'GET');
    }

    /**
     * Crear un reembolso
     */
    public function createRefund(string $chargeId, float $amount, string $reason = 'solicitud_comprador'): array
    {
        $data = [
            'amount' => (int) round($amount * 100),
            'charge_id' => $chargeId,
            'reason' => $reason
        ];

        return $this->makeRequest('/refunds', $data);
    }

    /**
     * Realizar petición a la API de Culqi
     */
    private function makeRequest(string $endpoint, ?array $data = null, string $method = 'POST'): array
    {
        $curl = curl_init();

        $options = [
            CURLOPT_URL => $this->apiUrl . $endpoint,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_HTTPHEADER => [
                'Authorization: Bearer ' . $this->secretKey,
                'Content-Type: application/json',
                'Accept: application/json'
            ],
            CURLOPT_SSL_VERIFYPEER => true
        ];

        if ($method === 'POST' && $data !== null) {
            $options[CURLOPT_CUSTOMREQUEST] = 'POST';
            $options[CURLOPT_POSTFIELDS] = json_encode($data);
        } elseif ($method === 'GET') {
            $options[CURLOPT_CUSTOMREQUEST] = 'GET';
        }

        curl_setopt_array($curl, $options);

        $response = curl_exec($curl);
        $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        $error = curl_error($curl);

        curl_close($curl);

        if ($error) {
            return [
                'success' => false,
                'error' => 'Error de conexión: ' . $error,
                'http_code' => 0
            ];
        }

        $decoded = json_decode($response, true);

        if ($httpCode >= 200 && $httpCode < 300) {
            return [
                'success' => true,
                'data' => $decoded,
                'http_code' => $httpCode
            ];
        }

        return [
            'success' => false,
            'error' => $decoded['merchant_message'] ?? $decoded['user_message'] ?? 'Error desconocido',
            'data' => $decoded,
            'http_code' => $httpCode
        ];
    }
}
