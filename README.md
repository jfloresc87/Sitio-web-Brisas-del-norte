# Sitio web Brisas del Norte

Sitio público y panel de administración para la junta de vecinos.

## Funcionalidades

- Información comunitaria, directiva, noticias y servicios.
- Edición de fotos y biografías de la directiva desde el panel.
- Carrusel de actividades administrable desde el panel.
- Formulario de contacto protegido con reCAPTCHA.
- Gestión de contenidos y cuentas administrativas.
- Modo claro y oscuro.
- Optimización de imágenes al subirlas y para los archivos existentes desde el panel.

## Requisitos

- PHP 8.1 o superior.
- MySQL con la extensión PDO para PHP.
- Extensiones de PHP `gd`, `fileinfo` y `mbstring`.
- Servidor web con HTTPS habilitado.

## Estructura principal

- `index.php`: sitio público.
- `admin/`: panel de administración.
- `includes/`: lógica compartida y configuración del servidor.
- `assets/`: estilos, scripts e imágenes del sitio.
- `inicio/` y `directiva/`: imágenes iniciales del sitio.
- `uploads/`: fotos y otros archivos que se agregan desde el panel.

## Puesta en marcha

1. Configura PHP, MySQL y HTTPS en el servidor.
2. Prepara la configuración privada del sitio directamente en el servidor; no publiques credenciales, claves ni datos personales en Git.
3. Configura las claves oficiales de reCAPTCHA en el entorno privado del servidor.
4. Sube los archivos del proyecto y habilita escritura únicamente en las carpetas necesarias para las cargas.
5. Inicia sesión en el panel para administrar noticias, servicios, imágenes y cuentas.
6. Comprueba el funcionamiento del sitio y del formulario antes de habilitarlo al público.

## Seguridad y mantenimiento

- Mantén contraseñas y claves fuera del repositorio y de la documentación pública.
- Usa HTTPS y cuentas de base de datos con privilegios limitados.
- Conserva copias de seguridad de la base de datos y de los archivos subidos.
- Actualiza las dependencias y el servidor con regularidad.

## Optimización de imágenes

- Las imágenes nuevas se reducen a un máximo de 1600 px por lado y se re-guardan en alta calidad (JPEG/WEBP calidad 90, PNG sin pérdida). Solo se usa la versión procesada si pesa menos que la original.
- En el panel, abre **Optimizar imágenes** y procesa los lotes hasta completar la revisión. El proceso incluye `assets/img/`, `inicio/`, `directiva/` y `uploads/`.
- Antes de reemplazar cualquier archivo existente, se guarda una copia privada bajo `includes/image-originals/`. El acceso web a `includes/` está bloqueado por `.htaccess`.
- Las imágenes animadas se conservan sin cambios. Las copias originales deben incluirse en las copias de seguridad del servidor.
