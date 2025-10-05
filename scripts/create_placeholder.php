<?php
// Create a simple placeholder image
$width = 400;
$height = 300;

// Create image
$image = imagecreate($width, $height);

// Define colors
$bg_color = imagecolorallocate($image, 243, 244, 246); // Light gray background
$rect_color = imagecolorallocate($image, 229, 231, 235); // Darker gray rectangle
$text_color = imagecolorallocate($image, 156, 163, 175); // Gray text

// Fill background
imagefill($image, 0, 0, $bg_color);

// Draw rectangle
imagefilledrectangle($image, 50, 50, $width - 50, $height - 50, $rect_color);

// Add text
$text = "Property Image";
$font_size = 5;
$text_width = imagefontwidth($font_size) * strlen($text);
$text_height = imagefontheight($font_size);
$x = ($width - $text_width) / 2;
$y = ($height - $text_height) / 2;

imagestring($image, $font_size, $x, $y, $text, $text_color);

// Save image
imagejpeg($image, 'assets/images/placeholder.jpg', 90);

// Clean up
imagedestroy($image);

echo "Placeholder image created successfully!";
