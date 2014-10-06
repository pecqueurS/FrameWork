<?php

namespace Services\Pictures;


/**
* 
*/
class ConvertImg {

	private $name = '';
	private $size = [];
	private $background = [255,255,255];
	private $border = 0;

	private $image;

	public function __construct ($name, $size, $background = null, $border = 0) {
		$this->name = $name;
		$this->size = $size;
		if(!empty($background)) $this->background = $background;
		$this->border = $border;

		$this->createBase();
	}
	
	public static function init ($name, $size) {
		$img = new ConvertImg($name, $size);
		return $img;
	}

	private function createBase () {
		$this->image = imagecreatetruecolor($this->size[0], $this->size[1]); // Creation de l'image de sortie

		// Declaration couleur
		$blanc = imagecolorallocate($this->image, $this->background[0], $this->background[1], $this->background[2]);
		// Couleur de fond
		$fond = ImageFilledRectangle ($this->image,0, 0, imagesx($this->image),imagesy($this->image), $blanc);
	}


	/**
	 * Creation jpg d'apres image uploade
	 */
	public function convertJPG($inputName,$dossier_Dest){

		$chemin_img = $_FILES[$inputName]['tmp_name'];
		
		switch ( strtolower( pathinfo( $_FILES[$inputName]['name'], PATHINFO_EXTENSION ))) {
	        case 'jpeg':
	        case 'jpg':
	            $source =  imagecreatefromjpeg($chemin_img);
	        break;

	        case 'png':
	            $source =  imagecreatefrompng($chemin_img);
	        break;

	        case 'gif':
	            $source =  imagecreatefromgif($chemin_img);
	        break;
	    }		

		// On crée la miniature vide
		// Les fonctions imagesx et imagesy renvoient la largeur et la hauteur d'une image
		$largeur_source = imagesx($source);
		$hauteur_source = imagesy($source);

		$largeur_destination = imagesx($this->image)-$this->border;
		$hauteur_destination = imagesy($this->image)-$this->border;

		$marge_supp_left = 0;
		$marge_supp_top = 0;

		if($largeur_source<$hauteur_source) { // SI PORTRAIT
			$rapport = $hauteur_destination/$hauteur_source;
			$futur_largeur = $largeur_source*$rapport;
			$marge_supp_left = round(($largeur_destination-$futur_largeur)/2);
			$largeur_destination = $futur_largeur;
		} else { // SI PAYSAGE ou CARRE
			$rapport = $largeur_destination/$largeur_source;
			$futur_hauteur = $hauteur_source*$rapport;
			$marge_supp_top = round(($hauteur_destination-$futur_hauteur)/2);
			$hauteur_destination = $futur_hauteur;
		}

		// On crée la miniature
		imagecopyresampled($this->image, $source, (intval($this->border/2)+$marge_supp_left), (intval($this->border/2)+$marge_supp_top), 0, 0, $largeur_destination,$hauteur_destination, $largeur_source,$hauteur_source);

		// On enregistre la miniature sous le nom $name
		return (imagejpeg($this->image,$dossier_Dest.$this->name.'.jpg')) ? $this->name : false;

	}
}




			
	
?>