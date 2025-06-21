<?php
// Create a default placeholder image for About Us
$width = 400;
$height = 300;
$image = imagecreatetruecolor($width, $height);

// Colors
$background = imagecolorallocate($image, 240, 240, 240); // Light gray
$text_color = imagecolorallocate($image, 50, 50, 50); // Dark gray
$accent = imagecolorallocate($image, 0, 123, 255); // Blue accent

// Fill background
imagefilledrectangle($image, 0, 0, $width, $height, $background);

// Add border
imagerectangle($image, 0, 0, $width - 1, $height - 1, $accent);

// Add text
$text = "About Us";
$font_size = 5;
$text_width = imagefontwidth($font_size) * strlen($text);
$text_height = imagefontheight($font_size);
$x = ($width - $text_width) / 2;
$y = ($height - $text_height) / 2;

imagestring($image, $font_size, $x, $y, $text, $text_color);

// Save the image
imagepng($image, __DIR__ . '/default-about-us.png');
imagedestroy($image);

echo "Default About Us image created successfully!";
