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
class GRLBMGUI extends GRLBM implements iGUIHTML2, icontextMenu {
	
	private $kdNummer = "";
	private $ustidnr = "";
	#public $additionalKalkFields = "";
	#public $additionalNonKalkFields = "";
	public $showPrintAB = false;
	public $useDefaultTransport = false;

	function __construct($id){
		if($id == -2)
			$id = $_SESSION["BPS"]->getProperty("GRLBMGUI","myID");

		parent::__construct($id);
	}
	
	public function markMeAsUnprinted(){
		parent::markMeAsUnprinted();
	}
	
	public function setLetterType($type){
		$this->letterType = $type;
	}
	
	public function setIsCopy($copy){
		$this->isCopy = $copy;
	}
	
	public function setKundennummer($kdnr){
		$this->kdNummer = $kdnr;
	}
	
	public function setKundenUStIdNr($ustidnr){
		$this->ustidnr = $ustidnr;
	}
	
	function getHTML($id){
		if($id == -2)
			$id = $_SESSION["BPS"]->getProperty("GRLBMGUI","myID");
			
		#------------------------ einige Einstellungen für die multiPrint-Liste
		$_SESSION["BPS"]->setActualClass("multiPrintBasketGUI");
		$basketBPS = $_SESSION["BPS"]->getAllProperties();
		
		if($basketBPS == -1 OR !isset($basketBPS["ids"])) {
			if($basketBPS == -1) $basketBPS = array();
			$basketBPS["ids"] = "";
		}
		if($basketBPS["ids"] == ",,") $basketBPS["ids"] = "";
		#------------------------ 

		$this->loadMeOrEmpty();

		$gui = new HTMLGUI2();
		$gui->setObject($this);
		$gui->setFormID("GRLBMForm");
		
		/**
		 * DEPRECATED BUT REQUIRED FOR THIS CLASS
		 */
		BPS::setProperty("GRLBMGUI", "myID", $id);
		/**
		 * NEW
		 */
		BPS::setProperty("Brief", "GRLBMID", $id);
		
		$type = $this->getMyPrefix();

		$Auftrag = new Auftrag($this->A("AuftragID"));
		
		#-------- Posten -----------------------------------------
			$postenRechnung = new mPostenGUI();
			$_SESSION["BPS"]->registerClass("mPostenGUI");
			$_SESSION["BPS"]->setACProperty("loadGRLBMID",$this->ID);
			$_SESSION["BPS"]->setACProperty("GRLBMType", $this->getMyPrefix());
			$_SESSION["BPS"]->setACProperty("AuftragID",$this->A->AuftragID);
			$_SESSION["BPS"]->registerClass("PostenGUI");
			$_SESSION["BPS"]->setACProperty("GRLBMType",$this->getMyPrefix());
			#$postenRechnung->setAssociation("GRLBMID",$this->ID);
		#/------- Posten -----------------------------------------
		
			
		// If Plugin Provisionen exists, set some values for it
		if(Session::isPluginLoaded("Provisionen")){
			#$_SESSION["BPS"]->registerClass("ProvisionSwitchGUI");
			#$_SESSION["BPS"]->setACProperty("useGRLBMID",$this->ID);
			#$_SESSION["BPS"]->setACProperty("useAuftragID",$this->A->AuftragID);
			BPS::setProperty("ProvisionSwitchGUI", "useGRLBMID", $this->ID);
			BPS::setProperty("ProvisionSwitchGUI", "useAuftragID", $this->A("AuftragID"));
		}



		$BAB = "";
		$BNO = "";
		if($type == "A" AND $this->A("printAB") == "0" AND !Auftrag::getBelegArten("B")){
			$BAB = new Button("AB\nerstellen", "bestaetigung");
			$BAB->style("float:right;margin-right:10px;");
			$BAB->id("createABButton");
			$BAB->className("backgroundColor2");
			$BAB->onclick("if(confirm('Auftragsbestätigung jetzt erstellen?')) ");
			$BAB->rmePCR("GRLBM", $this->ID, "makeAB", "", "contentManager.loadFrame('subframe','GRLBM', $this->ID);");
			
			$BNO = new Button("Angebot abgelehnt", "x", "iconicL");
			$BNO->style("float:right;margin-right:5px;margin-top:5px;");
			$BNO->rmePCR("Auftrag", $this->A("AuftragID"), "updateStatus", array("'declined'"), "function(){ showMessage('Angebot als abgeleht markiert'); contentManager.reloadFrame('contentRight'); }");
		}
		
		
		if($type == "B" AND ($this->A("GRLBMOrderGRLBMIDs") == "" OR $this->A("GRLBMOrderGRLBMIDs") == "note") AND Session::isPluginLoaded("mLBestellung")){
			$BAB = new Button("Zur Bestellung\nvormerken", "bestellung");
			$BAB->style("float:right;margin-right:10px;");
			$BAB->className("backgroundColor2");
			$BAB->rmePCR("GRLBM", $this->ID, "orderNote", "", "contentManager.loadFrame('subframe','GRLBM', $this->ID);");
			if($this->A("GRLBMOrderGRLBMIDs") == "note")
				$BAB->className("highlight");
		}
		
		if($type == "B" AND $this->A("GRLBMOrderGRLBMIDs") != "" AND $this->A("GRLBMOrderGRLBMIDs") != "note" AND Session::isPluginLoaded("mLBestellung")){
			$ids = explode(",", trim($this->A("GRLBMOrderGRLBMIDs"), ","));
			$belege = "";
			foreach ($ids AS $v){
				$G = new GRLBM($v, false);
				$belege .= ($belege == "" ? "" : ", ").$G->A("prefix").$G->A("nummer");
			}
			$BAB = "<div style=\"width:140px;float:right;margin-right:10px;height:30px;padding:5px;\" class=\"confirm\">Bestellt: $belege</div>";
		}
		
		if($type == "B" AND ($this->A("GRLBMProductionLaufzettel") == "" OR $this->A("GRLBMProductionLaufzettel") == "note") AND Session::isPluginLoaded("mLaufzettel")){
			$BAB = new Button("Zur Produktion\nvormerken", "./upFab/Laufzettel/Laufzettel.png");
			$BAB->style("float:right;margin-right:10px;");
			$BAB->className("backgroundColor2");
			$BAB->rmePCR("GRLBM", $this->ID, "productionNote", "", "contentManager.loadFrame('subframe','GRLBM', $this->ID);");
			if($this->A("GRLBMProductionLaufzettel") == "note")
				$BAB->className("highlight");
		}

		$pSpecData = mUserdata::getPluginSpecificData("Provisionen");

		$newType = $type;
		if($type == "B" AND !Auftrag::getBelegArten("B"))
			$newType = "A";
		
		$weiteres = "Zusätzliche";
		$genus = Stammdaten::getGenusType($newType);
		if($genus == "n")
			$weiteres = "Zusätzliches";
		if($genus == "m")
			$weiteres = "Zusätzlicher";
		
		$B = new Button($weiteres."\n".Stammdaten::getLongType($newType)."", "new");
		$B->onclick("$('".$newType."Button').name = '1'; Auftrag.createGRLBM('{$this->A->AuftragID}','Auftrag','$newType');");
		$B->className("backgroundColor0");
		$B->id("createNew{$newType}Button");
		$B->style("margin-left:0px;");
		
		$BP = "";
		if($type == "R" AND Session::isPluginLoaded("Provisionen") AND isset($pSpecData["pluginSpecificCanGiveProvisions"])){
			$BP = new Button("Provisionen\nvergeben", "provision");
			$BP->className("backgroundColor0");
			$BP->onclick("contentManager.loadFrame('contentLeft','ProvisionSwitch');");
			$BP->style("float:right;margin-right:10px;");
		}

		$T = "";
		if($type != "O" AND $type != "P") //dont show buttons when order or price request
			$T .= "<div style=\"padding-left:10px;padding-top:10px;padding-bottom:30px;\" class=\"AuftragBelegContent backgroundColor4\">$BAB$BNO$BP$B</div>";
		


		$belegButtons = array(
			"original" => true,
			"kopie" => true,
			"drucken" => true,
			"pdfdrucken" => true,
			"preview" => true,
			"signieren" => true,
			"email" => true,
			"termin" => true,
			"multiDruck" => true,
			"esr" => true,
			"etiketten" => true,
			"file" => true,
			"3rd" => false,
			"ugl" => true,
			"aufgabe" => true);
		
		$belegButtons = Aspect::joinPoint("belegButtons", $this, __METHOD__, $belegButtons, $belegButtons);
		$below = "";
		
		$Tab = new HTMLSideTable("right");
		#$Tab->setTableStyle("width:180px;margin:0px;margin-right:-190px;float:right;margin-top:60px;");

		Aspect::joinPoint("sideTableTop", $this, __METHOD__, array($Tab));
		
		#if(Session::isPluginLoaded("mPDFJS") AND $belegButtons["preview"])
		#	$Tab->addRow(PDFJS::getButtonGRLBM($this->A("AuftragID"), $this->getID()));

		$BPDFK = new Button("Original", "pdf");
		$BPDFK->windowRme("Auftrag", $this->A("AuftragID"), "getGRLBMPDF", array("'false'", "''", $this->getID()), "_Brief;templateType:PDF");
		$BPDFK->id("showOriginalButton");
		if($belegButtons["original"])
			$Tab->addRow($BPDFK);
		

		$BPDFKS = new Button("Einstellungen", "wrench", "iconic");
		$BPDFKS->className("buttonSettings iconicG");
		$BPDFKS->onclick("phynxContextMenu.start(this, 'GRLBM','1','Kopie:','right','up');");

		$BPDFK = new Button("Kopie", "./open3A/Auftraege/pdfKopie.png");
		$BPDFK->windowRme("Auftrag", $this->A("AuftragID"), "getGRLBMPDF", array("'true'", "''", $this->getID()), "_Brief;templateType:PDF");
		$BPDFK->id("showCopyButton");
		if($belegButtons["kopie"])
			$Tab->addRow($BPDFKS.$BPDFK);
		
		
		$BPDF = new Button("Original\ndrucken", "printer", "MPBig");
		$BPDF->doBefore("\$j(this).addClass('confirm'); %AFTER");
		$BPDF->windowRme("Auftrag", $this->A("AuftragID"), "getGRLBMPDF", array("'false'", "''", $this->getID(), 1), "_Brief;templateType:PDF");
		$BPDF->id("buttonPrintOriginal");
		
		if($this->A("isPrinted") > 0)
			$BPDF->addClass ("confirm");
		
		$BPDFK = new Button("Kopie\ndrucken", "printer", "LPBig");
		$BPDFK->doBefore("\$j(this).addClass('confirm'); %AFTER");
		$BPDFK->windowRme("Auftrag", $this->A("AuftragID"), "getGRLBMPDF", array("'true'", "''", $this->getID(), 1), "_Brief;templateType:PDF");
		$BPDFK->id("buttonPrintCopy");
		#$BPDFK->style("float:right;margin-right:3px;");
		if($this->A("isPrintedCopy") > 0)
			$BPDFK->addClass ("confirm");
		
		if($belegButtons["pdfdrucken"])
			$Tab->addRow($BPDF.$BPDFK.(($this->A("isPrinted") == "1" AND $this->A("isPrintedTime") != "0") ? "<br /><small style=\"color:grey;\">".Util::CLDateTimeParser($this->A("isPrintedTime"))."</small>" : ""));
		
		$BPDFKS = new Button("Einstellungen", "wrench", "iconic");
		$BPDFKS->className("buttonSettings iconicG");
		$BPDFKS->onclick("phynxContextMenu.start(this, 'GRLBM','3','Kopie:','right','up');");
		
		$BPDFK = new Button("Buchhaltung", "./open3A/Auftraege/pdfKopie.png");
		$BPDFK->windowRme("Auftrag", $this->A("AuftragID"), "getGRLBMPDF", array("'true'", "''", $this->getID()), "_Brief;templateType:PDF3rd");
		$BPDFK->id("show3rdButton");
		if($belegButtons["3rd"])
			$Tab->addRow($BPDFKS.$BPDFK);
		
		if(Session::isPluginLoaded("mGaeb") AND $belegButtons["ugl"] AND in_array($this->getMyPrefix(), array("P", "O")))
			$Tab->addRow (mGaebGUI::getUGLButton($this->getID()));
		
		if(Session::isPluginLoaded("mESR") AND $belegButtons["esr"])
			$Tab->addRow(mESRGUI::getButton($this->getID()));
		
		
		if(Session::isPluginLoaded("mDrucker") AND $belegButtons["drucken"])
			$Tab->addRow(DruckerWindowGUI::getButton("Auftrag", $this->A("AuftragID"), "printLetter", $this->getID(), true, $this->A("isPrinted") == "1"));
		


		$eSig = "";
		if(Session::isPluginLoaded("SP") AND ($type == "R" OR $type == "G")) {
			$BPL = new Button("versenden mit\nsignaturportal","./open3A/signaturportal/sp.png");
			$BPL->onclick("Auftrag.windowMail(".$this->A->AuftragID.", '".$this->getID()."','sign');");#plSign('{$this->A->AuftragID}');

			$SPL = new Button("Einstellungen","wrench", "iconic");
			$SPL->style("float:right;");
			$SPL->className("mouseoverFade iconicG");
			$SPL->onclick("phynxContextMenu.start(this, 'SP','1','Einstellungen:','right','up');");

			$eSig = $BPL.$SPL;
		}

		if(Session::isPluginLoaded("PL") AND ($type == "R" OR $type == "G")) {
			#$BPL = new Button("mit PixelLetter\nsignieren","./open3A/PixelLetter/pl.png");
			#$BPL->onclick("Auftrag.windowMail(".$this->A->AuftragID.", '".$this->getID()."','sign');");

			$BPL = PLGUI::getButton($this->A->AuftragID, $this->getID());
			
			$SPL = new Button("Einstellungen","wrench", "iconic");
			$SPL->className("buttonSettings iconicG");
			$SPL->onclick("phynxContextMenu.start(this, 'PL','1','Einstellungen:','right','up');");

			
			
			#$BPM = new Button("mit PixelLetter\nverschicken","./open3A/PixelLetter/pl.png");
			#$BPM->rmePCR("Auftrag", $this->A("AuftragID"), "sendViaMail", array($this->getID(), 1));
			#$Tab->addRow(array($BPM));
			
			if($this->A("isPixelLetteredTime") > 0)
				$BPL->addClass("confirm");
			
			$eSig = $BPL.(($this->A("isPixelLetteredTime") > 0) ? "<br /><small style=\"color:grey;\">".Util::CLDateTimeParser($this->A("isPixelLetteredTime"))."</small>" : "");#.$SPL;
		}

		if($eSig != "" AND $belegButtons["signieren"])
			$Tab->addRow(array($eSig));
		
		/*$AnsprechpartnerID = "0";
		if(Session::isPluginLoaded("mAnsprechpartner")){
			$ARecipient = Ansprechpartner::getAnsprechpartner("Adresse", $Auftrag->A("kundennummer"), $this->getMyPrefix());
			if($ARecipient != null)
				$AnsprechpartnerID = $ARecipient->getID();
		}*/

		$BMail = new Button("per E-Mail\nverschicken","mail");
		$BMail->onclick("Auftrag.windowMail(".$this->A->AuftragID.", '".$this->getID()."','E-Mail', \$j('[name=GRLBMAnsprechpartnerID]').val());");
		$BMail->id("sendViaEmailButton");
		if($this->A("isEMailed"))
			$BMail->addClass ("confirm");
		
		$BMailS = new Button("Einstellungen","wrench", "iconic");
		$BMailS->className("buttonSettings iconicG");
		$BMailS->onclick("phynxContextMenu.start(this, 'GRLBM','viaMail','E-Mail:','right','up');");

		if($belegButtons["email"])
			$Tab->addRow($BMailS.$BMail.(($this->A("isEMailed") == "1" AND $this->A("isEMailedTime") != "0") ? "<br /><small style=\"color:grey;\">".Util::CLDateTimeParser($this->A("isEMailedTime"))."</small>" : ""));

		if(Session::isPluginLoaded("mFile") AND $belegButtons["file"]){
			$BFiles = mFileGUI::getManagerButton("GRLBM", $this->getID(), true);
			$BFiles->style("float:right;");
			
			
			$below .= $BFiles;
		}
		
		$BAddMP = new Button("zur multiDruck-\nListe hinzufügen", "./images/navi/addtolist.png");
		$BAddMP->id("GRLBMAddToMultiPrintList");
		$BAddMP->style(((!strstr($basketBPS["ids"],",".$this->ID.",") OR $basketBPS["ids"] == "") ? "":"display:none;")."float:left;");
		$BAddMP->onclick("addToMultiPrintAusAuftrag($this->ID);");

		$BDelMP = new Button("von multiDruck-\nListe entfernen", "./images/navi/subfromlist.png");
		$BDelMP->id("GRLBMSubFromMultiPrintList");
		$BDelMP->style(((!strstr($basketBPS["ids"],",".$this->ID.",") OR $basketBPS["ids"] == "") ? "display:none;":"")."float:left;");
		$BDelMP->onclick("subFromMultiPrintAusAuftrag($this->ID);");

		if($belegButtons["multiDruck"])
			$below .= $BAddMP.$BDelMP;
		
		
		if(Session::isPluginLoaded("mEtikette") AND $belegButtons["etiketten"] AND !Session::isPluginLoaded("mBestellung")) //Now in Bestellungen!
			$Tab->addRow(Etikette::getButton("GRLBM", $this->getID()));

		if(Session::isPluginLoaded("mAufgabe") AND $Auftrag->A("kundennummer") > 0)
			$Tab->addRow(Aufgabe::getButton("WAdresse", Kappendix::getAdresseIDToKundennummer($Auftrag->A("kundennummer")), "GRLBM", $this->getID()));
		
		
		if($this->useDefaultTransport)
			$Tab->addRow(CustomizerStandardversandGUI::getButton($this));
		
		$gui->setName(Stammdaten::getLongType($type));

		if($this->A("isPayed") AND $type != "B")
			$gui->setIsDisplayMode(true);

		$fields = array();
		
		$Stammdaten = mStammdaten::getActiveStammdaten();
		$CurrentVorlage = $Stammdaten->A("ownTemplate");
		if($Auftrag->A("AuftragVorlage") != "")
			$CurrentVorlage = $Auftrag->A("AuftragVorlage");
		$ErrorVorlage = "";
		try {
			if($CurrentVorlage == "")
				throw new ClassNotFoundException();
			
			$CurrentVorlage = new $CurrentVorlage($Stammdaten);
		} catch (ClassNotFoundException $e){
			$ErrorVorlage = "<div class=\"error AuftragBelegContent\" style=\"padding:10px;padding-right:0px;\">Bitte überprüfen Sie Ihre Stammdaten, die ausgewählte Vorlage ($CurrentVorlage) konnte nicht gefunden werden.</div>";
			$CurrentVorlage = new Vorlage_any($Stammdaten);
		}
		
		for($i = 1; $i < 20; $i++){
			$labelName = "labelCustomField$i";

			if(isset($CurrentVorlage->$labelName) AND $CurrentVorlage->$labelName != null){
				$fields[] = "GRLBMCustomField$i";
				$gui->setLabel("GRLBMCustomField$i", $CurrentVorlage->$labelName);
			}
		}

		$fields = array_merge($fields, array(/*"isAbschlussrechnung", */"lieferAdresseID", "nummer", "datum", "lieferDatum", "textbausteinObenID", "textbausteinUntenID", "zahlungsbedingungenID", "rabatt", "rabattInW", "leasingrate"));

		if($this->A("GRLBMReferenz") != ""){
			array_unshift($fields, "GRLBMReferenznummer");
			$gui->setType("GRLBMReferenznummer", "readonly");
			$gui->setLabel("GRLBMReferenznummer", "Referenz");
		}
		
		if(Session::isPluginLoaded("mAnsprechpartner")){
			$ACAP = Ansprechpartner::getAllAnsprechpartner($Auftrag->A("kundennummer"));
			$ansprechpartner = array(0 => "Standard");
			while($AP = $ACAP->getNextEntry())
				$ansprechpartner[$AP->getID()] = trim($AP->A("AnsprechpartnerPosition").(trim($AP->A("AnsprechpartnerVorname")." ".$AP->A("AnsprechpartnerNachname")) != "" ? " (".trim($AP->A("AnsprechpartnerVorname")." ".$AP->A("AnsprechpartnerNachname")).")" : ""));
			
			if(count($ansprechpartner) > 1){
				array_unshift($fields, "GRLBMAnsprechpartnerID");
				$gui->setType("GRLBMAnsprechpartnerID", "select");
				$gui->setOptions("GRLBMAnsprechpartnerID", array_keys($ansprechpartner), array_values($ansprechpartner));
				$gui->setLabel("GRLBMAnsprechpartnerID", "Ansprechpartner");

				$gui->activateFeature("addCustomButton", $this, "GRLBMAnsprechpartnerID", Ansprechpartner::getButtonSmall("Adresse", Kappendix::getAdresseIDToKundennummer($Auftrag->A("kundennummer")), "{}", "Ansprechpartner;GRLBMID:".$this->getID().""));
			}
		}

		#$gui->setType("isAbschlussrechnung", "checkbox");
		$gui->setType("lieferAdresseID", "hidden");
		$gui->setType("datum", "calendar");
		#$gui->setType("lieferDatum", "calendar");
		
		#$gui->setInputJSEvent("isAbschlussrechnung", "onclick", "alert('Dieses Häkchen wird in einer zukünftigen Version deaktiviert, bitte informieren Sie sich über die neue Funktionsweise der Abschlussrechnungen.');");
		
		$gui->setParser("lieferDatum", "GRLBMGUI::parserLieferDatum", array($this->A("lieferDatumText")));
		
		#if(!Session::isPluginLoaded("Abschlussrechnung") OR $newType != "R")
		#	$gui->setType("isAbschlussrechnung", "hidden");

		if($newType == "L" OR $newType == "R"){
			$gui->setType("lieferAdresseID", "text");
			$gui->setParser("lieferAdresseID", "GRLBMGUI::lieferAdresseParser", array($this->ID));
		}
		
		if(Session::isPluginLoaded("mTodo") AND Session::isPluginLoaded("mKalender")){
			$gui->setType("datum", "text");
			$gui->setParser("datum", "GRLBMGUI::parserDatumTermin", array($this->ID, $Auftrag->A("AdresseID"), Util::CLDateParser($this->A("datum"), "store")));
		}
		
		if($newType != "R" AND $newType != "A" AND $newType != "B" AND $newType != "P" AND $newType != "0")
			$gui->setType("lieferDatum", "hidden");

		if($newType != "Kalk"){
			$gui->setType("rabatt", "hidden");
			$gui->setType("rabattInW", "hidden");
			$gui->setType("leasingrate", "hidden");

			$gui->setParser("textbausteinObenID", "GRLBMGUI::textbausteinParser", array("oben", $this->getMyPrefix(), $this->A("textbausteinOben"), $this->A("isPayed")));
			$gui->setParser("textbausteinUntenID", "GRLBMGUI::textbausteinParser", array("unten", $this->getMyPrefix(), $this->A("textbausteinUnten"), $this->A("isPayed")));
			$gui->setParser("zahlungsbedingungenID", "GRLBMGUI::textbausteinParser", array("zahlungsbedingungen", $this->getMyPrefix(), $this->A("zahlungsbedingungen"), $this->A("isPayed")));
		} else {
			$gui->setType("textbausteinObenID", "hidden");
			$gui->setType("textbausteinUntenID", "hidden");
			$gui->setType("zahlungsbedingungenID", "hidden");
		}

		$gui->setShowAttributes($fields);

		if(Session::isPluginLoaded("mZahlungsart")){
			$gui->insertSpaceAbove("GRLBMpayedVia");
			
			$gui->setLabel("GRLBMpayedVia", "Zahlungsart");
			$gui->insertAttribute("before", "zahlungsbedingungenID", "GRLBMpayedVia");
			$gui->setType("GRLBMpayedVia", "select");
			$Z = GRLBM::getPaymentVia();
			
			$if = "";
			
			foreach($Z AS $k => $ZA){
				$TB = Zahlungsart::getTB($k);
				if($TB != null){
					$if .= "if(this.value == '$k') \$j('[name=zahlungsbedingungenID]').val('".$TB->getID()."').trigger('change');";
					$Z[$k] = $ZA." (TB: ".$TB->A("label").")";
				} else
					$Z[$k] = $ZA." (kein TB)";
			}
			
			$gui->setOptions("GRLBMpayedVia", array_keys($Z), array_values($Z));
			
			$gui->setInputJSEvent("GRLBMpayedVia", "onChange", $if);
		}
		
		$gui->setLabel("isAbschlussrechnung", "Abschlussre.");
		$gui->setLabel("lieferAdresseID", "Belegadresse");
		$gui->setLabel("lieferDatum", "Lieferdatum");
		$gui->setLabel("rabattInW", "Rabatt");
		$gui->setLabel("leasingrate", "Leasingfaktor");
		$gui->setLabel("textbausteinObenID", "TB oben");
		$gui->setLabel("textbausteinUntenID", "TB unten");
		$gui->setLabel("zahlungsbedingungenID", "Zahlungsbed.");

		$gui->setLabelDescription("rabatt", "in %");
		$gui->setLabelDescription("rabattInW", "in €");
		$gui->setLabelDescription("leasingrate", "in %");


		$gui->setJSEvent("onSave", "function(){}");
		$gui->setStandardSaveButton($this);

		$gui->customize($this->customizer);


		$TN = new HTMLTable(1);
		$TN->setTableID("belegAchtungTabelle");
		$BN = new Button("Achtung", "notice", "icon");
		$BN->style("float:left;margin-right:10px;margin-bottom:15px;");
		
		if($this->A("isAbschlussrechnung"))
			$TN->addRow("$BN Dies ist eine <b>Schlussrechnung</b>.<br />Die Abschlags- oder Teilrechnungen aus diesem Auftrag werden automatisch abgezogen.");
		
		if($this->A("isAbschlagsrechnung"))
			$TN->addRow("$BN Dies ist eine <b>Abschlagsrechnung</b>.<br />Sie wird automatisch von einer Abschlussrechnung in diesem Auftrag abgezogen.");
		
		$html = "<div class=\"prettySubtitle\" style=\"margin-top:30px;\">Beleg</div><div style=\"\">$TN".$Tab.$gui->getEditHTML()."</div>";

		$TabBelow = "";
		if($below != ""){
			$TabBelow = new HTMLTable(1);
			$TabBelow->setTableStyle("margin-top:30px;");
			$TabBelow->setColClass(1, "");
			$TabBelow->addRow($below);
		}
		/*if(Session::isPluginLoaded("mFile")){
			$D = new mDateiGUI();
			$D->classID = $this->ID;
			$D->className = "GRLBM";
			$D->onAddClass = "Auftrag";
			$D->onDeleteFunction = "function() { new Ajax.Request('./interface/loadFrame.php?p=GRLBM&id=-2', {onSuccess: function(transport){if(checkResponse(transport)) $('subframe').update(transport.responseText);}}); }";
		}*/
		
		return $T.$ErrorVorlage."<div style=\"border-right:1px solid #eee;padding-right:9px;\" class=\"AuftragBelegContent\">"."<div id=\"AuftragBeleg\">".$postenRechnung->getHTML(-1,$this->A->AuftragID,"",$this->ID)."</div><div style=\"width:430px;\">".$html.$TabBelow."</div>".OnEvent::script("Auftrag.reWidth();")."</div>";#($type != "Kalk" ? $html : "");
	}

