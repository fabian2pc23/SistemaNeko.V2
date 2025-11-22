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
if ($_SESSION['ventas']==1)
{
?>
<html>
<head>
<meta http-equiv="content-type" content="text/html; charset=utf-8" />
<link href="../public/css/ticket.css" rel="stylesheet" type="text/css">
</head>
<body onload="window.print();">
<?php

//Incluímos la clase Venta
require_once "../modelos/Venta.php";
//Instanaciamos a la clase con el objeto venta
$venta = new Venta();
//En el objeto $rspta Obtenemos los valores devueltos del método ventacabecera del modelo
$rspta = $venta->ventacabecera($_GET["id"]);
//Recorremos todos los valores obtenidos
$reg = $rspta->fetch_object();

//Establecemos los datos de la empresa
$logo = "../assets/logo.png";
$empresa = "Ferretería neko";
$documento = "10406980788";
$direccion = "Carretera a lambayeque";
$telefono = "921263349";
$email = "nekosaccix@gmail.com";

?>
<div class="zona_impresion">
<!-- codigo imprimir -->
<br>
<table border="0" align="center" width="300px">
    <tr>
        <td align="center">
        <!-- Mostramos los datos de la empresa en el documento HTML -->
        <img src="<?php echo $logo; ?>" width="100" style="margin-bottom: 10px;"><br>
        <strong><?php echo $empresa; ?></strong><br>
        RUC: <?php echo $documento; ?><br>
        <?php echo $direccion; ?><br>
        Telf: <?php echo $telefono; ?><br>
        </td>
    </tr>
    <tr>
        <td align="center">-------------------------------------------------------</td>
    </tr>
    <tr>
        <td align="center">
            <strong><?php echo strtoupper($reg->tipo_comprobante); ?> ELECTRÓNICA</strong><br>
            <?php echo $reg->serie_comprobante." - ".$reg->num_comprobante ; ?><br>
            Fecha: <?php echo $reg->fecha; ?>
        </td>
    </tr>
    <tr>
        <td align="center">-------------------------------------------------------</td>
    </tr>
    <tr>
        <!-- Mostramos los datos del cliente en el documento HTML -->
        <td>
            Cliente: <?php echo $reg->cliente; ?><br>
            <?php echo $reg->tipo_documento; ?>: <?php echo $reg->num_documento; ?><br>
            Dirección: <?php echo $reg->direccion; ?>
        </td>
    </tr>
</table>
<br>
<!-- Mostramos los detalles de la venta en el documento HTML -->
<table border="0" align="center" width="300px">
    <tr>
        <td>CANT.</td>
        <td>DESCRIPCIÓN</td>
        <td align="right">IMPORTE</td>
    </tr>
    <tr>
      <td colspan="3">-------------------------------------------------------</td>
    </tr>
    <?php
    $rsptad = $venta->ventadetalle($_GET["id"]);
    $cantidad=0;
    while ($regd = $rsptad->fetch_object()) {
        echo "<tr>";
        echo "<td valign='top'>".$regd->cantidad."</td>";
        echo "<td valign='top'>".strtolower($regd->articulo)."</td>";
        echo "<td valign='top' align='right'>S/ ".number_format($regd->subtotal, 2)."</td>";
        echo "</tr>";
        $cantidad+=$regd->cantidad;
    }
    ?>
    <tr>
      <td colspan="3">-------------------------------------------------------</td>
    </tr>
    <!-- Mostramos los totales de la venta en el documento HTML -->
    <?php
    $igv = ($reg->total_venta * $reg->impuesto) / 100;
    $subtotal = $reg->total_venta - $igv;
    ?>
    <tr>
    <td>&nbsp;</td>
    <td align="right">Op. Gravada:</td>
    <td align="right">S/ <?php echo number_format($subtotal, 2); ?></td>
    </tr>
    <tr>
    <td>&nbsp;</td>
    <td align="right">IGV (<?php echo $reg->impuesto; ?>%):</td>
    <td align="right">S/ <?php echo number_format($igv, 2); ?></td>
    </tr>
    <tr>
    <td>&nbsp;</td>
    <td align="right"><b>TOTAL:</b></td>
    <td align="right"><b>S/ <?php echo number_format($reg->total_venta, 2); ?></b></td>
    </tr>
    <tr>
      <td colspan="3">&nbsp;</td>
    </tr>
    <tr>
      <td colspan="3" align="center">
        <?php 
        require_once "Letras.php";
        $V=new EnLetras(); 
        $con_letra=strtoupper($V->ValorEnLetras($reg->total_venta,"NUEVOS SOLES"));
        echo "SON: ".$con_letra;
        ?>
      </td>
    </tr>
    <tr>
      <td colspan="3">&nbsp;</td>
    </tr>      
    <tr>
      <td colspan="3" align="center">¡Gracias por su compra!</td>
    </tr>
    <tr>
      <td colspan="3" align="center">Ferretería Neko</td>
    </tr>
    
</table>
<br>
</div>
<p>&nbsp;</p>

</body>
</html>
<?php 
}
else
{
  echo 'No tiene permiso para visualizar el reporte';
}

}
ob_end_flush();
?>
