<?php
//Activamos el almacenamiento en el buffer
ob_start();
if (strlen(session_id()) < 1)
  session_start();

if (!isset($_SESSION["nombre"])) {
  echo 'Debe ingresar al sistema correctamente para visualizar el reporte';
} else {
  if ($_SESSION['acceso'] == 1) {

    //Incluímos a la clase PDF_MC_Table
    require('PDF_MC_Table.php');

    //Clase Premium con elementos visuales avanzados
    class PDF_Premium extends PDF_MC_Table
    {
      private $colorPrimario = array(52, 73, 94);     // Gris oscuro para usuarios
      private $colorSecundario = array(44, 62, 80);
      private $colorAcento = array(52, 152, 219);     // Azul claro
      private $colorAlerta = array(231, 76, 60);
      private $colorExito = array(46, 204, 113);
      private $colorTextoClaro = array(255, 255, 255);

      function Header()
      {
        $this->SetFillColor($this->colorPrimario[0], $this->colorPrimario[1], $this->colorPrimario[2]);
        $this->Rect(0, 0, 210, 40, 'F');

        $this->SetFillColor($this->colorAcento[0], $this->colorAcento[1], $this->colorAcento[2]);
        $this->Rect(0, 35, 210, 5, 'F');

        $logoPath = '../assets/logo.png';
        if (file_exists($logoPath)) {
          $this->Image($logoPath, 15, 8, 25, 0);
        }

        $this->SetTextColor($this->colorTextoClaro[0], $this->colorTextoClaro[1], $this->colorTextoClaro[2]);
        $this->SetFont('Arial', 'B', 18);
        $this->SetXY(45, 12);
        $this->Cell(0, 8, 'SISTEMA DE INVENTARIO', 0, 1, 'L');

        $this->SetFont('Arial', '', 10);
        $this->SetX(45);
        $this->Cell(0, 5, utf8_decode('Gestión Empresarial Inteligente'), 0, 1, 'L');

        $this->SetFont('Arial', 'B', 10);
        $this->SetXY(150, 12);
        $this->Cell(0, 5, 'REPORTE OFICIAL', 0, 1, 'R');
        $this->SetFont('Arial', '', 8);
        $this->SetX(150);
        $this->Cell(0, 4, date('d/m/Y H:i'), 0, 1, 'R');

        $this->Ln(20);
        $this->SetTextColor(0, 0, 0);
      }

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
        $this->_out(sprintf('%.2F %.2F l', ($x) * $k, ($hp - $yc) * $k));
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

      function Footer()
      {
        $this->SetY(-25);
        $this->SetDrawColor($this->colorAcento[0], $this->colorAcento[1], $this->colorAcento[2]);
        $this->SetLineWidth(0.8);
        $this->Line(10, $this->GetY(), 200, $this->GetY());
        $this->Ln(3);

        $this->SetFont('Arial', 'B', 8);
        $this->SetTextColor(80, 80, 80);
        $this->Cell(0, 3, 'NEKO S.A.C. - Sistema de Inventario', 0, 1, 'C');

        $this->SetFont('Arial', '', 7);
        $this->SetTextColor(120, 120, 120);
        $this->Cell(0, 3, utf8_decode('Carretera a Lambayeque , Lambayeque - Perú | Tel: (01) 234-5678'), 0, 1, 'C');

        $this->SetFont('Arial', 'I', 8);
        $this->SetTextColor(100, 100, 100);
        $this->Cell(0, 4, utf8_decode('Página ') . $this->PageNo() . ' de {nb} | Documento confidencial', 0, 0, 'C');
      }

      function PortadaReporte($titulo, $usuario, $departamento = "")
      {
        $this->AddPage();

        $this->SetFillColor($this->colorPrimario[0], $this->colorPrimario[1], $this->colorPrimario[2]);
        $this->Rect(0, 80, 210, 60, 'F');

        $this->SetY(95);
        $this->SetFont('Arial', 'B', 24);
        $this->SetTextColor($this->colorTextoClaro[0], $this->colorTextoClaro[1], $this->colorTextoClaro[2]);
        $this->Cell(0, 12, utf8_decode($titulo), 0, 1, 'C');

        $this->SetFont('Arial', '', 14);
        $this->Cell(0, 8, utf8_decode('Informe Detallado'), 0, 1, 'C');

        $this->SetY(170);
        $this->SetTextColor(0, 0, 0);
        $this->SetFont('Arial', 'B', 11);

        $this->SetFillColor(245, 245, 245);
        $this->SetDrawColor(200, 200, 200);
        $this->RoundedRect(50, 170, 110, 50, 3, 'DF');

        $this->SetY(175);
        $this->Cell(0, 6, 'GENERADO POR:', 0, 1, 'C');
        $this->SetFont('Arial', '', 10);
        $this->Cell(0, 6, utf8_decode($usuario), 0, 1, 'C');

        if ($departamento != "") {
          $this->Cell(0, 6, utf8_decode($departamento), 0, 1, 'C');
        }

        $this->Ln(3);
        $this->SetFont('Arial', 'B', 9);
        $this->Cell(0, 5, 'FECHA:', 0, 1, 'C');
        $this->SetFont('Arial', '', 9);
        $this->Cell(0, 5, date('d/m/Y - H:i:s'), 0, 1, 'C');

        $this->SetY(250);
        $this->SetFont('Arial', 'I', 9);
        $this->SetTextColor(150, 150, 150);
        $this->Cell(0, 5, utf8_decode('DOCUMENTO CONFIDENCIAL - USO INTERNO'), 0, 1, 'C');
      }

      function PanelEstadisticas($total, $admins, $empleados)
      {
        $this->SetFont('Arial', 'B', 14);
        $this->SetTextColor($this->colorSecundario[0], $this->colorSecundario[1], $this->colorSecundario[2]);
        $this->Cell(0, 8, utf8_decode('RESUMEN EJECUTIVO'), 0, 1, 'L');
        $this->Ln(2);

        $cardWidth = 60;
        $cardHeight = 35;
        $spacing = 5;
        $startX = 10;
        $y = $this->GetY();

        // Tarjeta 1: Total
        $this->SetFillColor($this->colorPrimario[0], $this->colorPrimario[1], $this->colorPrimario[2]);
        $this->RoundedRect($startX, $y, $cardWidth, $cardHeight, 3, 'F');
        $this->SetTextColor(255, 255, 255);
        $this->SetFont('Arial', 'B', 9);
        $this->SetXY($startX + 2, $y + 5);
        $this->Cell($cardWidth - 4, 6, 'TOTAL USUARIOS', 0, 0, 'C');
        $this->SetFont('Arial', 'B', 22);
        $this->SetXY($startX + 2, $y + 15);
        $this->Cell($cardWidth - 4, 15, $total, 0, 0, 'C');

        // Tarjeta 2: Administradores
        $posX2 = $startX + $cardWidth + $spacing;
        $this->SetFillColor($this->colorAcento[0], $this->colorAcento[1], $this->colorAcento[2]);
        $this->RoundedRect($posX2, $y, $cardWidth, $cardHeight, 3, 'F');
        $this->SetFont('Arial', 'B', 9);
        $this->SetXY($posX2 + 2, $y + 5);
        $this->Cell($cardWidth - 4, 6, 'ADMINISTRADORES', 0, 0, 'C');
        $this->SetFont('Arial', 'B', 22);
        $this->SetXY($posX2 + 2, $y + 15);
        $this->Cell($cardWidth - 4, 15, $admins, 0, 0, 'C');

        // Tarjeta 3: Empleados
        $posX3 = $posX2 + $cardWidth + $spacing;
        $this->SetFillColor($this->colorExito[0], $this->colorExito[1], $this->colorExito[2]);
        $this->RoundedRect($posX3, $y, $cardWidth, $cardHeight, 3, 'F');
        $this->SetFont('Arial', 'B', 9);
        $this->SetXY($posX3 + 2, $y + 5);
        $this->Cell($cardWidth - 4, 6, 'EMPLEADOS', 0, 0, 'C');
        $this->SetFont('Arial', 'B', 22);
        $this->SetXY($posX3 + 2, $y + 15);
        $this->Cell($cardWidth - 4, 15, $empleados, 0, 0, 'C');

        $this->SetY($y + $cardHeight + 15);
        $this->SetTextColor(0, 0, 0);
      }

      function SeccionTitulo($titulo)
      {
        $this->SetFillColor($this->colorAcento[0], $this->colorAcento[1], $this->colorAcento[2]);
        $this->Rect($this->GetX(), $this->GetY(), 3, 8, 'F');

        $this->SetFillColor($this->colorSecundario[0], $this->colorSecundario[1], $this->colorSecundario[2]);
        $this->SetTextColor($this->colorTextoClaro[0], $this->colorTextoClaro[1], $this->colorTextoClaro[2]);
        $this->SetFont('Arial', 'B', 13);
        $this->SetX($this->GetX() + 3);
        $this->Cell(187, 8, ' ' . utf8_decode($titulo), 0, 1, 'L', true);
        $this->Ln(3);
        $this->SetTextColor(0, 0, 0);
      }

      function TablaEncabezado($headers, $widths)
      {
        $this->SetFillColor($this->colorPrimario[0], $this->colorPrimario[1], $this->colorPrimario[2]);
        $this->SetTextColor(255, 255, 255);
        $this->SetDrawColor($this->colorSecundario[0], $this->colorSecundario[1], $this->colorSecundario[2]);
        $this->SetLineWidth(0.3);
        $this->SetFont('Arial', 'B', 9);

        for ($i = 0; $i < count($headers); $i++) {
          $this->Cell($widths[$i], 9, $headers[$i], 1, 0, 'C', true);
        }
        $this->Ln();

        $this->SetFillColor(248, 248, 248);
        $this->SetTextColor(0, 0, 0);
        $this->SetFont('Arial', '', 8);
      }
    }

    //Instanciamos la clase premium
    $pdf = new PDF_Premium();
    $pdf->AliasNbPages();

    //Obtener datos
    require_once "../modelos/Usuario.php";
    $usuario = new Usuario();
    $rspta = $usuario->listar();

    $total = 0;
    $admins = 0;
    $empleados = 0;
    $datos = array();

    while ($reg = $rspta->fetch_object()) {
      $total++;
      if (strtolower($reg->cargo) == 'administrador') {
        $admins++;
      } else {
        $empleados++;
      }
      $datos[] = $reg;
    }

    //Página de portada
    $pdf->PortadaReporte(
      'DIRECTORIO DE USUARIOS',
      $_SESSION["nombre"],
      'Departamento de Sistemas'
    );

    //Nueva página para el contenido
    $pdf->AddPage();

    //Panel de estadísticas
    $pdf->PanelEstadisticas($total, $admins, $empleados);

    //Título de la tabla
    $pdf->SeccionTitulo('LISTADO DE USUARIOS DEL SISTEMA');

    $headers = array(utf8_decode('N°'), 'Nombre', 'Documento', utf8_decode('Número'), utf8_decode('Teléfono'), 'Email', 'Cargo');
    $widths = array(10, 40, 20, 22, 22, 45, 31);
    $pdf->TablaEncabezado($headers, $widths);
    $pdf->SetWidths($widths);

    $contador = 0;
    $fill = false;

    foreach ($datos as $reg) {
      $contador++;

      if ($fill) {
        $pdf->SetFillColor(248, 248, 248);
      } else {
        $pdf->SetFillColor(255, 255, 255);
      }

      $numero = str_pad($contador, 3, "0", STR_PAD_LEFT);

      $pdf->Row(array(
        $numero,
        utf8_decode($reg->nombre),
        $reg->tipo_documento,
        $reg->num_documento,
        $reg->telefono,
        $reg->email,
        utf8_decode($reg->cargo)
      ), $fill);

      $fill = !$fill;
    }

    //Mostramos el documento
    $pdf->Output('I', 'Reporte_Premium_Usuarios_' . date('Ymd_His') . '.pdf');
?>
<?php
  } else {
    echo 'No tiene permiso para visualizar el reporte';
  }
}
ob_end_flush();
?>