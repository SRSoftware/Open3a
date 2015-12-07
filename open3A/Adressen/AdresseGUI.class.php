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
class AdresseGUI extends Adresse implements /*iFPDF, */iGUIHTML2 {
	
	protected $gui;
	
	public function __construct($ID){
		parent::__construct($ID);

		$this->gui = new HTMLGUI2();
	}
	
	function getHTML($id){
		$forReload = "";
		
		BPS::unsetProperty("AdresseGUI", "AuftragID");
		BPS::unsetProperty("AdresseGUI", "displayMode");
		
		
		$this->loadMeOrEmpty();
		
		if($id * 1 == -1) {
			$this->A = $this->newAttributes();

			if(Session::isPluginLoaded("mAdressBuch")){
				$AB = BPS::getProperty("AdressenGUI", "AdressBuch", null);
				if($AB)
					$this->A->type = "AB$AB";
			}
			
			$id = $this->newMe(true, false);
			$this->forceReload();

			try {
				$K = new Kunden();
				#if($this->A("type") == "default") //Or else a lieferAdresse will get a Kundennummer
				$K->createKundeToAdresse($id,false);
			} catch(ClassNotFoundException $e) {}

			$forReload = "<script type=\"text/javascript\">lastLoadedLeft = $id; lastLoadedLeftPlugin = 'Adresse';</script>";

		}
		
		$OptTab = new HTMLSideTable("right");
		
		
		/*$IIL = new HTMLInput("uploadLogo", "file");
		$IIL->onchange(OnEvent::rme($this, "processUpload", array("fileName", "'logo'"), OnEvent::rme($this, "getPic", array("'logo'", "1"), "function(t){ \$j('#picLogo').html(t.responseText); }")));
		
		$IIF = new HTMLInput("uploadFoto", "file");
		$IIF->onchange(OnEvent::rme($this, "processUpload", array("fileName", "'foto'"), OnEvent::rme($this, "getPic", array("'foto'", "1"), "function(t){ \$j('#picFoto').html(t.responseText); }")));
		
		
		$BL = new Button("Logo hochladen", "upload", "iconicG");
		$BL->onclick("\$j('#uploadLogo').toggle();");
		
		$BF = new Button("Foto hochladen", "upload", "iconicG");
		$BF->onclick("\$j('#uploadFoto').toggle();");
		
		$pics = "<div class=\"sideTableRight\" style=\"float:right;margin-right:-350px;\">
			<div id=\"picLogo\">".$this->getPic("logo")."</div>$BL<div style=\"display:none;\" id=\"uploadLogo\">$IIL</div>
			<div id=\"picFoto\">".$this->getPic("foto")."</div>$BF<div style=\"display:none;\" id=\"uploadFoto\">$IIF</div>
		</div>";*/
		
		if($this->A("logo") != "")
			$OptTab->addRow($this->getPic("logo"));
		
		if(Session::isPluginLoaded("Kunden")){
			$B = new Button("Kundendaten","kunden");
			$B->loadFrame("contentLeft", "Kunde", "-1", "0", "KundeGUI;AdresseID:{$this->getID()};action:Kappendix");
			
			$OptTab->addRow($B);
		}
		
		if(Session::isPluginLoaded("mAdresseNiederlassung")){
			$B = new Button("Filialen","./open3A/Niederlassungen/AdresseNiederlassung.png");
			$B->popup("", "Filialen", "mAdresseNiederlassung", "-1", "getPopup", $this->getID(), "", "{position: 'left'}");
			
			$OptTab->addRow($B);
		}
		
		if(Applications::activeApplication() == "open3A" OR Applications::activeApplication() == "lightAd"){
			if(($id == -1 OR $forReload != "") AND Session::isPluginLoaded("mImport")) {
				$OTBV = new Button("Schnell-\nImport","import");
				$OTBV->onclick("Import.openSchnellImportAdresse('Adresse importieren:');");
				$OTBV->id("ButtonAdresseSchnellImport");

				$OptTab->addRow($OTBV);

			}

			if($id != -1 AND Session::isPluginLoaded("Kundenpreise")){
				$ButtonKundenpreise = new Button("Kundenpreise","package");
				$ButtonKundenpreise->onclick("contentManager.loadFrame('contentLeft','Kunde', -1, 0, 'KundeGUI;AdresseID:$this->ID;action:Kundenpreise');");

				$OptTab->addRow($ButtonKundenpreise);
			}
		
			$B = $OptTab->addButton("Erweitert", "navigation");
			$B->popup("", "Erweitert", "Adresse", $this->getID(), "popupExtended");

			
			if(Session::isPluginLoaded("mAnsprechpartner") OR Session::isPluginLoaded("mOSM")){
				$OptTab->addRow("");
				$OptTab->addCellStyle(1, "height:30px;");
			}
			
			if($id != -1 AND Session::isPluginLoaded("mAnsprechpartner"))
				$OptTab->addRow(Ansprechpartner::getButton("Adresse", $this->getID()));
			
			if($id != -1 AND Session::isPluginLoaded("mKundenzugang"))
				$OptTab->addRow(Kundenzugang::getButton($this));
			
			
			if($id != -1 AND Session::isPluginLoaded("mOSM"))
				$OptTab->addRow(OpenLayers::getButton("Adresse", $this->getID()));
			
			if(Session::isPluginLoaded("mFile"))
				$OptTab->addRow(mFileGUI::getManagerButton("WAdresse", $this->getID()));
			
		}
		
		if(Session::isPluginLoaded("mklickTel"))
			$OptTab->addRow(klickTel::getButton($this->getID()));
		

		$this->loadMeOrEmpty();

		$gui = $this->gui;
		$gui->setFormID("AdresseForm");

		$fields = array(
			"firma",
			"anrede",
			"vorname",
			"nachname",
			"AdresseSpracheID",
			"strasse",
			"ort",
			"land",
			"tel",
			"fax",
			"mobil",
			"email",
			"lieferantennr",
			"homepage",
			"gebRem",
			"gebRemMail",
			"AuftragID",
			"KategorieID",
			"type",
			"geb",
			"bemerkung");
		
		$gui->setShowAttributes($fields);
		
		
		if(Session::isPluginLoaded("mSprache")) {
			$gui->setLabel("AdresseSpracheID","Sprache");
			$gui->setLabelDescription("AdresseSpracheID", "und Währung");
			
			$gui->selectWithCollection("AdresseSpracheID", new mSprache(), "SpracheName");
			$gui->activateFeature("addSaveDefaultButton", $this, "AdresseSpracheID");
		} else
			$gui->setType("AdresseSpracheID","hidden");
		
		$gui->setObject($this);
		$gui->setName("Adresse");
		
		$gui->setOptions("anrede", array_keys(self::getAnreden()), array_values(self::getAnreden()));
		$gui->setType("anrede","select");

		$gui->insertSpaceAbove("tel", "Kontakt");
		$gui->insertSpaceAbove("strasse", "Adresse");
		$gui->insertSpaceAbove("lieferantennr", "Sonstiges");
		
		$gui->setFieldDescription("exportToLDAP", "Soll die Adresse auf einen LDAP-Server exportiert werden?");
		$gui->setFieldDescription("lieferantennr", "Ihre Lieferantennummer bei diesem Kunden. Wird auf den Belegen angezeigt.");
		
		$gui->setType("geb","hidden");
		$gui->setType("gebRemMail","hidden");
		$gui->setType("gebRem","hidden");
		$gui->setType("exportToLDAP","checkbox");
		$gui->setType("AuftragID","hidden");
		$gui->setType("type","hidden");
		$gui->setType("bemerkung","textarea");

		$gui->setLabel("bemerkung", "Notizen");
		$gui->setLabel("ort","PLZ/Ort");
		$gui->setLabel("strasse","Straße/Hausnr.");
		$gui->setLabel("tel","Telefon");
		$gui->setLabel("email","E-Mail");
		$gui->setLabel("exportToLDAP","LDAP-Export?");
		$gui->setLabel("lieferantennr", "Lieferantennr.");
		
		$gui->setParser("strasse", "AdresseGUI::parserStrasse", array($this->A("nr")));
		$gui->setParser("ort", "AdresseGUI::parserOrt", array($this->A("plz")));
		
		
		if(Session::isPluginLoaded("mStammdaten") OR Applications::activeApplication() == "MMDB"){
			$gui->setType("land","select");

			$countries = ISO3166::getCountries();
			$labels = array_merge(array("" => "keine Angabe"), $countries);
			$values = array_merge(array("" => ""), $countries);
			$gui->setOptions("land", array_values($values), array_values($labels));
		}

		
		if(Session::isPluginLoaded("mTelefonanlage")){
				$gui->activateFeature("addCustomButton", $this, "tel", Telefonanlage::getCallButton("\$j('input[name=tel]').val()", "telephone", true));
				$gui->activateFeature("addCustomButton", $this, "mobil", Telefonanlage::getCallButton("\$j('input[name=tel]').val()", "mobile", true));
		}

		
		if(Session::isPluginLoaded("Kategorien")){
			$kat = new Kategorien();
			$kat->addAssocV3("type","=","1");
			$keys = $kat->getArrayWithKeys();
			$keys[] = "0";

			$values = $kat->getArrayWithValues();
			$values[] = "bitte auswählen";

			$gui->setOptions("KategorieID", $keys, $values);

			$gui->setLabel("KategorieID","Kategorie");
			$gui->setType("KategorieID","select");
		} else
			$gui->setType("KategorieID","hidden");

		Aspect::joinPoint("buttons", $this, __METHOD__, $OptTab);
		
		$gui->setStandardSaveButton($this, "Adressen");
		
		$gui->customize($this->customizer);

		return $forReload.$OptTab.$gui->getEditHTML();
	}
	
