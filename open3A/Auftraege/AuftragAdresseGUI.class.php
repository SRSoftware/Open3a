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
class AuftragAdresseGUI extends Adresse implements iGUIHTML2 {
	
	function getHTML($id){
		$AuftragID = BPS::getProperty ("AuftragAdresseGUI", "AuftragID", -1);
		$displayMode = BPS::getProperty ("AuftragAdresseGUI", "displayMode", null);
		
		BPS::unsetProperty("AuftragAdresseGUI", "AuftragID");
		BPS::unsetProperty("AuftragAdresseGUI", "displayMode");
		
		
		$this->loadMeOrEmpty();
		
		if($id * 1 == -1) {
			$this->A = $this->newAttributes();
			$this->A->AuftragID = $AuftragID;
			if($displayMode != null) 
				$this->A->type = $displayMode; //Has to stay or lieferAdresse will also overwrite a normal Auftrags-Adresse

		}
		
		$this->loadMeOrEmpty();

		$gui = new HTMLGUIX($this);
		$gui->formID("AdresseForm");

		$fields = array(
			"firma",
			"anrede",
			"vorname",
			"nachname",
			"AdresseSpracheID",
			"strasse",
			"ort",
			"land",
			"email",
			"lieferantennr",
			"AuftragID",
			"type",
			"createNew");
		
		$gui->attributes($fields);
		
		
		if(Session::isPluginLoaded("mSprache")) {
			$gui->label("AdresseSpracheID","Sprache");
			$gui->descriptionField("AdresseSpracheID", "und Währung");
			
			$gui->type("AdresseSpracheID", "select", new mSprache(), "SpracheName");
		} else
			$gui->type("AdresseSpracheID","hidden");
		
		$gui->name("Adresse");
		
		$gui->type("anrede", "select", self::getAnreden());
		$gui->type("createNew", "checkbox");
		
		$gui->space("strasse", "Adresse");
		$gui->space("lieferantennr", "Sonstiges");
		$gui->space("email", "Kontakt");
		
		$gui->descriptionField("lieferantennr", "Ihre Lieferantennummer bei diesem Kunden. Wird auf den Belegen angezeigt.");
		$gui->descriptionField("createNew", "Adresse in Adressbuch speichern und Kundennummer anlegen?");
		
		$gui->type("AuftragID","hidden");
		$gui->type("type","hidden");

		$gui->label("createNew", "Adresse anlegen?");
		$gui->label("land","Land");
		$gui->label("firma","Firma");
		$gui->label("anrede","Anrede");
		$gui->label("vorname","Vorname");
		$gui->label("nachname","Nachname");
		$gui->label("ort","PLZ/Ort");
		$gui->label("strasse","Straße/Hausnr.");
		$gui->label("email","E-Mail");
		$gui->label("lieferantennr", "Lieferantennr.");
		
		$gui->parser("strasse", "AdresseGUI::parserStrasse", array($this->A("nr")));
		$gui->parser("ort", "AdresseGUI::parserOrt", array($this->A("plz")));
		
		
		if(Session::isPluginLoaded("mStammdaten")){
			$countries = ISO3166::getCountries();
			$cNew = array("" => "keine Angabe");
			foreach($countries AS $v)
				$cNew[$v] = $v;
			
			$gui->type("land","select", $cNew);
		}

		$gui->displayMode("popupNothing"); //just need the "popup"-part
		
		
		switch($displayMode){
			case "auftragAdresse":
				$gui->addToEvent("onSave", OnEvent::reload("Right")."contentManager.loadFrame('contentLeft', 'Auftrag', {$this->A->AuftragID}); ".OnEvent::closePopup("AuftragAdresse"));
			break;
			case "lieferAdresse":
				#$this->A->type = "lieferAdresse";
				$gui->type("createNew", "hidden");
				$gui->type("lieferantennr", "hidden");
				$gui->addToEvent("onSave", OnEvent::reload("Right")."contentManager.loadFrame('subframe', 'GRLBM', {$this->A->AuftragID}); ".OnEvent::closePopup("AuftragAdresse"));
				#$gui->setJSEvent("onSave","function() {
				#	contentManager.loadFrame('contentRight','Auftraege');
				#	contentManager.loadFrame('subframe','GRLBM',{$this->A->AuftragID});
				#}");
			break;
		}

		$gui->customize($this->customizer);

		$gui->addToEvent("onSave", OnEvent::closePopup("AuftragAdresse").OnEvent::frame("Left", "Auftrag", $this->A("AuftragID")));
		
		$FI = "";
		if(Session::isPluginLoaded("mImport")){
			$I = new importAdresseGUI();
			$FI = "<div style=\"vertical-align:top;display:inline-block;width:400px;\">".$I->getFastImportWindow(true)."</div>";
		}
		
		return "<div style=\"vertical-align:top;display:inline-block;width:400px;\">".$gui->getEditHTML()."</div>".$FI.OnEvent::script("\$j('#editDetailsAuftragAdresse').css('width', 800);");
	}
	
	function newMe($checkUserData = true, $output = false) {
		if($this->AA("createNew")){
			$Auftrag = new Auftrag($this->A("AuftragID"));
			
			$this->changeA("type", "default");
			$this->changeA("AuftragID", "-1");
			
			$Adresse = new Adresse(-1);
			$Adresse->setA($this->getA());
			
			$id = $Adresse->newMe(true, false);
			
			try {
				$K = new Kunden();
				$K->createKundeToAdresse($id,false);
				
			} catch(ClassNotFoundException $e) {}
			
			$Auftrag->getAdresseCopy($id);
			
			return $id;
		}
		
		return parent::newMe($checkUserData, false);
	}
}
?>