	public static function parserDatumTermin($w, $l, $p){
		$p = HTMLGUI::getArrayFromParametersString($p);
		$Termin = anyC::get("Todo", "TodoClass", "GRLBM");
		$Termin->addAssocV3("TodoClassID", "=", $p[0]);
		$Termin = $Termin->getNextEntry();

		$ort = "";
		if($p[1] != "0"){
			$Adresse = new Adresse($p[1]);
			$ort = str_replace("\n", ", ", $Adresse->getFormattedAddress());
		}

		$BTermin = new Button("Termin\n".($Termin == null ? "hinzufügen" : "anzeigen"), "./ubiquitous/Kalender/".($Termin == null ? "add" : "has")."ToDo.png", "icon");
		if($Termin == null)
			$BTermin->popup("", "Neuer Termin", "mKalender", "-1", "newTodo", array("-1", $p[2], "'GRLBM'", $p[0], "''", "''", "'$ort'"));
		else
			$BTermin->popup ("", "Event", "mKalender", "-1", "getInfo", array("'mTodoGUI'", $Termin->getID(), $p[2]));
		$BTermin->style("float:right;margin-left:10px;");
		
		$I = new HTMLInput("datum", "date", $w);
		$I->style("width:80%;");
		
		return $BTermin.$I;
			#$Tab->addRow($BTermin);
		
	}
	
