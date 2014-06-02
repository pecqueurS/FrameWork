<?php

require_once __DIR__.'/ClassLoader/UniversalClassLoader.php';

use Symfony\Component\ClassLoader\UniversalClassLoader;




$loader = new UniversalClassLoader();

/*$loader->registerNamespaces(array(
    'Doctrine\\Common'           => __DIR__.'/vendor/doctrine/common/lib',
    'Doctrine\\DBAL\\Migrations' => __DIR__.'/vendor/doctrine/migrations/lib',
    'Doctrine\\DBAL'             => __DIR__.'/vendor/doctrine/dbal/lib',
    'Doctrine'                   => __DIR__.'/vendor/doctrine/orm/lib',
));*/



$loader->registerNamespaces(array(
    'Bundles\\FrontController'  => dirname(dirname(__DIR__)),
    'Bundles\\Bdd'           	=> dirname(dirname(__DIR__)),
    'Bundles\\Parametres'       => dirname(dirname(__DIR__)),
    'Bundles\\Calculs'			=> dirname(dirname(__DIR__)),
    'Bundles\\Templates'		=> dirname(dirname(__DIR__)),
    'Bundles\\Formulaires'      => dirname(dirname(__DIR__)),
    'Bundles\\Translate'        => dirname(dirname(__DIR__)),
    
    //'Controllers'               => dirname(dirname(__DIR__)),
    'Models'                    => dirname(dirname(__DIR__)),

    'WOTG'                      => dirname(dirname(__DIR__)).DIRECTORY_SEPARATOR.'App',
    
       
));

/*$loader->registerPrefixes(array(
    "Zend_" => "/path/to/zend/library",
    "Twig_" => dirname(dirname(__DIR__)).DIRECTORY_SEPARATOR."Bundles".DIRECTORY_SEPARATOR."Templates".DIRECTORY_SEPARATOR."Twig-1.15.1".DIRECTORY_SEPARATOR."lib".DIRECTORY_SEPARATOR));*/

$loader->register();





?>