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

class mVorlageGUI extends anyC implements iGUIHTMLMP2 {

	public function getHTML($id, $page){
		$dir = FileStorage::getFilesDir();#Util::getRootPath()."specifics/";
		
		$FS = new File($dir);
		
		if(!$FS->A("FileIsWritable")){
			$T = new HTMLTable(1);
			
			$B = new Button("Verzeichnis nicht beschreibbar", "warning", "icon");
			$B->style("float:left;margin-right:10px;");
			
			$T->addRow(array("{$B}Das Verzeichnis <code>$dir</code> ist nicht beschreibbar. Bitte machen Sie dieses Verzeichnis beschreibbar, damit die Vorlagen erstellt werden können."));
			
			die($T);
		}
		
		$this->loadMultiPageMode($id, $page, 0);

		$gui = new HTMLGUIX($this);
		$gui->version("mVorlage");

		$gui->name("Vorlage");
		
		$gui->attributes(array("VorlageName"));
		
		
		$DeleteIDs = array();
		$AC = anyC::get("Auftrag");
		$AC->addGroupV3("AuftragVorlage");
		$AC->setFieldsV3(array("AuftragVorlage", "COUNT(*) AS anzahl"));
		while($A = $AC->getNextEntry()){
			if(strpos($A->A("AuftragVorlage"), "Vorlage_VorlageID") !== 0)
				continue;
			
			if($A->A("anzahl") <= 10)
				continue;
			
			$DeleteIDs[] = str_replace("Vorlage_VorlageID", "", $A->A("AuftragVorlage"));
		}
		
		if($AC->numLoaded() > 10)
			Red::alertD ("Diese Vorlage kann nicht mehr gelöscht werden, das sie in mehr als 10 Aufträgen verwendet wird.");
		
		
		$gui->blacklists(array(), $DeleteIDs);
		
		return $gui->getBrowserHTML($id);
	}

	public static function doSomethingElse(){
		#if(FileStorage::getFilesDir() != Util::getRootPath()."specifics/") //moved to Auftraege::doSomethingElse
		#	addClassPath(FileStorage::getFilesDir());
	}

}
?>