	// <editor-fold defaultstate="collapsed" desc="textbausteinParser">
	public static function textbausteinParser($w, $l, $p){
		$s = HTMLGUI::getArrayFromParametersString($p);

		switch($s[0]){
			case "oben":
				$TBF = "textbausteinOben";
				$resize = "$('textbausteinUnten').style.height='18px';$('zahlungsbedingungen').style.height='18px';";
			break;
			case "unten":
				$TBF = "textbausteinUnten";
				$resize = "$('textbausteinOben').style.height='18px';$('zahlungsbedingungen').style.height='18px';";
			break;
			case "zahlungsbedingungen":
				$TBF = "zahlungsbedingungen";
				$resize = "$('textbausteinOben').style.height='18px';$('textbausteinUnten').style.height='18px';";
			break;
		}


		$To = array();
		try {
			$Tbos = new Textbausteine();
			$Tbos = $Tbos->getTBs($s[0], $s[1]);

			if($Tbos)
				foreach($Tbos[0] As $key => $value)
					$To[$value] = $Tbos[1][$key];
		} catch (FieldDoesNotExistException $e){ }

		$To["0"] = "Freitext...";

		$IS = new HTMLInput($TBF."ID", "select", $w, $To);
		#$IS->setOptions($To);
		$IS->onchange("if(this.value=='0') \$j('#{$TBF}Edit').show(); else \$j('#{$TBF}Edit').hide();");
		#$IS->onchange("if(this.value=='0') $('$TBF').style.display = ''; else $('$TBF').style.display = 'none';");
		$IS->isDisplayMode($s[3] == "1" AND $s[1] != "B");
		
		$IT = new HTMLInput($TBF, "nicEdit", $s[2], array("GRLBMForm", $TBF, "Auftrag.availabeTBSelection"));
		/*
		$IT = new HTMLInput($TBF, "textarea", $s[2]);
		$IT->style("margin-top:5px;".($w != "0" ? "display:none;" : "")."");
		$IT->id($TBF);
		$IT->setClass("resizableTextarea");
		$IT->hasFocusEvent(true);
		$IT->onfocus("focusMe(this); this.style.height='80px';$resize");*/
		
		return $IS."<div id=\"{$TBF}Edit\" style=\"margin-top:5px;".($w != "0" ? "display:none;" : "")."\">".$IT."</div>";
/*
		$IS = new HTMLInput("GRLBMTextbausteinServiceID", "select", $w, $To);

		$IS->onchange("if(this.value=='0') \$j('#GRLBMTextbausteinServiceEdit').show(); else \$j('#GRLBMTextbausteinServiceEdit').hide();");
		$IS->isDisplayMode($s[3] == "1" AND $s[1] != "B");
		
		$IT = new HTMLInput("GRLBMTextbausteinService", "nicEdit", $s[2], array("GRLBMForm", "GRLBMTextbausteinService"));
		
		return $IS."<div id=\"GRLBMTextbausteinServiceEdit\" style=\"margin-top:5px;".($w != "0" ? "display:none;" : "")."\">".$IT."</div>";*/
	}
	// </editor-fold>

