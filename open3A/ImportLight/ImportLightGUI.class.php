<?php
/**
 *  This file is part of phynx.

 *  phynx is free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 3 of the License, or
 *  (at your option) any later version.

 *  phynx is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.

 *  You should have received a copy of the GNU General Public License
 *  along with this program.  If not, see <http://www.gnu.org/licenses/>.
 * 
 *  2007, 2008, 2009, 2010, Rainer Furtmeier - Rainer@Furtmeier.de
 */
class ImportLightGUI extends ImportLight implements iGUIHTML2 {
	
	function  __construct($ID) {
		parent::__construct($ID);

	}
	
	function getHTML($id){
		$this->loadMeOrEmpty();
		$field="Dateiname";

		$file = $this->A($field);
		if ($file != null && !empty($file)){
			$F = new HTMLForm("Dateiupload", array($field),"importierte Datei");
			$F->setValue($field, $file);
			$F->setType($field, 'readonly');
		} else {
			$F = new HTMLForm("Dateiupload", array($field,"upload"),"neue Datei importieren");
			$F->setType($field, "hidden");
			$F->setValue($field, "");
			$F->setType("upload", "file");
			$F->addJSEvent("upload", "onChange", "\$j('#Dateiupload input[name=$field]').val(fileName);");
			$F->setSaveJSON("Import starten", "", "ImportLight", $this->getID(), "processImport", OnEvent::reload("Right").OnEvent::clear("Left"));
		}
	
		echo $F;
	}
	
	public function processImport($data){
		$data=json_decode($data);
		$obj=reset($data);
		if ($obj->name == "Dateiname"){
			$fileName=$obj->value;
			if ($fileName == ''){
				Red::alertD("Es wurde keine Datei hochgeladen!");
			}
			$tempDir = Util::getTempDir();
			$tempFile = $tempDir.$fileName.".tmp";
			if (file_exists($tempFile)){
				$this->importFrom($tempFile);
				copy($tempFile,FileStorage::getFilesDir().$fileName);
				unlink($tempFile);
				$this->changeA($obj->name, $obj->value);
				$this->newMe(true,true);
			} else {
				Red::alertD("Datei wurde nicht korrekt hochgeladen!");
			}
		} else {
			Red::alertD("Es wurden ungültige Daten übermittelt!");
		}
	}

	public static function getSeparator($string){
		$count=count_chars(trim($string),0);
		$semicolon=$count[ord(';')];
		$comma=$count[ord(',')];
		if ($semicolon > $comma){
			return ';';
		}
		if ($comma < 1){
			Red::alertD("Dies schein keine Datei mit komma-separierten Daten zu sein!");
		}
		return ',';
	}
	
	public function removeEnclosing(&$value, $key){
		if (substr($value, 0,1) == '"' && substr($value, -1) == '"'){
			$value = substr($value, 1,-1);
		} elseif (substr($value, 0,1) == '\'' && substr($value, -1) == '\''){
			$value = substr($value, 1,-1);
		}
	}
	
	public function explode($delimiter,$line){
		$arr=explode($delimiter, trim($line));
		array_walk($arr, array($this,'removeEnclosing'));
		return $arr;
		
	}
	
	public function getType($data){
		$data=strtolower($data);
		if (strpos($data, 'kundennummer') !== false) return 'adresse';
		if (strpos($data, 'artikelnummer')!== false) return 'artikel';
		Red::alertD('Konnte nicht entscheiden, ob diese CSV-Datei Adressen oder Artikel enthält!');
	}
	
	public function importFrom($file){
		$data = file($file);
		$separator=$this->getSeparator(reset($data));
		$type=$this->getType(reset($data));
		$keys=null;
		foreach ($data as $line){
			if ($keys == null){
				$keys=$this->explode($separator, $line);								
			} else {
				$values=$this->explode($separator, $line);
				if ($type == 'adresse'){
					$object=new Adresse(-1);
				}
				foreach ($values as $key => $value){
					$object->changeA($keys[$key], $value);
					print $keys[$key].' : '.$value.PHP_EOL;
				}
				print '--------------'.PHP_EOL;				
			}
		}
	}
}
?>