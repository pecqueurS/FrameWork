<?php

namespace Bundles\Formulaires\Utils;



/**
* 
*/
class Inputs {

	public function __construct ($name, $options) {
		
	}

	public static function render($name, $options) {
		$inputs = new Inputs($name, $options);

		$method = $options['type'].'HTML';

		return $inputs->$method($name, $options);

		
	}

	public function __call($method,$arguments) {
		echo 'Vous avez appelé la méthode ', $method, 'avec les arguments : ', implode(', ',$arguments);
	}



	private function input($name, $options, $import='', $after='') {
		$result = "<input type='{$options['type']}' name='$name' ";
		// Id (string)
		$result .= (isset($options['id'])) ? "id='{$options['id']}' " : "id='$name' ";
		// class (string)
		if(isset($options['class'])) $result .= "class='{$options['class']}' ";
		// placeholder (string)
		if(isset($options['placeholder'])) $result .= "placeholder='{$options['placeholder']}' ";
		//  maxlength (int)
		if(isset($options['maxlength'] )) $result .= "maxlength='{$options['maxlength']}' ";
		//  required (bol)
		if(isset($options['required']) && $options['required'] === true) $result .= "required ";
		//  disabled (bol)
		if(isset($options['disabled']) && $options['disabled'] === true) $result .= "disabled ";
		// readonly (bol)
		if(isset($options['readonly']) && $options['readonly'] === true) $result .= "readonly ";
		// attr (array)
		if(isset($options['attr'])) {
			foreach ($options['attr'] as $attr => $value) {
				$result .= "$attr='$value' ";
			}
		}

		// Options supplementaires
		$result .= $import;

		$result .= "value='";
		$response['preInput'] = $result;

		// Value si elle existe (array) ou (true) pour recuperer les valeurs 
		if(isset($options['value']) && is_array($options['value'])) {
			$response['valInput'] = $options['value'][0];
		} else {
			$response['valInput'] = '';
		}
		// Fin de balise
		$result = "'>\n";

		// Apres balise
		$result .= $after;

		$response['postInput'] = $result;

		return $response;
	}

	private function select($name, $options) {
		$arr = (isset($options['multiple']) && $options['multiple'] === true)? '[]' : '';
		$result = "<select name='$name$arr' ";
		// Id (string)
		$result .= (isset($options['id'])) ? "id='{$options['id']}' " : "id='$name' ";
		// class (string)
		if(isset($options['class'])) $result .= "class='{$options['class']}' ";
		// size (int)
		if(isset($options['size']) && $options['size'] === true) $result .= "size='{$options['size']}' ";
		//  required (bol)
		if(isset($options['required']) && $options['required'] === true) $result .= "required ";
		//  disabled (bol)
		if(isset($options['disabled']) && $options['disabled'] === true) $result .= "disabled ";
		// readonly (bol)
		if(isset($options['readonly']) && $options['readonly'] === true) $result .= "readonly ";
		// multiple (bol)
		if(isset($options['multiple']) && $options['multiple'] === true) $result .= "multiple ";
		// attr (array)
		if(isset($options['attr'])) {
			foreach ($options['attr'] as $attr => $value) {
				$result .= "$attr='$value' ";
			}
		}
		$result .= ">\n";
		if(isset($options['choices'])) {
			// choices (array)
			foreach ($options['choices'] as $value => $display) {
				$result .= '<option ';
				// selected (array)
				if(isset($options['selected']) && in_array($value, $options['selected'])) {
					$result .= 'selected ';
				}
				$result .= "value='$value'>$display</option>\n";
			}
		}
		$response['preInput'] = $result;
		$response['valInput'] = '';
		$response['postInput'] = "</select>\n";

		return $response;
		
	}