	public function getPic($target, $echo = false){
		if($this->A($target) == "")
			return "";
		
		$B = new Button("Bild löschen", "trash_stroke", "iconic");
		#$B->style("float:right;margin-top:-30px;");
		$B->rmePCR("Adresse", $this->getID(), "removeLogo", "", OnEvent::rme($this, "getPic", array("'logo'", "1"), "function(t){ \$j('#picLogo').html(t.responseText); }"));
		
		$Image = new Button("", DBImageGUI::imageLink("Adresse", $this->getID(), $target, false, true), "icon");
		$Image->style("max-width:150px;");
		
		if($echo)
			echo $Image.$B;
		
		return $Image.$B;
	}
	
	public function removeLogo(){
		$this->changeA("logo", "");
		$this->saveMe();
	}
	
	public function processUpload($fileName, $target = false){
		$ex = Util::ext($fileName);
		
		if($ex != "jpg" AND $ex != "jpeg" AND $ex != "png")
			Red::alertD("Bildtyp unbekannt. Bitte verwenden Sie jpg oder png-Dateien.");
		
		$tempDir = Util::getTempDir();
		
		
		$imgPath = $tempDir."/".$fileName.".tmp";
		
		$this->changeA($target, DBImageGUI::stringifyS("png", $imgPath, 250));
		$this->saveMe(true, false, false);
		
		unlink($imgPath);
		
		return true;
	}
	
