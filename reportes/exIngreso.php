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
if ($_SESSION['compras']==1)
{
//Incluímos el archivo Ingreso.php
require('Ingreso.php');

//Establecemos los datos de la empresa
$logo = "../assets/logo.png";
$ext_logo = "png";
$empresa = "Ferretería neko";
$documento = "10406980788";
$direccion = "Carretera a lambayeque";
$telefono = "921263349";
$email = "nekosaccix@gmail.com";


//Obtenemos los datos de la cabecera de la venta actual
require_once "../modelos/Ingreso.php";
$ingreso= new Ingreso();
$rsptav = $ingreso->ingresocabecera($_GET["id"]);
//Recorremos todos los valores obtenidos
$regv = $rsptav->fetch_object();

//Establecemos la configuración de la factura
$pdf = new PDF_Invoice( 'P', 'mm', 'A4' );
$pdf->AddPage();

//Enviamos los datos de la empresa al método addSociete de la clase Factura
$pdf->addSociete(utf8_decode($empresa),
                  $documento."\n" .
                  utf8_decode("Dirección: ").utf8_decode($direccion)."\n".
                  utf8_decode("Teléfono: ").$telefono."\n" .
                  "Email : ".$email,$logo,$ext_logo);
$pdf->fact_dev( utf8_decode("Constancia de Adquisición "), "$regv->serie_comprobante-$regv->num_comprobante" );
$pdf->temporaire( "" );
$pdf->addDate( $regv->fecha);

//Enviamos los datos del cliente al método addClientAdresse de la clase Factura
$pdf->addClientAdresse(utf8_decode($regv->proveedor),"Domicilio: ".utf8_decode($regv->direccion),$regv->tipo_documento.": ".$regv->num_documento,"Email: ".$regv->email,"Telefono: ".$regv->telefono);

//Establecemos las columnas que va a tener la sección donde mostramos los detalles de la venta
$cols=array( "DESCRIPCION"=>146,
             "CANTIDAD"=>22,
             "SUBTOTAL"=>22);
$pdf->addCols( $cols);
$cols=array( "DESCRIPCION"=>"L",
             "CANTIDAD"=>"C",
             "SUBTOTAL"=>"C");
$pdf->addLineFormat( $cols);
$pdf->addLineFormat($cols);
//Actualizamos el valor de la coordenada "y", que será la ubicación desde donde empezaremos a mostrar los datos
$y= 89;

//Obtenemos todos los detalles de la venta actual
$rsptad = $ingreso->ingresodetalle($_GET["id"]);

while ($regd = $rsptad->fetch_object()) {
  $line = array( "DESCRIPCION"=> utf8_decode("$regd->articulo"),
                "CANTIDAD"=> "$regd->cantidad",
                "SUBTOTAL"=> "$regd->subtotal");
            $size = $pdf->addLine( $y, $line );
            $y   += $size + 2;
}

//Convertimos el total en letras
require_once "Letras.php";
$V=new EnLetras(); 
$con_letra=strtoupper($V->ValorEnLetras($regv->total_compra,"NUEVOS SOLES"));
$pdf->addCadreTVAs("---".$con_letra);

//Mostramos el impuesto
$pdf->addTVAs( $regv->impuesto, $regv->total_compra,"S/ ");
$pdf->addCadreEurosFrancs("IGV"." $regv->impuesto %");
$pdf->Output('Reporte de Venta','I');


}
else
{
  echo 'No tiene permiso para visualizar el reporte';
}

}
ob_end_flush();
?>
