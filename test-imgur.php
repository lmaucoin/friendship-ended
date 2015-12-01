<?php
require __DIR__ . '/vendor/autoload.php';
$imgur_app_id = $_ENV['IMGUR_APP_ID'];
$imgur_endpoint = "https://api.imgur.com/3/image";
// $file_name_with_full_path = realpath('./' . $output_filename);
$post = array('image'=> '@'.('treasure.gif'));
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL,$imgur_endpoint);
curl_setopt($ch, CURLOPT_POST,1);
curl_setopt($ch, CURLOPT_POSTFIELDS, $post);
curl_setopt($ch, CURLOPT_HTTPHEADER, array('Authorization: Client-ID ' . $imgur_app_id));
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$result=curl_exec($ch);
$result_obj = json_decode($result);
// $remote_output = $result_obj->data;
curl_close ($ch);
print_r($result_obj);
?>
