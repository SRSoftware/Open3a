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
class StammdatenGUI extends Stammdaten implements iGUIHTML2 {
	public $fieldsBank = array(
			"bank",
			"bankOrt",
			"blz",
			"ktonr",
			"SWIFTBIC",
			"IBAN",
			"glaeubigerID");
	
	private static $locked = false;
	
	function __construct($ID) {
		parent::__construct($ID);
		
		try {
			$AC = anyC::get("Auftrag", "AuftragStammdatenID", $this->getID());
			$AC->setLimitV3(11);
			$AC->lCV3();

			self::$locked = $AC->numLoaded() > 10;
		} catch (TableDoesNotExistException $e){
			
		}
		
		$bps = $this->getMyBPSData();
		if($bps != -1 AND isset($bps["overwrite"]))
			self::$locked = false;
	}
	
	function getHTML($id){
		if($id == -1){
			$this->A = $this->newAttributes();
			
			$id = $this->newMe(true, false);
			$this->forceReload();
		}
		$this->loadMeOrEmpty();
		
		
		$a = Stammdaten::getVorlagen();
		
		$FB2 = new FileBrowser();
		$FB2->addDir("../open3A/Auftraege/");
		$FB2->addDir("../specifics/");
		$FB2->setDefaultConstructorParameter("-1");
		$a2 = array_flip($FB2->getAsLabeledArray("iReNr",".class.php",true));
		
		if(in_array("AuftragGUI", $a2)) $a2[array_search("AuftragGUI", $a2)] = "Auftrag";

		$gui = new HTMLGUIX($this);
		$gui->name("Stammdaten");

		if(self::$locked)
			$gui->optionsEdit(false, false);
		
		$gui->attributes(array(
			#"template",
			"ownTemplate",
			"ownTemplatePrint",
			"ownTemplateEmailNew",
			"templateReNr",
			"firmaKurz",
			"firmaLang",
			"vorname",
			"nachname",
			"inhaber",
			"geschaeftsfuehrer",
			"ustidnr",
			"amtsgericht",
			"handelsregister",
			"strasse",
			"nr",
			"land",
			"plz",
			"ort",
			"telefon",
			"mobil",
			"fax",
			"email",
			"internet"));

		$gui->type("aktiv","hidden");
		$gui->label("firmaKurz","Firma kurz");
		$gui->label("firmaLang","Firma lang");
		$gui->label("vorname","Vorname");
		$gui->label("nachname","Nachname");
		$gui->label("nr","Hausnummer");
		$gui->label("land","Land");
		$gui->label("plz","PLZ");
		$gui->label("ort","Ort");
		$gui->label("telefon","Telefon");
		$gui->label("mobil","Mobil");
		$gui->label("fax","Fax");
		$gui->label("email","E-Mail");
		$gui->label("internet","Internet");
		$gui->label("inhaber","Inhaber");
		$gui->label("amtsgericht","Amtsgericht");
		$gui->label("handelsregister","Handelsregister");
		$gui->label("ustidnr","USt-IdNr/St.Nr.");
		$gui->label("strasse","Straße");
		$gui->label("geschaeftsfuehrer","Geschäftsführer");
		$gui->label("templateReNr","Nummern");
		$gui->label("ownTemplate","Vorlage PDF");
		$gui->label("ownTemplatePrint","Vorlage Druck");
		$gui->label("ownTemplateEmailNew","Vorlage E-Mail");
		
		
		$gui->type("ownTemplate","select", $a);
		$gui->descriptionField("ownTemplate","für PDF-Ausgabe");
		
		$gui->type("ownTemplatePrint","select", array_merge(array("" => "Wie PDF-Ausgabe"), $a));
		$gui->descriptionField("ownTemplatePrint","für direkt-Druck");
		
		$gui->type("ownTemplateEmailNew","select", array_merge(array("" => "Wie PDF-Ausgabe"), $a));
		$gui->descriptionField("ownTemplateEmailNew","für E-Mail-Versand");
		
		if(!$_SESSION["S"]->checkForPlugin("mDrucker"))
			$gui->type("ownTemplatePrint", "hidden");
		
		#$gui->type("ownTemplateEmail","hidden");
		$gui->descriptionField("geschaeftsfuehrer","Bitte geben Sie einen Inhaber <b>oder</b> Geschäftsführer ein");
		$gui->descriptionField("inhaber","Bitte geben Sie einen Inhaber <b>oder</b> Geschäftsführer ein");

		if($this->A("land") == "D") $this->changeA("land", "DE");

		$gui->type("land","select", ISO3166::getCountries());
		
		$gui->addFieldEvent("templateReNr", "onchange", "if(\$j(this).val() == 'BelegnummernEditor' || \$j(this).val() == 'BelegnummernEditor2') \$j('#BelegNummernEditor').fadeIn(); else \$j('#BelegNummernEditor').fadeOut();");

		$gui->space("bank");
		$gui->space("inhaber");
		$gui->space("template","Vorlagen");
		$gui->space("firmaKurz","Firma");
		$gui->space("telefon", "Kontakt");
		$gui->space("bank", "Bank");
		$gui->space("strasse", "Adresse");
		$gui->space("ustidnr");
		

		$gui->descriptionField("templateReNr","Die Rechnungsnummern werden automatisch hochgezählt.");
		$gui->type("templateReNr","select", $a2);
		
		
		$B = $gui->addSideButton("Bank-\nDaten", "./open3A/Stammdaten/bank.png");
		$B->popup("", "Bank-Daten", "Stammdaten", $this->getID(), "popupBank");
		
		if(Session::isPluginLoaded("mESR")){
			$B = $gui->addSideButton("ESR-\nDaten", "./open3A/ESR/ESR.png");
			$B->popup("", "ESR-Daten", "Stammdaten", $this->getID(), "popupESR");
		}
		
		$B = $gui->addSideButton("Beleg-\nPräfixe", "./open3A/Stammdaten/prefix.png");
		$B->popup("", "Beleg-Präfixe", "Stammdaten", $this->getID(), "popupPrefixes");
		
		
		if(Session::isPluginLoaded("mZahlungsart")){
			$B = $gui->addSideButton("Zahlungs-\narten", "./open3A/Zahlungsarten/Zahlungsart.png");
			$B->popup("", "Zahlungsarten", "mZahlungsart", -1, "manage", $this->getID());
		}
		
		$B = $gui->addSideButton("Beleg-\nnummern", "./open3A/Stammdaten/renummern.png");
		$B->popup("", "Belegnummern", "Stammdaten", $this->getID(), "popupNumbers", "", "", "{width:800}");
		$B->id("BelegNummernEditor");
		if($this->A("templateReNr") != "BelegnummernEditor" AND $this->A("templateReNr") != "BelegnummernEditor2")
			$B->style ("display:none;");
		
		$table = new HTMLTable(1);
		if($this->ID != -1)
			if($this->A->aktiv == "0") $table->addRow(array("<span style=\"color:red;\">Diese Stammdaten sind inaktiv und werden nicht verwendet.</span><br /><br />Sie können diese Stammdaten aktivieren, indem Sie in der Liste auf der rechten Seite das Häkchen setzen.<br />Es kann immer nur ein Stammdatensatz aktiv sein."));
			else $table->addRow(array("<span style=\"color:green;\">Diese Stammdaten sind aktiv und werden verwendet.</span>"));
		
		$TL = new HTMLTable(1);
		$TL->setColClass(1, "");
		if(self::$locked){
			$B = new Button("Stammdaten\nkopieren", "seiten");
			$B->style("float:right;margin-left:10px;");
			$B->onclick("contentManager.rmePCR('Stammdaten', '".$this->getID()."', 'cloneMe', [''], function(transport){ lastLoadedLeft = (transport.responseText == '' ? -1 : transport.responseText); contentManager.reloadFrameLeft(); contentManager.reloadFrameRight(); }, '', true );");
			
			$TL->addRow(array("{$B}Diese Stammdaten können nicht mehr bearbeitet werden, da sie in mehr als 10 Aufträgen verwendet werden. Bitte kopieren Sie diese Stammdaten, um Änderungen vorzunehmen.<a href=\"#\" onclick=\"".OnEvent::frame("Left", "Stammdaten", $this->getID(), 0, "", "StammdatenGUI;overwrite:true")."return false;\" class=\"hiddenLink\">&nbsp;</a>"));
			$TL->addRowClass("highlight");
		} else {
			$BN = new Button("", "notice", "icon");
			$BN->style("float:left;margin-right:10px;");
			
			$TL->addRow(array("{$BN}Bitte beachten Sie: Diese Stammdaten werden gegen Veränderungen gesperrt, sobald Sie mehr als 10 Aufträge damit erstellt haben."));
		}
		
		return $TL.$table.$gui->getEditHTML();
	}
	
