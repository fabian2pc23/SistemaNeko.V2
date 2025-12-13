<?php
// visor_xml.php - Visor de XML para comprobantes electrónicos
ob_start();
if (strlen(session_id()) < 1)
    session_start();

if (!isset($_SESSION["nombre"])) {
    header("Location: ../vistas/login.html");
    exit;
}

$file = isset($_GET['file']) ? $_GET['file'] : '';

// Validar que el archivo existe y es un XML
$basePath = __DIR__ . '/../files/facturas/xml/';
$fullPath = $basePath . basename($file); // Evitar path traversal

if (!file_exists($fullPath) || pathinfo($fullPath, PATHINFO_EXTENSION) !== 'xml') {
    echo '<h3>Archivo XML no encontrado o no válido</h3>';
    exit;
}

$xmlContent = file_get_contents($fullPath);

// Formatear XML para mejor visualización
$dom = new DOMDocument();
$dom->preserveWhiteSpace = false;
$dom->formatOutput = true;
$dom->loadXML($xmlContent);
$formattedXml = $dom->saveXML();
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Visor XML - Comprobante Electrónico</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', 'Arial', sans-serif;
            background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%);
            min-height: 100vh;
            padding: 20px;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
        }

        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: #fff;
            padding: 16px 24px;
            border-radius: 12px 12px 0 0;
            border-bottom: 2px solid #e2e8f0;
        }

        .header h1 {
            font-size: 1.25rem;
            color: #1e293b;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .header h1 i {
            color: #2563eb;
        }

        .header-actions {
            display: flex;
            gap: 10px;
        }

        .btn {
            padding: 8px 16px;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            transition: all 0.2s;
            text-decoration: none;
            font-size: 0.9rem;
        }

        .btn-primary {
            background: #2563eb;
            color: #fff;
        }

        .btn-primary:hover {
            background: #1d4ed8;
        }

        .btn-secondary {
            background: #64748b;
            color: #fff;
        }

        .btn-secondary:hover {
            background: #475569;
        }

        .xml-container {
            background: #1e1e1e;
            padding: 24px;
            border-radius: 0 0 12px 12px;
            overflow-x: auto;
        }

        pre {
            color: #d4d4d4;
            font-family: 'Consolas', 'Monaco', monospace;
            font-size: 13px;
            line-height: 1.6;
            white-space: pre-wrap;
            word-wrap: break-word;
        }

        /* Sintaxis coloreada */
        .xml-tag {
            color: #569cd6;
        }

        .xml-attr {
            color: #9cdcfe;
        }

        .xml-value {
            color: #ce9178;
        }

        .xml-content {
            color: #d4d4d4;
        }

        .xml-comment {
            color: #6a9955;
            font-style: italic;
        }

        .file-info {
            background: #f8fafc;
            padding: 12px 24px;
            font-size: 0.85rem;
            color: #64748b;
            border-bottom: 1px solid #e2e8f0;
        }

        .file-info strong {
            color: #334155;
        }
    </style>
</head>

<body>
    <div class="container">
        <div class="header">
            <h1><i class="fa fa-file-code-o"></i> Visor de Comprobante XML</h1>
            <div class="header-actions">
                <a href="<?php echo $fullPath; ?>" download class="btn btn-primary">
                    <i class="fa fa-download"></i> Descargar
                </a>
                <a href="javascript:window.close();" class="btn btn-secondary">
                    <i class="fa fa-times"></i> Cerrar
                </a>
            </div>
        </div>
        <div class="file-info">
            <strong>Archivo:</strong> <?php echo htmlspecialchars(basename($file)); ?> &nbsp;|&nbsp;
            <strong>Tamaño:</strong> <?php echo number_format(filesize($fullPath) / 1024, 2); ?> KB
        </div>
        <div class="xml-container">
            <pre><?php echo htmlspecialchars($formattedXml); ?></pre>
        </div>
    </div>
</body>

</html>
<?php ob_end_flush(); ?>