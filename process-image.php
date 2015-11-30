<?php // CREATE IMAGE
/* Create a new imagick object */
$im = new Imagick();
$im->newPseudoImage(50, 120, "gradient:#CF4E09-#00B92C");

// Text 1
$draw = new ImagickDraw();
$draw->pushPattern('gradient', 0, 0, 50, 120);
$draw->composite(Imagick::COMPOSITE_OVER, 0, 0, 50, 120, $im);
$draw->popPattern();
$draw->setFillPatternURL('#gradient');

$draw->setFontSize(58);
$draw->setFontWeight(800);
$draw->setStrokeColor('#006488');
$draw->setStrokeWidth(1);
$draw->scale(0.8,2);
$draw->setStrokeAntialias(true);
$draw->annotation(0, 40, "Friendship ended with " . $old_friend_name); // Swap SODACAT with variable from post

// Text 2
$draw2 = new ImagickDraw();
$draw2->setFontSize(38);
$draw2->setFillColor('#DF0676');
$draw2->setFontWeight(800);
$draw2->setStrokeColor('#006488');
$draw2->setStrokeWidth(1);
$draw2->setStrokeAntialias(true);
$draw2->scale(1,2);
$draw2->annotation(300, 70, "Now");

// Text 5
$draw3 = new ImagickDraw();
$draw3->setFontSize(38);
$draw3->setStrokeAntialias(true);
$draw3->setFillColor('#AB5955');
$draw3->setFontWeight(800);
$draw3->setStrokeColor('#006488');
$draw3->setStrokeWidth(1);
$draw3->scale(1,2);
$draw3->annotation(300, 110, $new_friend_name); // Swap KIMMY with variable from post

// Text 4
$draw4 = new ImagickDraw();
$draw4->setFontSize(38);
$draw4->setStrokeAntialias(true);
$draw4->setFillColor('#7C9535');
$draw4->setFontWeight(800);
$draw4->setStrokeColor('#006488');
$draw4->setStrokeWidth(1);
$draw4->scale(1,2);
$draw4->annotation(300, 150, "is my");

$draw5 = new ImagickDraw();
$draw5->setFontSize(38);
$draw5->setStrokeAntialias(true);
$draw5->setFillColor('#4CBF1F');
$draw5->setFontWeight(800);
$draw5->setStrokeColor('#006488');
$draw5->setStrokeWidth(1);
$draw5->scale(1,2);
$draw5->annotation(300, 185, "best friend");

$canvas = new Imagick($new_friend_pic); // swap w/ image from post
$canvas->resizeImage(800,600,Imagick::FILTER_LANCZOS,1);
$soda1 = new Imagick($old_friend_pic_1); // swap w/ image from post
$soda1->resizeImage(172,259,Imagick::FILTER_LANCZOS,1);
$x1 = new Imagick('x1.png'); // swap w/ image from post
$soda1->compositeImage($x1,\Imagick::COMPOSITE_ATOP, 0, 0);
$soda2 = new Imagick($old_friend_pic_2); // swap w/ image from post
$soda2->resizeImage(221,242,Imagick::FILTER_LANCZOS,1);

$x2 = new Imagick('x2.png');
$soda2->compositeImage($x2,\Imagick::COMPOSITE_ATOP, 0, 0);

$canvas->drawImage($draw);
$canvas->drawImage($draw2);
$canvas->drawImage($draw3);
$canvas->drawImage($draw4);
$canvas->drawImage($draw5);
$canvas->compositeImage($soda1,\Imagick::COMPOSITE_ATOP, 0, 341);
$canvas->compositeImage($soda2,\Imagick::COMPOSITE_ATOP, 579, 358);

$canvas->setImageFormat('jpg');
$output_time = time();
$output_filename = 'tmp/'.time().".jpg";
$canvas->writeImage($output_filename);

/* 
/* Here's like where you would post the image elsewhere. 
/* http://uploads.im/apidocs is what I'm using but maybe you want something else
*/

$target_url = "http://uploads.im/api";
// $file_name_with_full_path = realpath('./' . $output_filename);
$post = array('upload'=>'http://friendship.leigh.cool/' . $output_filename,'format'=>'json');
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL,$target_url);
curl_setopt($ch, CURLOPT_POST,1);
curl_setopt($ch, CURLOPT_POSTFIELDS, $post);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$result=curl_exec($ch);
$result_obj = json_decode($result);
$remote_output = $result_obj->data;
curl_close ($ch);

?>
