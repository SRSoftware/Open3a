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
class KategorieGUI extends Kategorie implements iGUIHTML2 {
	function getHTML($id){
		$this->loadMeOrEmpty();
		
		$gui = new HTMLGUIX($this);
		$gui->name("Kategorie");

		$gui->label("name","Name");
		$gui->label("type","Typ");
		$gui->label("isDefault","Standard?");
		
		$gui->descriptionField("isDefault", "Diesen Eintrag als Standard für diesen Kategorietyp verwenden?");
		
		$Ks = new KategorienGUI();
		$gui->type("type", "select", $Ks->getAvailableCategories());
	
		$AC = anyC::get("Kategorie", "type", "OU");
		$AC->lCV3();
		
		if($AC->numLoaded() == 0){
			$gui->type("parentID", "hidden");
		} else {
			$AC = anyC::get("Kategorie", "type", "OU");
			$AC->addAssocV3("KategorieID", "!=", $this->getID());
			
			$gui->label("parentID","Elter");
			$gui->type("parentID","select", $AC, "name", "Kein Elternelement");
		}
		
		$gui->type("isDefault", "checkbox");
		
		return $gui->getEditHTML();
	}
}
?>