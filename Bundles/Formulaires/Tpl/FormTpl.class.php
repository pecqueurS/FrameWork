<?php

namespace Bundles\Formulaires\Tpl;

use Bundles\Parametres\Conf;
use Bundles\Templates\ExtentionsTwig\FormExtTwig;

use Bundles\Templates\Tpl;

/**
* 
*/
class FormTpl extends Tpl {
	
	public static function display($vars = array(), $tpl = null) {
		$tplObj = new Tpl('/Bundles/Formulaires/Tpl');
		//$this->dirTwigTpl = '/Bundles/Formulaires/Tpl';
		return $tplObj->addVars($vars)->getTpl($tpl);
	}

	

}







?>