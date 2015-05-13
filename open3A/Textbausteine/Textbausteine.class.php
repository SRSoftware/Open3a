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
class Textbausteine extends anyC {
	private $TBKategorien = array();
	function __construct() {
		$this->collectionOf = "Textbaustein";
		
		$this->customize();
	}

	public static function getDefaultID($kategorie, $type){
		$T = new Textbausteine();
		list($ids, $names) = $T->getTBs($kategorie, $type, true);

		if(isset($ids[0]))
			return $ids[0];

		return 0;
	}

	public function getTBs($kategorie, $type, $standardOnly = false){
		if($type == "Kalk")
			return null;
		
		switch($kategorie){
			case "oben":
				$this->addAssocV3("KategorieID","=","2");
			break;
			case "unten":
				$this->addAssocV3("KategorieID","=","3");
			break;
			case "zahlungsbedingungen":
				$this->addAssocV3("KategorieID","=","1");
			break;
			
			case "mahnung1":
				$this->addAssocV3("KategorieID","=","31");
			break;
			case "mahnung2":
				$this->addAssocV3("KategorieID","=","32");
			break;
			case "mahnung3":
				$this->addAssocV3("KategorieID","=","33");
			break;
			
			case "emailMahnung1":
				$this->addAssocV3("KategorieID","=","131");
			break;
			case "emailMahnung2":
				$this->addAssocV3("KategorieID","=","132");
			break;
			case "emailMahnung3":
				$this->addAssocV3("KategorieID","=","133");
			break;
			
			case "emailBetreff": //No longer used as of 1.6 [06.11.2011] still required for automatic recovery
				$this->addAssocV3("KategorieID","=","41");
			break;
			case "emailText":
				$this->addAssocV3("KategorieID","=","42");
			break;
		
			case "emailZahlungErhalten":
				$this->addAssocV3("KategorieID","=","101");
			break;
		
			case "emailBestellungVerschickt":
				$this->addAssocV3("KategorieID","=","102");
			break;
		
			case "emailKundenzugang":
				$this->addAssocV3("KategorieID","=","201");
			break;
		}
		
		if($type != "" AND $standardOnly)
			$this->addAssocV3("is".$type{0}."Standard","=","1");
		
		if($type != "")
			$this->addOrderV3("is".$type{0}."Standard","DESC");
		else
			$this->addOrderV3("isKatDefault","DESC");
		$this->addOrderV3("label","ASC");
		
		if($standardOnly AND $type == "")
			$this->addAssocV3("isKatDefault", "=", "1");
		
		$keys = array();
		$values = array();
		
		while($e = $this->n()){
			$keys[] = $e->getID();
			$values[] = $e->A("label");
		}
		
		$this->A = null;
		
		return array($keys, $values);
	}

	public function addTBKategorie($name, $key){
		if(!isset($_SESSION["TBKategorien"]))
			$_SESSION["TBKategorien"] = array();
		
		$_SESSION["TBKategorien"][$key] = $name;
	}
	
	public function addTBVariables($key, $name){
		if(!isset($_SESSION["TBVariables"]))
			$_SESSION["TBVariables"] = array();
		
			$_SESSION["TBVariables"][$key] = $name;
	}
	
	public function addTBVariablesCondition($key, $name, $condition){
		if(!isset($_SESSION["TBVariablesConditions"]))
			$_SESSION["TBVariablesConditions"] = array();
		
		if(!isset($_SESSION["TBVariablesConditions"][$key]))
			$_SESSION["TBVariablesConditions"][$key] = array();
		
		$_SESSION["TBVariablesConditions"][$key][$name] = $condition;
	}
}
?>
