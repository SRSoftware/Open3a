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
}
?>