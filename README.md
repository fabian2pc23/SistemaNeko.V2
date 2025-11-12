# SistemaNeko.V2

Version 4.0.0
-Sergio Gil Reyes

Header

- Se muestra el rol del usuario

Se añadio al Login

- El login verifica el estado del usuario para poder ingresar, mientras no este habilitado no puede ingresar.(condicion 1)

Se modifico Register 

- Eliminacion de Roles 
- Validacion de Telefono 
- Validaciones de numero de documento
- Validacion de correos actualizada

Se modifico el ChangePassword 

- Se realiza la busqueda del correo 
- Barra de intentos 
- Base de datos con los intentos que se realizan y sino se inabilita el token 
- Todos los errores posibles de contraseña se validan y cuentan en la barra

Se modifico la vista Usuario

- Ahora aparece estado pendiente permitiendo al administrador solo editar y asignar un rol para poder activarlo
- Se asigno imagenes predeterminadas por roles
