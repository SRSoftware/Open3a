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
class KundenGUI extends Kunden implements iGUIHTML2 {
	function getHTML($id){

		if($this->A == null) $this->lCV3($id);
		
		$gui = new HTMLGUI();
		if($this->collector != null) $gui->setAttributes($this->collector);
		
		#$kat = new Kategorien();
		#$kat->setAssociation("type","kndGrp");
		#$kv = $kat->getArrayWithKeysAndValues();
		
		#$gui->setDisplayGroup("KategorieID",$kv);
		$gui->setIsDisplayMode(true);
		$gui->setDeleteInDisplayMode(true);
		$gui->setEditInDisplayMode(true,'contentLeft');
		$gui->setName("Kunden");
		$gui->setCollectionOf("Kunde");
		$gui->setShowAttributes(array("firma"));
		
		$gui->setParser("firma","AdressenGUI::firmaParser",array("\$sc->vorname","\$sc->nachname")); # only works because \$sc->vorname is eval'd while $sc is set in HTMLGUI.class.php
		
		$gui->setMode($mode);
		
		$gui->addRowAfter("top","newKunde");
		$gui->setParser("newKunde","KundenGUI::newKundeParser");
		
		try {
			return $gui->getBrowserHTML($id);
		} catch (Exception $e){ }
	}
	
	public static function newKundeParser(){
		return "<input type=\"button\" value=\"Kunde aus Adresse erzeugen\" onclick=\"loadRightWithSelection('Adressen','singleSelection,Kunden,-1,createKundeFromAdresse,Kunden,contentLeft,Kunde,-1');\" />";
	}
	
	public function createKundeFromAdresse($id){
		parent::createKundeFromAdresse($id);
		echo "Kunde erzeugt";
	}
}
?>
