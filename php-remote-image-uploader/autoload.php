<?php

function chipvn_imageuploader_autoload($class)
{
    if (strpos($class, 'ChipVN_ImageUploader') === 0) {
        require strtr($class, array(
            'ChipVN_ImageUploader' => dirname(__FILE__),
            '_'                    => DIRECTORY_SEPARATOR
        )).'.php';
    }
}
spl_autoload_register('chipvn_imageuploader_autoload');