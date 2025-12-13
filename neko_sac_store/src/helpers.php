<?php

/**
 * helpers.php - Funciones auxiliares para la tienda
 */

/**
 * Obtiene la URL correcta de la imagen de un artículo
 * @param string|null $imagen Nombre del archivo de imagen
 * @return string URL completa de la imagen
 */
function getProductImageUrl(?string $imagen): string
{
    if (!empty($imagen)) {
        // Ruta a las imágenes de la aplicación principal (SistemaNeko.V2/files/articulos/)
        $mainAppUrl = str_replace('/neko_sac_store/public', '', BASE_URL);
        return $mainAppUrl . '/files/articulos/' . $imagen;
    }
    return BASE_URL . '/assets/img/placeholder.jpg';
}

/**
 * Formatea un precio en soles
 * @param float $precio
 * @return string
 */
function formatPrice(float $precio): string
{
    return 'S/ ' . number_format($precio, 2);
}

/**
 * Genera URL para la tienda con parámetros
 * @param array $params
 * @return string
 */
function storeUrl(array $params = []): string
{
    $base = BASE_URL . '/tienda';
    if (empty($params)) {
        return $base;
    }
    $merged = array_merge($_GET ?? [], $params);
    return $base . '?' . http_build_query($merged);
}
