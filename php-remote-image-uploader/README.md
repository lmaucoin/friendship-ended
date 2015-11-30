# PHP-Remote-Image-Uploader
This project mainly help to remote upload images to some hosting services like Picasa, Imageshack, Imgur, Postimage, etc.

The library is free, but if you need an add-on for xenforo or web tools to upload images, please purchase PAID version under with $12.5, just like gift me a beer.

* Author:     Phan Thanh Cong <ptcong90@gmail.com>
* Copyright:  2010-2014 Phan Thanh Cong.
* License:    MIT
* Version:    5.2.17

### PAID version
* Demo: http://ptcong.com/imageuploader5
* PAID version that include user interface and more features, improved
* Purchase Upload tools: http://ptcong.com/?p=10
* Purchase XenForo add-on: http://ptcong.com/?p=23
* Purchase Wordpress plugin: http://ptcong.com/?p=1105
* After purchased, you will get emails for new version if the item have updated.
* Just like gift me a beer.


### Issues (for both paid and free version)
* `Picasa::doLogin: Error=BadAuthentication. Info=WebLoginRequired`.
    * To solve the issue, go to https://www.google.com/settings/security/lesssecureapps, signin with your account and change Access for less secure apps to Enabled.
    * Go to https://accounts.google.com/DisplayUnlockCaptcha, signin with your account and Enable to unlock captcha.

## Change Logs

#### Version 5.2.17; Jun 13, 2015
* Postimage fully fixes

#### Version 5.2.15; May 27, 2015
* No longer support Flickr login with accounts. Must use API
* Add new PicasaNew uploader that use API version 2 with OAuth 2.0

#### Version 5.2.14; Mar 07, 2015
* Fix Postimage not found image url in some cases.

#### Version 5.2.13; Jan 19, 2015
* Clean all plugins
* Optimize, rewrite a part of Picasa plugin and fixed a bug while checking permission; increase session expires time to 900 seconds.
* Use new version of ChipVN_Http_Client to get higher performance when upload large file.

#### Version 5.2.12; Oct 14, 2014
* Update: Flickr `requestToken` method to avoid error if headers has sent.

##### Version 5.2.8; Oct 06, 2014
* Update: Imgur plugin to use API version 3 (require API Client ID, Secret)

##### Version 5.2.3: Jul 10, 2014
* Update Flickr API (SSL required)
* Update vendor, ChipVN library
* Update Picasa plugin to get URL not resized, use account by custom email
* New Postimage plugin

##### Version 5.0.1: Apr 2, 2014
* Change class name from `\ChipVN\ImageUploader\ImageUploader` to `ChipVN_ImageUploader_ImageUploader` for usable on all PHP version >= 5.0

##### Version 5.0: Mar 07, 2014
* Supports composer
* Change factory method from `\ChipVN\Image_Uploader::factory` to `ChipVN_ImageUploader_ImageUploader::make`
* Make it simpler (only 5 php files)
* Remove ~~upload to local server~~
* Update Imageshack plugin

##### Version 4.0.1: Sep, 2013
* Fix Imgur auth

##### Version 4.0: Jul 25, 2013
* ~~Require PHP 5.3 or newer~~
* Rewrite all plugins to clear

##### Version 1.0: June 17, 2010
* Upload image to Imageshack, Picasa

## Features
* ~~Upload image to local server~~
* Upload image to remote service like (picasa, imageshack, imgur)
* Remote: can free upload to imgur, imageshack or upload to your account. Picasa must be login to upload
* Easy to make new plugin for uploading to another service

## Usage
###### If you use composer
Add require `"ptcong/php-image-uploader": "dev-master"` to _composer.json_ and run `composer update`, if you catch an issue about stability, should add `"minimum-stability" : "dev"` to your `composer.json`

###### If you don't use composer
Download
- https://github.com/ptcong/php-chipvn-classloader and put it to `ChipVN/ClassLoader` folder
- https://github.com/ptcong/php-http-class (2.x) and put it to `ChipVN/Http` folder
- https://github.com/ptcong/php-cache-manager and put it to `ChipVN/Cache` folder

and include the code on the top of your file:

    include '/path/path/ChipVN/ClassLoader/Loader.php';
    ChipVN_ClassLoader_Loader::registerAutoLoad();

then
### Upload to Picasa - ver 1
To upload image to Picasa, you need to have some AlbumIds otherwise the image will be uploaded to _default_ album.
To create new AlbumId faster, you may use echo `$uploader->addAlbum('testing 1');`

```php
$uploader = ChipVN_ImageUploader_Manager::make('Picasa');
$uploader->login('your account', 'your password');
// you can set upload to an albumId by array of albums or an album, system will get a random album to upload
//$uploader->setAlbumId(array('51652569125195125', '515124156195725'));
//$uploader->setAlbumId('51652569125195125');
echo $uploader->upload(getcwd(). '/test.jpg');
// this plugin does not support transload image
```

### Upload to Picasanew - ver 2 (use OAuth 2.0)
To upload image to Picasanew, you need to have some AlbumIds otherwise the image will be uploaded to _default_ album.
To create new AlbumId faster, you may use echo `$uploader->addAlbum('testing 1');`

```php
$uploader = ChipVN_ImageUploader_Manager::make('Picasanew');
$uploader->login('your user name', ''); // we don't need password here
$uploader->setApi('Client ID'); // register in console.developers.google.com
$uploader->setSecret('Client secret');
// you can set upload to an albumId by array of albums or an album, system will get a random album to upload
//$uploader->setAlbumId(array('51652569125195125', '515124156195725'));
//$uploader->setAlbumId('51652569125195125');
if (!$uploader->hasValidToken()) {
    $uploader->getOAuthToken('http://yourdomain.com/test.php');
}
echo $uploader->upload(getcwd(). '/test.jpg');
// this plugin does not support transload image
```

### Upload to Flickr
To upload image to Picasa, you need to have some AlbumIds otherwise the image will be uploaded to _default_ album.
To create new AlbumId faster, you may use echo `$uploader->addAlbum('testing 1');`
```php
$uploader = ChipVN_ImageUploader_Manager::make('Flickr');
$uploader->setApi('API key');
$uploader->setSecret('API secret');
$token = $uploader->getOAuthToken('http://yourdomain.com/test.php');
$uploader->setAccessToken($token['oauth_token'], $token['oauth_token_secret']);
echo $uploader->upload(getcwd(). '/test.jpg');
// this plugin does not support transload image
```

### Upload to Imageshack
```php
$uploader = ChipVN_ImageUploader_Manager::make('Imageshack');
$uploader->login('your account', 'your password');
$uploader->setApi('your api here');
echo $uploader->upload(getcwd(). '/a.jpg');
echo $uploader->transload('http://img33.imageshack.us/img33/6840/wz7u.jpg');
```

### Upload to Imgur
```php
$uploader = ChipVN_ImageUploader_Manager::make('Imgur');
$uploader->setApi('your client id');
$uploader->setSecret('your client secret');
// you may upload with anonymous account but may be the image will be deleted after a period of time
// $uploader->login('your account here', 'your password here');
echo $uploader->upload(getcwd(). '/a.jpg');
echo $uploader->transload('http://img33.imageshack.us/img33/6840/wz7u.jpg');
```

### Upload to Postimage
```php
$uploader = ChipVN_ImageUploader_Manager::make('Postimage');
// you may upload with anonymous account but may be the image will be deleted after a period of time
// $uploader->login('your account here', 'your password here');
echo $uploader->upload(getcwd(). '/a.jpg');
echo $uploader->transload('http://img33.imageshack.us/img33/6840/wz7u.jpg');
```

