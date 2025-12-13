<?php

/**
 * GreenterApi.php - API de Facturación Electrónica con Greenter
 * 
 * Reemplaza a NubeFact usando la librería Greenter para facturación electrónica
 * con SUNAT en Perú.
 * 
 * @author SistemaNeko
 * @version 1.0
 */

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/sunat_config.php';

use Greenter\See;
use Greenter\Ws\Services\SunatEndpoints;
use Greenter\Model\Client\Client;
use Greenter\Model\Company\Company;
use Greenter\Model\Company\Address;
use Greenter\Model\Sale\FormaPagos\FormaPagoContado;
use Greenter\Model\Sale\Invoice;
use Greenter\Model\Sale\SaleDetail;
use Greenter\Model\Sale\Legend;

class GreenterApi
{
    private $see;
    private $ruc;
    private $razonSocial;
    private $nombreComercial;
    private $direccion;
    private $ubigeo;
    private $departamento;
    private $provincia;
    private $distrito;

    // Rutas para guardar documentos
    private $xmlPath;
    private $cdrPath;

    // Último XML generado
    private $lastXml;

    /**
     * Constructor - Configura Greenter con certificado y credenciales SOL
     */
    public function __construct()
    {
        // Configuración de la empresa desde sunat_config.php
        $this->ruc = EMPRESA_RUC;
        $this->razonSocial = EMPRESA_RAZON_SOCIAL;
        $this->nombreComercial = EMPRESA_NOMBRE_COMERCIAL;
        $this->direccion = EMPRESA_DIRECCION;
        $this->ubigeo = EMPRESA_UBIGEO;
        $this->departamento = EMPRESA_DEPARTAMENTO;
        $this->provincia = EMPRESA_PROVINCIA;
        $this->distrito = EMPRESA_DISTRITO;

        // Rutas de almacenamiento desde sunat_config.php
        $this->xmlPath = PATH_XML;
        $this->cdrPath = PATH_CDR;

        // Inicializar Greenter See
        $this->see = new See();

        // Cargar certificado PEM
        if (file_exists(CERT_PATH)) {
            $this->see->setCertificate(file_get_contents(CERT_PATH));
        } else {
            throw new Exception('Certificado PEM no encontrado: ' . CERT_PATH);
        }

        // Configurar servicio SUNAT según modo
        if (SUNAT_MODO === 'produccion') {
            $this->see->setService(SunatEndpoints::FE_PRODUCCION);
        } else {
            $this->see->setService(SunatEndpoints::FE_BETA);
        }

        // Credenciales SOL desde sunat_config.php
        $this->see->setClaveSOL($this->ruc, SOL_USUARIO, SOL_CLAVE);
    }

    /**
     * Crear objeto Company (Emisor)
     */
    private function getCompany(): Company
    {
        $address = (new Address())
            ->setUbigueo($this->ubigeo)
            ->setDepartamento($this->departamento)
            ->setProvincia($this->provincia)
            ->setDistrito($this->distrito)
            ->setUrbanizacion('-')
            ->setDireccion($this->direccion)
            ->setCodLocal('0000'); // 0000 = establecimiento principal

        return (new Company())
            ->setRuc($this->ruc)
            ->setRazonSocial($this->razonSocial)
            ->setNombreComercial($this->nombreComercial)
            ->setAddress($address);
    }

    /**
     * Crear objeto Client (Receptor/Cliente)
     * 
     * @param string $tipoDoc Tipo documento: '1' = DNI, '6' = RUC, '0' = Sin RUC
     * @param string $numDoc Número de documento
     * @param string $rznSocial Razón social o nombre
     * @param string $direccion Dirección del cliente
     */
    private function getClient($tipoDoc, $numDoc, $rznSocial, $direccion = '-'): Client
    {
        return (new Client())
            ->setTipoDoc($tipoDoc)
            ->setNumDoc($numDoc)
            ->setRznSocial($rznSocial)
            ->setAddress((new Address())->setDireccion($direccion));
    }

