<?php
$imgur_app_id = $_ENV['IMGUR_APP_ID']; // Register for Imgur API here: http://api.imgur.com/oauth2/addclient
$imgur_endpoint = "https://api.imgur.com/3/image"; // Img upload endpoint
if($_SERVER['HTTP_HOST'] == 'localhost') { // Upload the file directly from your machine if local
  $file_name_with_full_path = realpath('./' . $output_filename);
  $post = array('image'=> '@' . $file_name_with_full_path, 'type' => 'file');
} else { // It's gonna at least temporarily exist on the webserver. This is probably a bad idea.
  if($_SERVER['HTTPS']) {$protocol = "https";}
  else {$protocol = "http";} // Whatever
  $image_url = $protocol . "://" . $_SERVER['HTTP_HOST'] . "/" . $output_filename; // Temporary live URL here
  $post = array('image'=> $image_url, 'type' => 'URL');
}
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL,$imgur_endpoint);
curl_setopt($ch, CURLOPT_POST,1);
curl_setopt($ch, CURLOPT_POSTFIELDS, $post);
curl_setopt($ch, CURLOPT_HTTPHEADER, array('Authorization: Client-ID ' . $imgur_app_id));
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$result=curl_exec($ch);
curl_close ($ch);
$result_obj = json_decode($result);
if($result_obj->status == "200") {
  $remote_output = $result_obj->data;
  $direct_url = $remote_output->link; // Direct link to file
  $share_url = "http://imgur.com/" . $remote_output->id; // "Share" link to file
} else {
      header('Location: index.php');
}
?>
