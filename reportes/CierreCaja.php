<?php
// reportes/CierreCaja.php
require_once "../modelos/Caja.php";
require '../fpdf181/fpdf.php';

$caja = new Caja();
$idcaja = $_GET["id"];

// Obtener resumen de caja
$resumen = $caja->obtenerResumenCaja($idcaja);

if (!$resumen) {
    die("Caja no encontrada");
}

$datos_caja = $resumen['caja'];
$ventas = $resumen['ventas'];
$compras = $resumen['compras'];

// Crear PDF
$pdf = new FPDF('P', 'mm', 'A4');
$pdf->AddPage();

// Encabezado
$pdf->SetFont('Arial', 'B', 16);
$pdf->Cell(0, 10, utf8_decode('CIERRE DE CAJA'), 0, 1, 'C');
$pdf->Ln(5);

// Información de la caja
$pdf->SetFont('Arial', 'B', 12);
$pdf->Cell(0, 8, utf8_decode('Información General'), 0, 1);
$pdf->SetFont('Arial', '', 10);
$pdf->Cell(50, 6, utf8_decode('Caja N°:'), 0, 0);
$pdf->Cell(0, 6, $datos_caja['idcaja'], 0, 1);
$pdf->Cell(50, 6, utf8_decode('Usuario:'), 0, 0);
$pdf->Cell(0, 6, utf8_decode($datos_caja['usuario']), 0, 1);
$pdf->Cell(50, 6, utf8_decode('Fecha Apertura:'), 0, 0);
$pdf->Cell(0, 6, date('d/m/Y H:i', strtotime($datos_caja['fecha_apertura'])), 0, 1);
$pdf->Cell(50, 6, utf8_decode('Fecha Cierre:'), 0, 0);
$pdf->Cell(0, 6, $datos_caja['fecha_cierre'] ? date('d/m/Y H:i', strtotime($datos_caja['fecha_cierre'])) : '-', 0, 1);
$pdf->Ln(5);

// Resumen financiero
$pdf->SetFont('Arial', 'B', 12);
$pdf->Cell(0, 8, utf8_decode('Resumen Financiero'), 0, 1);
$pdf->SetFont('Arial', '', 10);

$pdf->Cell(50, 6, utf8_decode('Monto Inicial:'), 0, 0);
$pdf->Cell(0, 6, 'S/. ' . number_format($datos_caja['monto_inicial'], 2), 0, 1);

$pdf->Cell(50, 6, utf8_decode('Total Ventas:'), 0, 0);
$pdf->SetTextColor(0, 128, 0);
$pdf->Cell(0, 6, 'S/. ' . number_format($datos_caja['total_ventas'], 2), 0, 1);
$pdf->SetTextColor(0, 0, 0);

$pdf->Cell(50, 6, utf8_decode('Total Compras:'), 0, 0);
$pdf->SetTextColor(255, 0, 0);
$pdf->Cell(0, 6, 'S/. ' . number_format($datos_caja['total_compras'], 2), 0, 1);
$pdf->SetTextColor(0, 0, 0);

$saldo_calculado = $datos_caja['monto_inicial'] + $datos_caja['total_ventas'] - $datos_caja['total_compras'];
$pdf->SetFont('Arial', 'B', 10);
$pdf->Cell(50, 6, utf8_decode('Saldo Calculado:'), 0, 0);
$pdf->Cell(0, 6, 'S/. ' . number_format($saldo_calculado, 2), 0, 1);

if ($datos_caja['monto_final']) {
    $pdf->Cell(50, 6, utf8_decode('Monto Final Real:'), 0, 0);
    $pdf->Cell(0, 6, 'S/. ' . number_format($datos_caja['monto_final'], 2), 0, 1);
    
    $diferencia = $datos_caja['monto_final'] - $saldo_calculado;
    $pdf->Cell(50, 6, utf8_decode('Diferencia:'), 0, 0);
    if ($diferencia != 0) {
        $pdf->SetTextColor($diferencia > 0 ? 0 : 255, 0, 0);
    }
    $pdf->Cell(0, 6, 'S/. ' . number_format($diferencia, 2), 0, 1);
    $pdf->SetTextColor(0, 0, 0);
}

