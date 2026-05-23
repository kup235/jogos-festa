<?php
// Serve ícone PWA como PNG gerado dinamicamente
// Se o PNG já existe em icons/, serve-o; senão gera um simples via GD
$size = isset($_GET['s']) ? (int)$_GET['s'] : 192;
if ($size !== 192 && $size !== 512) $size = 192;

$dir = __DIR__ . '/icons';
$file = "$dir/icon-$size.png";

// Se o PNG já foi gerado pelo Canvas, serve-o
if (file_exists($file)) {
    header('Content-Type: image/png');
    header('Cache-Control: public, max-age=604800');
    readfile($file);
    exit;
}

// Senão, gera um ícone simples via GD
if (!extension_loaded('gd')) {
    http_response_code(404);
    exit('GD not available');
}

$img = imagecreatetruecolor($size, $size);
imagesavealpha($img, true);

// Background escuro
$bg = imagecolorallocate($img, 18, 10, 42);
imagefilledrectangle($img, 0, 0, $size, $size, $bg);

// Circulo roxo central
$purple = imagecolorallocate($img, 124, 58, 237);
$cx = (int)($size * 0.5);
$cy = (int)($size * 0.42);
$cr = (int)($size * 0.26);
imagefilledellipse($img, $cx, $cy, $cr * 2, $cr * 2, $purple);

// Highlight no circulo
$light = imagecolorallocatealpha($img, 200, 180, 255, 80);
$hcy = (int)($size * 0.35);
$hr = (int)($size * 0.18);
imagefilledellipse($img, $cx, $hcy, $hr * 2, (int)($hr * 1.2), $light);

// Texto "JF" (Jogos de Festa) no centro
$white = imagecolorallocate($img, 255, 255, 255);
$gold = imagecolorallocate($img, 249, 212, 35);
$dim = imagecolorallocate($img, 180, 180, 200);

$fontSize = (int)($size * 0.18);
$fontPath = null;
// Tentar encontrar uma font no sistema
$fontCandidates = [
    'C:/Windows/Fonts/arialbd.ttf',
    'C:/Windows/Fonts/arial.ttf',
    'C:/Windows/Fonts/segoeui.ttf',
    '/usr/share/fonts/truetype/dejavu/DejaVuSans-Bold.ttf'
];
foreach ($fontCandidates as $f) {
    if (file_exists($f)) { $fontPath = $f; break; }
}

if ($fontPath) {
    // "JF" grande no circulo
    $bbox = imagettfbbox($fontSize, 0, $fontPath, 'JF');
    $tw = $bbox[2] - $bbox[0];
    $th = $bbox[1] - $bbox[7];
    $tx = $cx - $tw / 2;
    $ty = $cy + $th / 2 - (int)($size * 0.02);
    imagettftext($img, $fontSize, 0, (int)$tx, (int)$ty, $white, $fontPath, 'JF');

    // "JOGOS" em dourado
    $fs2 = (int)($size * 0.09);
    $bbox2 = imagettfbbox($fs2, 0, $fontPath, 'JOGOS');
    $tw2 = $bbox2[2] - $bbox2[0];
    $tx2 = $cx - $tw2 / 2;
    $ty2 = (int)($size * 0.76);
    imagettftext($img, $fs2, 0, (int)$tx2, $ty2, $gold, $fontPath, 'JOGOS');

    // "DE FESTA" pequeno
    $fs3 = (int)($size * 0.05);
    $bbox3 = imagettfbbox($fs3, 0, $fontPath, 'DE FESTA');
    $tw3 = $bbox3[2] - $bbox3[0];
    $tx3 = $cx - $tw3 / 2;
    $ty3 = (int)($size * 0.86);
    imagettftext($img, $fs3, 0, (int)$tx3, $ty3, $dim, $fontPath, 'DE FESTA');
} else {
    // Fallback sem TTF - texto simples
    $fsize = (int)($size / 5);
    imagestring($img, 5, $cx - 10, $cy - 8, 'JF', $white);
}

// Borda subtil
$border = imagecolorallocatealpha($img, 139, 92, 246, 100);
imagerectangle($img, 0, 0, $size - 1, $size - 1, $border);

// Guardar para cache futuro
if (!is_dir($dir)) mkdir($dir, 0777, true);
imagepng($img, $file);

// Servir
header('Content-Type: image/png');
header('Cache-Control: public, max-age=604800');
imagepng($img);
imagedestroy($img);
