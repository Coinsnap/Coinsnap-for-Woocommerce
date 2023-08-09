<?php

//namespace Loader;

class CoinsnapLoaderInit {
    private static $loader;
    public static function loadClassLoader($class){
        if ('Loader\ClassLoader' === $class) {
            require __DIR__ . '/ClassLoader.php';
        }
    }

//  @return \Loader\ClassLoader
    public static function getLoader(){
        if (null !== self::$loader) { return self::$loader; }

        require __DIR__ . '/platform_check.php';
        spl_autoload_register(array('CoinsnapLoaderInit', 'loadClassLoader'), true, true);
        self::$loader = $loader = new Loader\ClassLoader(\dirname(__DIR__));
        spl_autoload_unregister(array('CoinsnapLoaderInit', 'loadClassLoader'));

        require __DIR__ . '/autoload_static.php';
        call_user_func(Loader\CoinsnapLoaderStaticInit::getInitializer($loader));
        $loader->register(true);

        return $loader;
    }
}