	function popupExtended(){
		echo "<p id=\"currentAddress\">".$this->getHTMLFormattedAddress()."</p>";
		$fields = array(
			"abteilung",
			"titelPrefix",
			"titelSuffix");
		$fields[] = "position";
		
		if($this->A("land") == ISO3166::getCountryToCode("GB") OR $this->A("land") == ISO3166::getCountryToCode("US") OR $this->A("land") == ISO3166::getCountryToCode("CH")){
			$fields[] = "zusatz1";
		}
		
		if($this->A("land") == ISO3166::getCountryToCode("DK") OR $this->A("land") == ISO3166::getCountryToCode("ES")){
			$fields[] = "bezirk";
		}
		if($this->A("land") == ISO3166::getCountryToCode("DE") OR $this->A("land") == ISO3166::getCountryToCode("FR"))
			$fields[] = "zusatz2";
		
		
		$F = new HTMLForm("extended", $fields);
		
		#if($this->A("land") != ISO3166::getCountryToCode("GB") AND $this->A("land") != ISO3166::getCountryToCode("US") AND $this->A("land") != ISO3166::getCountryToCode("CH")) {
		#	$gui->setLineStyle("zusatz1", "display:none;");
		#	$gui->setLineStyle("position", "display:none;");
		#}

		#if($this->A("land") != ISO3166::getCountryToCode("DK") AND $this->A("land") != ISO3166::getCountryToCode("ES"))
		#	$gui->setLineStyle("bezirk", "display:none;");

		foreach ($fields AS $field){
			$F->addJSEvent($field, "onkeyup", OnEvent::rme($this, "buildPreview", array("JSON.stringify(contentManager.formContent('extended'))"), "function(t){ \$j('#currentAddress').html(t.responseText); }"));
		}
			
		$F->getTable()->setColWidth(1, 120);
		
		$F->setValues($this);
		
		$F->insertSpaceAbove("abteilung", "Firma");
		$F->insertSpaceAbove("titelPrefix", "Person");
		$F->insertSpaceAbove("zusatz1", "Anschrift");
		if(!in_array("zusatz1", $fields) AND in_array("zusatz2", $fields))
			$F->insertSpaceAbove("zusatz2", "Anschrift");
		
		$F->setLabel("zusatz1", "Zusatz");
		$F->setLabel("zusatz2", "Zusatz");
		$F->setDescriptionField("zusatz1", "Wird in der Adresse unterhalb des Namens der Person angezeigt");
		$F->setDescriptionField("zusatz2", "Wird in der Adresse unterhalb der Straße angezeigt");
		$F->setDescriptionField("abteilung", "Wird in der Adresse unterhalb des Firmennamens angezeigt");
		
		$F->setLabel("titelPrefix","Titel Präfix");
		$F->setLabel("titelSuffix","Titel Suffix");
		
		$F->setSaveClass("Adresse", $this->getID(), "function(){ ".OnEvent::closePopup("Adresse")." }");
		$F->useRecentlyChanged();
		
		echo $F;
	}
	
