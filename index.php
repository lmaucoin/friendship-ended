<!doctype html>

<html lang="en">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Friendship Ended Generator</title>
    <meta content="Your friendship has ended and I am so sorry." name="description">
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.5/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.5/css/bootstrap-theme.min.css">
</head>
<body>
    <div class="container">
      <div class="page-header">
        <h1>Friendship Ended Generator</h1>
        <p class="lead">Dedicated to the <a href="http://internet.gawker.com/facebook-user-asif-ends-friendship-with-mudasir-welc-1731160013" target="_blank">new friendship of ASIF and SALMAN</a>.</p>
      </div>
      <p><em>(All fields are required. Just like in <strong>friendship</strong>...)</em></p>
      <div class="row">
          <form class="form-horizontal" enctype="multipart/form-data" action="goodbyemyfriend.php" method="post">
            <div class="form-group col-sm-7">
              <label for="old-friend-name" class="control-label">BYE</label>
              <div class="">
                <input type="text" class="form-control" id="old-friend-name" name="old-friend-name" placeholder="Old Friend's Name" required>
              </div>
            </div>
            <div class="form-group col-sm-7">
              <label for="new-friend-name" class="control-label">HI</label>
              <div class="">
                <input type="text" class="form-control" id="new-friend-name" name="new-friend-name" placeholder="New Friend's Name" required>
              </div>
            </div>
            <div class="form-group col-sm-7">
                <label for="new-friend-pic">Attach a picture of your new friend</label>
                <input type="file" id="new-friend-pic" name="new-friend-pic" required>
                <p class="help-block">You can be in it but you don't have to be</p>
            </div>
            <div class="form-group col-sm-7">
                <label for="old-friend-1">Attach a picture of your old friend</label>
                <input type="file" id="old-friend-1" name="old-friend-1" required>
                <p class="help-block">Hopefully, an unflattering one.</p>
            </div>
            <div class="form-group col-sm-7">
                <label for="old-friend-2">Attach another picture of your old friend</label>
                <input type="file" id="old-friend-2" name="old-friend-2" required>
                <p class="help-block">They are no longer your friend.</p>
            </div>
            <div class="form-group">
              <div class="col-sm-offset-2 col-sm-10">
                <button type="submit" class="btn btn-default">FRIEND</button>
              </div>
            </div>
          </form>
      </div>
    </div>
    <script src="https://code.jquery.com/jquery-1.11.3.min.js"></script>
    <script src="https://ajax.aspnetcdn.com/ajax/jquery.validate/1.14.0/jquery.validate.min.js"></script>
    <script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.5/js/bootstrap.min.js"></script>
    <script>
    $("form").validate();
    </script>
</body>
</html>
