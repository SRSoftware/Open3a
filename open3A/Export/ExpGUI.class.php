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
class ExpGUI extends Exp implements iGUIHTML2 {
	function getHTML($id){
		
		$this->loadMeOrEmpty();
		
		$gui = new HTMLGUI();
		$gui->setObject($this);
		$gui->setName("Export");

		$gui->setLabel("sep","Trennzeichen");
		$gui->setLabel("textSep","Texttrenner");
		$gui->setType("textSep","select");
		$gui->setType("newline","select");
		$gui->setType("showID","checkbox");
		$gui->setType("selectors","textarea");
		$gui->setType("sort","textarea");
		$gui->setType("felder","textarea");
		$gui->setOptions("newline",array("Unix","Windows","HTML"),array("Unix","Windows","HTML"));
		$gui->setOptions("textSep",array("2","1","none"),array("\"","'","kein"));
		
		$gui->setInputStyle("selectors","font-size:9px;");
		$gui->setInputStyle("felder","font-size:9px;");
		$gui->setInputStyle("sort","font-size:9px;");
		
		$gui->setLabel("newline","neue Zeile");
		$gui->setLabel("showID","ID anzeigen");
		$gui->setLabel("filename","Dateiname");
		$gui->setLabel("type","Typ");
		$gui->setLabel("selectors","Selektoren");
		$gui->setLabel("sort","Sortierung");
		$gui->setFieldDescription("showID","die Datensatz-ID in der ersten Spalte anzeigen. Nützlich für re-Import.");
		$gui->setFieldDescription("selectors","z.B. firma != 'MS' AND plz = '86682' OR name = 'Müller'");
		$gui->setFieldDescription("sort","z.B. plz ASC, firma DESC");
		
		$gui->setType("type","select");
		$gui->setOptions("type",array("txt"),array("Textdatei"));
		
		$p = $_SESSION["CurrentAppPlugins"]->getMenuEntries();
		$values = array();
		$keys = array();
		
		foreach($p as $key => $value) {
			if(!$_SESSION["CurrentAppPlugins"]->getIsAdminOnly($key)){
				$values[] = $key;
				$keys[] = $value;
			}
		}
		$gui->setType("plugin","select");
		$gui->setOptions("plugin",$keys,$values);
		
		$gui->setType("kodierung","select");
		$gui->setOptions("kodierung",array("UTF-8","ISO-8859-15"),array("UTF-8","ISO-8859-15"));
		
		$gui->setInputJSEvent("plugin","onchange","updateFelder();");
		$gui->setStandardSaveButton($this);
		#$gui->setSaveButtonValues(get_parent_class($this), $this->ID, $_SESSION["CurrentAppPlugins"]->isCollectionOf(get_parent_class($this)));
				
		return $gui->getEditHTML();
	}
	
	function getFelder($plugin){
		$plugin .= "GUI";
		$c = new $plugin();
		$c = $c->getCollectionOf();
		if($c == "Nix") die("Keine Felder verfügbar");
		$c = new $c(-1);
		$cA = $c->newAttributes();
		
		echo implode(", ",PMReflector::getAttributesArray($cA));
		
	}
}
?>