    /**
     * Emitir Factura o Boleta Electrónica
     * 
     * @param array $data Datos del comprobante
     * @return array Resultado con éxito, mensaje, XML y CDR
     */
    public function emitirComprobante(array $data): array
    {
        $resp = [
            'exito' => false,
            'mensaje' => '',
            'codigo' => '',
            'hash' => '',
            'xml_local' => '',
            'cdr_local' => '',
            'sunat_code' => null,
            'sunat_description' => '',
            'observations' => []
        ];

        try {
            // Tipo de comprobante: 01 = Factura, 03 = Boleta
            $tipoDoc = ($data['tipo_comprobante'] === 'Factura') ? '01' : '03';

            // Tipo documento cliente
            $tipoDocCliente = '0'; // Sin RUC por defecto
            if (strlen($data['num_documento']) == 8) $tipoDocCliente = '1'; // DNI
            if (strlen($data['num_documento']) == 11) $tipoDocCliente = '6'; // RUC

            // Crear cliente
            $client = $this->getClient(
                $tipoDocCliente,
                $data['num_documento'],
                $data['cliente'],
                $data['direccion'] ?? '-'
            );

            // Crear factura/boleta
            $invoice = (new Invoice())
                ->setUblVersion('2.1')
                ->setTipoOperacion('0101') // Venta interna - Catalog. 51
                ->setTipoDoc($tipoDoc)
                ->setSerie($data['serie'])
                ->setCorrelativo($data['numero'])
                ->setFechaEmision(new DateTime($data['fecha'] . ' ' . date('H:i:s') . '-05:00'))
                ->setFormaPago(new FormaPagoContado())
                ->setTipoMoneda('PEN')
                ->setCompany($this->getCompany())
                ->setClient($client)
                ->setMtoOperGravadas($data['gravada'])
                ->setMtoIGV($data['igv'])
                ->setTotalImpuestos($data['igv'])
                ->setValorVenta($data['gravada'])
                ->setSubTotal($data['total'])
                ->setMtoImpVenta($data['total']);

            // Agregar detalles (items)
            $details = [];
            foreach ($data['items'] as $item) {
                $detail = (new SaleDetail())
                    ->setCodProducto($item['codigo'])
                    ->setUnidad('NIU') // Unidad
                    ->setCantidad($item['cantidad'])
                    ->setMtoValorUnitario($item['valor_unitario']) // Sin IGV
                    ->setDescripcion($item['descripcion'])
                    ->setMtoBaseIgv($item['base_igv'])
                    ->setPorcentajeIgv(18.00)
                    ->setIgv($item['igv'])
                    ->setTipAfeIgv('10') // Gravado Op. Onerosa - Catalog. 07
                    ->setTotalImpuestos($item['igv'])
                    ->setMtoValorVenta($item['base_igv'])
                    ->setMtoPrecioUnitario($item['precio_unitario']); // Con IGV

                $details[] = $detail;
            }

            // Leyenda (total en letras)
            $legend = (new Legend())
                ->setCode('1000')
                ->setValue($data['total_letras']);

            $invoice->setDetails($details)
                ->setLegends([$legend]);

            // Enviar a SUNAT
            $result = $this->see->send($invoice);

            // Guardar XML firmado
            $this->lastXml = $this->see->getFactory()->getLastXml();
            $xmlFilename = $invoice->getName() . '.xml';
            $xmlFullPath = $this->xmlPath . $xmlFilename;
            file_put_contents($xmlFullPath, $this->lastXml);
            $resp['xml_local'] = 'files/facturas/xml/' . $xmlFilename;

            // Verificar resultado
            if (!$result->isSuccess()) {
                $error = $result->getError();
                $resp['mensaje'] = 'Error SUNAT: ' . $error->getCode() . ' - ' . $error->getMessage();
                $resp['codigo'] = $error->getCode();
                return $resp;
            }

            // Guardar CDR
            $cdrZip = $result->getCdrZip();
            if ($cdrZip) {
                $cdrFilename = 'R-' . $invoice->getName() . '.zip';
                $cdrFullPath = $this->cdrPath . $cdrFilename;
                file_put_contents($cdrFullPath, $cdrZip);
                $resp['cdr_local'] = 'files/facturas/cdr/' . $cdrFilename;
            }

            // Leer respuesta CDR
            $cdr = $result->getCdrResponse();
            $code = (int)$cdr->getCode();

            $resp['sunat_code'] = $code;
            $resp['sunat_description'] = $cdr->getDescription();
            // getHash no existe en CdrResponse, usamos getId si está disponible
            $resp['hash'] = method_exists($cdr, 'getId') ? ($cdr->getId() ?? '') : '';

            if ($code === 0) {
                // ACEPTADA
                $resp['exito'] = true;
                $resp['mensaje'] = 'Comprobante aceptado por SUNAT';
                $resp['codigo'] = '0';

                // Verificar observaciones
                $notes = $cdr->getNotes();
                if (count($notes) > 0) {
                    $resp['observations'] = $notes;
                }
            } else if ($code >= 2000 && $code <= 3999) {
                // RECHAZADA
                $resp['mensaje'] = 'Comprobante rechazado por SUNAT: ' . $cdr->getDescription();
                $resp['codigo'] = (string)$code;
            } else {
                // Excepción
                $resp['mensaje'] = 'Excepción SUNAT: ' . $cdr->getDescription();
                $resp['codigo'] = (string)$code;
            }
        } catch (Exception $e) {
            $resp['mensaje'] = 'Error: ' . $e->getMessage();
        }

        return $resp;
    }

