<?php 
//Incluímos inicialmente la conexión a la base de datos
require_once "../config/Conexion.php";

Class Marca
{
	//Implementamos nuestro constructor
	public function __construct()
	{

	}

	//Implementamos un método para insertar registros
	public function insertar($nombre,$descripcion)
	{
		$validar = $this->validarDuplicado($nombre);
		if ($validar) {
			return "duplicado";
		}

		$sql="INSERT INTO marca (nombre,descripcion,condicion)
		      VALUES ('$nombre','$descripcion','1')";
		return ejecutarConsulta($sql);
	}

	//Implementamos un método para editar registros
	public function editar($idmarca,$nombre,$descripcion)
	{
		$validar = $this->validarDuplicado($nombre, $idmarca);
		if ($validar) {
			return "duplicado";
		}

		$sql="UPDATE marca SET nombre='$nombre',descripcion='$descripcion' WHERE idmarca='$idmarca'";
		return ejecutarConsulta($sql);
	}

	//Validar duplicados (Ultra Estricto: Alpha-only y Levenshtein)
	public function validarDuplicado($nombre, $idmarca = 0)
	{
		// 1. Normalización y Limpieza
		$nombre = trim($nombre);
		$nombreNorm = mb_strtolower($nombre, 'UTF-8');
		
		// Extraer solo letras (Alpha-only). Elimina números, espacios y símbolos.
		// Ejemplo: "Bosch 123" -> "bosch"
		// Ejemplo: "Bosch-2" -> "bosch"
		$nombreAlpha = preg_replace('/[^a-z\x{00C0}-\x{00FF}]/u', '', $nombreNorm);

		// Si el nombre es solo números/símbolos (ej. "123"), usamos el normalizado
		$usarAlpha = ($nombreAlpha !== '');

		$sql = "SELECT idmarca, nombre FROM marca WHERE idmarca != '$idmarca'";
		$resultado = ejecutarConsulta($sql);

		while ($reg = $resultado->fetch_object()) {
			$dbNombre = $reg->nombre;
			$dbNombreNorm = mb_strtolower($dbNombre, 'UTF-8');
			$dbNombreAlpha = preg_replace('/[^a-z\x{00C0}-\x{00FF}]/u', '', $dbNombreNorm);

			// Regla 1: Comparación "Alpha-only" (Detecta variaciones numéricas/simbólicas)
			// Si "Bosch" ya existe, "Bosch 123", "1. Bosch", "Bosch!" serán rechazados.
			if ($usarAlpha && $nombreAlpha === $dbNombreAlpha) {
				return "La marca '$nombre' es demasiado similar a '$dbNombre' (variación numérica o de símbolos).";
			}

			// Regla 2: Comparación Directa (para marcas numéricas como "3M" vs "3M")
			if (!$usarAlpha && $nombreNorm === $dbNombreNorm) {
				return "La marca '$dbNombre' ya existe.";
			}

			// Regla 3: Levenshtein sobre la parte Alpha (Detecta typos en la parte de texto)
			// Ejemplo: "Bosch" vs "Bosc123" -> Alpha: "bosch" vs "bosc" -> Distancia 1 -> Rechazado
			if ($usarAlpha && $dbNombreAlpha !== '') {
				$lev = levenshtein($nombreAlpha, $dbNombreAlpha);
				$largo = strlen($nombreAlpha);
				
				// Tolerancia muy estricta
				// Palabras cortas (<= 4 letras): 0 tolerancia (debe ser exacto)
				// Palabras largas (> 4 letras): 1 tolerancia (máximo 1 letra de diferencia)
				$tolerancia = ($largo <= 4) ? 0 : 1;

				if ($lev <= $tolerancia) {
					return "La marca '$nombre' es muy similar a '$dbNombre'.";
				}
			}
		}

		return false;
	}

	//Implementamos un método para desactivar marcas
	public function desactivar($idmarca)
	{
		$sql="UPDATE marca SET condicion='0' WHERE idmarca='$idmarca'";
		return ejecutarConsulta($sql);
	}

	//Implementamos un método para activar marcas
	public function activar($idmarca)
	{
		$sql="UPDATE marca SET condicion='1' WHERE idmarca='$idmarca'";
		return ejecutarConsulta($sql);
	}

	//Implementar un método para mostrar los datos de un registro a modificar
	public function mostrar($idmarca)
	{
		$sql="SELECT * FROM marca WHERE idmarca='$idmarca'";
		return ejecutarConsultaSimpleFila($sql);
	}

	//Implementar un método para listar los registros
	public function listar()
	{
		$sql="SELECT * FROM marca ORDER BY nombre ASC";
		return ejecutarConsulta($sql);		
	}

	//Implementar un método para listar los registros y mostrar en el select
	public function select()
	{
		$sql="SELECT * FROM marca WHERE condicion=1 ORDER BY nombre ASC";
		return ejecutarConsulta($sql);		
	}

	//Implementar un método para listar los registros activos (alias de select para consistencia)
	public function listarActivos()
	{
		$sql="SELECT * FROM marca WHERE condicion=1 ORDER BY nombre ASC";
		return ejecutarConsulta($sql);		
	}

	// ==================== MÉTODOS PARA KPIs ====================
	
	// Marcas sin artículos
	public function marcasSinArticulos()
	{
		$sql = "SELECT m.idmarca, m.nombre, COUNT(a.idarticulo) as total_articulos
		        FROM marca m
		        LEFT JOIN articulo a ON m.idmarca = a.idmarca
		        WHERE m.condicion = 1
		        GROUP BY m.idmarca, m.nombre
		        HAVING total_articulos = 0
		        ORDER BY m.nombre ASC";
		return ejecutarConsulta($sql);
	}

	// Marcas con stock crítico (al menos un artículo con stock <= 5)
	public function marcasStockCritico()
	{
		$sql = "SELECT DISTINCT m.idmarca, m.nombre
		        FROM marca m
		        INNER JOIN articulo a ON m.idmarca = a.idmarca
		        WHERE m.condicion = 1 AND a.stock <= 5
		        ORDER BY m.nombre ASC";
		return ejecutarConsulta($sql);
	}

	// Marcas nuevas (últimas 5 registradas)
	public function marcasNuevas()
	{
		$sql = "SELECT idmarca, nombre
		        FROM marca
		        WHERE condicion = 1
		        ORDER BY idmarca DESC
		        LIMIT 5";
		return ejecutarConsulta($sql);
	}
}

