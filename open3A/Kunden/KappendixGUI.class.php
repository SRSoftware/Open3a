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
class KappendixGUI extends Kappendix implements iGUIHTML2, icontextMenu {
	public $showAttributes = array(
		"kundennummer",
		"UStIdNr"/*,"KappendixErloeskonto"*/,
		"KappendixSameKontoinhaber",
		"KappendixKontoinhaber",
		#"KappendixKontonummer",
		#"KappendixBLZ",
		"KappendixIBAN",
		"KappendixSWIFTBIC",
		"KappendixEinzugsermaechtigung",
		"KappendixIBANMandatDatum",
		"KappendixIBANMandatReferenz",
		"KappendixEinzugsermaechtigungAltZBTB",
		"KappendixKreditkarte",
		"KappendixKartennummer",
		"KappendixKarteValidUntil"/*,
			"RTextbausteinOben","RTextbausteinUnten","RZahlungsbedingungen",
			"GTextbausteinOben","GTextbausteinUnten","GZahlungsbedingungen",
			"ATextbausteinOben","ATextbausteinUnten","AZahlungsbedingungen",
			"LTextbausteinOben","LTextbausteinUnten","LZahlungsbedingungen"*/);

	public $fieldsTextbausteine = array("RTextbausteinOben","RTextbausteinUnten","RZahlungsbedingungen",
			"GTextbausteinOben","GTextbausteinUnten","GZahlungsbedingungen",
			"ATextbausteinOben","ATextbausteinUnten","AZahlungsbedingungen",
			"LTextbausteinOben","LTextbausteinUnten","LZahlungsbedingungen");
	
	function __construct($id) {
		parent::__construct($id);
		
		$this->setParser("KappendixIBANMandatDatum", "Util::CLDateParserE");
		$this->setParser("KappendixProvisionUserUntil", "Util::CLDateParserE");
		$this->setParser("KappendixProvisionUserBaseValue", "Util::CLNumberParserZ");
	}
	
