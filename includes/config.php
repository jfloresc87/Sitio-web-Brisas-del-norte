<?php
/* =====================================================================
 *  CONFIGURACIÓN GENERAL  –  edita SOLO este archivo para cambiar datos
 * ===================================================================== */

// ---- Base de datos (créalas en cPanel, ver instrucciones) -------------
define('DB_HOST', 'localhost');
define('DB_USER', 'ulnlxkwm_junta');            // usuario MySQL creado en cPanel
define('DB_PASS', 'Bri4424.-*');         // clave de ese usuario
define('DB_WEB',  'ulnlxkwm_brisas');           // BDD del sitio (usuarios, noticias, servicios)
define('DB_REC',  'ulnlxkwm_comprob');          // BDD APARTE para comprobantes

// ---- Sitio y correo ---------------------------------------------------
define('BASE_URL',   'https://brisasdelnorteuv8.cl');       // sin "/" final
define('MAIL_FROM',  'no-reply@brisasdelnorteuv8.cl');      // casilla del dominio (evita spam)
define('MAIL_TO',    'brisasdelnorteuv8@gmail.com'); // recibe los mensajes del formulario
define('INSTALL_KEY','Brisas4424'); // se pide al abrir /install.php

// ---- Datos de la junta (se usan en el sitio y en los comprobantes) ----
$JUNTA = [
  'nombre'    => 'Junta de Vecinos N°8 Brisas del Norte',   // <-- EDITAR
  'lema'      => 'Unidos hoy, Más fuertes mañana',           // <-- EDITAR
  'direccion' => 'Pasaje Apolo N° 4424, Conchalí',       // <-- EDITAR
  'telefono'  => '+56 9 7407 5381',                          // <-- EDITAR (se muestra)
  'whatsapp'  => '56974075381',                              // <-- EDITAR: solo números con 56, sin + (agendamiento)
  'email'     => 'brisasdelnorteuv8@gmail.com',
  'logo'      => 'assets/img/logo.png',                      // <-- reemplaza el archivo por tu logo (PNG)
  'redes'     => [
    'facebook'  => 'https://www.facebook.com/brisasdelnorte.juntadevecinos',
    'instagram' => 'https://www.instagram.com/brisasdelnorte.jv8/',
  ],
];

// Las claves de Google se guardan en un archivo local ignorado por Git.
$RECAPTCHA = ['site_key'=>'', 'secret_key'=>''];
$recaptchaConfig = __DIR__.'/recaptcha.local.php';
if (is_file($recaptchaConfig)) {
  $localRecaptcha = require $recaptchaConfig;
  if (!is_array($localRecaptcha)
      || !isset($localRecaptcha['site_key'], $localRecaptcha['secret_key'])
      || !is_string($localRecaptcha['site_key'])
      || !is_string($localRecaptcha['secret_key'])) {
    throw new RuntimeException('La configuración local de reCAPTCHA debe incluir site_key y secret_key como texto.');
  }
  $RECAPTCHA = [
    'site_key'=>trim($localRecaptcha['site_key']),
    'secret_key'=>trim($localRecaptcha['secret_key']),
  ];
  if (($RECAPTCHA['site_key'] === '') !== ($RECAPTCHA['secret_key'] === '')) {
    throw new RuntimeException('Configura ambas claves de reCAPTCHA o elimina includes/recaptcha.local.php.');
  }
}
$RECAPTCHA_ENABLED = $RECAPTCHA['site_key'] !== '';

// ---- Carrusel de Inicio: archivos dentro de la carpeta /inicio --------
$CARRUSEL = [
  'foto1.jpg',   // <-- EDITAR: agrega o quita líneas con los nombres de tus fotos
  'foto2.jpg',
  'foto3.jpg',
];

// ---- Directiva: fotos dentro de la carpeta /directiva -----------------
$DIRECTIVA = [
  ['cargo'=>'Presidente',  'nombre'=>'Jesús Flores Cisternas', 'foto'=>'presidente.jpg',
   'bio'=>'Profesional del área informática, con cerca de 20 años de experiencia y una trayectoria marcada por el esfuerzo y la perseverancia. Padre de dos hijos y vecino de Conchalí, destaca por su compromiso social y vocación de servicio. Actualmente, trabaja por impulsar iniciativas que fortalezcan la seguridad, la participación ciudadana y el bienestar de su comunidad.'],
  ['cargo'=>'Secretaria',  'nombre'=>'Valentina Orellana Abarca', 'foto'=>'secretaria.jpg',
   'bio'=>'Periodista de profesión, aporta sus conocimientos en comunicación, redacción y difusión de información al trabajo comunitario. Se caracteriza por su compromiso, responsabilidad y capacidad de organización, contribuyendo a mantener una comunicación clara y una gestión ordenada en beneficio de los vecinos.'],
  ['cargo'=>'Tesorera',    'nombre'=>'Verónica Proboste Oliva', 'foto'=>'tesorera.jpg',
   'bio'=>'Se caracteriza por su compromiso, responsabilidad y cercanía con los vecinos, aportando dedicación y entusiasmo al trabajo comunitario. Desde su rol como tesorera, promueve una gestión ordenada, transparente y responsable, contribuyendo al desarrollo de proyectos y al bienestar de las familias del sector.'],
  ['cargo'=>'Directora',   'nombre'=>'Dominga Oliva Martinez', 'foto'=>'directora.jpg',
   'bio'=>'Con una larga trayectoria como vecina del sector, conoce de cerca la historia, evolución y necesidades de la comunidad. Su experiencia y conocimiento del barrio son un aporte al trabajo de la directiva, desde donde busca fomentar la unión, la participación y el bienestar de todos los vecinos.'],
];

// ---- Tipos de noticia -------------------------------------------------
$TIPOS_NOTICIA = ['Noticias', 'Seguridad', 'Avisos Comunitarios', 'Asambleas y Reuniones'];

// ---- Tarifas (comprobantes) -------------------------------------------
define('PRECIO_CANCHA_HORA', 8000);
define('PRECIO_SEDE',        100000);   // valor final del arriendo
define('DEPOSITO_SEDE',      120000);   // monto a depositar (solo informativo)
define('CANCHA_HORA_INICIO', 19);       // desde esta hora (19:00) se arrienda