	// <editor-fold defaultstate="collapsed" desc="lieferAdresseParser">
	public static function lieferAdresseParser($w, $l, $p){
		$s = HTMLGUI::getArrayFromParametersString($p);

		$BD = new Button("Belegadresse löschen", "./images/i2/delete.gif");
		$BD->style("float:right;margin-left:10px;");
		$BD->type("icon");
		$BD->onclick("rme('GRLBM','$s[0]','getLieferscheinAdresseCopy','0','contentManager.loadFrame(\'subframe\', \'GRLBM\', \'$s[0]\');');");

		$B1 = new Button("1x-Belegadresse verwenden", "./open3A/Auftraege/1xlieferAdresse.png");
		$B1->style("float:right;margin-left:10px;");
		$B1->type("icon");
		$B1->editInPopup("AuftragAdresse", "-1", "Adresse anlegen", "AuftragAdresseGUI;AuftragID:$s[0];displayMode:lieferAdresse");
		#$B1->onclick("contentManager.loadFrame('contentRight','Adresse', -1, 0,'AdresseGUI;AuftragID:$s[0];displayMode:lieferAdresse');");

		$BC = new Button("Belegadresse ändern", "./open3A/Auftraege/lieferAdresse.png");
		$BC->style("float:right;");
		$BC->type("icon");
		$BC->onclick("contentManager.loadFrame('contentRight','Adressen', -1, 0,'AdressenGUI;selectionMode:singleSelection,GRLBM,$s[0],getLieferscheinAdresseCopy,Auftraege,subframe,GRLBM,$s[0]');");
		
		return ($w != 0 ? $BD : "").$B1.$BC.($w == "0" ? "wie Auftragsadresse" : new Adresse($w));
	}
	// </editor-fold>

