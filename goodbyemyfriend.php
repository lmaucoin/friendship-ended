<?php

$errorz = [];
$filez = $_FILES;

if(!empty($_POST['old-friend-name']) && isset($_POST['old-friend-name'])) {
  $old_friend_name=trim(strtoupper($_POST['old-friend-name']));
} else {
  array_push($errorz, 'old-friend-name');
}
if(!empty($_POST['new-friend-name']) && isset($_POST['new-friend-name'])) {
  $new_friend_name=trim(strtoupper($_POST['new-friend-name']));
} else {
  array_push($errorz, 'new-friend-name');
}

$valid_filetypes = ['image/gif','image/jpeg','image/png'];
if(sizeof($filez) == 3) {
  foreach($filez as $key=>$value) {
    if(!in_array($value['type'],$valid_filetypes)) {
      array_push($errorz,"file-".$key);
    } else {
      if($value['error'] == 0) {
        $file_name = "tmp/" . $value['name'];
        $tmp = $value['tmp_name'];
        if(move_uploaded_file($tmp,$file_name)) {
          if($key=='new-friend-pic') {
            $new_friend_pic = $file_name;
          }
          elseif($key=='old-friend-1') {
            $old_friend_pic_1 = $file_name;
          }
          elseif($key=='old-friend-2') {
            $old_friend_pic_2 = $file_name;
          }
        }

      }
    }
  }
}

if(empty($errorz)) {
  include('process-image.php');
  unlink($new_friend_pic);
  unlink($old_friend_pic_1);
  unlink($old_friend_pic_2); 
//  unlink($output_filename);
?>
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Friendship Ended Generator</title>
    <meta content="Your friendship has ended and I am so sorry. Let us celebrate your new friendship." name="description">
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.5/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.5/css/bootstrap-theme.min.css">
</head>
      <body>
          <div class="container">
              <div class="page-header">
                <h1>Bless Your New Friendship With <em><?php echo $old_friend_name; ?></em></h1>
              </div>
              <h2>YOUR FORTUNE AWAITS HERE ............</h2>
	      <img src="http://friendship.leigh.cool/<?php echo $output_filename; ?>" />
              <a href="<?php echo $remote_output->img_view;?>" target="_blank"><img src="treasure.gif"></a>
              <h3><a href="<?php echo $remote_output->img_view;?>" target="_blank"><?php echo $remote_output->img_url; ?></a></h3>
              <br>
              <h4><em><a href="index.php">Do you have another new friend...........</a></em></h4>
          </div>
      </body>
  </html>
  <?php
} else {
    header('Location: index.php');
}

?>

