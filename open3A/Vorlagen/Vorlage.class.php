<?php
/**
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
class Vorlage extends PersistentObject implements iCloneable {
	public function saveMe($checkUserData = true, $output = false) {
		$this->createVorlage();
		
		return parent::saveMe($checkUserData, $output);
	}

	public function createVorlage(){
		$dir = FileStorage::getFilesDir();#Util::getRootPath()."specifics/";
		$parameters = "";
		
		$parameters .= $this->getParameters("font");
		$parameters .= $this->getParameters("width");
		$parameters .= $this->getParameters("label");
		$parameters .= $this->getParameters("margin");
		$parameters .= $this->getParameters("sum");
		$parameters .= $this->getParameters("footer");
		$parameters .= $this->getParameters("logo");
		$parameters .= $this->getParameters("show");
		$parameters .= $this->getParameters("payment");
		$parameters .= $this->getParameters("align");
		$parameters .= $this->getParameters("background");
		$parameters .= $this->getParameters("append");
		$parameters .= $this->getParameters("color");
		$parameters .= $this->getOrder();
		
		if($this->A("VorlagePosition") != ""){
			$positions = json_decode($this->A("VorlagePosition"));
			foreach($positions AS $key => $value){
				if(strpos($value->name, "optional") === 0)
					continue;
				
				$index = substr($value->name, -1);
				$A = substr($value->name, 0, strlen($value->name) - 1);
				
				$parameters .= '
		$this->'.$A.'['.$index.'] = "'.$value->value.'";';
			}
			
			$optionals = $this->findOptionals($positions);
			foreach($optionals AS $field => $value){
				if($value == "0")
				$parameters .= '
		$this->'.$field.' = null;';
			}
		}
		
		$fonts = "";
		$newFonts = json_decode($this->A("VorlageNewFonts") == "" ? "[]" : $this->A("VorlageNewFonts"));
		foreach ($newFonts AS $F)
			$fonts .= '
		$this->AddFont("'.$F->name.'", "'.$F->type.'", "'.$F->file.'");';
		
		
		$rename = "";
		$stammdaten = json_decode($this->A("VorlageStammdaten") == "" ? "[]" : $this->A("VorlageStammdaten"));
		foreach ($stammdaten AS $S)
			$rename .= '
			$S->changeA("'.$S->name.'", "'.html_entity_decode($S->value, ENT_COMPAT, "UTF-8").'");';
		
		unlink($dir."Vorlage_VorlageID".$this->getID().".class.php");
		unlink($dir.$this->className().".class.php");
		
		if(file_exists($dir."Vorlage_VorlageID".$this->getID().".class.php") AND !is_writable($dir."Vorlage_VorlageID".$this->getID().".class.php"))
			Red::errorD("Die Datei ".$dir.$this->className().".class.php ist nicht beschreibbar!");
		
		file_put_contents($dir.$this->className().".class.php", '<?php
class '.$this->className().' extends Vorlage_any implements iVorlage {

	function __construct($S = null, $SpracheID = null){
		'.$fonts.'
		'.html_entity_decode($parameters, ENT_COMPAT, "UTF-8").'
		'.($rename != "" ? "if(\$S != null) { $rename
		}" : "").'
		
		parent::__construct($S, $SpracheID);
	}

	function getLabel(){
		return "'.$this->A("VorlageName").'";
	}

'.stripslashes(trim($this->A("VorlageCustomCode"))).'

}
?>');
		
	}
	
	protected function className($includeCloud = true){
		$cloudUser = null;
		if(Environment::$currentEnvironment){
			#return null;
			$cloudUser = strtolower(Environment::$currentEnvironment->cloudUser());
			if(!$includeCloud)
				$cloudUser = null;
		}
		
		return "Vorlage_VorlageID".$this->getID().($cloudUser ? "_{$cloudUser}" : "");
	}
	
	public function deleteMe() {
		$AC = anyC::get("Auftrag", "AuftragVorlage", $this->className());
		$AC->setLimitV3(11);
		$AC->lCV3();
		
		if($AC->numLoaded() > 10)
			Red::alertD ("Diese Vorlage kann nicht mehr gelöscht werden, das sie in mehr als 10 Aufträgen verwendet wird.");
		
		
		unlink(FileStorage::getFilesDir().$this->className().".class.php");
		return parent::deleteMe();
	}
	
	private function getOrder(){
		$parameters = "";
		if($this->A("VorlageOrder") != ""){
			$D = array();
			foreach(explode(";", trim($this->A("VorlageOrder"), ";")) AS $v){
				$ex = explode(":", $v);
				if(!$ex[1])
					continue;
				
				$D[$ex[0]] = $ex[1];
			}
			
			foreach($D AS $N => $A){
				$ex = explode(",", $A);
				
				$parameters .= '
		$this->'.$N.' = array(';
				
				foreach($ex AS $k => $E)
					$parameters .= '
			"'.$E.'"'.($k < count($ex) - 1 ? "," : "");
				
				$parameters .= '
		);';
			}
		}
		
		return $parameters;
	}
	
	private function getParameters($find){
		$parameters = "";
		if($this->A("Vorlage".ucfirst($find)) != ""){
			$widths = json_decode($this->A("Vorlage".ucfirst($find)));
			foreach($widths AS $key => $value){
				if(strpos($value->name, "optional") === 0)
					continue;
				
				if((strpos($value->name, "show") === 0 OR strpos($value->name, "sumShow") === 0 OR strpos($value->name, "footerShow") === 0) AND $value->name != "showDezimalstellen" AND $value->name != "showDezimalstellenMenge" AND $value->name != "showImagesOn")
					$parameters .= '
		$this->'.$value->name.' = '.($value->value == "1" ? "true" : "false").';';
				else {
					$val = '"'.$value->value.'"';
					if(is_numeric($value->value))
						$val = $value->value;
					
					
					#$val = preg_replace_callback('/\\\u(\w\w\w\w)/',
					#	function($matches) {
					#		return '&#'.hexdec($matches[1]).';';
					#	}
					#	, $val);

					#$changes = html_entity_decode($changes);
					#die($changes);
					
					
					if(strpos($value->name, "labelCustomField") === false)
						$parameters .= '
		$this->'.preg_replace("/([0-9])$/", "[\\1]", $value->name).' = '.$val.';';
					
					else
						$parameters .= '
		$this->'.$value->name.' = '.$val.';';
						
				}
			}
			
			$optionals = $this->findOptionals($widths);
			foreach($optionals AS $field => $value){
				if($value == "1")
					continue;
				
				#if(self::$instance->A($key) === null)
				#	continue;
				
				$parameters .= '
		$this->'.$field.' = null;';
			}
		}
		
		return $parameters;
	}
	
	private function findOptionals($data){
		$optionals = array();
		foreach($data AS $field){
			if(strpos($field->name, "optional") !== 0)
				continue;
			
			$optionals[substr($field->name, 8)] = $field->value;
		}
		
		return $optionals;
	}

	public function cloneMe() {
		$this->changeA("VorlageName", $this->A("VorlageName")." Kopie vom ".Util::CLDateParser(time()));
		$id = $this->newMe();
		
		$V = new Vorlage($id);
		$V->saveMe();
		
		echo $id;
	}
}
?>