	public function getContextMenuHTML($identifier){
		
		$options = explode(":",$identifier);
		$identifier = $options[0];
		
		switch($identifier) {
			case "3":
				$gui = new HTMLGUI();
				$aSd = mStammdaten::getActiveStammdaten();
				
				$selectedKey = mUserdata::getUDValueS("activePDF3rdTemplate", $aSd->A("ownTemplate"));
				
				$FB = new FileBrowser();
				$FB->addDir("../open3A/Brief/");
				$FB->addDir("../specifics/");
				if(FileStorage::getFilesDir() != Util::getRootPath()."specifics/")
					$FB->addDir(FileStorage::getFilesDir());
				$a = $FB->getAsLabeledArray("iVorlage",".class.php",true);
				echo "<div style=\"max-height:400px;overflow:auto;\">";
				echo $gui->getContextMenu(array_flip($a), "GRLBM", "3", $selectedKey);
				echo "</div>";
			break;
		
			case "1":
				$gui = new HTMLGUI();
				$aSd = mStammdaten::getActiveStammdaten();
				
				$selectedKey = mUserdata::getUDValueS("activePDFCopyTemplate", $aSd->A("ownTemplate"));
				
				$FB = new FileBrowser();
				$FB->addDir("../open3A/Brief/");
				$FB->addDir("../specifics/");
				if(FileStorage::getFilesDir() != Util::getRootPath()."specifics/")
					$FB->addDir(FileStorage::getFilesDir());
				$a = $FB->getAsLabeledArray("iVorlage",".class.php",true);
				echo "<div style=\"max-height:400px;overflow:auto;\">";
				echo $gui->getContextMenu(array_flip($a), "GRLBM", "1", $selectedKey);
				echo "</div>";
				echo "<br />";
				
				#$ud = new mUserdata();
				$selectedKey = mUserdata::getUDValueS("activePDFCopyVermerk", "true");
				
				#if($aT == null){
				#	$selectedKey = "true";
				#} else $selectedKey = $aT;
				
				$kAL = array();
				$kAL["true"] = "mit Kopie-Vermerk";
				$kAL["false"] = "ohne Kopie-Vermerk";
				
				echo $gui->getContextMenu($kAL, "GRLBM", "2", $selectedKey);
			break;
			
			case "viaMail":
				echo "
				<div style=\"margin:1px;padding:3px;font-weight:bold;\" class=\"backgroundColor2\">BCC an ".$_SESSION["S"]->getCurrentUser()->getA()->UserEmail."?:</div>";

				$gui = new HTMLGUI();
				#$ud = new mUserdata();
				$selectedKey = mUserdata::getUDValueS("BCCToUser", "false");
				
				$kAL = array();
				$kAL["false"] = "Nein";
				$kAL["true"] = "Ja";
				
				echo $gui->getContextMenu($kAL, "GRLBM", "BCCToUser", $selectedKey);
						
				echo "<div style=\"margin:1px;margin-top:10px;padding:3px;font-weight:bold;\" class=\"backgroundColor2\">Absender:</div>";
				$Stammdaten = mStammdaten::getActiveStammdaten();
				
				#$ud = new mUserdata();
				$selectedKey = mUserdata::getUDValueS("sendBelegViaEmailSender", "firm");
				
				$kAL = array();
				$kAL["firm"] = "Firma (".$Stammdaten->A("email").")";
				$kAL["user"] = "Benutzer (".$_SESSION["S"]->getCurrentUser()->A("UserEmail").")";
				
				echo $gui->getContextMenu($kAL, "GRLBM", "mailSender", $selectedKey);
				
				echo "<div style=\"margin:1px;margin-top:10px;padding:3px;font-weight:bold;\" class=\"backgroundColor2\">Datei-Anhänge:</div>";

				if(Session::isPluginLoaded("mFile")){
					#$ud = new mUserdata();
					$selectedKey = mUserdata::getUDValueS("sendBelegViaEmailAttachments", "false");
					
					$kAL = array();
					$kAL["true"] = "mit";
					$kAL["false"] = "ohne";
					
					echo $gui->getContextMenu($kAL, "GRLBM", "mailAttachments", $selectedKey);
				}
				
				$MailServer = LoginData::get("MailServerUserPass");
				if($MailServer != null AND $MailServer->A("server") != ""){
					echo "<div style=\"margin:1px;margin-top:10px;padding:3px;font-weight:bold;\" class=\"backgroundColor2\">Übermittlungsstatus (DSN) anfordern:</div>";

					$selectedKey = mUserdata::getUDValueS("sendBelegViaEmailDSN", "false");

					$kAL = array();
					$kAL["true"] = "ja";
					$kAL["false"] = "nein";

					echo $gui->getContextMenu($kAL, "GRLBM", "useDSN", $selectedKey);
				}
				
				echo "<div style=\"margin:1px;margin-top:10px;padding:3px;font-weight:bold;\" class=\"backgroundColor2\">Bei Mahnung:</div>";
				
				$kAL = array();
				$kAL["false"] = "Keine Rechnung anhängen";
				$kAL["true"] = "Rechnung anhängen";
				
				echo $gui->getContextMenu($kAL, "GRLBM", "mailAttachInvoice", mUserdata::getUDValueS("sendBelegViaEmailAttachInvoice", "false"));
			break;
			
			case "setPayed":
				$pSpecData = mUserdata::getPluginSpecificData("Auftraege");
				if(!isset($pSpecData["pluginSpecificCanSetPayed"])) 
					die("<p><small>Sie können keine Rechnungen als bezahlt markieren.<br /><br />Damit Sie Rechnungen als bezahlt markieren können, muss im Administrations-Bereich eine Plugin-spezifische Berechtigung gesetzt werden. Wählen Sie dazu den Benutzer aus, klicken Sie auf \"Plugin-spezifisch\". Wählen Sie Aufträge und \"kann Rechnungen als bezahlt markieren\".</small></p>");
				
				$G = new GRLBM($options[2]);
				$G->loadMe();
				
				$tab = new HTMLTable(1);
				
				if(isset($G->getA()->GRLBMTeilzahlungen) AND $this->CustomizerTeilzahlungen) {
					$tab->addRow("<input type=\"checkbox\" onclick=\"Auftrag.showTeilzahlung();\" id=\"isTeilzahlung\" style=\"float:left;margin-right:5px;\" /> <label style=\"text-align:left;font-weight:normal;float:none;\" for=\"isTeilzahlung\">Teilzahlung</label>");
					$tab->addRowClass("backgroundColor0");
					
					$tab->addRow("Betrag: <input id=\"GRLBMTeilzahlungenBetrag\" type=\"text\" style=\"text-align:right;width:100px;\" value=\"".Util::CLFormatCurrency(0,false)."\" onkeydown=\"if(event.keyCode == 13) Auftrag.nowSetRechnungPayed('$options[1]', '$options[2]');\" />");
					$tab->addCellStyle(1,"display:none;");
					$tab->addCellID(1, "GRLBMTeilzahlungenBetragCell");
				}
				
				$tab->addRow("mit Skonto: <input id=\"withSkonto\" type=\"text\" value=\"\" style=\"text-align:right;width:50px;\" onkeydown=\"if(event.keyCode == 13) Auftrag.nowSetRechnungPayed('$options[1]', '$options[2]');\" /> %");
				$tab->addCellID(1, "markAsPayedSkonto");
				$tab->addRowClass("backgroundColor3");
				
				$ID = new HTMLInput("withDate", "date", Util::CLDateParser(time()));
				$ID->id("withDate");
				$ID->style("width:50%;text-align:right;");
				$ID->onkeydown("if(event.keyCode == 13) Auftrag.nowSetRechnungPayed('$options[1]', '$options[2]');");
				$tab->addRow("Datum: $ID");
				#$tab->addRow("Datum: <input id=\"withDate\" type=\"text\" style=\"text-align:right;width:100px;\" value=\"".date("d.m.Y")."\" onkeydown=\"if(event.keyCode == 13) Auftrag.nowSetRechnungPayed('$options[1]', '$options[2]');\" />");
				$tab->addRowClass("backgroundColor3");
				
				$tab->addRow("<input style=\"background-image:url(./images/i2/save.gif);\" type=\"button\" value=\"markieren\" onclick=\"Auftrag.nowSetRechnungPayed('$options[1]', '$options[2]');\" />");
				
				
				echo $tab;
			break;
		}

	}