	private function radio($name, $options) {
		$result ='';
		// choices (array)
		if(isset($options['choices'])) {
			$result .= "<div id='$name-choice' class='choice-form' >\n";
			$i=0;
			foreach ($options['choices'] as $value => $display) {
				$result .= "<div>\n<input type='radio' name='$name' ";
				// Id (string)
				$result .= (isset($options['id'])) ? "id='{$options['id']}$i' " : "id='$name$i' ";
				// class (string)
				if(isset($options['class'])) $result .= "class='{$options['class']}' ";
				// selected (array)
				if(isset($options['selected']) && in_array($value, $options['selected'])) {
					$result .= 'checked ';
				}
				//  disabled (bol)
				if(isset($options['disabled']) && $options['disabled'] === true) $result .= "disabled ";
				// readonly (bol)
				if(isset($options['readonly']) && $options['readonly'] === true) $result .= "readonly ";
				// attr (array)
				if(isset($options['attr'])) {
					foreach ($options['attr'] as $attr => $value) {
						$result .= "$attr='$value' ";
					}
				}
				$result .= "> <span>$display</span>\n</div>\n";
		
				$i++;
			}
			$result .= "</div>\n";
			
		}
		$response['preInput'] = $result;
		$response['valInput'] = '';
		$response['postInput'] = '';
		return $response;
		
	}

	private function checkbox($name, $options) {
		$result ='';
		// choices (array)
		if(isset($options['choices'])) {
			$result .= "<div id='$name-choice' class='choice-form' >\n";
			$i=0;
			foreach ($options['choices'] as $value => $display) {
				$arr = (isset($options['multiple']) && $options['multiple'] === true)? '[]' : '';
				$result .= "<div>\n<input type='checkbox' name='$name$arr' ";
				// Id (string)
				$result .= (isset($options['id'])) ? "id='{$options['id']}$i' " : "id='$name$i' ";
				// class (string)
				if(isset($options['class'])) $result .= "class='{$options['class']}' ";
				// selected (array)
				if(isset($options['selected']) && in_array($value, $options['selected'])) {
					$result .= 'checked ';
				}
				//  disabled (bol)
				if(isset($options['disabled']) && $options['disabled'] === true) $result .= "disabled ";
				// readonly (bol)
				if(isset($options['readonly']) && $options['readonly'] === true) $result .= "readonly ";
				// attr (array)
				if(isset($options['attr'])) {
					foreach ($options['attr'] as $attr => $value) {
						$result .= "$attr='$value' ";
					}
				}
				$result .= "> <span>$display</span>\n</div>\n";
		
				$i++;
			}
			$result .= "</div>\n";
			
		}

		$response['preInput'] = $result;
		$response['valInput'] = '';
		$response['postInput'] = '';
		return $response;
		
	}

	private function textareaHTML($name, $options) {
		$result = "<{$options['type']} name='$name' ";
		// Id (string)
		$result .= (isset($options['id'])) ? "id='{$options['id']}' " : "id='$name' ";
		// class (string)
		if(isset($options['class'])) $result .= "class='{$options['class']}' ";
		// placeholder (string)
		if(isset($options['placeholder'])) $result .= "placeholder='{$options['placeholder']}' ";
		//  maxlength (int)
		if(isset($options['maxlength'] )) $result .= "maxlength='{$options['maxlength']}' ";
		//  pattern (string) expression reguliere
		if(isset($options['pattern'] )) $result .= "pattern=='{$options['pattern']}' ";
		//  required (bol)
		if(isset($options['required']) && $options['required'] === true) $result .= "required ";
		//  disabled (bol)
		if(isset($options['disabled']) && $options['disabled'] === true) $result .= "disabled ";
		// readonly (bol)
		if(isset($options['readonly']) && $options['readonly'] === true) $result .= "readonly ";
		// attr (array)
		if(isset($options['attr'])) {
			foreach ($options['attr'] as $attr => $value) {
				$result .= "$attr='$value' ";
			}
		}
		$result .= ">\n";
		$response['preInput'] = $result;
		// Value si elle existe (array) ou (true) pour recuperer les valeurs 
		$response['valInput'] = (isset($options['value']) && is_array($options['value'])) ? $options['value'][0] : '';
		
		$response['postInput'] = "</{$options['type']}>\n";

		return $response;
	}

	private function textHTML($name, $options) {
		return $this->input($name, $options);
	}

	private function emailHTML($name, $options) {
		return $this->input($name, $options);
	}

