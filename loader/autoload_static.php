<?php
namespace Loader;
class CoinsnapLoaderStaticInit{
    public static $prefixLengthsPsr4 = array (
        'C' => array ('Coinsnap\\WC\\' => 12,'Coinsnap\\' => 9,),
    );

    public static $prefixDirsPsr4 = array (
        'Coinsnap\\WC\\' => array (0 => __DIR__ . '/../src',),
        'Coinsnap\\' => array (0 => __DIR__ . '/../release',),
    );

    public static function getInitializer(ClassLoader $loader)
    {
        return \Closure::bind(function () use ($loader) {
            $loader->prefixLengthsPsr4 = CoinsnapLoaderStaticInit::$prefixLengthsPsr4;
            $loader->prefixDirsPsr4 = CoinsnapLoaderStaticInit::$prefixDirsPsr4;
            //$loader->classMap = CoinsnapLoaderStaticInit::$classMap;

        }, null, ClassLoader::class);
    }
}
