<?php
if (!defined( 'ABSPATH' )){
    exit;
}
spl_autoload_register(
    function ($className){
        $libName = 'Coinsnap';
        $wcName = 'Coinsnap\\WC';
        
        if(strpos($className, $libName) !== 0 && strpos($className, $wcName) !== 0) {
            return;
        }

        else {
            $filePath =  (strpos($className, $wcName) !== 0)? 
                    __DIR__ .'/'. str_replace([$libName, '\\'], ['', DIRECTORY_SEPARATOR], $className).'.php' :
                __DIR__ .'/../includes/'. str_replace([$wcName, '\\'], ['', DIRECTORY_SEPARATOR], $className).'.php' ;
                
            
            if(file_exists($filePath)) {
                require_once($filePath);
                return;
            }
        }
    }
);