$pdf->Ln(10);

// Detalle de Ventas
$pdf->SetFont('Arial', 'B', 12);
$pdf->Cell(0, 8, utf8_decode('Detalle de Ventas'), 0, 1);

if ($ventas->num_rows > 0) {
    $pdf->SetFont('Arial', 'B', 9);
    $pdf->SetFillColor(200, 220, 255);
    $pdf->Cell(15, 6, '#', 1, 0, 'C', true);
    $pdf->Cell(40, 6, 'Comprobante', 1, 0, 'C', true);
    $pdf->Cell(80, 6, 'Cliente', 1, 0, 'C', true);
    $pdf->Cell(35, 6, 'Fecha/Hora', 1, 0, 'C', true);
    $pdf->Cell(20, 6, 'Monto', 1, 1, 'C', true);
    
    $pdf->SetFont('Arial', '', 8);
    $num = 1;
    while ($venta = $ventas->fetch_assoc()) {
        $pdf->Cell(15, 5, $num++, 1, 0, 'C');
        $pdf->Cell(40, 5, $venta['tipo_comprobante'] . ' ' . $venta['serie_comprobante'] . '-' . $venta['num_comprobante'], 1, 0);
        $pdf->Cell(80, 5, utf8_decode(substr($venta['cliente'], 0, 35)), 1, 0);
        $pdf->Cell(35, 5, date('d/m/Y H:i', strtotime($venta['fecha_hora'])), 1, 0, 'C');
        $pdf->Cell(20, 5, number_format($venta['total_venta'], 2), 1, 1, 'R');
    }
} else {
    $pdf->SetFont('Arial', 'I', 10);
    $pdf->Cell(0, 6, 'No hay ventas registradas', 0, 1);
}

$pdf->Ln(5);

// Detalle de Compras
$pdf->SetFont('Arial', 'B', 12);
$pdf->Cell(0, 8, utf8_decode('Detalle de Compras'), 0, 1);

if ($compras->num_rows > 0) {
    $pdf->SetFont('Arial', 'B', 9);
    $pdf->SetFillColor(255, 220, 200);
    $pdf->Cell(15, 6, '#', 1, 0, 'C', true);
    $pdf->Cell(40, 6, 'Comprobante', 1, 0, 'C', true);
    $pdf->Cell(80, 6, 'Proveedor', 1, 0, 'C', true);
    $pdf->Cell(35, 6, 'Fecha/Hora', 1, 0, 'C', true);
    $pdf->Cell(20, 6, 'Monto', 1, 1, 'C', true);
    
    $pdf->SetFont('Arial', '', 8);
    $num = 1;
    while ($compra = $compras->fetch_assoc()) {
        $pdf->Cell(15, 5, $num++, 1, 0, 'C');
        $pdf->Cell(40, 5, $compra['tipo_comprobante'] . ' ' . $compra['serie_comprobante'] . '-' . $compra['num_comprobante'], 1, 0);
        $pdf->Cell(80, 5, utf8_decode(substr($compra['proveedor'], 0, 35)), 1, 0);
        $pdf->Cell(35, 5, date('d/m/Y H:i', strtotime($compra['fecha_hora'])), 1, 0, 'C');
        $pdf->Cell(20, 5, number_format($compra['total_compra'], 2), 1, 1, 'R');
    }
} else {
    $pdf->SetFont('Arial', 'I', 10);
    $pdf->Cell(0, 6, 'No hay compras registradas', 0, 1);
}

// Observaciones
if (!empty($datos_caja['observaciones'])) {
    $pdf->Ln(10);
    $pdf->SetFont('Arial', 'B', 12);
    $pdf->Cell(0, 8, utf8_decode('Observaciones'), 0, 1);
    $pdf->SetFont('Arial', '', 10);
    $pdf->MultiCell(0, 5, utf8_decode($datos_caja['observaciones']));
}

// Pie de página
$pdf->Ln(10);
$pdf->SetFont('Arial', 'I', 8);
$pdf->Cell(0, 5, utf8_decode('Documento generado el ' . date('d/m/Y H:i:s')), 0, 1, 'C');

$pdf->Output('I', 'Cierre_Caja_' . $idcaja . '.pdf');
?>
