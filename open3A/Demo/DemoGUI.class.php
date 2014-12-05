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
class DemoGUI extends Demo implements iGUIHTML2 {
	function  __construct($ID) {
		parent::__construct($ID);

	}
	
	function getHTML($id){
		$this->loadMeOrEmpty();
		$find="Dateiname";

		$file = $this->A($find);
		if ($file != null && !empty($file)){
			$F = new HTMLForm("Dateiupload", array($find),"importierte Datei");
			$F->setValue($find, $file);
			$F->setType($find, 'readonly');
		} else {
			$F = new HTMLForm("Dateiupload", array($find,"upload"),"neue Datei importieren");
			$F->setType($find, "hidden");
			$F->setType("upload", "file");
			$F->addJSEvent("upload", "onChange", "contentManager.rmePCR('Demo', '".$this->getID()."', 'storeFile', [fileName], function(){ \$j('#Dateiupload input[name=$find]').val(fileName); });");
			$F->setSaveJSON("Import starten", "", "Demo", $this->getID(), "processImport", OnEvent::reload("Right"));
		}
	
		echo $F;
	}
	
	public static function demoRME($p1, $p2){
		Red::alertD("Parameter1: $p1; Parameter2: $p2");
	}

	public function storeFile($fileName){
		$tempDir = Util::getTempDir();
		$tempFile = $tempDir.$fileName.".tmp";
		copy($tempFile,FileStorage::getFilesDir().$fileName);
		unlink($tempFile);		
	}
	
	public function processImport($data){
		$data=json_decode($data);
		$obj=reset($data);
		$this->changeA($obj->name, $obj->value);
		$this->newMe(true,true);
	}	
}
?>