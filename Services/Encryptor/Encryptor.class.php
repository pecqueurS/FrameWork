<?php

namespace Services\Encryptor;


/**
* 
*/
Abstract class Encryptor {
	
	
	/**
	 * ************** HASHING *******************
	 */	
	
	/**
	 * algo($mdp) algo
	 * Ex : 'jean-paul' -> '5ORJpIwFYJlCIBbBoB'
	 * @param STR $mdp
	 * @return STR
	 */
	public static function crypt($mdp){
		$arr1 = str_split($mdp);
		$arr2 = array();
		$count = count($arr1);
	
		$lettre = array();
		for ($i=65 ;$i<=90;$i++){
			$lettre[] = chr($i);
		}
		for ($i=48 ;$i<=57;$i++){
			$lettre[] = chr($i);
		}
		for ($i=97 ;$i<=122;$i++){
			$lettre[] = chr($i);
		}
	
		$code_int1 ='';
	
		for ($i=0;$i<$count;$i++){
			$arr1[$i] = ord ($arr1[$i]);
			$arr2[$i] = intval((pow ($i+10, 4)*($i+7))/$arr1[$i]);
			$arr2[$i] = str_pad($arr2[$i], 6, "001", STR_PAD_LEFT);
			$arr3[$i] = str_split($arr2[$i],3);
			$a = ((($arr3[$i][0])%61));
			$b = ((($arr3[$i][1])%61));
	
			$code_int1 .= $lettre[$a];
			$code_int1 .= $lettre[$b];
		}
		$code_int2 = strrev ($code_int1);
	
		return $code_int2;
	}
	
	/**
	 * code($mdp) code
	 * Ex : '5ORJpIwFYJlCIBbBoB' -> 'AKC5OEQORJzi4pIXNqwFszJYJb6alClPCIBFbobBWItoB'
	 * @param STR $mdp
	 * @return STR
	 */
	public static function code($mdp){
	
	
		$code_array = str_split($mdp,2);
		$count = count($code_array);
		$code_fini = '';
		for ($i=0;$i<$count;$i++){
			$random = substr(str_shuffle(str_repeat('ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789',3)),0,3);
			$code_fini .= $random.$code_array[$i];
		}
	
		return $code_fini;
	}
	
	/**
	 * decode ($mdp) decode
	 * Ex : 'AKC5OEQORJzi4pIXNqwFszJYJb6alClPCIBFbobBWItoB' -> '5ORJpIwFYJlCIBbBoB'
	 * @param STR $mdp
	 * @return STR
	 */
	public static function decode ($mdp){
		$code_array = str_split($mdp,5);
		$count = count($code_array);
		$code_fini = '';
		for ($i=0;$i<$count;$i++){
			$code_fini .= substr($code_array[$i], -2);
		}
		return $code_fini;
	}

	public static function newPwd () {
		$newPwd = substr(str_shuffle(str_repeat('0123456789',1)),0,1);
		$newPwd .= substr(str_shuffle(str_repeat('ABCDEFGHIJKLMNOPQRSTUVWXYZ',1)),0,1);
		$newPwd .= substr(str_shuffle(str_repeat('ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz',1)),0,8);
		$newPwd .= substr(str_shuffle(str_repeat('abcdefghijklmnopqrstuvwxyz',1)),0,1);
		$newPwd .= substr(str_shuffle(str_repeat('0123456789',1)),0,1);
		$codePwd = self::code (self::crypt($newPwd));

		return array(
			'newPwd' 	=> $newPwd,
			'encodePwd' => $codePwd
		);
	}
	
	
	
}




			
	
?>