	private function numberHTML($name, $options) {
		$import = '';
		// step (float)
		if(isset($options['step'])) $import .= "step='{$options['step']}' ";
		// min (float)
		if(isset($options['min'])) $import .= "min='{$options['min']}' ";
		// max (float)
		if(isset($options['max'])) $import .= "max='{$options['max']}' ";

		return $this->input($name, $options, $import);
	}

	private function rangeHTML($name, $options) {
		$import = '';
		// step (float)
		if(isset($options['step'])) $import .= "step='{$options['step']}' ";
		// min (float)
		if(isset($options['min'])) $import .= "min='{$options['min']}' ";
		// max (float)
		if(isset($options['max'])) $import .= "max='{$options['max']}' ";

		return $this->input($name, $options, $import);
	}

	private function moneyHTML($name, $options) {
		$options['type'] = 'number';
		$import = '';
		$after = '';
		// step (float)
		if(isset($options['step'])) $import .= "step='{$options['step']}' ";
		// min (float)
		if(isset($options['min'])) $import .= "min='{$options['min']}' ";
		// max (float)
		if(isset($options['max'])) $import .= "max='{$options['max']}' ";

		// Devise (string)
		if(isset($options['devise'])) $after .= "<span class='devise-form'>{$options['devise']}</span>\n";

		return $this->input($name, $options, $import, $after);
	}

	private function passwordHTML($name, $options) {
		return $this->input($name, $options);
	}

	private function searchHTML($name, $options) {
		return $this->input($name, $options);
	}

	private function urlHTML($name, $options) {
		$result .= "<input type='{$options['type']}' name='$name' ";
		// Id (string)
		$result .= (isset($options['id'])) ? "id='{$options['id']}' " : "id='$name' ";
		// class (string)
		if(isset($options['class'])) $result .= "class='{$options['class']}' ";
		// placeholder (string)
		if(isset($options['placeholder'])) $result .= "placeholder='{$options['placeholder']}' ";
		//  maxlength (int)
		if(isset($options['maxlength'] )) $result .= "maxlength='{$options['maxlength']}' ";
		//  required (bol)
		if(isset($options['required']) && $options['required'] === true) $result .= "required ";
		//  disabled (bol)
		if(isset($options['disabled']) && $options['disabled'] === true) $result .= "disabled ";
		// readonly (bol)
		if(isset($options['readonly']) && $options['readonly'] === true) $result .= "readonly ";
		// attr (array)
		if(isset($options['attr'])) {
			foreach ($options['attr'] as $attr => $value) {
				$result .= "$attr='$value' ";
			}
		}
		// Value si elle existe (array) ou (true) pour recuperer les valeurs & protocol (string)
		$result .= "value='";

		$response['preInput'] = $result;
		// Value si elle existe (array) ou (true) pour recuperer les valeurs 
		$response['valInput'] = ((isset($options['protocol']) ? $options['protocol'] : "http://")).((isset($options['value']) && is_array($options['value'])) ? $options['value'][0] : '');
		
		$result = "' ";
		
		// Finde balise
		$result .= ">\n";

		
		$response['postInput'] = $result;

		return $response;

		
	}

	private function hiddenHTML($name, $options) {
		return $this->input($name, $options);
	}

	private function dateHTML($name, $options) {
		return $this->input($name, $options);
	}

	private function choiceHTML($name, $options) {
		// Choix entre un select, un checkbox ou un radio
		if(isset($options['list']) && $options['list'] === true) {
			$result = $this->select($name, $options);
		} else {
			$result = (isset($options['multiple']) && $options['multiple'] === FALSE) ? $this->radio($name, $options) : $this->checkbox($name, $options);
		}
		
		return $result;
	}

	private function fileHTML($name,$options) {
		$result = "<div id='$name-file' class='file-form' >\n";
		$import = '';
		// accept (string)
		if(isset($options['accept'])) $import .= "accept='{$options['accept']}' ";
		
		$result .= $this->input($name, $options, $import);
		// maxsize (int)
		$maxSize = (isset($options['maxsize'])) ? $options['maxsize'] : "8000000" ;
		$result .= "<input type='hidden' name='MAX_FILE_SIZE' value='$maxSize'>\n";
		
		$result .= "</div>\n";
		$response['preInput'] = $result;
		$response['valInput'] = '';
		$response['postInput'] = '';
		return $response;
	}

	


}







?>