	// <editor-fold defaultstate="collapsed" desc="saveContextMenu">
	public function saveContextMenu($identifier, $key){
		$ud = new mUserdata();
		
		if($identifier == "1")
			$ud->setUserdata("activePDFCopyTemplate",$key);
		
		if($identifier == "3")
			$ud->setUserdata("activePDF3rdTemplate",$key);
		
		if($identifier == "2")
			$ud->setUserdata("activePDFCopyVermerk",$key);
		
		if($identifier == "BCCToUser")
			$ud->setUserdata("BCCToUser",$key);
		
		if($identifier == "viaMail")
			$ud->setUserdata("sendBelegCopyViaEmail",$key);
		
		if($identifier == "mailBetreff")
			$ud->setUserdata("sendBelegViaEmailBetreff",$key);
		
		if($identifier == "mailText")
			$ud->setUserdata("sendBelegViaEmailText",$key);
		
		if($identifier == "mailSender")
			$ud->setUserdata("sendBelegViaEmailSender",$key);
		
		if($identifier == "mailAttachments")
			$ud->setUserdata("sendBelegViaEmailAttachments",$key);
		
		if($identifier == "useDSN")
			$ud->setUserdata("sendBelegViaEmailDSN",$key);
		
		if($identifier == "mailAttachInvoice")
			$ud->setUserdata("sendBelegViaEmailAttachInvoice",$key);
		
		
	}
	// </editor-fold>
	