	public function buildPreview($data){
		$json = json_decode($data);
		foreach($json AS $field)
			$this->changeA($field->name, $field->value);
		
		
		echo $this->getHTMLFormattedAddress();
	}

	public static function parserStrasse($w, $l, $p){
		
		$I1 = new HTMLInput("strasse", "text", $w);
		$I1->style("width:185px;margin-right:10px;");
		$I1->id("strasse");
		
		if(is_object($p)){
			if($p instanceof AkquiseGUI)
				$I1->style("width:170px;margin-right:10px;");
			
			$p = $p->A("nr");
		}
		
		$I2 = new HTMLInput("nr", "text", $p);
		$I2->style("width:50px;text-align:right;");
		$I2->id("nr");
		
		return $I1.$I2;
	}

	public static function parserOrt($w, $l, $p){
		
		$I1 = new HTMLInput("ort", "text", $w);
		$I1->style("width:185px;");
		$I1->id("ort");
		
		if(is_object($p)){
			if($p instanceof AkquiseGUI)
				$I1->style("width:170px;");
			
			$p = $p->A("plz");
		}
		
		$I2 = new HTMLInput("plz", "text", $p);
		$I2->style("width:50px;text-align:right;margin-right:10px;");
		$I2->id("plz");
		
		return $I2.$I1;
	}
	
	public function saveMe($checkUserData = true, $output = false, $deleteBPS = true){
		if($deleteBPS){
			$_SESSION["BPS"]->setActualClass(get_class($this));
			$_SESSION["BPS"]->unsetACProperty("edit");
		}
			
		parent::saveMe($checkUserData, $output);
	}
	
	public function getXML(){
		$xml = parent::getXML();
		$lines = explode("\n",$xml);
		foreach($lines as $k => $v)
			$lines[$k] = str_pad(($k + 1),5, " ", STR_PAD_LEFT).": ";
			
		echo Util::getBasicHTML("<pre class=\"backgroundColor2\" style=\"font-size:9px;float:left;\">".implode("\n",$lines)."</pre><pre class=\"backgroundColor0\" style=\"font-size:9px;margin-left:40px;\">".htmlentities(utf8_decode($xml))."</pre>","XML-Export");
	}

        #EE ab hier
    public static function testAusgabe($p1, $p2){
		Red::alertD("IDNr1: $p1; IDNr2: $p2");
	}
	
	public function getHTMLFormattedAddress($echo = false) {
		$A = parent::getHTMLFormattedAddress();
		
		if($echo)
			echo $A;
		
		return $A;
	}
	
	public function ACLabel(){
		return $this->getShortAddress();
	}
}
?>
