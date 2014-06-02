<?php

namespace Bundles\Translate;

use Bundles\Translate\bdd\DictionnaireModel;
use Bundles\Templates\Tpl;
/**
* 
*/
Abstract class Dico extends Tpl {

	private static $traduction;

	public static function init($type) {
		switch ($type) {
			case 'bdd':
				self::$traduction = DictionnaireModel::init()->getValues();
				break;
		}
		
	}
	
	public static function trad($name) {
		return self::$traduction[$name];
	}

	

}





	



?>