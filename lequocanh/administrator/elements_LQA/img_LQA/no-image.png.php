<?php
// Create a simple "No Image" PNG file
header("Content-Type: image/png");

// Create a 200x200 image
$width = 200;
$height = 200;
$image = imagecreatetruecolor($width, $height);

// Colors
$bgColor = imagecolorallocate($image, 240, 240, 240);
$textColor = imagecolorallocate($image, 100, 100, 100);
$borderColor = imagecolorallocate($image, 200, 200, 200);

// Fill background
imagefilledrectangle($image, 0, 0, $width, $height, $bgColor);

// Draw border
imagerectangle($image, 0, 0, $width - 1, $height - 1, $borderColor);

// Add text
$text = "No Image";
$font = 5;
$textWidth = imagefontwidth($font) * strlen($text);
$textHeight = imagefontheight($font);
$x = ($width - $textWidth) / 2;
$y = ($height - $textHeight) / 2;

imagestring($image, $font, $x, $y, $text, $textColor);

// Output and free memory
imagepng($image);
imagedestroy($image);
