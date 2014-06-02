<?php

namespace Bundles\Formulaires;

use Bundles\Parametres\Conf;

use Bundles\Formulaires\Utils\Inputs;
use Bundles\Formulaires\Utils\Inspector;

use Bundles\Formulaires\Tpl\FormTpl;

use Bundles\Translate\Dico;




class Forms {
	public $dirForm = FORMS;

	public static $renderHTML = array();

	public $nameForm = 'form1';
	public $type;
	public $inputs;
	public $errors;

	public function __construct($nameForm=null, $type="POST", $dirForm=null) {
		if($nameForm) $this->nameForm = $nameForm;
		if($dirForm) $this->dirForm = ROOT.$dirForm;
		$this->type = $type;
		$this->loadForm();
	}

	public static function make($nameForm=null, $type="POST", $dirForm=null) {
		$form = new Forms($nameForm, $type, $dirForm);
		return $form;
	}

	public function loadForm(){
		$form = json_decode(file_get_contents($this->dirForm.$this->nameForm.'.json'), true);
		foreach ($form as $input) {
			$this->add($input['name'], $input['options']);
		}
	}


	/**
	 * ->add("nom_du_champs", array(
	 *     "label" => "nom_du_champs_different",
	 *     "type" => "text",
	 *     "id" => "id_du_champs",
	 *     "class" => "class_du_champs",
	 *     "attr" => array(
	 *         ["attr1","value1"], 
	 *         ["attr2","value2"]
	 *     ),
	 *     "errorMsg" => "Ce_message_peut_etre_personnalisé",
	 *     "constraints" => array(
	 *         "NotBlank" => true,
	 *         "Type" => "integer",
	 *         "LessThan" => 12
	 *         
	 *     )
	 * ));
	 * @param [str] $name    [description]
	 * @param [arr] $options [description]
	 */
	public function add($name,$options=array()) {
		$this->input[$name] = array("name" => (string)$name, "options" =>(array)$options);
	}

	public function changeOption($name, $option, $value) {
		$this->input[$name][$option] = $value;
	}



	public function render(){
		$inputsHTML = array();
		foreach ($this->input as $input) {
			$inputsHTML[$input['name']] = $this->constructBlock($input['name'], $input['options']);
			self::$renderHTML[$this->nameForm][$input['name']] = $inputsHTML[$input['name']];
		}
		
		return $inputsHTML;
	}


	public function isValid(){
		$result = true;
		foreach ($this->input as $input) {
			if(!$this->verifBlock($input['name'], $input['options'])) {
				//var_dump($input);
				$result = false;
			}
		}
		return $result;
		
	}


	private function constructBlock($name, $options) {

		$response['name'] = $name;
		
		// required
		$response['required'] = (isset($options['required']) && $options['required'] === TRUE) ? "<span class='required-form'> *</span>" : '' ;

		// Label option
		if(isset($options['label'])) {
			if($options['label']!==FALSE) {
				$response['label'] = (isset($options['translate']) && $options['translate'] === TRUE) ? Dico::trad($options['label']) : $options['label'] ;
			} 
		} else {
			$response['label'] = $name;
		}

		// Input
		$response['input'] = $this->constructInput($name, $options);

		// Error
		if(isset($this->errors[$name])) {
			$response['errors'] = (isset($options['errorMsg']))? $options['errorMsg'] : $this->errors[$name] ;
		}

		// Description
		if(isset($options['desc'])) {
			$response['description'] = $options['desc'];
		}


		//Tpl::$dirTwigTpl = '/Bundles/Formulaires/Tpl';
		return FormTpl::display($response, 'form.twig');




	}

	private function constructInput($name, $options) {
		if(empty($options)) $options['type'] = 'text'; 
		return Inputs::render($name, $options);
	}

	

	private function verifBlock($name, $options) {
		switch ($this->type) {
			case 'GET':
				$vars = $_GET;
				break;
			
			default:
				$vars = $_POST;
				break;
		}
		if(isset($vars[$name])) {
			if (isset($options['constraints'])) {
				$response = true;
				$vars = (($options['type'] == 'file')? $_FILES : $vars);
				$value = $vars[$name];
				foreach ($options['constraints'] as $type => $constraint) {
					if(!(isset($options['disabled']) && $options['disabled'] === true)) {
						if(!Inspector::checkData($value, $type, $constraint)) {
							if(isset($this->errors[$name])) {
								$this->errors[$name] .= Inspector::getMsg();
							} else {
								$this->errors[$name] = Inspector::getMsg();
							}
							$response = false;
						}
					}
					
					if(isset($options['value'])){
						$this->input[$name]['options']['value'] = (is_array($vars[$name])) ? $vars[$name] : array($vars[$name]);
					}
				}
				return $response;
			} else {
				return true;
			}
		} else {
			return false;
		}
		
	}

	






}



?>

