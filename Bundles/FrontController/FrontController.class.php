<?php


namespace Bundles\FrontController;

use Bundles\Parametres\Conf;
use Bundles\Templates\Tpl;
use Bundles\Translate\Dico;

use WOTG\Models\PagesModel;
use WOTG\Models\DescPagesModel;

class FrontController {

	protected static $page;

	protected $response;

	protected $controller;

	public function __construct() {
		
		// Prepare la route
		$this->checkRoute();

		// Prepare les données communes à toutes les pages
		$this->prepare();

		// Lance le controleur de la page demandée
		$this->launchController();
			
		// Affiche la vue
		echo Tpl::display($this->response, "/App/".Conf::$appName."/Views/Twig_Tpl");
		

	}


	public static function launch() {
		$fc = new FrontController();
		return $fc;
	}



	private function checkRoute() {
		if(!empty(Conf::$route)) {
			self::$page = (isset(Conf::$route["loadUrl"])) ? substr(Conf::$route["loadUrl"],1) : substr(Conf::$route["url"],1) ;
		} else {
			$this->notFound();
		}
	}


	private function launchController() {
		$access = preg_replace('/::[a-zA-Z0-9\/_]+$/', '.class.php', Conf::$route["controller"]);
		$access = str_replace('\\', DS, $access);

		if(is_file(APP.$access)){
			$routeArr = explode("::", Conf::$route["controller"]);

			$nsArr = explode("/", $routeArr[0]);
			$class = $nsArr[count($nsArr)-1];

			$method = $routeArr[1];
			require APP.$access;
			$this->controller = new $class();
			$response = $this->controller->$method();
			
			$this->response = array_merge($this->response, $response);

			
		} else {
			$this->notFound();
		}
	}

	private function notFound() {
			// Page inexistante
			header("HTTP/1.1 404 Not Found");
			echo file_get_contents(URL_ERR); 
			exit();
	}

	private function prepare() {
		$this->response["session"] =& $_SESSION;
		$this->response["url"] = Conf::$links;
		$this->response["href"] = Conf::$server["href"].substr(Conf::$route["url"],1); 

		// Initialisation du Dictionnaire
		Dico::init('bdd');


		// Information sur les pages
		// $req1
		$pagesInfos = PagesModel::init()->getPagesInformations();
		$this->response['PAGES_NOJS'] = $pagesInfos->getValues("pag_name", array("pag_template"=>"styleAccueil")); 

		// Informations sur la page
		// $req2
		$pageHeadInfos = $pagesInfos->getValues(array("tra_nom","pag_name","pag_template"), array("pag_name"=>self::$page));
		$this->response['PAGE_HEAD_INFO']["traduction"] = $pageHeadInfos["tra_nom"];
		$this->response['PAGE_HEAD_INFO']["page"] = $pageHeadInfos["pag_name"];
		$this->response['PAGE_HEAD_INFO']["template"] = $pageHeadInfos["pag_template"];

		// Traduction des liens des pages
		// $req3
		$tradLiensPages = $pagesInfos->getValues(array("pag_name","tra_nom"));
		$this->response['LINKS'] = array();
		foreach ($tradLiensPages as $lien) {
			$this->response['LINKS'][$lien["pag_name"]] = $lien["tra_nom"];
		}

		

		// Tradutions
		// $req5
		$description = DescPagesModel::init()->getValues(array("tra_nom"), array("pag_name"=>self::$page));
		$this->response['DESC_PAGE'] = array();
		foreach ($description as $desc) {
		  	$this->response['DESC_PAGE'][] = $desc["tra_nom"];
		}
	}




/*









/*

// Appel templates
require_once (TEMPLATES."template.php");*/


















}


?>