    /**
     * Generar solo XML firmado (sin enviar a SUNAT)
     * Útil para boletas que se envían en resumen diario
     */
    public function generarXmlFirmado(array $data): array
    {
        $resp = [
            'exito' => false,
            'mensaje' => '',
            'xml_local' => ''
        ];

        try {
            $tipoDoc = ($data['tipo_comprobante'] === 'Factura') ? '01' : '03';

            $tipoDocCliente = '0';
            if (strlen($data['num_documento']) == 8) $tipoDocCliente = '1';
            if (strlen($data['num_documento']) == 11) $tipoDocCliente = '6';

            $client = $this->getClient(
                $tipoDocCliente,
                $data['num_documento'],
                $data['cliente'],
                $data['direccion'] ?? '-'
            );

            $invoice = (new Invoice())
                ->setUblVersion('2.1')
                ->setTipoOperacion('0101')
                ->setTipoDoc($tipoDoc)
                ->setSerie($data['serie'])
                ->setCorrelativo($data['numero'])
                ->setFechaEmision(new DateTime($data['fecha'] . '-05:00'))
                ->setFormaPago(new FormaPagoContado())
                ->setTipoMoneda('PEN')
                ->setCompany($this->getCompany())
                ->setClient($client)
                ->setMtoOperGravadas($data['gravada'])
                ->setMtoIGV($data['igv'])
                ->setTotalImpuestos($data['igv'])
                ->setValorVenta($data['gravada'])
                ->setSubTotal($data['total'])
                ->setMtoImpVenta($data['total']);

            $details = [];
            foreach ($data['items'] as $item) {
                $detail = (new SaleDetail())
                    ->setCodProducto($item['codigo'])
                    ->setUnidad('NIU')
                    ->setCantidad($item['cantidad'])
                    ->setMtoValorUnitario($item['valor_unitario'])
                    ->setDescripcion($item['descripcion'])
                    ->setMtoBaseIgv($item['base_igv'])
                    ->setPorcentajeIgv(18.00)
                    ->setIgv($item['igv'])
                    ->setTipAfeIgv('10')
                    ->setTotalImpuestos($item['igv'])
                    ->setMtoValorVenta($item['base_igv'])
                    ->setMtoPrecioUnitario($item['precio_unitario']);

                $details[] = $detail;
            }

            $legend = (new Legend())
                ->setCode('1000')
                ->setValue($data['total_letras']);

            $invoice->setDetails($details)
                ->setLegends([$legend]);

            // Solo generar XML, no enviar
            $xml = $this->see->getXmlSigned($invoice);

            $xmlFilename = $invoice->getName() . '.xml';
            $xmlFullPath = $this->xmlPath . $xmlFilename;
            file_put_contents($xmlFullPath, $xml);

            $resp['exito'] = true;
            $resp['mensaje'] = 'XML generado correctamente';
            $resp['xml_local'] = 'files/facturas/xml/' . $xmlFilename;
        } catch (Exception $e) {
            $resp['mensaje'] = 'Error: ' . $e->getMessage();
        }

        return $resp;
    }

    /**
     * Obtener el último XML generado
     */
    public function getLastXml(): ?string
    {
        return $this->lastXml;
    }

    /**
     * Convertir número a letras para la leyenda
     */
    public static function numtoletras($num): string
    {
        require_once __DIR__ . '/SunatApi.php';
        return SunatApi::numtoletras($num);
    }
}
