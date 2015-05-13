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
class KundenpreisGUI extends Kundenpreis implements iGUIHTML2 {
	function getHTML($id){
		
		$this->loadMeOrEmpty();
		
		$gui = new HTMLGUI();
		$gui->setAttributes($this->A);
		$gui->setName("Kundenpreis");

		$gui->setSaveButtonValues(get_parent_class($this), $this->ID, $_SESSION["CurrentAppPlugins"]->isCollectionOf(get_parent_class($this)));
				
		return $gui->getEditHTML();
	}
	
	/*function makeKundenpreis($ArtikelID){
		$return = parent::makeKundenpreis($ArtikelID);
		
		if($return == "-1") 
			Red::alertD("Artikel bereits vorhanden");
		else 
			Red::messageD("Artikel übernommen");
	}*/
	
	public function saveMultiEditField($field,$value){
		parent::saveMultiEditField($field,$value);
		Red::messageD("Änderung gespeichert");
	}
}
?>