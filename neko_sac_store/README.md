# NEKO SAC Store (Frontend e‑commerce en PHP)

Este proyecto es una **tienda web de autopartes** para NEKO SAC, inspirada en el diseño de sitios como Renusa y conectada a tu base de datos existente `bd_ferreteria`.

## Características

- Mini framework MVC en PHP 8 (router simple + controllers + modelos + vistas).
- Diseño moderno con **Bootstrap 5** + CSS personalizado (layout tipo Renusa).
- Listado de productos, filtro por categoría, búsqueda y carrito de compras en sesión.
- Se conecta directamente a las tablas `articulo` y `categoria` de tu BD.

## Requisitos

- PHP 8.1 o superior
- Servidor web (Apache recomendado)
- MySQL / MariaDB con la base de datos `bd_ferreteria` ya importada.

## Instalación rápida

1. Copia la carpeta del proyecto (por ejemplo `neko_sac_store`) dentro de tu servidor web:
   - En XAMPP: `C:\\xampp\\htdocs\\neko_sac_store`

2. Asegúrate de tener creada la BD `bd_ferreteria` (usando tu dump `.sql` existente).

3. Ajusta la configuración de conexión en:

   `config/config.php`

   ```php
   const DB_HOST = '127.0.0.1';
   const DB_NAME = 'bd_ferreteria';
   const DB_USER = 'root';
   const DB_PASS = '';
   ```

4. En el navegador, entra a:

   - `http://localhost/neko_sac_store/public`  → Home
   - `http://localhost/neko_sac_store/public/tienda` → Catálogo

## Estructura

- `public/` → Front controller (`index.php`), assets, `.htaccess`
- `src/Core/` → Router, Controller base, View, Database
- `src/Controllers/` → `HomeController`, `ProductController`, `CartController`
- `src/Models/` → `Articulo`, `Categoria`
- `src/Views/` → Vistas organizadas en carpetas (`home`, `products`, `cart`, `layouts`, `partials`)
- `config/` → configuración global y definición de rutas

Puedes integrar tus **headers actuales de SistemaNeko.V2** reemplazando el contenido de `src/Views/partials/header.php` por tu header PHP existente, manteniendo las rutas `/`, `/tienda` y `/carrito`.

Este es un punto de partida limpio y moderno para seguir extendiendo módulos (auth, checkout completo, integración con tu backend de ventas, etc.).