	function popupNumbers(){
		$fields = array();
		
		$useID = $this->A("templateReNr") == "BelegnummernEditor2";
		
		$AC = anyC::get("Userdata", "typ", "belegNummer".($useID ? $this->getID() : ""));
		while($U = $AC->getNextEntry()){
			if(!$useID AND preg_match("/[0-9]+$/", $U->A("name")))
				continue;
			
			$fields[] = $U->A("name");
		}
		
		$belegArten = Auftrag::getBelegArten();
		foreach($belegArten AS $B)
			if(!in_array("belegNummerNext$B".($useID ? $this->getID() : ""), $fields))
				$fields[] = "belegNummerNext$B".($useID ? $this->getID() : "");
		
		$f = $fields;
		
		$fields[] = "belegNummerResetR";
		$fields[] = "belegNummerFormatR";
		
		
		$F = new HTMLForm("belegNext", $fields, "Zähler");
		$F->getTable()->setColWidth(1, 120);
		$F->getTable()->weight("light");
		
		$F->setValue("belegNummerFormatR", $this->A("belegNummerFormatR"));
		$F->setType("belegNummerFormatR", "hidden");
		
		$F->setLabel("belegNummerResetR", "Zurücksetzen");
		$F->setType("belegNummerResetR", "select", $this->A("belegNummerResetR"), array("" => "nie", "daily" => "täglich", "monthly" => "monatlich", "yearly" => "jährlich"));

		foreach($f AS $v){
			$LT = Stammdaten::getLongType(str_replace($this->getID(), "", str_replace("belegNummerNext", "", $v)));
			if($LT === false){
				$F->setType($v, "hidden");
				continue;
			}
			
			$F->setLabel($v, $LT);
		}
		
		foreach($f AS $v)
			$F->setValue ($v, mUserdata::getGlobalSettingValue($v, 1));
		
		#$F->setSaveJSON("Speichern", "", "Stammdaten", $this->getID(), "saveNumbers", OnEvent::closePopup("Stammdaten"));
	
		$B = new Button("Fertig", "bestaetigung");
		$B->style("float:right;margin:10px;margin-top:20px;");
		#$B->rmePCR("Stammdaten", $this->getID(), "saveNumbers", array("\$j('[name=belegNummerFormatR]').val()", "\$j('[name=belegNummerResetR]').val()"), OnEvent::closePopup("Stammdaten"));
		$B->onclick("contentManager.rmePCR('Stammdaten', '".$this->getID()."', 'saveNumbers', encodeURIComponent(JSON.stringify(contentManager.formContent('belegNext'))) , function(transport){ ".OnEvent::closePopup("Stammdaten")." });");
		
		
		echo "<div style=\"width:399px;float:right;border-left-style:solid;border-left-width:1px;margin-left:0px;\" class=\"borderColor1\">$F$B</div>";
		
		echo "<div style=\"width:400px;height:350px;border-right-style:solid;border-right-width:1px;\" class=\"borderColor1\">";
		
		$T = new HTMLTable(2, "Format");
		$T->weight("light");
		$T->setColWidth(1, 120);
		
		$I = new HTMLInput("belegNummerFormatR", "text", $this->A("belegNummerFormatR"));
		#$I->style("width:100%;text-align:center;font-size:18px;");
		#$I->placeholder("Belegnummernformat");
		$I->onkeyup("\$j('[name=belegNummerFormatR]').val(\$j(this).val());");
		
		$T->addLV("Format:", $I);
		
		echo "<div class=\"\" style=\"margin-bottom:20px;\">".$T."</div>";
		
		echo "<p>Für das Format der Belegnummer können Sie folgende Variablen verwenden:</p>";
		
		echo "<ul>";
		echo "<li><strong style=\"display:inline-block;width:60px;\">{N:#}</strong> Die fortlaufende Nummer; Für das #-Zeichen setzen Sie 2, 3 oder 4 für für die Mindestanzahl der Stellen.</li>";
		echo "<li><strong style=\"display:inline-block;width:60px;\">{J}</strong> Das vierstellige Jahr</li>";
		echo "<li><strong style=\"display:inline-block;width:60px;\">{J2}</strong> Das zweistellige Jahr</li>";
		echo "<li><strong style=\"display:inline-block;width:60px;\">{T}</strong> Tag im Jahr</li>";
		echo "<li><strong style=\"display:inline-block;width:60px;\">{M}</strong> Der zweistellige Monat mit führender Null</li>";
		echo "<li><strong style=\"display:inline-block;width:60px;\">{M1}</strong> Der Monat ohne führende Null</li>";
		echo "<li><strong style=\"display:inline-block;width:60px;\">{K}</strong> Die Kundennummer</li>";
		echo "</ul>";
		
		
		
		echo "</div>";
		
		echo "<div style=\"clear:both;\"></div>";
	}
	
