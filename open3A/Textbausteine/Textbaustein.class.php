<?php
/*
 *  This file is part of open3A.

 *  open3A is free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 3 of the License, or
 *  (at your option) any later version.

 *  open3A is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.

 *  You should have received a copy of the GNU General Public License
 *  along with this program.  If not, see <http://www.gnu.org/licenses/>.
 * 
 *  2007 - 2015, Rainer Furtmeier - Rainer@Furtmeier.IT
 */
class Textbaustein extends PersistentObject implements iCloneable, iNewWithValues, iDeletable {

	function  __construct($ID) {
		parent::__construct($ID);
		$this->customize();
	}

	function getA(){
		if($this->A == null) $this->loadMe();
		return $this->A;
	}

	public function cloneMe(){
		echo $this->newMe();
	}
	
	public static function getTextOf($ID){
		if($ID == 0) return "";
		
		$T = new Textbaustein($ID);
		if($T->getA() != null) return $T->A("text");
		
		return "";
	}
	
	public static function getFakeTextbaustein($text){
		$T = new TextbausteinGUI(-1);
		$TA = $T->newAttributes();
		$TA->text = $text != "0" ? $text : "";
		$T->setA($TA);
		
		return $T;
	}

	public function fixVariables(){
		if(mb_substr($this->A("text"), 0, 3) !== "<p " AND mb_substr($this->A("text"), 0, 3) !== "<p>")
			return;
		
		$text = $this->A("text");
		preg_match_all("/\{([a-zA-Z0-9]*)([<]+)(.*)\}/ismU", $text, $matches);
		
		if(count($matches[0]) == 0)
			return;

		$move = array("i", "b", "u");
		
		$replaceMatch = array();
		foreach($matches[0] AS $k => $match){

			preg_match_all("/<(\/[".implode("", $move)."])>/", $match, $SM);
			if(count($SM) > 0)
				foreach($SM[0] AS $sub)
					$match = str_replace($sub, "", $match).$sub;


			preg_match_all("/<([".implode("", $move)."])>/", $match, $SM);
			if(count($SM) > 0)
				foreach($SM[0] AS $sub)
					$match = $sub.str_replace($sub, "", $match);


			$replaceMatch[$k] = $match;
		}

		foreach($replaceMatch AS $k => $RM)
			$text = str_replace ($matches[0][$k], $RM, $text);
	
	}
	
	public function newMe($checkUserData = true, $output = false) {
		$this->fixVariables();
		$this->checkUnique();
		
		return parent::newMe($checkUserData, $output);
	}

	private function checkUnique(){
		if(!Session::isPluginLoaded("Auftraege"))
			return;
		
		$TS = new anyC();
		$TS->setCollectionOf("Textbaustein");
		$TS->addAssocV3("KategorieID", "=", $this->A("KategorieID"), "AND", "1");
		$TS->addAssocV3("TextbausteinID", "!=", $this->getID(), "AND", "1");

		$Bs = Auftrag::getBelegArten();
		$Bs[] = "M";
		$or = false;
		foreach($Bs As $B)
			if($this->A("is{$B}Standard") == "1") {
				$TS->addAssocV3("is{$B}Standard", "=", "1", $or ? "OR" : "AND", "2");
				$or = true;
			}


		if($or) $TS->lCV3();
		if($or AND $TS->numLoaded() > 0)
			Red::errorD("Es kann nur ein Textbaustein pro Textbausteinkategorie und Belegart als Standard aktiviert sein. Bitte überprüfen Sie die Anzeige auf der rechten Seite.");
	}

	public function saveMe($checkUserData = true, $output = false) {
		$this->fixVariables();
		$this->checkUnique();
		
		parent::saveMe($checkUserData, $output);
	}
	
	public static function types(){
		$options = array();
		foreach($_SESSION["TBKategorien"] AS $k => $v)
			$options[$k] = $v;

		while($R = Registry::callNext("Textbausteine"))
			$options[$R[0]] = $R[1];
			
		return $options;
	}
	
}
?>