	function getHTML($id){
		$this->loadMeOrEmpty();
		
		$bps = $this->getMyBPSData();

		$gui = new HTMLGUI2();
		$gui->setObject($this);
		$gui->setName("Kundendaten");

		$gui->setShowAttributes($this->showAttributes);
		

		if(Session::isPluginLoaded("mexportLexware") OR Session::isPluginLoaded("mexportDatev"))
			$gui->insertAttribute("after", "UStIdNr", "KappendixErloeskonto");
		
		if(Session::isPluginLoaded("mPreisgruppe")){
			$gui->insertAttribute ("after", "UStIdNr", "KappendixPreisgruppe");
			$gui->setLabel("KappendixPreisgruppe", "Preisgruppe");
			$gui->setType("KappendixPreisgruppe", "select");
			$gui->setOptions("KappendixPreisgruppe", array(0, 1, 2, 3, 4, 5, 6, 7, 8), array("Keine", 1, 2, 3, 4, 5, 6, 7, 8));
		}
		
		if(Session::isPluginLoaded("mZahlungsart")){
			$gui->insertAttribute ("after", "UStIdNr", "KappendixRZahlungsart");
			$gui->setLabel("KappendixRZahlungsart", "Zahlungsart");
			$gui->setType("KappendixRZahlungsart", "select");
			
			$Z = GRLBM::getPaymentVia();
			$N = array("" => "Standard");
			foreach($Z AS $k => $ZA){
				$TB = Zahlungsart::getTB($k);
				if($TB != null)
					$N[$k] = $ZA." (TB: ".$TB->A("label").")";
				else
					$N[$k] = $ZA." (kein TB)";
			}
			
			
			$gui->setOptions("KappendixRZahlungsart", array_keys($N), array_values($N));
			
		}
		
		$gui->setType("AdresseID","hidden");
		$gui->setType("bemerkung","textarea");
		$gui->setFormID("KappendixForm");
		
		$gui->setLabel("UStIdNr","USt-IdNr/St.Nr.");
		
		$B = IBANCalcGUI::getButton("KappendixIBAN", "KappendixSWIFTBIC", "KappendixKontonummer", "KappendixBLZ");
		$B->style("float:right;");
		$gui->activateFeature("addCustomButton", $this, "KappendixKontonummer", $B);
		
		$gui->setLabel("KappendixKontoinhaber","Kontoinhaber");
		$gui->setLabel("KappendixKontonummer","Kontonummer");
		$gui->setLabel("KappendixBLZ","BLZ");
		$gui->setLabel("KappendixSWIFTBIC","BIC");
		$gui->setLabel("KappendixIBAN","IBAN");
		if(!mUserdata::getGlobalSettingValue("DVKappendixKundenKonto", "0"))
			$gui->setLabel("KappendixErloeskonto","Erlöskonto");
		else
			$gui->setLabel("KappendixErloeskonto","Kundenkonto");
		
		$gui->insertSpaceAbove("KappendixSameKontoinhaber", "Bank");
		$gui->insertSpaceAbove("bemerkung");
		$gui->insertSpaceAbove("KappendixKreditkarte", "Kreditkarte");
		
		if(Session::isPluginLoaded("Provisionen")){
			$gui->insertSpaceAbove("KappendixProvisionUserID", "Provisionen");
			#$gui->setShowAttributes(array_merge(array_slice($fields, 0, 1), array("KappendixProvisionUserID", "KappendixProvisionUserUntil"), array_slice($fields, 1)));
			$gui->insertAttribute("before", "KappendixSameKontoinhaber", "KappendixProvisionUserID");
			$gui->insertAttribute("after", "KappendixProvisionUserID", "KappendixProvisionUserUntil");
			$gui->insertAttribute("after", "KappendixProvisionUserUntil", "KappendixProvisionUserBaseValue");
			$gui->setLabel("KappendixProvisionUserID", "Provisionen");
			$gui->setLabel("KappendixProvisionUserUntil", "Erhält Prov. bis");
			$gui->setLabel("KappendixProvisionUserBaseValue", "Grundwert");
			$gui->setFieldDescription("KappendixProvisionUserBaseValue", "Dieser Wert wird pro Rechnung mindestens gutgeschrieben. Dazu werden Artikelprovsionen addiert.");
			$gui->setLabelDescription("KappendixProvisionUserBaseValue", "(netto)");
			$gui->setParser("KappendixProvisionUserBaseValue", "KappendixGUI::parserProvBaseValue", array($this->A("KappendixProvisionUserBaseType")));
			
			$gui->setType("KappendixProvisionUserID", "select");
			$gui->setType("KappendixProvisionUserUntil", "calendar");
			
			$gui->selectWithCollection("KappendixProvisionUserID", Users::getUsers(), "name", "keine Provision");
		}
		
		$BE = new Button("Einstellungen", "./images/i2/settings.png", "icon");
		$BE->contextMenu("Kappendix", "erloerkonto", "Einstellungen", "right", ((isset($bps["mode"]) AND $bps["mode"] == "short")) ? "up" : "down");
		
		$BIBAN = new Button("IBAN-Prüfung", "./images/i2/".(parent::checkIBAN($this->A("KappendixIBAN")) ? "okCatch" : "note").".png", "icon");
		$BIBAN->id("IBANCheck");
		if(trim($this->A("KappendixIBAN")) == "")
			$BIBAN->image("./images/i2/empty.png");
		
		$gui->activateFeature("addCustomButton", $this, "KappendixErloeskonto", $BE);
		$gui->activateFeature("addCustomButton", $this, "KappendixIBAN", $BIBAN);
		
		$gui->setInputJSEvent("KappendixIBAN", "onkeyup", "if(this.value != '') ".OnEvent::rme($this, "checkIBAN", array("this.value"), "function(t){ \$j('#IBANCheck').css('display', ''); if(t.responseText == '1') \$j('#IBANCheck').prop('src', './images/i2/okCatch.png'); else \$j('#IBANCheck').prop('src', './images/i2/note.png'); }")." else \$j('#IBANCheck').css('display', 'none');");
		
		$gui->setLabel("KappendixEinzugsermaechtigung","Einzugserm.?");
		$gui->setType("KappendixEinzugsermaechtigung","checkbox");
		$gui->setLabel("KappendixKreditkarte","Kreditkarte");
		$gui->setType("KappendixKreditkarte","select");
		$gui->setType("KappendixIBANMandatDatum", "calendar");
		$gui->setOptions("KappendixKreditkarte", array_keys(self::kreditkarten()), array_values(self::kreditkarten()));
		$gui->setLabel("KappendixKartennummer","Kartennummer");
		$gui->setLabel("KappendixKarteValidUntil","gültig bis");
		$gui->setLabel("KappendixEinzugsermaechtigungAltZBTB","ZahlBed.");
		$gui->setLabel("KappendixIBANMandatDatum", "Mandatsdatum");
		$gui->setLabel("KappendixIBANMandatReferenz", "Mandatsreferenz");
		
		$B = new Button("USt-IdNt überprüfen","./images/i2/okCatch.png");
		$B->type("icon");
		$B->rmePCR("Kappendix", $this->ID, "checkUStIdNr", "", " ");

		$gui->activateFeature("addCustomButton", $this, "UStIdNr", $B);

		$gui->setInputJSEvent("KappendixEinzugsermaechtigung", "onclick", "contentManager.toggleFormFields(!this.checked ? 'hide' : 'show', ['KappendixEinzugsermaechtigungAltZBTB', 'KappendixIBANMandatDatum', 'KappendixIBANMandatReferenz']);");

		$tb = new Textbausteine();
		list($keys,$values) = $tb->getTBs("zahlungsbedingungen","R");
		array_unshift($keys, "0");
		array_unshift($values, "nicht ändern");
		$gui->setType("KappendixEinzugsermaechtigungAltZBTB","select");
		$gui->setOptions("KappendixEinzugsermaechtigungAltZBTB", $keys, $values);
		$gui->setFieldDescription("KappendixEinzugsermaechtigungAltZBTB", "Dieser Textbaustein wird verwendet, wenn eine Einzugsermächtigung erteilt wurde.");
		$gui->setFieldDescription("KappendixIBANMandatReferenz", "Standard: Kundennummer + IBAN");
		if($this->A("KappendixEinzugsermaechtigung") === "0"){
			$gui->setLineStyle("KappendixEinzugsermaechtigungAltZBTB", "display:none;");
			$gui->setLineStyle("KappendixIBANMandatDatum", "display:none;");
			$gui->setLineStyle("KappendixIBANMandatReferenz", "display:none;");
		}
		$gui->setType("KappendixSameKontoinhaber","checkbox");
		$gui->setLabel("KappendixSameKontoinhaber", "Inhaber?");
		$gui->setFieldDescription("KappendixSameKontoinhaber", "Kontoinhaber entspricht Adresse");
		if($this->A("KappendixSameKontoinhaber") == 1)
			$gui->setLineStyle("KappendixKontoinhaber", "display:none;");
		$gui->setInputJSEvent("KappendixSameKontoinhaber", "onclick", "contentManager.toggleFormFields(this.checked ? 'hide' : 'show', ['KappendixKontoinhaber']);");
		#$gui->setFieldDescription("UStIdNr","TIN = Steuernummer");
		
		
		
		
		/*if(isset($bps["mode"]) AND $bps["mode"] == "short") {

			$gui->insertSpaceAbove("KappendixSameKontoinhaber", "Kontodaten", true);
			$gui->insertSpaceAbove("KappendixKreditkarte", "Kreditkartendaten", true);
			$gui->setStandardSaveButton($this, "Adressen", "\$j('#AdresseForm input[name=currentSaveButton]').trigger('click');");
			#$gui->setSaveButtonValues(get_parent_class($this), $this->ID, "Adressen", );#saveAdresseToo();
			$_SESSION["BPS"]->unregisterClass(get_class($this));
			
			$B = new Button("alle Kunden-\ndaten anzeigen","kunden");
			$B->loadFrame("contentLeft", "Kunde", "-1", "0", "KundeGUI;AdresseID:{$this->A->AdresseID};action:Kappendix");
			$B->style("float:right;");
			$B->id("buttonShowAllCustomerData");
		} else*/
			$gui->setStandardSaveButton($this, "Adressen");
		#$gui->setSaveButtonValues(get_parent_class($this), $this->ID, "Adressen");
		
		$gui->customize($this->customizer);
		
		#if(isset($bps["mode"]) AND $bps["mode"] == "short"){
		#	$T = new HTMLTable(1);
		#	$T->addRow($B.$B2);
		#} else {
		$T = new HTMLSideTable("right");
		
		$BA = $T->addButton("Adresse\nanzeigen", "address");
		$BA->loadFrame("contentLeft", "Adresse", $this->getAdresseIDToKundennummer($this->A("kundennummer")));
		
		$B2 = $T->addButton("freie Kunden-\nnummern","empty");
		$B2->onclick("Popup.create('$this->ID', 'Kappendix', 'freie Kundennummern'); contentManager.loadFrame('KappendixDetailsContent$this->ID','KundennummernBelegung');Popup.show('$this->ID', 'Kappendix');");
		
		$B = $T->addButton("Textbausteine", "document");
		$B->popup("", "Textbausteine", "Kappendix", $this->getID(), "popupTextbausteine");
		
		Aspect::joinPoint("sideTable", $this, __METHOD__, $T);
		
		#<input type=\"button\" class=\"bigButton backgroundColor3\" value=\"freie\nKundennummern\" onclick=\"\" />
		return $T.$gui->getEditHTML();
	}
	
