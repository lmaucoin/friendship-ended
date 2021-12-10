<?php // CREATE IMAGE

require 'classes/TextImage.php';

$topText = new TextImage([
  'text' => "Friendship ended with {$old_friend_name}",
  'fontSize' => 58,
  'position' => [0,40],
  'gradient' => '#CF4E09-#00B92C',
  'scale' => [0.8, 2]
]);

$nowText = new TextImage([
  'text' => "Now",
  'position' => [300,70],
  'color' => '#DF0676'
]);

$newFriendText = new TextImage([
  'text' => $new_friend_name,
  'position' => [300, 110],
  'color' => '#AB5955'
]);

$isMyText = new TextImage([
  'text' => "is my",
  'position' => [300, 150],
  'color' => '#7C9535'
]);

$bestFriendText = new TextImage([
  'text' => "best friend",
  'position' => [300, 185],
  'color' => '#4CBF1F'
]);

$newFriendPic = new Imagick($new_friend_pic); // swap w/ image from post
$newFriendPic->resizeImage(800,600,Imagick::FILTER_LANCZOS,1);

$oldFriendPic1 = new Imagick($old_friend_pic_1); // swap w/ image from post
$oldFriendPic1->resizeImage(172,259,Imagick::FILTER_LANCZOS,1);
$x1 = new Imagick('x1.png');
$oldFriendPic1->compositeImage($x1,\Imagick::COMPOSITE_ATOP, 0, 0);

$oldFriendPic2 = new Imagick($old_friend_pic_2); // swap w/ image from post
$oldFriendPic2->resizeImage(221,242,Imagick::FILTER_LANCZOS,1);
$x2 = new Imagick('x2.png');
$oldFriendPic2->compositeImage($x2,\Imagick::COMPOSITE_ATOP, 0, 0);

$texts = [
  $topText,
  $nowText,
  $newFriendText,
  $isMyText,
  $bestFriendText
];

foreach( $texts as $text ) { $newFriendPic->drawImage($text); }

$newFriendPic->compositeImage($oldFriendPic1,\Imagick::COMPOSITE_ATOP, 0, 341);
$newFriendPic->compositeImage($oldFriendPic2,\Imagick::COMPOSITE_ATOP, 579, 358);

$newFriendPic->setImageFormat('jpg');
$output_time = time();
$output_filename = 'tmp/'.time().".jpg";

$newFriendPic->writeImage($output_filename);

?>
