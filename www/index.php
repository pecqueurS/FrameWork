<?php

use Bundles\Bdd\Db;
use Bundles\Parametres\Conf;
use Bundles\FrontController\FrontController;


// Inclus l'autoloader
require_once ("../Bundles/autoloader/autoloader.php");


/* Paramètres */
$confFiles = array(
		"app" => "/config/app.json",
		"routing" => "/config/routing.json",
);
Conf::init($confFiles);

/* Constantes */
foreach (Conf::$constants as $key => $value) {
	define ($key, $value);
}

FrontController::launch();

//var_dump($_POST);
//var_dump($_FILES);
//var_dump($_SESSION);
//var_dump($_SERVER);
//var_dump(get_defined_vars()); 
//print_r(get_defined_constants()); 
?>