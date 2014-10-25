<?php

use Bundles\Bdd\Db;
use Bundles\Parametres\Conf;
use Bundles\FrontController\FrontController;


// Inclus l'autoloader
require_once ("../Bundles/autoloader/autoloader.php");

$confFiles = array(
		"app" => "WishList/Conf/app.json",
		"routing" => "WishList/Conf/Routing/routing.json",
);
Conf::init($confFiles);

FrontController::launch();

//var_dump($_POST);
//var_dump($_FILES);
//var_dump($_SESSION);
//var_dump($_SERVER);
//var_dump(get_defined_vars()); 
//print_r(get_defined_constants()); 
?>
