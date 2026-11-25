<?php

/*
|--------------------------------------------------------------------------
| Configuración del sitio público
|--------------------------------------------------------------------------
|
| Datos de contacto del laboratorio que usa el sistema para avisar por correo
| de las solicitudes recibidas desde el sitio web.
|
*/

return [

    // Bandeja que recibe el aviso de cada solicitud del formulario público.
    'correo_solicitudes' => env('CONTACTO_CORREO', 'contacto@netsaldental.pe'),

    // Teléfono que se muestra en los mensajes de respaldo.
    'telefono' => env('CONTACTO_TELEFONO', '+51 984 900 939'),

    /*
    | Zona horaria con la que se muestran las fechas y horas al usuario.
    |
    | La aplicación almacena las marcas de tiempo en UTC (config/app.php), que
    | es la práctica recomendada. Para mostrarlas se convierten a la hora del
    | laboratorio, de modo que una solicitud recibida a las 15:54 no aparezca
    | como 20:54.
    */
    'zona_horaria' => env('APP_DISPLAY_TIMEZONE', 'America/Lima'),

    /*
    | Acceso rápido de demostración en la pantalla de ingreso.
    |
    | Muestra botones con las cuentas de prueba para facilitar la revisión del
    | proyecto en clase. Debe quedar en false en cualquier despliegue real:
    | publicar credenciales es exponer el sistema.
    */
    'acceso_demo' => (bool) env('MOSTRAR_ACCESO_DEMO', true),

];