	function saveNumbers($data){
		$data = json_decode($data);
		$useID = $this->A("templateReNr") == "BelegnummernEditor2";
		
		foreach($data AS $e){
			if($e->name == "belegNummerFormatR")
				$format = $e->value;
			
			if($e->name == "belegNummerResetR")
				$reset = $e->value;
		}
		
		if(!preg_match("/\{N:([0-9]+)\}/", $format))
			Red::alertD("Die Variable {N:#} muss vorkommen.");
		
		if($reset == "monthly" 
				AND ((strpos($format, "{M}") === false AND strpos($format, "{M1}") === false) 
				OR (strpos($format, "{J}") === false AND strpos($format, "{J2}") === false)))
			Red::alertD("Wenn die Nummer monatlich zurückgesetzt werden soll, muss eine Variable für den Monat sowie eine für das Jahr verwendet werden.");
		
		if($reset == "yearly" 
				AND (strpos($format, "{J}") === false AND strpos($format, "{J2}") === false))
			Red::alertD("Wenn die Nummer jährlich zurückgesetzt werden soll, muss eine Variable für das Jahr verwendet werden.");
		
		
		foreach($data AS $e){
			if(strpos($e->name, "belegNummerNext") === false)
				continue;
			
			mUserdata::setUserdataS($e->name, $e->value, "belegNummer".($useID ? $this->getID() : ""), -1);
		}
		
		$this->changeA("belegNummerFormatR", $format);
		$this->changeA("belegNummerResetR", $reset);
		$this->saveMe(true, true);
	}
	
