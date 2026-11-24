<?php
/* Pregunta de verificación (suma simple) – sin servicios externos */
require __DIR__.'/includes/bootstrap.php';
$a = random_int(2, 9); $b = random_int(2, 9);
$_SESSION['captcha'] = $a + $b;
header('Cache-Control: no-store');
jout(['q' => "¿Cuánto es $a + $b?"]);
