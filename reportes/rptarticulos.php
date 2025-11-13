<?php
//Activamos el almacenamiento en el buffer
ob_start();
if (strlen(session_id()) < 1) 
  session_start();
if (!isset($_SESSION["nombre"]))
{
  echo 'Debe ingresar al sistema correctamente para visualizar el reporte';
}
else
{
if ($_SESSION['almacen']==1)
{
//Incluímos a la clase PDF_MC_Table
require('PDF_MC_Table.php');
 
//Clase Premium con elementos visuales avanzados
class PDF_Premium extends PDF_MC_Table
{
    private $colorPrimario = array(41, 128, 185);
    private $colorSecundario = array(52, 73, 94);
    private $colorAcento = array(26, 188, 156);
    private $colorAlerta = array(231, 76, 60);
    private $colorExito = array(46, 204, 113);
    private $colorTextoClaro = array(255, 255, 255);
    private $totalArticulos = 0;
    private $articulosConStock = 0;
    private $articulosSinStock = 0;
    
    function Header()
    {
        // Fondo superior
        $this->SetFillColor($this->colorPrimario[0], $this->colorPrimario[1], $this->colorPrimario[2]);
        $this->Rect(0, 0, 210, 40, 'F');
        
        $this->SetFillColor($this->colorSecundario[0], $this->colorSecundario[1], $this->colorSecundario[2]);
        $this->Rect(0, 35, 210, 5, 'F');
        
        // === LOGO SOLO (SIN FONDO) ===
        // Ruta relativa desde /reportes/ hacia /assets/logo.png
        $logoPath = '../assets/logo.png';

        if (file_exists($logoPath)) {
            // Solo el logo, sin RoundedRect
            $this->Image($logoPath, 15, 8, 25, 0); // ancho 25, alto proporcional
        }

        // Información de la empresa
        $this->SetTextColor($this->colorTextoClaro[0], $this->colorTextoClaro[1], $this->colorTextoClaro[2]);
        $this->SetFont('Arial', 'B', 18);
        $this->SetXY(45, 12);
        $this->Cell(0, 8, 'SISTEMA DE INVENTARIO', 0, 1, 'L');
        
        $this->SetFont('Arial', '', 10);
        $this->SetX(45);
        $this->Cell(0, 5, utf8_decode('Gestión Empresarial Inteligente'), 0, 1, 'L');
        
        // Sección derecha - Tipo de reporte
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
        if($style=='F')
            $op='f';
        elseif($style=='FD' || $style=='DF')
            $op='B';
        else
            $op='S';
        $MyArc = 4/3 * (sqrt(2) - 1);
        $this->_out(sprintf('%.2F %.2F m',($x+$r)*$k,($hp-$y)*$k ));
        $xc = $x+$w-$r ;
        $yc = $y+$r;
        $this->_out(sprintf('%.2F %.2F l', $xc*$k,($hp-$y)*$k ));
        $this->_Arc($xc + $r*$MyArc, $yc - $r, $xc + $r, $yc - $r*$MyArc, $xc + $r, $yc);
        $xc = $x+$w-$r ;
        $yc = $y+$h-$r;
        $this->_out(sprintf('%.2F %.2F l',($x+$w)*$k,($hp-$yc)*$k));
        $this->_Arc($xc + $r, $yc + $r*$MyArc, $xc + $r*$MyArc, $yc + $r, $xc, $yc + $r);
        $xc = $x+$r ;
        $yc = $y+$h-$r;
        $this->_out(sprintf('%.2F %.2F l',$xc*$k,($hp-($y+$h))*$k));
        $this->_Arc($xc - $r*$MyArc, $yc + $r, $xc - $r, $yc + $r*$MyArc, $xc - $r, $yc);
        $xc = $x+$r ;
        $yc = $y+$r;
        $this->_out(sprintf('%.2F %.2F l',($x)*$k,($hp-$yc)*$k ));
        $this->_Arc($xc - $r, $yc - $r*$MyArc, $xc - $r*$MyArc, $yc - $r, $xc, $yc - $r);
        $this->_out($op);
    }

    function _Arc($x1, $y1, $x2, $y2, $x3, $y3)
    {
        $h = $this->h;
        $this->_out(sprintf('%.2F %.2F %.2F %.2F %.2F %.2F c ', $x1*$this->k, ($h-$y1)*$this->k,
            $x2*$this->k, ($h-$y2)*$this->k, $x3*$this->k, ($h-$y3)*$this->k));
    }
    
    function Footer()
    {
        $this->SetY(-25);
        
        // Línea decorativa degradada
        $this->SetDrawColor($this->colorAcento[0], $this->colorAcento[1], $this->colorAcento[2]);
        $this->SetLineWidth(0.8);
        $this->Line(10, $this->GetY(), 200, $this->GetY());
        
        $this->Ln(3);
        
        // Información corporativa
        $this->SetFont('Arial', 'B', 8);
        $this->SetTextColor(80, 80, 80);
        $this->Cell(0, 3, 'NEKO S.A.C. - Sistema de Inventario', 0, 1, 'C');
        
        $this->SetFont('Arial', '', 7);
        $this->SetTextColor(120, 120, 120);
        $this->Cell(0, 3, utf8_decode('Carretera a Lambayeque , Lambayeque - Perú | Tel: (01) 234-5678 | nekosaccix@gmail.com'), 0, 1, 'C');
        
        // Paginación
        $this->SetFont('Arial', 'I', 8);
        $this->SetTextColor(100, 100, 100);
        $this->Cell(0, 4, utf8_decode('Página ') . $this->PageNo() . ' de {nb} | Documento confidencial', 0, 0, 'C');
    }
    
    function PortadaReporte($titulo, $usuario, $departamento = "")
    {
        $this->AddPage();
        
        // Rectángulo decorativo superior
        $this->SetFillColor($this->colorPrimario[0], $this->colorPrimario[1], $this->colorPrimario[2]);
        $this->Rect(0, 80, 210, 60, 'F');
        
        // Título principal
        $this->SetY(95);
        $this->SetFont('Arial', 'B', 24);
        $this->SetTextColor($this->colorTextoClaro[0], $this->colorTextoClaro[1], $this->colorTextoClaro[2]);
        $this->Cell(0, 12, utf8_decode($titulo), 0, 1, 'C');
        
        $this->SetFont('Arial', '', 14);
        $this->Cell(0, 8, utf8_decode('Informe Detallado'), 0, 1, 'C');
        
        // Información del documento
        $this->SetY(170);
        $this->SetTextColor(0, 0, 0);
        $this->SetFont('Arial', 'B', 11);
        
        // Cuadro de información
        $this->SetFillColor(245, 245, 245);
        $this->SetDrawColor(200, 200, 200);
        $this->RoundedRect(50, 170, 110, 50, 3, 'DF');
        
        $this->SetY(175);
        $this->Cell(0, 6, 'GENERADO POR:', 0, 1, 'C');
        $this->SetFont('Arial', '', 10);
        $this->Cell(0, 6, utf8_decode($usuario), 0, 1, 'C');
        
        if($departamento != "") {
            $this->Cell(0, 6, utf8_decode($departamento), 0, 1, 'C');
        }
        
        $this->Ln(3);
        $this->SetFont('Arial', 'B', 9);
        $this->Cell(0, 5, 'FECHA:', 0, 1, 'C');
        $this->SetFont('Arial', '', 9);
        $this->Cell(0, 5, date('d/m/Y - H:i:s'), 0, 1, 'C');
        
        // Marca de agua de confidencialidad
        $this->SetY(250);
        $this->SetFont('Arial', 'I', 9);
        $this->SetTextColor(150, 150, 150);
        $this->Cell(0, 5, utf8_decode('DOCUMENTO CONFIDENCIAL - USO INTERNO'), 0, 1, 'C');
    }
    
    function PanelEstadisticas($total, $conStock, $sinStock, $stockTotal)
    {
        $this->SetFont('Arial', 'B', 14);
        $this->SetTextColor($this->colorSecundario[0], $this->colorSecundario[1], $this->colorSecundario[2]);
        $this->Cell(0, 8, utf8_decode('RESUMEN EJECUTIVO'), 0, 1, 'L');
        $this->Ln(2);
        
        // Panel de estadísticas con tarjetas
        $cardWidth = 45;
        $cardHeight = 35;
        $spacing = 4;
        $startX = 10;
        $y = $this->GetY();
        
        // Tarjeta 1: Total
        $this->SetFillColor($this->colorPrimario[0], $this->colorPrimario[1], $this->colorPrimario[2]);
        $this->RoundedRect($startX, $y, $cardWidth, $cardHeight, 3, 'F');
        
        $this->SetTextColor(255, 255, 255);
        $this->SetFont('Arial', 'B', 9);
        $this->SetXY($startX + 2, $y + 5);
        $this->Cell($cardWidth - 4, 6, utf8_decode('TOTAL ARTÍCULOS'), 0, 0, 'C');
        
        $this->SetFont('Arial', 'B', 18);
        $this->SetXY($startX + 2, $y + 15);
        $this->Cell($cardWidth - 4, 15, $total, 0, 0, 'C');
        
        // Tarjeta 2: Con Stock
        $posX2 = $startX + $cardWidth + $spacing;
        $this->SetFillColor($this->colorExito[0], $this->colorExito[1], $this->colorExito[2]);
        $this->RoundedRect($posX2, $y, $cardWidth, $cardHeight, 3, 'F');
        
        $this->SetFont('Arial', 'B', 9);
        $this->SetXY($posX2 + 2, $y + 5);
        $this->Cell($cardWidth - 4, 6, 'CON STOCK', 0, 0, 'C');
        
        $this->SetFont('Arial', 'B', 18);
        $this->SetXY($posX2 + 2, $y + 15);
        $this->Cell($cardWidth - 4, 15, $conStock, 0, 0, 'C');
        
        // Tarjeta 3: Sin Stock
        $posX3 = $posX2 + $cardWidth + $spacing;
        $this->SetFillColor($this->colorAlerta[0], $this->colorAlerta[1], $this->colorAlerta[2]);
        $this->RoundedRect($posX3, $y, $cardWidth, $cardHeight, 3, 'F');
        
        $this->SetFont('Arial', 'B', 9);
        $this->SetXY($posX3 + 2, $y + 5);
        $this->Cell($cardWidth - 4, 6, 'SIN STOCK', 0, 0, 'C');
        
        $this->SetFont('Arial', 'B', 18);
        $this->SetXY($posX3 + 2, $y + 15);
        $this->Cell($cardWidth - 4, 15, $sinStock, 0, 0, 'C');
        
        // Tarjeta 4: Stock Total
        $posX4 = $posX3 + $cardWidth + $spacing;
        $this->SetFillColor($this->colorAcento[0], $this->colorAcento[1], $this->colorAcento[2]);
        $this->RoundedRect($posX4, $y, $cardWidth, $cardHeight, 3, 'F');
        
        $this->SetFont('Arial', 'B', 9);
        $this->SetXY($posX4 + 2, $y + 5);
        $this->Cell($cardWidth - 4, 6, 'STOCK TOTAL', 0, 0, 'C');
        
        $this->SetFont('Arial', 'B', 18);
        $this->SetXY($posX4 + 2, $y + 15);
        $this->Cell($cardWidth - 4, 15, $stockTotal, 0, 0, 'C');
        
        // Mover el cursor después de las tarjetas
        $this->SetY($y + $cardHeight + 10);
        $this->SetX(10);
        
        // Gráfico de barras simple
        if($total > 0) {
            $this->SetFont('Arial', 'B', 10);
            $this->SetTextColor(80, 80, 80);
            $this->Cell(0, 6, utf8_decode('DISTRIBUCIÓN POR DISPONIBILIDAD'), 0, 1, 'L');
            $this->Ln(3);
            
            $barHeight = 20;
            $maxWidth = 160;
            $startBarX = 10;
            $barY = $this->GetY();
            
            $porcentajeConStock = ($conStock / $total) * 100;
            $porcentajeSinStock = ($sinStock / $total) * 100;
            
            // Barra de con stock
            $widthConStock = ($conStock / $total) * $maxWidth;
            if($widthConStock > 0) {
                $this->SetFillColor($this->colorExito[0], $this->colorExito[1], $this->colorExito[2]);
                $this->Rect($startBarX, $barY, $widthConStock, $barHeight, 'F');
                
                if($widthConStock > 30) {
                    $this->SetTextColor(255, 255, 255);
                    $this->SetFont('Arial', 'B', 9);
                    $this->SetXY($startBarX + 2, $barY + 6);
                    $this->Cell($widthConStock - 4, 6, 'Con Stock: ' . number_format($porcentajeConStock, 1) . '%', 0, 0, 'L');
                }
            }
            
            // Barra de sin stock
            $widthSinStock = ($sinStock / $total) * $maxWidth;
            if($widthSinStock > 0) {
                $this->SetFillColor($this->colorAlerta[0], $this->colorAlerta[1], $this->colorAlerta[2]);
                $this->Rect($startBarX + $widthConStock, $barY, $widthSinStock, $barHeight, 'F');
                
                if($widthSinStock > 30) {
                    $this->SetTextColor(255, 255, 255);
                    $this->SetFont('Arial', 'B', 9);
                    $this->SetXY($startBarX + $widthConStock + 2, $barY + 6);
                    $this->Cell($widthSinStock - 4, 6, 'Sin Stock: ' . number_format($porcentajeSinStock, 1) . '%', 0, 0, 'L');
                }
            }
            
            $this->SetY($barY + $barHeight + 5);
        }
        
        $this->Ln(5);
        $this->SetTextColor(0, 0, 0);
    }
    
    function SeccionTitulo($titulo)
    {
        $this->SetFillColor($this->colorSecundario[0], $this->colorSecundario[1], $this->colorSecundario[2]);
        $this->SetTextColor($this->colorTextoClaro[0], $this->colorTextoClaro[1], $this->colorTextoClaro[2]);
        $this->SetFont('Arial', 'B', 13);
        
        // Barra lateral decorativa
        $this->SetFillColor($this->colorAcento[0], $this->colorAcento[1], $this->colorAcento[2]);
        $this->Rect($this->GetX(), $this->GetY(), 3, 8, 'F');
        
        $this->SetFillColor($this->colorSecundario[0], $this->colorSecundario[1], $this->colorSecundario[2]);
        $this->SetX($this->GetX() + 3);
        $this->Cell(187, 8, ' ' . utf8_decode($titulo), 0, 1, 'L', true);
        $this->Ln(3);
        $this->SetTextColor(0, 0, 0);
    }
    
    function TablaEncabezado($headers, $widths)
    {
        // Encabezado con gradiente
        $this->SetFillColor($this->colorPrimario[0], $this->colorPrimario[1], $this->colorPrimario[2]);
        $this->SetTextColor(255, 255, 255);
        $this->SetDrawColor($this->colorSecundario[0], $this->colorSecundario[1], $this->colorSecundario[2]);
        $this->SetLineWidth(0.3);
        $this->SetFont('Arial', 'B', 9);
        
        for($i = 0; $i < count($headers); $i++) {
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

//Obtener datos para estadísticas
require_once "../modelos/Articulo.php";
$articulo = new Articulo();
$rspta = $articulo->listar();

$total = 0;
$conStock = 0;
$sinStock = 0;
$stockTotal = 0;
$datos = array();

while($reg = $rspta->fetch_object()) {
    $total++;
    $stockTotal += $reg->stock;
    
    if($reg->stock > 0) {
        $conStock++;
    } else {
        $sinStock++;
    }
    $datos[] = $reg;
}

//Página de portada
$pdf->PortadaReporte(
    'CATÁLOGO DE ARTÍCULOS',
    $_SESSION["nombre"],
    'Departamento de Almacén'
);

//Nueva página para el contenido
$pdf->AddPage();

//Panel de estadísticas
$pdf->PanelEstadisticas($total, $conStock, $sinStock, $stockTotal);

//Título de la tabla
$headers = array('N°', 'Nombre', utf8_decode('Categoría'), utf8_decode('Código'), 'Stock');
$widths = array(12, 60, 50, 35, 18);
$pdf->TablaEncabezado($headers, $widths);

//Configuración de anchos para las filas
$pdf->SetWidths($widths);

//Generar filas
$contador = 0;
$fill = false;

foreach($datos as $reg) {
    $contador++;
    
    if($fill) {
        $pdf->SetFillColor(248, 248, 248);
    } else {
        $pdf->SetFillColor(255, 255, 255);
    }
    
    $numero = str_pad($contador, 3, "0", STR_PAD_LEFT);
    $nombre = utf8_decode($reg->nombre);
    $categoria = utf8_decode($reg->categoria);
    $codigo = $reg->codigo;
    
    // Stock con indicador visual
    $stock = $reg->stock;
    if($reg->stock > 0) {
        $pdf->SetTextColor(46, 204, 113); // Verde
    } else {
        $pdf->SetTextColor(231, 76, 60); // Rojo
    }
    
    // Crear la fila
    $pdf->Row(array($numero, $nombre, $categoria, $codigo, $stock), $fill);
    
    $pdf->SetTextColor(0, 0, 0);
    $fill = !$fill;
}

//Sección de conclusiones
$pdf->AddPage();
$pdf->SeccionTitulo('ANÁLISIS Y CONCLUSIONES');

$pdf->SetFont('Arial', '', 10);
$pdf->MultiCell(0, 6, utf8_decode(
    "El presente reporte muestra un total de $total artículos registrados en el sistema, " .
    "de los cuales $conStock cuentan con stock disponible (" . number_format(($conStock/$total)*100, 1) . "%) " .
    "y $sinStock están sin stock (" . number_format(($sinStock/$total)*100, 1) . "%). " .
    "El inventario total suma $stockTotal unidades.\n\n" .
    "Este análisis permite visualizar de manera clara el estado del inventario " .
    "y facilita la toma de decisiones en cuanto a la gestión de artículos y reposición de stock."
), 0, 'J');

$pdf->Ln(10);

// Recomendaciones
$pdf->SetFont('Arial', 'B', 11);
$pdf->SetTextColor(41, 128, 185);
$pdf->Cell(0, 6, 'RECOMENDACIONES:', 0, 1, 'L');
$pdf->SetFont('Arial', '', 9);
$pdf->SetTextColor(0, 0, 0);

$pdf->MultiCell(0, 5, utf8_decode(
    "- Priorizar la reposición de los $sinStock artículos sin stock para evitar pérdidas de ventas.\n" .
    "- Implementar un sistema de alertas automáticas cuando el stock llegue a niveles críticos.\n" .
    "- Realizar auditorías mensuales del inventario físico versus el sistema.\n" .
    "- Analizar los artículos con mayor rotación para optimizar el nivel de stock.\n" .
    "- Evaluar la eliminación o descontinuación de artículos con nulo movimiento."
), 0, 'L');

//Mostramos el documento
$pdf->Output('I', 'Reporte_Premium_Articulos_' . date('Ymd_His') . '.pdf');
?>
<?php
}
else
{
  echo 'No tiene permiso para visualizar el reporte';
}
}
ob_end_flush();
?>