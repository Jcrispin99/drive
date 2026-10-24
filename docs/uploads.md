# Carga de archivos

El límite predeterminado es **1 GB (1024 MB) por archivo**, tanto para archivos nuevos como para versiones. Se admiten todos los formatos, incluidos `.ppt` y `.pptx`. La vista previa sigue disponible para imágenes y PDF; las presentaciones se descargan para abrirlas.

`DRIVE_MAX_UPLOAD_SIZE_MB` controla la validación y el límite que muestra la interfaz. Después de cambiarlo, actualiza la caché de configuración si tu despliegue la utiliza.

## Servidor

Los límites del servidor deben permitir el archivo más los campos del formulario. Para el valor predeterminado, configura PHP:

```ini
upload_max_filesize = 1024M
post_max_size = 1056M
max_input_time = 3600
max_execution_time = 360
```

En el bloque `server` de Nginx correspondiente a esta aplicación:

```nginx
client_max_body_size 1056M;
client_body_timeout 3600s;
fastcgi_read_timeout 360s;
```

Recarga los servicios después de cambiar su configuración. Comprueba también los límites de cualquier proxy de tu despliegue.

En Laravel Herd para macOS, el servidor predeterminado puede tener un límite menor que el global. Usa una configuración específica de `drive.test` en `~/Library/Application Support/Herd/config/valet/Nginx/` para aplicar los límites a este proyecto. En su bloque FastCGI, PHP permite establecerlos para el sitio:

```nginx
fastcgi_param PHP_VALUE "upload_max_filesize=1024M\npost_max_size=1056M\nmax_input_time=3600\nmax_execution_time=360";
```

La carga usa streams en el servidor y envía un archivo a la vez en el navegador. Hace falta espacio disponible tanto para el archivo temporal recibido por PHP/Nginx como para la copia almacenada. La interfaz muestra progreso, distingue los archivos guardados de los fallidos y permite volver a seleccionar los que fallaron.
