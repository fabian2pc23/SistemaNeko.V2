<?php
ob_start();
if (strlen(session_id()) < 1)
  session_start();

if (!isset($_SESSION["nombre"])) {
  echo 'Debe ingresar al sistema correctamente para visualizar el reporte';
} else {
  if ($_SESSION['ventas'] == 1) {

    require('../fpdf181/fpdf.php');
    require_once "../modelos/Venta.php";
    require_once "Letras.php";

    // ==== DATOS DE LA EMPRESA ====
    $logo = "../assets/logo.png";
    $empresa = "Ferretería Neko";
    $documento_ruc = "10406980788";
    $direccion = "Carretera a Lambayeque";
    $telefono = "921263349";
    $email = "nekosaccix@gmail.com";

    // ==== COLORES CORPORATIVOS ====
    $colorPrimario = [21, 101, 192]; // Azul corporativo
    $colorSecundario = [15, 71, 161]; // Azul oscuro
    $colorAccento = [239, 68, 68]; // Rojo para alertas

    // ==== DATOS DE LA VENTA ====
    $venta = new Venta();
    $rsptav = $venta->mostrar($_GET["id"]);
    $regv = (object)$rsptav;

    // ==== CLASE PDF PERSONALIZADA ====
    class PDF_Factura extends FPDF
    {
      var $colorPrimario;
      var $colorSecundario;

      function Header()
      {
        // Fondo del encabezado
        $this->SetFillColor(245, 247, 251);
        $this->Rect(0, 0, 210, 45, 'F');
      }

      function Footer()
      {
        $this->SetY(-20);
        $this->SetFont('Arial', '', 8);
        $this->SetTextColor(100, 100, 100);
        $this->Cell(0, 5, utf8_decode('Documento generado electrónicamente - Sistema Neko ERP'), 0, 1, 'C');
        $this->Cell(0, 5, utf8_decode('Página ') . $this->PageNo() . '/{nb}', 0, 0, 'C');
      }

      // Rectángulo redondeado
      function RoundedRect($x, $y, $w, $h, $r, $style = '')
      {
        $k = $this->k;
        $hp = $this->h;
        if ($style == 'F') $op = 'f';
        elseif ($style == 'FD' || $style == 'DF') $op = 'B';
        else $op = 'S';

        $MyArc = 4 / 3 * (sqrt(2) - 1);
        $this->_out(sprintf('%.2F %.2F m', ($x + $r) * $k, ($hp - $y) * $k));
        $xc = $x + $w - $r;
        $yc = $y + $r;
        $this->_out(sprintf('%.2F %.2F l', $xc * $k, ($hp - $y) * $k));
        $this->_Arc($xc + $r * $MyArc, $yc - $r, $xc + $r, $yc - $r * $MyArc, $xc + $r, $yc);
        $xc = $x + $w - $r;
        $yc = $y + $h - $r;
        $this->_out(sprintf('%.2F %.2F l', ($x + $w) * $k, ($hp - $yc) * $k));
        $this->_Arc($xc + $r, $yc + $r * $MyArc, $xc + $r * $MyArc, $yc + $r, $xc, $yc + $r);
        $xc = $x + $r;
        $yc = $y + $h - $r;
        $this->_out(sprintf('%.2F %.2F l', $xc * $k, ($hp - ($y + $h)) * $k));
        $this->_Arc($xc - $r * $MyArc, $yc + $r, $xc - $r, $yc + $r * $MyArc, $xc - $r, $yc);
        $xc = $x + $r;
        $yc = $y + $r;
        $this->_out(sprintf('%.2F %.2F l', $x * $k, ($hp - $yc) * $k));
        $this->_Arc($xc - $r, $yc - $r * $MyArc, $xc - $r * $MyArc, $yc - $r, $xc, $yc - $r);
        $this->_out($op);
      }

      function _Arc($x1, $y1, $x2, $y2, $x3, $y3)
      {
        $h = $this->h;
        $this->_out(sprintf(
          '%.2F %.2F %.2F %.2F %.2F %.2F c ',
          $x1 * $this->k,
          ($h - $y1) * $this->k,
          $x2 * $this->k,
          ($h - $y2) * $this->k,
          $x3 * $this->k,
          ($h - $y3) * $this->k
        ));
      }
    }

    // ==== CONFIGURACIÓN PDF ====
    $pdf = new PDF_Factura('P', 'mm', 'A4');
    $pdf->colorPrimario = $colorPrimario;
    $pdf->colorSecundario = $colorSecundario;
    $pdf->AliasNbPages();
    $pdf->SetMargins(15, 15, 15);
    $pdf->SetAutoPageBreak(true, 25);
    $pdf->AddPage();

    // ==== LOGO Y DATOS EMPRESA ====
    if (file_exists($logo)) {
      $pdf->Image($logo, 15, 8, 30, 30);
    }

    $pdf->SetXY(50, 10);
    $pdf->SetFont('Arial', 'B', 16);
    $pdf->SetTextColor($colorPrimario[0], $colorPrimario[1], $colorPrimario[2]);
    $pdf->Cell(80, 8, utf8_decode($empresa), 0, 1);

    $pdf->SetXY(50, 18);
    $pdf->SetFont('Arial', '', 9);
    $pdf->SetTextColor(80, 80, 80);
    $pdf->MultiCell(80, 4.5, utf8_decode("RUC: $documento_ruc\nDir: $direccion\nTel: $telefono\nEmail: $email"));

    // ==== RECUADRO DEL COMPROBANTE ====
    $pdf->SetDrawColor($colorPrimario[0], $colorPrimario[1], $colorPrimario[2]);
    $pdf->SetLineWidth(0.5);
    $pdf->RoundedRect(135, 8, 60, 32, 3, 'D');

    // Tipo de comprobante
    $pdf->SetXY(135, 12);
    $pdf->SetFont('Arial', 'B', 10);
    $pdf->SetTextColor($colorPrimario[0], $colorPrimario[1], $colorPrimario[2]);
    $pdf->Cell(60, 6, 'RUC ' . $documento_ruc, 0, 1, 'C');

    $pdf->SetXY(135, 20);
    $pdf->SetFont('Arial', 'B', 12);
    $pdf->Cell(60, 7, utf8_decode(strtoupper($regv->tipo_comprobante)), 0, 1, 'C');

    $pdf->SetXY(135, 28);
    $pdf->SetFont('Arial', '', 10);
    $pdf->SetTextColor(40, 40, 40);
    $pdf->Cell(60, 6, utf8_decode("$regv->serie_comprobante-$regv->num_comprobante"), 0, 1, 'C');

    // ==== DATOS DEL CLIENTE (en recuadro) ====
    $pdf->Ln(8);
    $yCliente = $pdf->GetY();

    // Fondo para datos cliente
    $pdf->SetFillColor(248, 250, 252);
    $pdf->SetDrawColor(226, 232, 240);
    $pdf->RoundedRect(15, $yCliente, 180, 28, 2, 'DF');

    $pdf->SetXY(18, $yCliente + 3);
    $pdf->SetFont('Arial', 'B', 9);
    $pdf->SetTextColor($colorPrimario[0], $colorPrimario[1], $colorPrimario[2]);
    $pdf->Cell(45, 5, 'DATOS DEL CLIENTE', 0, 0);
    $pdf->SetXY(120, $yCliente + 3);
    $pdf->Cell(45, 5, utf8_decode('FECHA DE EMISIÓN'), 0, 1);

    // Línea separadora
    $pdf->SetDrawColor(200, 200, 200);
    $pdf->Line(15, $yCliente + 9, 195, $yCliente + 9);

    $pdf->SetFont('Arial', '', 9);
    $pdf->SetTextColor(40, 40, 40);

    // Datos cliente columna izquierda
    $pdf->SetXY(18, $yCliente + 11);
    $pdf->Cell(25, 4.5, $regv->tipo_documento . ':', 0, 0);
    $pdf->SetFont('Arial', 'B', 9);
    $pdf->Cell(70, 4.5, $regv->num_documento, 0, 1);

    $pdf->SetXY(18, $yCliente + 15.5);
    $pdf->SetFont('Arial', '', 9);
    $pdf->Cell(25, 4.5, utf8_decode('DENOMINACIÓN:'), 0, 0);
    $pdf->SetFont('Arial', 'B', 9);
    $pdf->Cell(70, 4.5, utf8_decode($regv->cliente), 0, 1);

    $pdf->SetXY(18, $yCliente + 20);
    $pdf->SetFont('Arial', '', 9);
    $pdf->Cell(25, 4.5, utf8_decode('DIRECCIÓN:'), 0, 0);
    $pdf->SetFont('Arial', '', 9);
    $direccionCliente = !empty($regv->direccion) ? $regv->direccion : '-';
    $pdf->Cell(70, 4.5, utf8_decode(substr($direccionCliente, 0, 45)), 0, 1);

    // Datos columna derecha
    $pdf->SetXY(120, $yCliente + 11);
    $pdf->SetFont('Arial', '', 9);
    $pdf->Cell(25, 4.5, 'FECHA:', 0, 0);
    $pdf->SetFont('Arial', 'B', 9);
    $pdf->Cell(45, 4.5, $regv->fecha, 0, 1);

    $pdf->SetXY(120, $yCliente + 15.5);
    $pdf->SetFont('Arial', '', 9);
    $pdf->Cell(25, 4.5, 'MONEDA:', 0, 0);
    $pdf->SetFont('Arial', 'B', 9);
    $pdf->Cell(45, 4.5, 'SOLES', 0, 1);

    // ==== TABLA DE DETALLES ====
    $pdf->Ln(8);
    $yTabla = $pdf->GetY();

    // Encabezado tabla
    $pdf->SetFillColor($colorPrimario[0], $colorPrimario[1], $colorPrimario[2]);
    $pdf->SetTextColor(255, 255, 255);
    $pdf->SetFont('Arial', 'B', 8);

    $anchos = [15, 15, 18, 77, 20, 20, 25];
    $encabezados = ['CANT.', 'UM', utf8_decode('CÓD.'), utf8_decode('DESCRIPCIÓN'), 'V/U', 'P/U', 'IMPORTE'];

    $pdf->SetX(15);
    foreach ($encabezados as $i => $enc) {
      $pdf->Cell($anchos[$i], 8, $enc, 0, 0, 'C', true);
    }
    $pdf->Ln();

    // Detalles
    $rsptad = $venta->ventadetalle($_GET["id"]);
    $pdf->SetFont('Arial', '', 8);
    $pdf->SetTextColor(40, 40, 40);
    $fill = false;
    $totalItems = 0;

    while ($regd = $rsptad->fetch_object()) {
      $totalItems++;

      if ($fill) {
        $pdf->SetFillColor(248, 250, 252);
      } else {
        $pdf->SetFillColor(255, 255, 255);
      }

      $valorUnitario = $regd->precio_venta / 1.18;
      $importe = $regd->subtotal;

      $pdf->SetX(15);
      $pdf->Cell($anchos[0], 7, $regd->cantidad, 1, 0, 'C', true);
      $pdf->Cell($anchos[1], 7, 'NIU', 1, 0, 'C', true);
      $pdf->Cell($anchos[2], 7, $regd->codigo, 1, 0, 'C', true);
      $pdf->Cell($anchos[3], 7, utf8_decode(substr($regd->articulo, 0, 48)), 1, 0, 'L', true);
      $pdf->Cell($anchos[4], 7, number_format($valorUnitario, 3), 1, 0, 'R', true);
      $pdf->Cell($anchos[5], 7, number_format($regd->precio_venta, 3), 1, 0, 'R', true);
      $pdf->Cell($anchos[6], 7, number_format($importe, 2), 1, 1, 'R', true);

      $fill = !$fill;
    }

    // ==== TOTALES ====
    $pdf->Ln(5);

    // Base imponible y IGV
    $igv = ($regv->total_venta * $regv->impuesto) / 100;
    $subtotal = $regv->total_venta - $igv;

    // Caja de totales
    $yTotales = $pdf->GetY();
    $pdf->SetFillColor(248, 250, 252);
    $pdf->RoundedRect(130, $yTotales, 65, 28, 2, 'F');

    $pdf->SetFont('Arial', '', 9);
    $pdf->SetTextColor(80, 80, 80);

    $pdf->SetXY(132, $yTotales + 3);
    $pdf->Cell(30, 6, 'GRAVADA', 0, 0, 'L');
    $pdf->Cell(30, 6, 'S/ ' . number_format($subtotal, 2), 0, 1, 'R');

    $pdf->SetXY(132, $yTotales + 9);
    $pdf->Cell(30, 6, 'IGV ' . $regv->impuesto . ' %', 0, 0, 'L');
    $pdf->Cell(30, 6, 'S/ ' . number_format($igv, 2), 0, 1, 'R');

    // Línea separadora
    $pdf->SetDrawColor(200, 200, 200);
    $pdf->Line(132, $yTotales + 17, 193, $yTotales + 17);

    $pdf->SetXY(132, $yTotales + 19);
    $pdf->SetFont('Arial', 'B', 11);
    $pdf->SetTextColor($colorPrimario[0], $colorPrimario[1], $colorPrimario[2]);
    $pdf->Cell(30, 6, 'TOTAL', 0, 0, 'L');
    $pdf->Cell(30, 6, 'S/ ' . number_format($regv->total_venta, 2), 0, 1, 'R');

    // ==== TOTAL EN LETRAS ====
    $pdf->Ln(3);
    $V = new EnLetras();
    $con_letra = strtoupper($V->ValorEnLetras($regv->total_venta, "SOLES"));

    $pdf->SetX(15);
    $pdf->SetFillColor(240, 253, 244); // Verde claro
    $pdf->SetDrawColor(34, 197, 94);
    $pdf->SetFont('Arial', 'B', 8);
    $pdf->SetTextColor(22, 101, 52);
    $pdf->MultiCell(110, 6, utf8_decode('IMPORTE EN LETRAS: ' . $con_letra), 1, 'L', true);

    // ==== OBSERVACIONES / DISCLAIMER ====
    $pdf->Ln(8);
    $pdf->SetFont('Arial', 'I', 8);
    $pdf->SetTextColor(100, 116, 139);
    $pdf->MultiCell(0, 4, utf8_decode("Este documento es una representación impresa del comprobante electrónico emitido.\nPuede verificar su validez en www.sunat.gob.pe"));

    // ==== SALIDA PDF ====
    $nombreArchivo = 'Comprobante_' . $regv->serie_comprobante . '-' . $regv->num_comprobante . '.pdf';
    $pdf->Output($nombreArchivo, 'I');
  } else {
    echo 'No tiene permiso para visualizar el reporte';
  }
}

ob_end_flush();
