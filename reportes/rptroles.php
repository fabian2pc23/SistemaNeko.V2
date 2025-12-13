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
            private $colorPrimario = array(155, 89, 182);   // Púrpura para roles
            private $colorSecundario = array(142, 68, 173);
            private $colorAcento = array(52, 152, 219);     // Azul
            private $colorAlerta = array(231, 76, 60);
            private $colorExito = array(46, 204, 113);
            private $colorTextoClaro = array(255, 255, 255);

            function Header()
            {
                $this->SetFillColor($this->colorPrimario[0], $this->colorPrimario[1], $this->colorPrimario[2]);
                $this->Rect(0, 0, 210, 40, 'F');

                $this->SetFillColor($this->colorSecundario[0], $this->colorSecundario[1], $this->colorSecundario[2]);
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

            function PanelEstadisticas($total, $activos, $inactivos)
            {
                $this->SetFont('Arial', 'B', 14);
                $this->SetTextColor(142, 68, 173);
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
                $this->Cell($cardWidth - 4, 6, 'TOTAL ROLES', 0, 0, 'C');
                $this->SetFont('Arial', 'B', 22);
                $this->SetXY($startX + 2, $y + 15);
                $this->Cell($cardWidth - 4, 15, $total, 0, 0, 'C');

                // Tarjeta 2: Activos
                $posX2 = $startX + $cardWidth + $spacing;
                $this->SetFillColor($this->colorExito[0], $this->colorExito[1], $this->colorExito[2]);
                $this->RoundedRect($posX2, $y, $cardWidth, $cardHeight, 3, 'F');
                $this->SetFont('Arial', 'B', 9);
                $this->SetXY($posX2 + 2, $y + 5);
                $this->Cell($cardWidth - 4, 6, 'ACTIVOS', 0, 0, 'C');
                $this->SetFont('Arial', 'B', 22);
                $this->SetXY($posX2 + 2, $y + 15);
                $this->Cell($cardWidth - 4, 15, $activos, 0, 0, 'C');

                // Tarjeta 3: Inactivos
                $posX3 = $posX2 + $cardWidth + $spacing;
                $this->SetFillColor($this->colorAlerta[0], $this->colorAlerta[1], $this->colorAlerta[2]);
                $this->RoundedRect($posX3, $y, $cardWidth, $cardHeight, 3, 'F');
                $this->SetFont('Arial', 'B', 9);
                $this->SetXY($posX3 + 2, $y + 5);
                $this->Cell($cardWidth - 4, 6, 'INACTIVOS', 0, 0, 'C');
                $this->SetFont('Arial', 'B', 22);
                $this->SetXY($posX3 + 2, $y + 15);
                $this->Cell($cardWidth - 4, 15, $inactivos, 0, 0, 'C');

                $this->SetY($y + $cardHeight + 15);
                $this->SetTextColor(0, 0, 0);
            }

            function SeccionTitulo($titulo)
            {
                $this->SetFillColor($this->colorAcento[0], $this->colorAcento[1], $this->colorAcento[2]);
                $this->Rect($this->GetX(), $this->GetY(), 3, 8, 'F');

                $this->SetFillColor(142, 68, 173);
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

        // Descripciones por rol (basadas en permisos comunes)
        function obtenerDescripcionRol($nombreRol, $permisos)
        {
            $nombre = strtolower($nombreRol);

            // Descripciones predefinidas según nombre común
            $descripciones = array(
                'admin' => 'Administrador del sistema con acceso total a todos los módulos y funcionalidades.',
                'administrador' => 'Administrador del sistema con acceso total a todos los módulos y funcionalidades.',
                'vendedor' => 'Personal de ventas con acceso a crear y gestionar ventas y consultar clientes.',
                'almacenero' => 'Encargado del almacén con acceso a inventario, ingresos y gestión de productos.',
                'supervisor' => 'Supervisor con acceso a reportes, consultas y supervisión de operaciones.',
                'tecnico' => 'Personal técnico con acceso limitado a módulos específicos del sistema.',
                'seguridad' => 'Rol de seguridad con acceso a logs, auditoría y control de accesos.',
                'scrum master' => 'Facilitador del equipo ágil con acceso a gestión de proyectos y tareas.'
            );

            // Buscar descripción por nombre
            foreach ($descripciones as $key => $desc) {
                if (strpos($nombre, $key) !== false) {
                    return $desc;
                }
            }

            // Si no hay match, generar descripción basada en permisos
            if (empty($permisos)) {
                return 'Rol sin permisos asignados. Pendiente de configuración.';
            }

            $numPermisos = count(explode(', ', $permisos));
            return "Rol personalizado con acceso a $numPermisos módulo(s) del sistema.";
        }

        //Instanciamos la clase premium
        $pdf = new PDF_Premium();
        $pdf->AliasNbPages();

        //Obtener datos
        require_once "../modelos/Rol.php";
        require_once "../config/Conexion.php";

        $rol = new Rol();
        $rspta = $rol->listar();

        $total = 0;
        $activos = 0;
        $inactivos = 0;
        $datos = array();

        while ($reg = $rspta->fetch_object()) {
            $total++;
            if ($reg->estado == 1) {
                $activos++;
            } else {
                $inactivos++;
            }

            // Obtener permisos del rol
            $sqlPermisos = "SELECT p.nombre FROM permiso p 
                    INNER JOIN rol_permiso rp ON p.idpermiso = rp.idpermiso 
                    WHERE rp.id_rol = '{$reg->id_rol}'";
            $rsPermisos = ejecutarConsulta($sqlPermisos);
            $permisosArr = array();
            while ($perm = $rsPermisos->fetch_object()) {
                $permisosArr[] = $perm->nombre;
            }
            $reg->permisos = implode(', ', $permisosArr);
            $reg->descripcion = obtenerDescripcionRol($reg->nombre, $reg->permisos);

            $datos[] = $reg;
        }

        //Página de portada
        $pdf->PortadaReporte(
            'MATRIZ DE ROLES Y PERMISOS',
            $_SESSION["nombre"],
            'Departamento de Sistemas'
        );

        //Nueva página para el contenido
        $pdf->AddPage();

        //Panel de estadísticas
        $pdf->PanelEstadisticas($total, $activos, $inactivos);

        //Título de la tabla
        $pdf->SeccionTitulo('ROLES DEL SISTEMA');

        $headers = array(utf8_decode('N°'), 'Nombre', utf8_decode('Descripción'), 'Estado', 'Creado');
        $widths = array(10, 35, 80, 25, 40);
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

            $numero = str_pad($contador, 2, "0", STR_PAD_LEFT);
            $nombre = utf8_decode($reg->nombre);
            $descripcion = utf8_decode(substr($reg->descripcion, 0, 60) . (strlen($reg->descripcion) > 60 ? '...' : ''));
            $estado = $reg->estado == 1 ? 'Activo' : 'Inactivo';
            $creado = date('d/m/Y', strtotime($reg->creado_en));

            $pdf->Row(array($numero, $nombre, $descripcion, $estado, $creado), $fill);

            $fill = !$fill;
        }

        // Nueva página para detalle de permisos
        $pdf->AddPage();
        $pdf->SeccionTitulo('DETALLE DE PERMISOS POR ROL');

        foreach ($datos as $reg) {
            // Cabecera del rol
            $pdf->SetFillColor(245, 245, 245);
            $pdf->SetFont('Arial', 'B', 11);
            $pdf->SetTextColor(142, 68, 173);
            $pdf->Cell(0, 8, utf8_decode($reg->nombre), 0, 1, 'L', true);

            // Descripción
            $pdf->SetFont('Arial', 'I', 9);
            $pdf->SetTextColor(100, 100, 100);
            $pdf->MultiCell(0, 5, utf8_decode($reg->descripcion), 0, 'L');

            // Estado
            $pdf->SetFont('Arial', '', 9);
            if ($reg->estado == 1) {
                $pdf->SetTextColor(46, 204, 113);
                $pdf->Cell(50, 5, 'Estado: ACTIVO', 0, 0, 'L');
            } else {
                $pdf->SetTextColor(231, 76, 60);
                $pdf->Cell(50, 5, 'Estado: INACTIVO', 0, 0, 'L');
            }

            $pdf->SetTextColor(100, 100, 100);
            $pdf->Cell(0, 5, 'Creado: ' . date('d/m/Y H:i', strtotime($reg->creado_en)), 0, 1, 'R');

            // Permisos
            $pdf->SetFont('Arial', 'B', 9);
            $pdf->SetTextColor(0, 0, 0);
            $pdf->Cell(0, 6, 'Permisos asignados:', 0, 1, 'L');

            $pdf->SetFont('Arial', '', 9);
            if (!empty($reg->permisos)) {
                $permisosLista = explode(', ', $reg->permisos);
                $permisosTexto = '';
                foreach ($permisosLista as $idx => $perm) {
                    $permisosTexto .= '- ' . utf8_decode($perm);
                    if (($idx + 1) % 3 == 0) {
                        $permisosTexto .= "\n";
                    } else {
                        $permisosTexto .= '    ';
                    }
                }
                $pdf->MultiCell(0, 5, $permisosTexto, 0, 'L');
            } else {
                $pdf->SetTextColor(200, 200, 200);
                $pdf->Cell(0, 5, utf8_decode('Sin permisos asignados'), 0, 1, 'L');
            }

            $pdf->Ln(5);

            // Verificar salto de página
            if ($pdf->GetY() > 240) {
                $pdf->AddPage();
            }
        }

        //Sección de conclusiones
        $pdf->AddPage();
        $pdf->SeccionTitulo('ANÁLISIS Y RECOMENDACIONES');

        $pdf->SetFont('Arial', '', 10);
        $pdf->MultiCell(0, 6, utf8_decode(
            "El presente reporte muestra un total de $total roles configurados en el sistema, " .
                "de los cuales $activos están activos y $inactivos están inactivos.\n\n" .
                "La correcta configuración de roles y permisos es fundamental para la seguridad " .
                "y el control de acceso al sistema. Cada rol debe tener únicamente los permisos " .
                "necesarios para realizar sus funciones (principio de mínimo privilegio)."
        ), 0, 'J');

        $pdf->Ln(10);

        $pdf->SetFont('Arial', 'B', 11);
        $pdf->SetTextColor(155, 89, 182);
        $pdf->Cell(0, 6, 'RECOMENDACIONES DE SEGURIDAD:', 0, 1, 'L');
        $pdf->SetFont('Arial', '', 9);
        $pdf->SetTextColor(0, 0, 0);

        $pdf->MultiCell(0, 5, utf8_decode(
            "- Revisar periódicamente los permisos asignados a cada rol.\n" .
                "- No asignar permisos de administrador a roles operativos.\n" .
                "- Mantener un registro de cambios en la configuración de roles.\n" .
                "- Desactivar roles que ya no se utilicen en lugar de eliminarlos.\n" .
                "- Capacitar a los usuarios sobre sus responsabilidades según su rol."
        ), 0, 'L');

        //Mostramos el documento
        $pdf->Output('I', 'Reporte_Premium_Roles_' . date('Ymd_His') . '.pdf');
?>
<?php
    } else {
        echo 'No tiene permiso para visualizar el reporte';
    }
}
ob_end_flush();
?>
