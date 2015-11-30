<?php
require __DIR__ . '/vendor/autoload.php';
ChipVN_ClassLoader_Loader::registerAutoLoad();
$uploader = ChipVN_ImageUploader_Manager::make('Imgur');
$uploader->setApi('959b5e4667b3d62');
$uploader->setSecret('9709065b895d1ab8d92d88c380a7a4315d0e6ed9');
echo $uploader->upload(getcwd(). '/treasure.gif');
?>
