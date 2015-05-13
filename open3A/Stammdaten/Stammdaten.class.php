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
class Stammdaten extends PersistentObject implements iCloneable {

	function  __construct($ID) {
		parent::__construct($ID);
		$this->customize();
	}
	
	public static function getVorlagen(){
		$FB = new FileBrowser();
		$FB->addDir("../open3A/Brief/");
		$FB->addDir(Util::getRootPath()."specifics/");
		if(FileStorage::getFilesDir() != Util::getRootPath()."specifics/")
			$FB->addDir(FileStorage::getFilesDir());
		return array_flip($FB->getAsLabeledArray("iVorlage",".class.php",true)); 
	}

	public static function getIconType($of){
		$types = array();
		$types["R"] = "rechnung";
		$types["G"] = "gutschrift";
		$types["L"] = "lieferschein";
		$types["M"] = "mahnung";
		$types["A"] = "angebot";
		$types["B"] = "bestaetigung";
		$types["Kalk"] = "kalkulation";
		$types["O"] = "bestellung";
		$types["P"] = "anfrage";
		$types["C"] = "verkauf";

		$ASicon = Aspect::joinPoint("belegIcon", __CLASS__, __METHOD__);
		if($ASicon != null) $types = array_merge($types, is_array($ASicon) ? $ASicon : array($ASicon));

		if(isset($types[$of]))
			return $types[$of];
	}
	
	public static function getPluralType($of){
		$types = array();
		$types["R"] = "Rechnungen";
		$types["G"] = "Gutschriften";
		$types["L"] = "Lieferscheine";
		$types["M"] = "Mahnungen";
		$types["A"] = "Angebote";
		$types["B"] = "Bestätigungen";
		$types["Kalk"] = "Kalkulationen";
		$types["O"] = "Bestellungen";
		$types["P"] = "Preisanfragen";
		$types["C"] = "Verkäufe";
		
		
		$AStype = Aspect::joinPoint("belegTypePlural", __CLASS__, __METHOD__);
		if($AStype != null) $types = array_merge($types, is_array($AStype) ? $AStype : array($AStype));
		

		if(isset($types[$of])) 
			return $types[$of];
		else {
			echo "Type '$of' unknown!";
			exit();
		}
	}

	public static function getGenusType($of){
		$types = array();
		$types["R"] = "f";
		$types["G"] = "f";
		$types["L"] = "m";
		$types["M"] = "f";
		$types["A"] = "n";
		$types["B"] = "f";
		$types["Kalk"] = "f";
		$types["O"] = "f";
		$types["P"] = "f";
		$types["C"] = "m";
		
		$AStype = Aspect::joinPoint("belegTypeGenus", __CLASS__, __METHOD__);
		if($AStype != null) $types = array_merge($types, is_array($AStype) ? $AStype : array($AStype));


		if(isset($types[$of])) 
			return $types[$of];
		else {
			echo "Type '$of' unknown!";
			exit();
		}
	}
	
	public static function getLongType($of, $withS = false){
		$types = array();
		$types["R"] = "Rechnung";
		$types["G"] = "Gutschrift";
		$types["L"] = "Lieferschein";
		$types["M"] = "Mahnung";
		$types["A"] = "Angebot";
		$types["B"] = "Bestätigung";
		$types["Kalk"] = "Kalkulation";
		$types["O"] = "Bestellung";
		$types["P"] = "Preisanfrage";
		$types["C"] = "Verkauf";
		
		$typesS = array();
		$typesS["R"] = "Rechnungs";
		$typesS["G"] = "Gutschrifts";
		$typesS["L"] = "Lieferschein";
		$typesS["M"] = "Mahnungs";
		$typesS["A"] = "Angebots";
		$typesS["B"] = "Bestätigungs";
		$typesS["Kalk"] = "Kalkulations";
		$typesS["O"] = "Bestellungs";
		$typesS["P"] = "Preisanfrage";
		$typesS["C"] = "Verkaufs";
		
		$AStype = Aspect::joinPoint("belegType", __CLASS__, __METHOD__);
		if($AStype != null) $types = array_merge($types, is_array($AStype) ? $AStype : array($AStype));

		$AStypeS = Aspect::joinPoint("belegTypeS", __CLASS__, __METHOD__);
		if($AStypeS != null) $typesS = array_merge($typesS, is_array($AStypeS) ? $AStypeS : array($AStypeS));

		if(isset($typesS[$of]) OR isset($types[$of])) 
			if($withS) return $typesS[$of];
			else return $types[$of];
		#elseif(isset($_SESSION["additionalTypes"][$of]) OR isset($_SESSION["additionalTypesWs"][$of]))
		#	if($withS) return $_SESSION["additionalTypesWs"][$of];
		#	else return $_SESSION["additionalTypes"][$of];
		else {
			return false;
			#echo "Type '$of' unknown!";
			#exit();
		}
	}

	public function parseContent($string, $type){
		if($this->A == null) $this->loadMe();
		
		while(ereg("%%\(([A-Za-z0-9?/&\_:| \-]*)\)%%",$string,$regs)){
			$replaceWith = "";
			
			$types = array("G","R","L","A");
			$replaces = array("S" => array("Firmenname" => $this->A->firmaLang));
			
			$s = explode("|",$regs[1]);
			$s = array_map("trim",$s);
			
			for($i=0;$i<count($s);$i++){
				$e = explode(":",$s[$i]);
				$e = array_map("trim",$e);
				
				if(in_array($e[0],$types)){
					if($type == $e[0]){
						$replaceWith = $e[1];
						break;
					}
				} elseif(isset($replaces[$e[0]])){
					if(!isset($replaces[$e[0]][$e[1]])) {
						echo "Error: Did not recognize $e[1]\n";
						continue;
					}
					
					$replaceWith = $replaces[$e[0]][$e[1]];
					break;
				} else {
					echo "Error: Did not recognize $e[0]\n";
				}
			}
			
			$string = str_replace($regs[0],$replaceWith,$string);
		}
		return $string;
	}
	
	public function getParsedEmailContent($type){
		return $this->parseContent($this->A->emailContent, $type);
	}

	public static function getActiveStammdaten(){
		$msd = new mStammdaten();
		$msd->addAssocV3("aktiv","=","1");
		
		return $msd->getNextEntry();
	}
	
	function getPrefix($of){
		$ps = array("G","R","L","B","M","K","A");
		$var = "prefix".$of;
		if(in_array($of,$ps)) return $this->A->$var;
		else return $of;
	}

	public function cloneMe() {
		$this->changeA("aktiv", "0");
		$id = $this->newMe();
		
		echo $id;
	}
	
	public function deleteMe() {
		if($this->A("aktiv"))
			Red::errorD ("Die aktiven Stammdaten können nicht gelöscht werden!");
		
		$this->changeA("isDeleted", "1");
		$this->saveMe();
	}
}
?>