	function popupESR(){
		$F = new HTMLForm("esr", array(
			"ESRKonto",
			"ESRIDNr"));
		
		$F->getTable()->setColWidth(1, 120);
		
		$F->setValues($this);
		
		$F->setLabel("ESRKonto","Konto");
		$F->setLabel("ESRIDNr","ID-Nr");
		
		if(!self::$locked)
			$F->setSaveClass("Stammdaten", $this->getID(), "function(){ ".OnEvent::closePopup("Stammdaten")." }");
		else
			$F->isEditable(false);
		
		$F->useRecentlyChanged();
		echo $F;
	}
	
	function popupBank(){
		$F = new HTMLForm("bank", $this->fieldsBank);
		
		$F->getTable()->setColWidth(1, 120);
		
		$F->setValues($this);
		
		$F->setLabel("blz","Bankleitzahl");
		$F->setLabel("ktonr","Kontonummer");
		$F->setLabel("SWIFTBIC","SWIFT/BIC");
		$F->setLabel("bank","Name der Bank");
		$F->setLabel("bankOrt", "Ort der Bank");
		$F->setLabel("glaeubigerID", "Gläubiger-ID");
		
		if(!Session::isPluginLoaded("mESR"))
			$F->setType("bankOrt", "hidden");
		
		Aspect::joinPoint("bank", $this, __METHOD__, $F);
		
		if(!self::$locked)
			$F->setSaveClass("Stammdaten", $this->getID(), "function(){ ".OnEvent::closePopup("Stammdaten")." }");
		else
			$F->isEditable(false);
		
		$F->useRecentlyChanged();
		echo $F;
	}
	
	function popupPrefixes(){
		$F = new HTMLForm("prefixes", array(
			"prefixG",
			"prefixR",
			"prefixL",
			"prefixB",
			"prefixM",
			"prefixA",
			"prefixK",
			"prefixD"));
		
		$F->getTable()->setColWidth(1, 120);
		
		$F->setValues($this);
		
		$F->setLabel("prefixB","Bestätigung");
		$F->setDescriptionField("prefixB","standard: B");
		
		$F->setLabel("prefixG","Gutschrift");
		$F->setDescriptionField("prefixG","standard: G");
		
		$F->setLabel("prefixR","Rechnung");
		$F->setDescriptionField("prefixR","standard: R");
		
		$F->setLabel("prefixL","Lieferschein");
		$F->setDescriptionField("prefixL","standard: L");
		
		$F->setLabel("prefixK","Kunde");
		$F->setDescriptionField("prefixK","standard: K");
		
		$F->setLabel("prefixA","Angebot");
		$F->setDescriptionField("prefixA","standard: A");
		
		$F->setLabel("prefixM","Mahnung");
		$F->setDescriptionField("prefixM","standard: M");
		
		$F->setLabel("prefixD","Dokument");
		$F->setDescriptionField("prefixD","standard: D");
		
		if(!self::$locked)
			$F->setSaveClass("Stammdaten", $this->getID(), "function(){ ".OnEvent::closePopup("Stammdaten")." }");
		else
			$F->isEditable(false);
		
		$F->useRecentlyChanged();
		echo $F;
	}
	
	function getLetter(){
		if($this->A ==  null AND $this->ID != -1) $this->loadMe();
		
		$brief = new Brief();
		$brief->setStammdaten($this);
		
		$brief->generate(false);
	}
}
?>