	public function popupTextbausteine(){
		$F = new HTMLForm("bank", $this->fieldsTextbausteine);
		
		$F->getTable()->setColWidth(1, 120);
		
		$F->setValues($this);
		
		
		$grlbms = Auftrag::getBelegArten();

		foreach($this->getA() AS $k => $v){
			if(!strpos($k, "TextbausteinOben") AND !strpos($k, "TextbausteinUnten") AND !strpos($k, "Zahlungsbedingungen")) continue;
			
			$F->setType($k,"hidden");
		}


		for($i = 0; $i < count($grlbms); $i++){
			if($this->A($grlbms[$i]."TextbausteinOben") == null) continue;
			
			$F->insertSpaceAbove($grlbms[$i]."TextbausteinOben", "Textbausteine ".Stammdaten::getLongType($grlbms[$i]));
			
			$tb = new Textbausteine();
			list($keys, $values) = $tb->getTBs("oben", $grlbms[$i]);
			$T = array("0" => "Standard verwenden");
			foreach($keys AS $k => $v) $T[$v] = $values[$k];
			
			$F->setLabel($grlbms[$i]."TextbausteinOben","TB oben");
			$F->setType($grlbms[$i]."TextbausteinOben","select", null, $T);
			
			$tb = new Textbausteine();
			list($keys, $values) = $tb->getTBs("unten", $grlbms[$i]);
			$T = array("0" => "Standard verwenden");
			foreach($keys AS $k => $v) $T[$v] = $values[$k];
			
			$F->setLabel($grlbms[$i]."TextbausteinUnten","TB unten");
			$F->setType($grlbms[$i]."TextbausteinUnten","select", null, $T);
			
			
			$tb = new Textbausteine();
			list($keys, $values) = $tb->getTBs("zahlungsbedingungen", $grlbms[$i]);
			$T = array("0" => "Standard verwenden");
			foreach($keys AS $k => $v) $T[$v] = $values[$k];
			
			$F->setLabel($grlbms[$i]."Zahlungsbedingungen","ZahlBed.");
			$F->setType($grlbms[$i]."Zahlungsbedingungen","select", null, $T);
			
			if(Session::isPluginLoaded("mZahlungsart") AND $grlbms[$i] == "R"){
				$F->setType($grlbms[$i]."Zahlungsbedingungen", "parser", null, array("KappendixGUI::parserTBR"));
			}
		}
		
		$F->setSaveClass("Kappendix", $this->getID(), "function(){ ".OnEvent::closePopup("Kappendix")." }");
		
		echo $F;
	}
	