	public function getProvisionCopy($ProvisionID){
		parent::getProvisionCopy($ProvisionID);
		echo "Posten erstellt";
	}
	
	public function getPostenCopy($ArtikelID, $menge = 1, $beschreibung = null, $kundennummer = null, $preis = null){
		$PostenID = parent::getPostenCopy($ArtikelID);
		Red::messageD("Posten erstellt", array("PostenID" => $PostenID, "bestand" => $this->newPostenMessageBestand));
	}

	public function setPayed($p, $skonto = "0,00", $datum = "0", $isTeilzahlung = "false", $TeilzahlungBetrag = "", $save = true){
		echo parent::setPayed($p, $skonto, $datum, $isTeilzahlung, $TeilzahlungBetrag, $save);
	}
	
	public function copyPostenFrom($fromId, $addToSort = 0){
		parent::copyPostenFrom($fromId, $addToSort);
		echo "message:AuftraegeMessages.M002";
	}
	
	public function copyPostenByTypeAndNumber($fromNumber, $fromType){
		parent::copyPostenByTypeAndNumber($fromNumber, $fromType);
	}
	
	public static function parserLieferDatum($w, $l, $p){
		$I = new HTMLInput("lieferDatum", "date", $w);
		$I->style("width:88%;");
		
		$IF = new HTMLInput("lieferDatumUseText", "checkbox", $p != "" ? "1" : "0");
		$IF->style("float:right;");
		$IF->onclick("if(this.checked) { \$j('#lieferDatumContainer').hide(); \$j('[name=lieferDatum]').val(''); \$j('[name=lieferDatumText]').show(); } else { \$j('#lieferDatumContainer').show(); \$j('[name=lieferDatumText]').val('').hide(); }");
		$IF->title("Text statt Datum verwenden");
		
		$IT = new HTMLInput("lieferDatumText", "text", $p);
		$IT->style($p != "" ? "" : "display:none;");
		
		return $IF."<div id=\"lieferDatumContainer\" style=\"width:90%;".($p != "" ? "display:none;" : "")."\">".$I."</div>".$IT;
	}
	
