<?php

/**
 * SunatApi.php - API de NubeFact para Facturación Electrónica
 * 
 * Este archivo maneja la conexión con NubeFact como proveedor alternativo
 * de facturación electrónica.
 */

require_once __DIR__ . '/sunat_config.php';

class SunatApi
{
    private $token;
    private $ruta;

    public function __construct()
    {
        // Usar configuración desde sunat_config.php
        $this->ruta = NUBEFACT_RUTA;
        $this->token = NUBEFACT_TOKEN;
    }

    public function emitirComprobante($data)
    {
        $json_data = json_encode($data);
        return $this->callApi($this->ruta, $json_data);
    }

    // Métodos legacy (ApisPeru) - Se pueden mantener o deprecar
    public function generarPdf($data)
    {
        return null;
    }
    public function generarXml($data)
    {
        return null;
    }

    private function callApi($url, $json_data)
    {
        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => $json_data,
            CURLOPT_HTTPHEADER => array(
                'Content-Type: application/json',
                'Authorization: Token token="' . trim($this->token) . '"'
            ),
            CURLOPT_SSL_VERIFYPEER => false
        ));
        $response = curl_exec($curl);
        $httpcode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        curl_close($curl);
        return ['status' => $httpcode, 'response' => $response];
    }

    public static function numtoletras($xcifra)
    {
        $xarray = array(
            0 => "Cero",
            1 => "UN",
            "DOS",
            "TRES",
            "CUATRO",
            "CINCO",
            "SEIS",
            "SIETE",
            "OCHO",
            "NUEVE",
            "DIEZ",
            "ONCE",
            "DOCE",
            "TRECE",
            "CATORCE",
            "QUINCE",
            "DIECISEIS",
            "DIECISIETE",
            "DIECIOCHO",
            "DIECINUEVE",
            "VEINTI",
            30 => "TREINTA",
            40 => "CUARENTA",
            50 => "CINCUENTA",
            60 => "SESENTA",
            70 => "SETENTA",
            80 => "OCHENTA",
            90 => "NOVENTA",
            100 => "CIENTO",
            200 => "DOSCIENTOS",
            300 => "TRESCIENTOS",
            400 => "CUATROCIENTOS",
            500 => "QUINIENTOS",
            600 => "SEISCIENTOS",
            700 => "SETECIENTOS",
            800 => "OCHOCIENTOS",
            900 => "NOVECIENTOS"
        );

        $xcifra = trim($xcifra);
        $xlength = strlen($xcifra);
        $xpos_punto = strpos($xcifra, ".");
        $xaux_int = $xcifra;
        $xdecimales = "00";
        if (!($xpos_punto === false)) {
            if ($xpos_punto == 0) {
                $xcifra = "0" . $xcifra;
                $xpos_punto = strpos($xcifra, ".");
            }
            $xaux_int = substr($xcifra, 0, $xpos_punto); // obtengo el entero de la cifra a covertir
            $xdecimales = substr($xcifra . "00", $xpos_punto + 1, 2); // obtengo los valores decimales
        }

        $XAUX = str_pad($xaux_int, 18, " ", STR_PAD_LEFT); // ajusto la longitud de la cifra, para que sea divisible por ternas de miles
        $xcadena = "";
        for ($xz = 0; $xz < 3; $xz++) {
            $xaux = substr($XAUX, $xz * 6, 6);
            $xi = trim(substr($xaux, 0, 3));
            $xbox = "";
            if ($xi > 0) {
                $x3digitos = SunatApi::cons($xi, $xarray);
                $xbox = $x3digitos . (($xz == 1) ? " MIL" : "");
                $xsp = ($xi > 0 && substr($xaux, 3, 3) > 0) ? " " : "";
                $xcadena .= SunatApi::cons(substr($xaux, 3, 3), $xarray) . $xsp . $xbox;
            } else {
                $xcadena .= SunatApi::cons(substr($xaux, 3, 3), $xarray);
            }
            if (substr(trim($xcadena), -5, 5) == "ILLON") // si la cadena obtenida termina en MILLON o BILLON, entonces le agrega al final la conjuncion DE
                $xcadena .= " DE";

            if (substr(trim($xcadena), -7, 7) == "ILLONES") // si la cadena obtenida en MILLONES o BILLONES, entoncea le agrega al final la conjuncion DE
                $xcadena .= " DE";

            // trabajo con desicion "ciento"
            if (trim($xaux) != "") {
                switch ($xz) {
                    case 0:
                        if (trim(substr($XAUX, $xz * 6, 6)) == "1")
                            $xcadena .= "UN BILLON ";
                        else
                            $xcadena .= " BILLONES ";
                        break;
                    case 1:
                        if (trim(substr($XAUX, $xz * 6, 6)) == "1")
                            $xcadena .= "UN MILLON ";
                        else
                            $xcadena .= " MILLONES ";
                        break;
                    case 2:
                        if ($xcifra < 1) {
                            $xcadena = "CERO TANTOS  ";
                        }
                        if ($xcifra >= 1 && $xcifra < 2) {
                            $xcadena = "UN  ";
                        }
                        if ($xcifra >= 2) {
                            $xcadena .= "  "; //
                        }
                        break;
                }
            }
        }
        $xcadena = str_replace("VEINTI ", "VEINTI", $xcadena);
        $xcadena = str_replace("  ", " ", $xcadena);
        $xcadena = str_replace("UN UN", "UN", $xcadena);
        $xcadena = str_replace("  ", " ", $xcadena);
        $xcadena = str_replace("BILLON DE MILLONES", "BILLON DE", $xcadena);
        $xcadena = str_replace("BILLONES DE MILLONES", "BILLONES DE", $xcadena);
        $xcadena = str_replace("DE UN", "DE", $xcadena);
        $xcadena = str_replace("UN MIL", "MIL", $xcadena);
        return trim($xcadena) . " CON " . $xdecimales . "/100 SOLES";
    }

    private static function cons($x3digitos, $xarray)
    {
        $x3digitos = trim($x3digitos);
        if (strlen($x3digitos) == 1) {
            $x3digitos = "00" . $x3digitos;
        }
        if (strlen($x3digitos) == 2) {
            $x3digitos = "0" . $x3digitos;
        }

        $n1 = substr($x3digitos, 0, 1);
        $n2 = substr($x3digitos, 1, 1);
        $n3 = substr($x3digitos, 2, 1);

        $str = "";

        // Centenas
        if ($n1 > 0) {
            if ($n1 == 1) {
                if ($n2 == 0 && $n3 == 0) $str = "CIEN";
                else $str = "CIENTO";
            } else {
                $str = $xarray[$n1 * 100];
            }
        }

        // Decenas y Unidades
        if ($n2 > 0) {
            if ($n2 == 1) { // 10-19
                if ($n3 < 6) $str = ($str ? $str . " " : "") . $xarray[10 + $n3]; // 10-15
                else $str = ($str ? $str . " " : "") . "DIECI" . $xarray[$n3]; // 16-19
            } else if ($n2 == 2) { // 20-29
                if ($n3 == 0) $str = ($str ? $str . " " : "") . "VEINTE";
                else $str = ($str ? $str . " " : "") . "VEINTI" . $xarray[$n3];
            } else { // 30-99
                $str = ($str ? $str . " " : "") . $xarray[$n2 * 10];
                if ($n3 > 0) $str .= " Y " . $xarray[$n3];
            }
        } else if ($n3 > 0) {
            $str = ($str ? $str . " " : "") . $xarray[$n3];
        }

        return $str;
    }
}