	public static function parserTBR(){
		return "Festgelegt durch Zahlungsart";
	}

	public static function parserProvBaseValue($w, $l, $p){
		$I = new HTMLInput("KappendixProvisionUserBaseValue", "text", $w);
		$I->style("width:60%;text-align:right;");
		
		$CS = Util::getLangCurrencyFormat();
		$IT = new HTMLInput("KappendixProvisionUserBaseType", "select", $p, array("%", $CS[0]));
		$IT->style("width:30%;margin-left:10px;");
		
		return $I.$IT;
	}
	
	public static function kreditkarten($karte = null){
		$K = array("-" => "keine","visa" => "Visa", "mastercard" => "MasterCard", "americanExpress" => "American Express");
		
		if($karte != null)
			return $K[$karte];
		
		return $K;
	}
	
	public function checkUStIdNr($UStIdNr1 = null, $UStIdNr2 = null){
		if($UStIdNr1 == null) {
			$S = mStammdaten::getActiveStammdaten();
			$UStIdNr1 = $S->A("ustidnr");
		}

		if($UStIdNr2 == null)
			$UStIdNr2 = str_replace(array("-"," ","/"), "",trim($this->A("UStIdNr")));

		$errors = array();

		$errors["200"] = "Die angefragte USt-IdNr. ist gültig.";
		$errors["201"] = "Die angefragte USt-IdNr. ist ungültig.";
		$errors["202"] = "Die angefragte USt-IdNr. ist ungültig. Sie ist nicht in der Unternehmerdatei des betreffenden EU-Mitgliedstaates registriert.";
		$errors["203"] = "Die angefragte USt-IdNr. ist ungültig. Sie ist erst ab dem ... gültig (siehe Feld Gueltig_ab).";
		$errors["204"] = "Die angefragte USt-IdNr. ist ungültig. Sie war im Zeitraum von ... bis ... gültig (siehe Feld Gueltig_ab und Gueltig_bis).";
		$errors["205"] = "Ihre Anfrage kann derzeit durch den angefragten EU-Mitgliedstaat oder aus anderen Gründen nicht beantwortet werden. Bitte versuchen Sie es später noch einmal. Bei wiederholten Problemen wenden Sie sich bitte an das Bundeszentralamt für Steuern - Dienstsitz Saarlouis.";
		$errors["206"] = "Ihre deutsche USt-IdNr. ist ungültig. Eine Bestätigungsanfrage ist daher nicht möglich. Den Grund hierfür können Sie beim Bundeszentralamt für Steuern - Dienstsitz Saarlouis - erfragen.";
		$errors["207"] = "Ihnen wurde die deutsche USt-IdNr. ausschliesslich zu Zwecken der Besteuerung des innergemeinschaftlichen Erwerbs erteilt. Sie sind somit nicht berechtigt, Bestätigungsanfragen zu stellen.";
		$errors["208"] = "Für die von Ihnen angefragte USt-IdNr. läuft gerade eine Anfrage von einem anderen Nutzer. Eine Bearbeitung ist daher nicht möglich. Bitte versuchen Sie es später noch einmal. ";
		$errors["209"] = "Die angefragte USt-IdNr. ist ungültig. Sie entspricht nicht dem Aufbau der für diesen EU-Mitgliedstaat gilt.";
		$errors["210"] = "Die angefragte USt-IdNr. ist ungültig. Sie entspricht nicht den Prüfziffernregeln die für diesen EU-Mitgliedstaat gelten. ";
		$errors["211"] = "Die angefragte USt-IdNr. ist ungültig. Sie enthält unzulässige Zeichen.";
		$errors["212"] = "Die angefragte USt-IdNr. ist ungültig. Sie enthält ein unzulässiges Länderkennzeichen.";
		$errors["213"] = "Die Abfrage einer deutschen USt-IdNr. ist nicht möglich.";
		$errors["214"] = "Ihre deutsche USt-IdNr. ist fehlerhaft. Sie beginnt mit DE gefolgt von 9 Ziffern.";
		$errors["215"] = "Ihre Anfrage enthält nicht alle notwendigen Angaben für eine einfache Bestätigungsanfrage (Ihre deutsche USt-IdNr. und die ausl. USt-IdNr.).Ihre Anfrage kann deshalb nicht bearbeitet werden.";
		$errors["216"] = "Ihre Anfrage enthält nicht alle notwendigen Angaben für eine qualifizierte Bestätigungsanfrage (Ihre deutsche USt-IdNr., die ausl. USt-IdNr., Firmenname einschl. Rechtsform und Ort). Es wurde eine einfache Bestätigungsanfrage durchgeführt mit folgenden Ergebnis: Die angefragte USt-IdNr. ist gültig.";
		$errors["217"] = "Bei der Verarbeitung der Daten aus dem angefragten EU-Mitgliedstaat ist ein Fehler aufgetreten. Ihre Anfrage kann deshalb nicht bearbeitet werden.";
		$errors["218"] = "Eine qualifizierte Bestätigung ist zur Zeit nicht möglich. Es wurde eine einfache Bestätigungsanfrage mit folgendem Ergebnis durchgeführt: Die angefragte USt-IdNr. ist gültig.";
		$errors["219"] = "Bei der Durchführung der qualifizierten Bestätigungsanfrage ist ein Fehler aufgetreten. Es wurde eine einfache Bestätigungsanfrage mit folgendem Ergebnis durchgeführt: Die angefragte USt-IdNr. ist gültig.";
		$errors["220"] = "Bei der Anforderung der amtlichen Bestätigungsmitteilung ist ein Fehler aufgetreten. Sie werden kein Schreiben erhalten. ";
		$errors["999"] = "Eine Bearbeitung Ihrer Anfrage ist zurzeit nicht möglich. Bitte versuchen Sie es später noch einmal.";

		if(!ini_get("allow_url_fopen"))
			Red::errorD("Ihr Server unterstützt diese Anfrage nicht. Sie können Sie manuell stellen unter http://evatr.bff-online.de");
		
		$c = file_get_contents("http://evatr.bff-online.de/evatrRPC?UstId_1=$UStIdNr1&UstId_2=$UStIdNr2&Firmenname=&Ort=&PLZ=&Strasse=&Druck=");

		$xml = new SimpleXMLElement($c);

		echo "alert:'".$errors[$xml->param[1]->value->array->data->value[1]->string.""]."'";
	}

