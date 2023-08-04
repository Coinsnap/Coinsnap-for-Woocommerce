<?php

namespace Composer\Autoload;

class CoinsnapComposerStaticInit
{
    public static $prefixLengthsPsr4 = array (
        'C' => 
        array (
            'Coinsnap\\WC\\' => 12,
            'Coinsnap\\' => 9,
        ),
    );

    public static $prefixDirsPsr4 = array (
        'Coinsnap\\WC\\' => 
        array (
            0 => __DIR__ . '/../..' . '/src',
        ),
        'Coinsnap\\' => 
        array (
            0 => __DIR__ . '/..' . '/coinsnap/coinsnap-php/src',
        ),
    );

    public static $classMap = array (
        'Composer\\InstalledVersions' => __DIR__ . '/..' . '/composer/InstalledVersions.php',
    );

    public static function getInitializer(ClassLoader $loader)
    {
        return \Closure::bind(function () use ($loader) {
            $loader->prefixLengthsPsr4 = CoinsnapComposerStaticInit::$prefixLengthsPsr4;
            $loader->prefixDirsPsr4 = CoinsnapComposerStaticInit::$prefixDirsPsr4;
            $loader->classMap = CoinsnapComposerStaticInit::$classMap;

        }, null, ClassLoader::class);
    }
}