	public function orderNote(){
		$this->changeA("GRLBMOrderGRLBMIDs", "note");
		$this->changeA("isPayed", "1");
		$this->changeA("GRLBMpayedDate", time());
		$this->saveMe();
	}
	
	public function productionNote(){
		$this->changeA("GRLBMProductionLaufzettel", "note");
		$this->changeA("isPayed", "1");
		$this->changeA("GRLBMpayedDate", time());
		$this->saveMe();
	}
	
	public function addFile($id){
		mFileGUI::addFile("GRLBM",$this->ID, $id);
		Red::messageSaved();
	}

	// <editor-fold defaultstate="collapsed" desc="getLieferscheinAdresseCopy">
	public function getLieferscheinAdresseCopy($adresseID){

		if($this->A("lieferAdresseID") != "0"){
			$Adresse = new Adresse($this->A("lieferAdresseID"));
			$Adresse->deleteMe();
		}

		if($adresseID != 0){
			$AdresseOld = new Adresse($adresseID);
			$AdresseOld->changeA("AuftragID", $this->ID);
			$AdresseOld->changeA("type","lieferAdresse");
			$newID = $AdresseOld->newMe();
		} else {
			
			$newID = 0;
		}
		#if($newID == 0){
			$this->loadMe();
			$this->changeA("lieferAdresseID", $newID);
			$this->saveMe();
		#}
	}
	// </editor-fold>
}
?>