	public function getContextMenuHTML($identifier) {
		$T = new HTMLForm("erloeskontoBerechnung", array("default", "addCustomerNumber"));
		$T->cols(1);
		
		$T->setLabel("default", "Standard");
		$T->setLabel("addCustomerNumber", "Kundennummer addieren?");
		$T->setDescriptionField("addCustomerNumber", "Dies aktiviert auch den Kundenkonten-Modus");
		
		$T->setValue("default", mUserdata::getGlobalSettingValue("DVKappendixErloeskonto", "8400"));
		$T->setValue("addCustomerNumber", mUserdata::getGlobalSettingValue("DVKappendixKundenKonto", "0"));
		
		$T->setType("addCustomerNumber", "checkbox");
		$T->setSaveContextMenu($this, "saveErloeskontoDefaults");
		echo $T;
	}
	
	public function saveErloeskontoDefaults($defaultKonto, $addCustomerNumber){
		$F = new Factory("Userdata");
		$F->sA("name", "DVKappendixErloeskonto");
		$F->sA("UserID", "-1");
		$U = $F->exists(true);
		if($U){
			$U->changeA ("wert", $defaultKonto);
			$U->saveMe();
		} else {
			$F->sA("wert", $defaultKonto);
			$F->store();
		}
		
		$F = new Factory("Userdata");
		$F->sA("name", "DVKappendixKundenKonto");
		$F->sA("UserID", "-1");
		$U = $F->exists(true);
		if($U){
			$U->changeA ("wert", $addCustomerNumber);
			$U->saveMe();
		} else {
			$F->sA("wert", $addCustomerNumber);
			$F->store();
		}
	}
	
	public function checkIBAN($IBAN){
		if(parent::checkIBAN($IBAN))
			echo "1";
		else
			echo "0";
	}
}
?>
