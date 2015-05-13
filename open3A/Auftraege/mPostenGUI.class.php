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
class mPostenGUI extends mPosten implements iGUIHTML2, icontextMenu {

	public $additionalCalculatorFields = "";
	public $showRabatt = false;
	public $showPostenTable = true;
	private $GRLBM;
	
	
	function __construct(){
		$this->setParser("menge","Util::CLNumberParserZ");
		$this->setParser("preis","Util::CLNumberParserZ");
		$this->setParser("EK1","Util::CLNumberParserZ");
		$this->setParser("EK2","Util::CLNumberParserZ");
		parent::__construct();
	}
	
	public static function getNewPostenButton($GRLBMID, $kundennummer, $lieferantennummer = null){
		$addBPS = "";
		$addBPS .= (($kundennummer != "" AND $kundennummer > 0) ? "kundennummer:$kundennummer" : "");
		$addBPS .= (($lieferantennummer != null AND $lieferantennummer != "0") ? ($addBPS != "" ? ";" : "")."lieferantFilter:$lieferantennummer" : "");
		
		$BPosten = new Button("Artikel\nhinzufügen", "package");
		$BPosten->customSelect("contentRight", $GRLBMID, "mArtikel", "Auftrag.addArtikel", $addBPS);
		$BPosten->id("grlbmAddArtikel");
		return $BPosten;
	}
	
	public static function getNew1PostenButton(){
		$B1Posten = new Button("1x-Posten\nhinzufügen", "1xPosten");
		$B1Posten->onclick("contentManager.editInPopup('Posten', '-1', 'Posten bearbeiten');");
		$B1Posten->id("grlbmAdd1xPosten");
		
		return $B1Posten;
	}
	
	function getHTML($id){
		$bps = $this->getMyBPSData();
		$this->GRLBM = $GRLBM = new GRLBM($bps["loadGRLBMID"]);

		// <editor-fold defaultstate="collapsed" desc="Aspect:jP">
		try {
			$MArgs = array($id, $GRLBM);
			return Aspect::joinPoint("around", $this, __METHOD__, $MArgs);
		} catch (AOPNoAdviceException $e) {}
		Aspect::joinPoint("before", $this, __METHOD__, $MArgs);
		// </editor-fold>

		$ud = new mUserdata();
		$aT = $ud->getUDValue("GRLBMAnsicht");
		
		if($aT == null)
			$view = "2";
		else $view = $aT;
		
		$Stammdaten = mStammdaten::getActiveStammdaten();
		try {
			$CurrentVorlage = $Stammdaten->A("ownTemplate");
			$Vorlage = new $CurrentVorlage($Stammdaten);
		} catch (ClassNotFoundException $e){
			$Vorlage = new Vorlage_de_DE_leer($Stammdaten);
		}

		$showPrices = true;
		if(in_array($GRLBM->getMyPrefix(), $Vorlage->sumHideOn))
			$showPrices = false;
		
		$message = array();
		$message["R"] = "Diese Rechnung wurde als bezahlt makiert und kann daher nicht mehr bearbeitet werden.";
		$message["G"] = "Diese Gutschrift wurde als bezahlt makiert und kann daher nicht mehr bearbeitet werden.";
		$message["B"] = "Diese Auftragsbestätigung wurde gesperrt.";
		$message["A"] = "Für dieses Angebot wurde eine Auftragsbestätigung erstellt. Die Posten können daher nicht mehr verändert werden.";

		if($bps["GRLBMType"] == "Kalk" AND $view < 3) $view = 3;
		#print_r($this->getMyBPSData());
		$this->setAssocV3("GRLBMID","=",$bps["loadGRLBMID"]);
		
		$tempPosten = new Posten(-1);
		$PostenAttributes = $tempPosten->newAttributes();
		
		//Be sure this field exists else the Rechnung will show 0,00€ total
		if(!isset($PostenAttributes->bruttopreis))
			throw new FieldDoesNotExistException("bruttopreis","");
		
		unset($tempPosten);
		$PostenAttributes = PMReflector::getAttributesArrayAnyObject($PostenAttributes);
		#unset($PostenAttributes[array_search("createArtikel", $PostenAttributes)]);
		$PostenAttributes[] = "menge AS mengeUnparsed";
		$PostenAttributes[] = "preis AS preisUnparsed";
		$PostenAttributes[] = "EK1 AS EK1Unparsed";
		$PostenAttributes[] = "EK2 AS EK2Unparsed";
		$PostenAttributes[] = "mwst AS mwstUnparsed";
		
		$this->setFieldsV3($PostenAttributes);
		

		$Auftrag = new Auftrag($GRLBM->A("AuftragID"));
		
		if($this->A == null) $this->lCV3($id);
		$userLabels = mUserdata::getRelabels("Artikel");
		$userHiddenFields = mUserdata::getHides("Artikel");

		$gui = new HTMLGUI();
		$gui->setObject($this);
		$gui->customize($this->customizer);


		$BPosten = self::getNewPostenButton($bps["loadGRLBMID"], $Auftrag->A("kundennummer"), $Auftrag->A("lieferantennummer"));
		#$BPosten->style("margin-left:10px;");

		$B1Posten = self::getNew1PostenButton();
		#$B1Posten->settings("mPosten", "100:$bps[loadGRLBMID]");

		$L = new HTMLList();
		$L->setItemsStyle("display:inline-block;margin-top:0px;margin-right:15px;");
		$L->addListStyle("list-style-type:none;");
		
		$L->addItem($BPosten);
		$L->addItemStyle("margin-left:10px;");
		
		$L->addItem($B1Posten);
		
		Aspect::joinPoint("PostenSideTable", $this, __METHOD__, array($GRLBM, $L, $Auftrag));
		
		
		$IPosten = new HTMLInput("addPostenFromArtikel");
		$IPosten->placeholder("Artikelsuche...");
		$IPosten->style("width:150px;");
		$IPosten->autocomplete("mArtikel", "function(selection){ Auftrag.addArtikel('".$bps["loadGRLBMID"]."', selection.ArtikelID, function(){ \$j('[name=addPostenFromArtikel]').trigger('focus'); }) }");
		$IPosten->onEnter(OnEvent::rme($this, "findArtikel", array("this.value"), "function(t){ if(t.responseText == '') return; Auftrag.addArtikel('".$bps["loadGRLBMID"]."', t.responseText, function(){ \$j('[name=addPostenFromArtikel]').trigger('focus'); }) }"));
		$L->addItem($IPosten);
		
		$T = "";
		if($this->showPostenTable)
			if($GRLBM->A("isPayed") == "0") 
				$T .= "<div style=\"\" class=\"AuftragBelegContent\">".$L."</div>";
			else{
				$unlock = "";
				$pSpecData = mUserdata::getPluginSpecificData("Auftraege");
				if(isset($pSpecData["pluginSpecificCanSetPayed"])) 
					$unlock = "<span style=\"float:right;\"><a href=\"#\" onclick=\"".OnEvent::rme($GRLBM, "setPayed", array("'false'"), "function(){ Auftrag.reloadBeleg(".$GRLBM->getID().") }")." return false;\" style=\"color:grey;\">Markierung aufheben</a></span>";
				
				$T .= "<p class=\"highlight\" style=\"margin-right:10px;\">$unlock".$message[$bps["GRLBMType"]]."</p>";
			}#$T .= ;
		
		$BOP = new Button("Operationen", "wrench", "iconicG");
		$BOP->onclick("phynxContextMenu.start(this, 'mPosten','100:$bps[loadGRLBMID]','Optionen anzeigen:');");
		$BOP->style("margin-top:5px;margin-left:5px;");

		$html = Aspect::joinPoint("prepend", $this, __METHOD__, $MArgs, "")."<div style=\"clear:right;padding-top:60px;\" class=\"prettySubtitle\">Posten von ".$GRLBM->A("prefix").$GRLBM->A("nummer")."$BOP</div>$T<div class=\"Tab backgroundColor1\" style=\"font-weight:bold;\"><p>".Stammdaten::getLongType($bps["GRLBMType"],true)."posten:"."</p></div>";

		#$tabC1 = new HTMLTable(2);
		#$tabC1->addTableClass("AuftragBelegContent");
		#$tabC1->setColWidth(1, 120);
		
		#$tabC2 = new HTMLTable(2);
		#$tabC2->setColWidth(1, 120);
		#$tabC2->setTableStyle("width:530px;");
		#$tabC2->addTableClass("AuftragBelegContent");
		#$tabC2->addColStyle(2, "text-align:right");
		#$tabC2->addColStyle(3, "text-align:right");
		$divC1  = "";
		$divC2 = "";
		if($this->numLoaded() == 0 OR $view > 2){
			$ICO = array();
			$BelegArten = Auftrag::getBelegArten(null, true);
			if(Applications::activeApplication() == "openWaWi")
				$BelegArten = LBestellungGUI::getBelegArten(null, true);
			
			foreach($BelegArten AS $B)
				$ICO[$B] = new HTMLInput(Stammdaten::getLongType($B), "option", $B);

			$IC = new HTMLInput("CopyPostenDD", "select", "A", $ICO);
			$IC->style("width:120px;margin-top:5px;");
			$IC->id("CopyPostenDD");

			$ICN = new HTMLInput("CopyPostenNumer");
			$ICN->style("width:120px;margin-top:5px;");
			$ICN->placeholder("Belegnummer");
			$ICN->id("CopyPostenNumer");
			$ICN->onEnter("Auftrag.copyPostenByTypeAndNumber($('CopyPostenNumer').value, $('CopyPostenDD').value, '".$GRLBM->getID()."', 'subframe');");
			$ICN->hasFocusEvent(true);

			#$BCS = new Button("Posten aus diesem Beleg kopieren","seiten", "icon");
			#$BCS->style("float:left;margin-right:10px;");
			#$BCS->onclick("Auftrag.copyPostenByTypeAndNumber($('CopyPostenNumer').value, $('CopyPostenDD').value, '".$GRLBM->getID()."', 'subframe');");

			$BSa = new Button("Posten kopieren","seiten", "icon");
			$BSa->style("float:right;margin-left:10px;");
			$BSa->onclick("Auftrag.copyPostenByTypeAndNumber($('CopyPostenNumer').value, $('CopyPostenDD').value, '".$GRLBM->getID()."', 'subframe');");
			
			$BD = "";
			if($this->numLoaded() != 0) {
				$BD = new Button("Belege aus diesem Auftrag anzeigen", "down", "icon");
				$BD->style("float:right;");
				$BD->onclick("\$j('#copyList').slideToggle();");
			}
			
			$divC1 = "<div style=\"margin-left:10px;margin-top:10px;\">";
			
			#$tabC1->addRow(array(""));
			#$tabC1->addRowClass("backgroundColor0");
			
			#$tabC1->addRow(array("Belegdaten übernehmen"));
			#$tabC1->addRowClass("backgroundColor0");
			#$tabC1->addRowColspan(1, 2);
			
			#$tabC1->addLV("Kopieren aus:", "$BSa$BD $IC $ICN");
			#$tabC1->addRowColspan(1, 2);
			$divC1 .= $this->belegAction("Posten kopieren", "$BSa$BD $IC $ICN");
			
			if(Session::isPluginLoaded("mGaeb") AND $GRLBM->getMyPrefix() != "O"){
				$FUGL = new HTMLInput("UGLUpload", "file");
				$FUGL->onchange(OnEvent::popup("UGL-Import", "Gaeb", "-1", "importToBelegPopup", array("fileName", $GRLBM->getID())));
				#$tabC1->addLV("UGL-Datei:", $FUGL);
				
				$divC1 .= $this->belegAction("UGL-Datei importieren", $FUGL);
			}
			
			if(Session::isPluginLoaded("mGaeb") AND ($GRLBM->getMyPrefix() == "P" OR $GRLBM->getMyPrefix() == "A") AND $this->numLoaded() == 0){
				$FUGL = new HTMLInput("X83Upload", "file");
				$FUGL->onchange(OnEvent::popup("X83 Import", "mGaeb", "-1", "X83", array("fileName", $GRLBM->getID()), "", "{width:900, top:20}"));
				#$tabC1->addLV("X83-Datei:", $FUGL);
				
				$divC1 .= $this->belegAction("X83-Datei importieren", $FUGL);
			}
		
			if(Session::isPluginLoaded("mImportExcel")){
				$FUGL = new HTMLInput("ExcelUpload", "file");
				$FUGL->onchange(OnEvent::popup("Excel-Import", "ImportExcel", "-1", "importToBelegPopup", array("fileName", $GRLBM->getID()), "", "{width:600}"));
				#$tabC1->addLV("Excel-Datei:", $FUGL);
				
				$divC1 .= $this->belegAction("Excel-Datei importieren", $FUGL);
			}
			
			if(Session::isPluginLoaded("mLager") AND $GRLBM->getMyPrefix() != "O"){
				$IL = new HTMLInput("lager", "select", mUserdata::getUDValueS("userMainLager", "0"));
				$IL->setOptions(anyC::get("Lager"), "LagerName", "Standardlager", array("-1" => "Ohne Lagerbuchung"));
				$IL->onchange(OnEvent::rme("mUserdata", "setUserdata", array("'userMainLager'", "this.value")));
				#$tabC1->addLV("Entnahme aus:", $IL);
				
				$divC1 .= $this->belegAction("Warenentnahme aus", $IL);
			}
			
			if(Session::isPluginLoaded("Abschlussrechnung") AND $GRLBM->getMyPrefix() == "R" AND $this->numLoaded() == 0){
				#$tabC1->addRow(array("",""));
				#$tabC1->addRowClass("backgroundColor0");
			
				$Belege = anyC::get("GRLBM");
				$Belege->addAssocV3("AuftragID", "=", $GRLBM->A("AuftragID"));
				$Belege->addAssocV3("GRLBMID", "!=", $GRLBM->getID());
				$Belege->addAssocV3("isB", "=", "1");
				$Belege->addOrderV3("datum", "DESC");
			
				$Bel = array("0" => "Bestätigung auswählen");
				while($B = $Belege->getNextEntry())
					$Bel[$B->getID()] = $B->getMyPrefix().$B->A("nummer")." vom ".Util::CLDateParser($B->A("datum")).", ".Util::CLFormatCurrency($B->A("bruttobetrag") * 1, true);
				$Bel["-1"] = "ohne Bestätigung";
				
				$IL = new HTMLInput("abschlagsrechnungBeleg", "select", "0", $Bel);
				#$IL->onchange(OnEvent::rme("mUserdata", "setUserdata", array("'userMainLager'", "this.value")));
				$IL->style("width:180px;margin-top:5px;");
				$IL->id("abschlagsrechnungBeleg");
				
				$IV = new HTMLInput("abschlagsrechnungProzent", "text", "50");
				$IV->style("margin-left:10px;width:40px;text-align:right;margin-top:5px;");
				$IV->hasFocusEvent(true);
				$IV->id("abschlagsrechnungProzent");
				
				$IA = new HTMLInput("abschlagsrechnungAbsolut", "text", "");
				$IA->style("width:50px;text-align:right;margin-top:5px;");
				$IA->hasFocusEvent(true);
				$IA->id("abschlagsrechnungAbsolut");
				
				
				$IGo = new Button("Abschlagsrechnung erstellen", "navigation", "icon");
				$IGo->style("float:right;margin-top:5px;");
				$IGo->id("abschlagsrechnungLos");
				$IGo->rmePCR("Abschlagsrechnung", "-1", "createNewFromBeleg", array("\$j('#abschlagsrechnungBeleg').val()", $GRLBM->getID(), "\$j('#abschlagsrechnungProzent').val()", "\$j('#abschlagsrechnungAbsolut').val()"), OnEvent::frame("subframe", "GRLBM", $GRLBM->getID()));
				
				$format = Util::getLangCurrencyFormat();
				#$tabC1->addLV("Abschlagsre.:", );
				$divC1 .= "<br />";
				$divC1 .= $this->belegAction("Abschlagsrechnung erstellen", $IGo.$IL.$IV."% <span style=\"color:grey;\">oder</span> $IA$format[0]<br /><small style=\"color:grey;\">Eine Abschlagsrechnung ist für die Zahlung eines bestimmten Betrags.</small>");
				
				$IL = new HTMLInput("teilrechnungBeleg", "select", "0", $Bel);
				$IL->style("width:180px;margin-top:5px;");
				$IL->id("teilrechnungBeleg");
				
				$IGo = new Button("Teilrechnung erstellen", "navigation", "icon");
				$IGo->style("float:right;margin-top:5px;");
				$IGo->id("teilrechnungLos");
				$IGo->rmePCR("Teilrechnung", "-1", "createNewFromBeleg", array("\$j('#teilrechnungBeleg').val()", $GRLBM->getID()), OnEvent::frame("subframe", "GRLBM", $GRLBM->getID()));
				
				#$tabC1->addLV("Teilrechnung:", $IGo.$IL."<br /><small style=\"color:grey;\">Eine Teilrechnung enthält bestimmte Positionen des Gesamtauftrags.</small>");
				$divC1 .= $this->belegAction("Teilrechnung erstellen", $IGo.$IL."<br /><small style=\"color:grey;\">Eine Teilrechnung enthält bestimmte Positionen des Gesamtauftrags.</small>");
						
				$IL = new HTMLInput("abschlussrechnungBeleg", "select", "0", $Bel);
				$IL->style("width:180px;margin-top:5px;");
				$IL->id("abschlussrechnungBeleg");
				
				$IGo = new Button("Schlussrechnung erstellen", "navigation", "icon");
				$IGo->style("float:right;margin-top:5px;");
				$IGo->id("abschlussrechnungLos");
				$IGo->rmePCR("Abschlussrechnung", "-1", "createNewFromBeleg", array("\$j('#abschlussrechnungBeleg').val()", $GRLBM->getID()), OnEvent::frame("subframe", "GRLBM", $GRLBM->getID()));
				
				#$tabC1->addLV("Schlussre.:", $IGo.$IL."<br /><small style=\"color:grey;\">Von der Schlussrechnung werden Abschlags- und Teilrechnungen abgezogen.</small>");
				$divC1 .= $this->belegAction("Schlussrechnung erstellen", $IGo.$IL."<br /><small style=\"color:grey;\">Von der Schlussrechnung werden Abschlags- und Teilrechnungen abgezogen.</small>");
			}
			
			$divC1 .= "</div>";#.OnEvent::script("console.log(\$j('.AuftragBelegAction').map(function(){ return \$j(this).height(); })); \$j('.AuftragBelegAction').css('height', Math.max.apply(null, \$j('.AuftragBelegAction').map(function(){ return \$j(this).height(); }).get())+'px');");
			
			$Belege = $this->getCopyBelege($GRLBM);
		
			if($Belege->numLoaded() > 0){
				#$tabC2->addRow(array("",""));
				#$tabC2->addRowClass("backgroundColor0");
				$divC2 = "<div class=\"spell\"><p class=\"backgroundColor3\" style=\"margin-bottom:5px;\">";
				if($GRLBM->getMyPrefix() != "O" AND $GRLBM->getMyPrefix() != "P")
					$divC2 .= "Posten aus Beleg aus diesem Auftrag kopieren";
				else
					$divC2 .= "Aktuelle Angebote und Auftragsbestätigungen";
				$divC2 .= "</p>";
				#$tabC2->addRowClass("backgroundColor0");
				#$tabC2->addRowColspan(1, 2);
			}
			
			while($B = $Belege->getNextEntry()){
				$B->resetParsers();
				
				
				#$BCS = new Button("Posten aus diesem Beleg kopieren", "seiten", "icon");
				#$BCS->className("copyFromButton");
				#$BCS->style("float:right;margin-left:10px;");
				#$BCS->onclick("Auftrag.copyPostenFrom('".$B->getID()."','".$GRLBM->getID()."','','subframe');");

				$auftragName = "";
				if($B->A("AuftragID") != $GRLBM->A("AuftragID")){
					$Auftrag = new Auftrag($B->A("AuftragID"));
					$Adresse = new Adresse($Auftrag->A("AdresseID"));
					
					$auftragName = $Adresse->getShortAddress();
				}
				
				$divC2 .= mGRLBMGUI::belegBox($B, "Auftrag.copyPostenFrom('".$B->getID()."','".$GRLBM->getID()."','','subframe');", $auftragName);
				
				#$pre = $B->getMyPrefix();
				#$tabC2->addRow(array(
				#	"<label>".$pre[0].$B->A("nummer").":</label>",
				#	Aspect::joinPoint("copyListEntry", $this, __METHOD__, array($B), "").$BCS."<span style=\"color:grey;\">$auftragName</span><span style=\"float:right;text-align:right;\">".Util::CLDateParser($B->A("datum"))."<br /><span style=\"color:grey;\">".Util::CLFormatCurrency($B->A("bruttobetrag") * 1, true)."</span>"."</span>"));
				#$html .= "<tr>".$tab->getHTMLForUpdate(false)."</tr>";
			}
			
			if($Belege->numLoaded() > 0)
				$divC2 .= "</div>";
		}

		$html .= $divC1."<div id=\"copyList\" style=\"margin-left:10px;width:95%;".(($this->numLoaded() == 0 OR $view == 4) ? "" : "display:none;")."\">".$divC2."</div>"."
			<form id=\"mPostenForm\">
				<ul class=\"\" style=\"list-style-type:none;padding:0px;max-height:600px;width:100%;width:-webkit-calc(100% - 10px); width:calc(100% - 10px);overflow:auto;\" id=\"PostenSortableContainer\">";

		$postenOptions = array("fold" => true);
		$aspectPostenOptions = Aspect::joinPoint("options", $this, __METHOD__);
		if(is_array($aspectPostenOptions))
			foreach($aspectPostenOptions AS $k => $v)
				$postenOptions[$k] = $v;
		
		$positionsNummern = parent::getPositionsNummern();
		$i = 1;
		$lastID = null;
		if($this->numLoaded() > 0) while($t = $this->getNextEntry()) {
			$ta = $t->getA();

			$postenButtons = Aspect::joinPoint("postenButtons", $this, __METHOD__, $t);
			if(is_array($postenButtons))
				$postenButtons = implode("", $postenButtons);

			if($t->A("PostenHasKalkulation")){
				$B = new Button("Kalkulation anzeigen", "./ubiquitous/PostenKalkulation/showKalkulation.png", "icon");
				$B->style("float:right;margin-right:10px;");
				$B->popup("", "Kalkulation", "mPostenKalkulation", "-1", "getKalkulation", array("'GRLBM'", $t->A("GRLBMID"), "", $t->getID()), "", "{hPosition: 'center', width:1000, top:30, blackout: true}");
				$postenButtons .= $B;
			}
			
			#<img style=\"float:right;\" class=\"calendarIcon mouseoverFade\" style=\"margin-right:0px;\" src=\"./images/i2/delete.gif\" onclick=\"\"/>
			/*$BSettings = new Button("Optionen","./images/i2/settings.png", "icon");
			$BSettings->className("postenOptionsButton");
			$BSettings->style("float:right;margin-right:20px;");
			$BSettings->onclick("if($('optionsPosten".$t->getID()."').style.display == 'none') $('optionsPosten".$t->getID()."').style.display = ''; else $('optionsPosten".$t->getID()."').style.display = 'none';");
			if(!$postenOptions["fold"])
				$BSettings = "";*/

			$BTrash = new Button("Posten löschen","./images/i2/delete.gif");
			$BTrash->type("icon");
			$BTrash->style("float:right;margin-right:10px;");
			$BTrash->onclick("deleteClass('Posten','".$t->getID()."',function() { $('PostenDisplayD".$t->getID()."1').style.display='none'; $('PostenDisplayD".$t->getID()."2').style.display='none'; if($('PostenDisplayD".$t->getID()."4')) $('PostenDisplayD".$t->getID()."4').style.display='none'; $('PostenDisplayD".$t->getID()."3').style.display='none'; $('optionsPosten".$t->getID()."').style.display='none'; Auftrag.updateNettoBrutto(".$t->A("GRLBMID").", ".$t->getID()."); },'Posten wirklich löschen?');");

			$BEdit = new Button("Posten bearbeiten","./images/i2/edit.png");
			$BEdit->type("icon");
			$BEdit->style("float:right;margin-right:20px;");
			$BEdit->onclick("contentManager.editInPopup('Posten', '{$t->getID()}', 'Posten bearbeiten');");

			if($ta->isBrutto == "1") {
				$ta->preis = Util::CLNumberParserZ($ta->bruttopreis);
				$ta->preisUnparsed = $ta->bruttopreis;
			}
			
			$TP = new HTMLTable($view > 1 ? 4 : 3);
			$TP->setColClass(1, "");
			$TP->setColClass(2, "");
			$TP->setColClass(3, "");
			$TP->setColClass(4, "");
			$TP->setTableStyle("width:100%;margin:0px;border-collapse:collapse;border-spacing:0px;");
			#$TP->addTableClass("AuftragBelegContent");
			
			$TP->setColWidth(1, 80);
			$TP->setColWidth(3, 130);
			$TP->setColWidth(4, 35);
			
			$TP->addRow(array(""));
			$TP->addCellStyle(1, "height:30px;");
			#$TP->addRowClass("backgroundColor0");
			$TP->setRowID("PostenDisplayD".$t->getID()."3");
			
			Aspect::joinPoint("abovePosten", $this, __METHOD__, array($TP, $t));
			
			/**
			 * ROW 2
			 */
			$TP->addRow(array($postenButtons));
			$TP->addRowColspan(1, 4);
			$TP->setRowID("optionsPosten".$t->getID());
			$TP->addRowStyle(trim($postenButtons) == "" ? "display:none;" : "");
			$TP->addCellStyle(1, "padding-right:10px;");
			#$TP->addRowClass("backgroundColor0");
			
			/**
			 * ROW 1
			 */
			$TP->addRow(array("
					<div id=\"posNr".$t->getID()."\">".$positionsNummern[$t->getID()]."</div>",
					"$BEdit$BTrash
					<input
						style=\"width:90%;text-align:left;font-weight:bold;\"
						class=\"multiEditInput2 postenNameInput\"
						onfocus=\"oldValue = this.value;\"
						onblur=\"if(oldValue != this.value) saveMultiEditInput('Posten','".$t->getID()."','name');\"
						value=\"".htmlentities($ta->name, ENT_COMPAT, "UTF-8")."\"
						id=\"nameID".$t->getID()."\"
						type=\"text\"
						onkeydown=\"if(event.keyCode == 13) saveMultiEditInput('Posten','".$t->getID()."','name');\"  />"));
			$TP->addRowColspan(2, 3);
			$TP->addCellStyle(1, "padding:0px;padding-right:3px;text-align:right;font-weight:bold;");
			$TP->addRowStyle("background-color:#eee;");
			$TP->setRowID("PostenDisplayD".$t->getID()."1");
			#$TP->addRowClass("backgroundColor0");
			
			
			/**
			 * ROW 3
			 */
			$IPreis = new HTMLInput("preis", "text", $ta->preis);
			$IPreis->activateMultiEdit("Posten", $t->getID(), "function(){ Auftrag.updateNettoBrutto(".$t->A("GRLBMID").", ".$t->getID()."); }");
			$IPreis->setClass("multiEditInput2");
			$IPreis->onfocus("setTimeout('continueSearch".$t->getID()." = true;', 10); ");
			$IPreis->onkeydown("if(typeof continueSearch".$t->getID()." == 'boolean' && event.keyCode == 9 && \$j('#quickSearchmArtikel').length) setTimeout(function(){ \$j('#quickSearchmArtikel').trigger('focus');}, 10);");
			$IPreis->style("width:90px;");
			
			$IVK = new HTMLInput("", "readonly", (Util::CLFormatCurrency($t->A("preisUnparsed") * ($t->A("rabatt") != null ? (100 - $t->A("rabatt")) / 100 : 1))));
			$IVK->style("width:50px;text-align:right;color:black;border:0px;");
			$IVK->id("VKsID".$t->getID());
			
			$IMenge = new HTMLInput("menge", "text", $ta->menge);
			$IMenge->activateMultiEdit("Posten", $t->getID(), "function(t){ Auftrag.checkBestand(t); Auftrag.updateNettoBrutto(".$t->A("GRLBMID").", ".$t->getID()."); }");
			$IMenge->setClass("multiEditInput2");
			$IMenge->style("width:90px;");
			if($ta->PostenUsedSerials != "[]" AND $ta->PostenUsedSerials != "")
				$IMenge->isDisabled (true);
			#<input 
			#class=\"multiEditInput2\" 
			#onfocus=\"oldValue = this.value;\" 
			#onblur=\"if(oldValue != this.value) saveMultiEditInput('Posten','".$t->getID()."','menge', function(){ Auftrag.updateNettoBrutto(".$t->A("GRLBMID").", ".$t->getID()."); });\"
			#value=\"$ta->menge\"
			#id=\"mengeID".$t->getID()."\"
			#type=\"text\"
			#onkeydown=\"if(event.keyCode == 13) saveMultiEditInput('Posten','".$t->getID()."','menge', function(){ Auftrag.updateNettoBrutto(".$t->A("GRLBMID").", ".$t->getID()."); });\"  />
			$TP->addRow(array("",
				"M: $IMenge ".Aspect::joinPoint("postenDetails", $this, __METHOD__, array($t), "").(($this->showRabatt AND $showPrices) ? "<span style=\"float:right;margin-left:5px;\">P: $IPreis</span>".CustomizerRabatt::getInput($t) : "<span style=\"color:grey;\">".$t->A("gebinde")."</span>"),
				($showPrices ? ($this->showRabatt ? "VK: $IVK" : "P: $IPreis") : ""),
				"<span style=\"".($t->A("mwstCheck") ? "color:red;font-weight:bold;" : "color:grey;")."\">".Util::CLFormatNumber($ta->mwst*1, 2, false, false)."%</span>"));
			$TP->addCellStyle(4, "text-align:right;");
			$TP->setRowID("PostenDisplayD".$t->getID()."2");
			#$TP->addRowClass("backgroundColor0");
			#<input class=\"multiEditInput2\" onfocus=\"oldValue = this.value;\" onblur=\"if(oldValue != this.value) saveMultiEditInput('Posten','".$t->getID()."','preis', function(){ Auftrag.updateNettoBrutto(".$t->A("GRLBMID").", ".$t->getID()."); });\" value=\"$ta->preis\" id=\"preisID".$t->getID()."\" type=\"text\" onkeydown=\"if(event.keyCode == 13) saveMultiEditInput('Posten','".$t->getID()."','preis', function(){ Auftrag.updateNettoBrutto(".$t->A("GRLBMID").", ".$t->getID()."); });\"  />
			
			/**
			 * ROW 4A
			 */
			if($view > 2 AND $bps["GRLBMType"] == "Kalk"){
				$TP->addRow(array("",
				"
					".(!isset($userHiddenFields["EK1"]) ? (isset($userLabels["EK1"]) ? $userLabels["EK1"] : "EK1").": <input style=\"margin-right:30px;\" class=\"multiEditInput2\" onfocus=\"oldValue = this.value;\" onblur=\"if(oldValue != this.value) saveMultiEditInput('Posten','".$t->getID()."','EK1');\" value=\"$ta->EK1\" id=\"EK1ID".$t->getID()."\" type=\"text\" onkeydown=\"if(event.keyCode == 13) saveMultiEditInput('Posten','".$t->getID()."','EK1');\"  />" : "")."

					".(!isset($userHiddenFields["EK2"]) ? (isset($userLabels["EK2"]) ? $userLabels["EK2"] : "EK2").": <input class=\"multiEditInput2\" onfocus=\"oldValue = this.value;\" onblur=\"if(oldValue != this.value) saveMultiEditInput('Posten','".$t->getID()."','EK2');\" value=\"$ta->EK2\" id=\"EK2ID".$t->getID()."\" type=\"text\" onkeydown=\"if(event.keyCode == 13) saveMultiEditInput('Posten','".$t->getID()."','EK2');\"  />" : "").""));
				$TP->addRowColspan(2, 3);
				
				if(isset($userHiddenFields["EK1"]) AND isset($userHiddenFields["EK2"]))
					$TP->addRowStyle("display:none;");
				
				$TP->setRowID("PostenDisplayD".$t->getID()."4");
				#$TP->addRowClass("backgroundColor0");
			}
			
			
			/**
			 * ROW 4B
			 */
			if(($view > 2 AND $bps["GRLBMType"] != "Kalk" AND $showPrices)){
			$postenCalculator = Aspect::joinPoint("postenCalculator", $this, __METHOD__, array($t, $TP));
			
				if($postenCalculator == null){
					$TP->addRow(array("",
						(!isset($userHiddenFields["EK1"]) ? (isset($userLabels["EK1"]) ? $userLabels["EK1"] : "EK1").": <input style=\"width:70px;text-align:right;margin-right:20px;\" value=\"".(Util::CLNumberParserZ($ta->mengeUnparsed * $ta->EK1Unparsed))."\" id=\"EK1dID".$t->getID()."\" type=\"text\" readonly=\"readonly\"  /><input value=\"$ta->EK1\" id=\"EK1ID".$t->getID()."\" type=\"hidden\" />" : "")."
						".(!isset($userHiddenFields["EK2"]) ? (isset($userLabels["EK2"]) ? $userLabels["EK2"] : "EK2").": <input style=\"width:70px;text-align:right;margin-right:20px;\" value=\"".(Util::CLNumberParserZ($ta->mengeUnparsed * $ta->EK2Unparsed))."\" id=\"EK2dID".$t->getID()."\" type=\"text\" readonly=\"readonly\"  /><input value=\"$ta->EK2\" id=\"EK2ID".$t->getID()."\" type=\"hidden\" />" : "")."
						VK: <input style=\"width:70px;text-align:right;margin-right:30px;\" value=\"".(Util::CLNumberParserZ($ta->mengeUnparsed * $ta->preisUnparsed * ($t->A("rabatt") != null ? (100 - $t->A("rabatt")) / 100 : 1)))."\" id=\"VKID".$t->getID()."\" type=\"text\" readonly=\"readonly\"  />"));
				
					$TP->addRowColspan(2, 3);
					$TP->addRowStyle("color:grey;");

					if(isset($userHiddenFields["EK1"]) AND isset($userHiddenFields["EK2"]))
						$TP->addRowStyle("display:none;");
					
					#$TP->addRowClass("backgroundColor0");
				}
				$TP->setRowID("PostenDisplayD".$t->getID()."4");
			}
			
			Aspect::joinPoint("belowPosten", $this, __METHOD__, array($TP, $t));

			$html .= "<li style=\"padding:0px;margin:0px;margin-left:10px;\" id=\"Posten_".$t->getID()."\">$TP</li>";
			
			$lastID = $t->getID();
			$i++;
		}

		$autofocus = "<script type=\"text/javascript\">/*$('mengeID$lastID').focus();*/</script>";
		$autofocus = Aspect::joinPoint("autofocus", $this, __METHOD__, array($autofocus), $autofocus);
		$html .= ($lastID != null ? $autofocus : "")."</ul></form>"."<div id=\"belegSummen\" style=\"".(!$showPrices ? "display:none;" : "")."\">".$this->getSummen()."</div>".OnEvent::script("\$j('#PostenSortableContainer').css('max-height', contentManager.maxHeight()+'px');");

		if($this->CustomizerPostenSort) $html .= "
			<script type=\"text/javascript\">
				\$j('#PostenSortableContainer').sortable({
					axis: 'y', 
					update: function() {
						contentManager.rmePCR('CustomizerPostenSort','', 'saveOrder', Sortable.serialize('PostenSortableContainer').replace(/&/g,'').replace(/Posten/g, ',').replace(/PostenSortableContainer\[\]=/g, ','), function(transport){ Auftrag.updatePositionsNummern(\$j.parseJSON(transport.responseText)); });
					},
					dropOnEmpty: true,
					scroll: true,
					handle: \$j('.PostenSortableHandle')});
			</script>";
/*
		if($bps["GRLBMType"] == "Kalk") $html .= "
		<table style=\"margin-top:20px;width:530px;\">
			<colgroup>
				<col class=\"backgroundColor2\" style=\"width:120px;\" />
				<col class=\"backgroundColor3\" />
			</colgroup>
			<tr>
				<td class=\"backgroundColor0\"></td>
			</tr>
			".(!isset($userHiddenFields["EK1"]) ? "<tr>
				<td><label>ges. ".(isset($userLabels["EK1"]) ? $userLabels["EK1"] : "EK1").":</label></td>
				<td id=\"gesamtEK1\" style=\"text-align:right;\"></td>
			</tr>" : "")."
			".(!isset($userHiddenFields["EK2"]) ? "<tr>
				<td><label>ges. ".(isset($userLabels["EK2"]) ? $userLabels["EK2"] : "EK2").":</label></td>
				<td id=\"gesamtEK2\" style=\"text-align:right;\"></td>
			</tr>" : "")."
			<tr>
				<td><label>ges. VK:</label></td>
				<td id=\"gesamtVK\" style=\"text-align:right;\"></td>
			</tr>
			<tr>
				<td class=\"backgroundColor0\"></td>
			</tr>
			<tr>
				<td><label>Rabatt in %:</label></td>
				<td id=\"rabattInP\" style=\"text-align:right;\"></td>
			</tr>
			<tr>
				<td><label>Rabatt in €:</label></td>
				<td id=\"rabattInW2\" style=\"text-align:right;\"></td>
			</tr>
			<tr>
				<td><label>ges. VK:</label></td>
				<td id=\"gesamtVK2\" style=\"text-align:right;\"></td>
			</tr>
			<tr>
				<td class=\"backgroundColor0\"></td>
			</tr>
			".(!isset($userHiddenFields["EK1"]) ? "<tr>
				<td><label>VK - ".(isset($userLabels["EK1"]) ? $userLabels["EK1"] : "EK1").":</label></td>
				<td id=\"VKEK1\" style=\"text-align:right;font-weight:bold;\"></td>
			</tr>" : "")."
			".(!isset($userHiddenFields["EK2"]) ? "<tr>
				<td><label>VK - ".(isset($userLabels["EK2"]) ? $userLabels["EK2"] : "EK2").":</label></td>
				<td id=\"VKEK2\" style=\"text-align:right;font-weight:bold;\"></td>
			</tr>" : "")."
			<tr>
				<td><label>Leasingrate:</label></td>
				<td id=\"leasingrate2\" style=\"text-align:right;\"></td>
			</tr>
			$this->additionalCalculatorFields
			<tr>
				<td colspan=\"2\"><input type=\"button\" value=\"Werte berechnen\" onclick=\"calculateKalk();\" /></td>
			</tr>
		</table>";
		elseif($view > 3){
			$html .= "
		<table class=\"AuftragBelegContent\" style=\"margin-top:20px;\">
			<colgroup>
				<col class=\"backgroundColor2\" style=\"width:120px;\" />
				<col class=\"backgroundColor3\" />
			</colgroup>
			".(!isset($userHiddenFields["EK1"]) ? "<tr>
				<td><label>ges. ".(isset($userLabels["EK1"]) ? $userLabels["EK1"] : "EK1").":</label></td>
				<td id=\"gesamtEK1\" style=\"text-align:right;\"></td>
			</tr>" : "")."
			".(!isset($userHiddenFields["EK2"]) ? "<tr>
				<td><label>ges. ".(isset($userLabels["EK2"]) ? $userLabels["EK2"] : "EK2").":</label></td>
				<td id=\"gesamtEK2\" style=\"text-align:right;\"></td>
			</tr>" : "")."
			<tr>
				<td><label>ges. VK:</label></td>
				<td id=\"gesamtVK\" style=\"text-align:right;\"></td>
			</tr>
			<tr>
				<td class=\"backgroundColor0\"></td>
			</tr>
			".(!isset($userHiddenFields["EK1"]) ? "<tr>
				<td><label>VK - ".(isset($userLabels["EK1"]) ? $userLabels["EK1"] : "EK1").":</label></td>
				<td id=\"VKEK1\" style=\"text-align:right;font-weight:bold;\"></td>
			</tr>" : "")."
			".(!isset($userHiddenFields["EK2"]) ? "<tr>
				<td><label>VK - ".(isset($userLabels["EK2"]) ? $userLabels["EK2"] : "EK2").":</label></td>
				<td id=\"VKEK2\" style=\"text-align:right;font-weight:bold;\"></td>
			</tr>" : "")."
			<tr>
				<td colspan=\"2\"><input type=\"button\" value=\"Werte berechnen\" onclick=\"calculateKalk();\" /></td>
			</tr>
		</table>";
		}*/
		
		return $html;
	}
	
	public function belegAction($title, $content){
		return "
		<div style=\"width:31%;display:inline-block;vertical-align:top;margin-right:1%;margin-bottom:10px;\" class=\"spell AuftragBelegAction\">
			<p class=\"backgroundColor3\">$title</p>
			<div style=\"padding:10px;\">
				$content
			</div>
		</div>";
	}
	
	public function getCopyBelege(GRLBM $GRLBM){
		if($GRLBM->getMyPrefix() == "O" OR $GRLBM->getMyPrefix() == "P"){
			$Belege = anyC::get("GRLBM");
			$Belege->addAssocV3("GRLBMID", "!=", $GRLBM->getID());
			$Belege->addAssocV3("isA", "=", "1", "AND", "2");
			$Belege->addAssocV3("isB", "=", "1", "OR", "2");

			$Belege->addOrderV3("datum", "DESC");
			$Belege->addOrderV3("isA", "DESC");
			$Belege->addOrderV3("isB", "DESC");

			$Belege->setLimitV3("12");
			$Belege->lCV3();
			
			return $Belege;
		}
		$Belege = anyC::get("GRLBM");
		$Belege->addAssocV3("AuftragID", "=", $GRLBM->A("AuftragID"));
		$Belege->addAssocV3("GRLBMID", "!=", $GRLBM->getID());
		$Belege->addAssocV3("isM", "=", "0");

		$Belege->addOrderV3("datum", "DESC");
		$Belege->addOrderV3("isA", "DESC");
		$Belege->addOrderV3("isL", "DESC");
		$Belege->addOrderV3("isR", "DESC");
		$Belege->addOrderV3("isG", "DESC");
		$Belege->addOrderV3("isWhat", "DESC");

		$Belege->setLimitV3("12");
		$Belege->lCV3();

		return $Belege;
	}
	
	public function getPositionsNummern($GRLBMID = null, $echo = false) {
		if($GRLBMID != null)
			$this->addAssocV3 ("GRLBMID", "=", $GRLBMID);
		
		$data = json_encode(parent::getPositionsNummern());
		
		if($echo)
			echo $data;
		
		return $data;
	}

	public function getSummen($GRLBMID = null, $PostenID = null){
		$echo = false;
		if($this->GRLBM == null){
			$this->GRLBM = new GRLBM($GRLBMID);
			$echo = true;
		}
		$t = new HTMLTable(3);
		$t->setColClass(1, "");
		$t->setColClass(2, "");
		$t->setColClass(3, "");
		$t->setTableStyle("margin-top:20px;border-top-style:double;border-left-width:0px;");
		$t->addTableClass("AuftragBelegContent");
		$t->setColWidth(1, "30%");
		$t->setColWidth(2, "30%");
		$t->setColWidth(3, "40%");
		#$t->addColStyle(2, "text-align:right;");
		$t->addColStyle(3, "text-align:right;");

		$B = new Button("Gewinn (EK1): ".Util::CLFormatCurrency($this->GRLBM->A("nettobetrag") - $this->GRLBM->A("ek1betrag"), false), "bars", "iconicG");
		
		$t->addRow(array($B, "<span style=\"color:grey;\">Netto: ".Util::CLFormatCurrency($this->GRLBM->A("nettobetrag")*1, false)."</span>", "Brutto: <b>".Util::CLFormatCurrency($this->GRLBM->A("bruttobetrag")*1, false)."</b>"));
		#$t->addRowClass("backgroundColor0");

		$js = "";
		if($PostenID != null){
			$Posten = new Posten($PostenID, false);
			
			$js = "<script type=\"text/javascript\">";
			
			/*if(Session::isPluginLoaded("mStaffelpreis") AND $Posten->A("oldArtikelID") != "0"){
				Staffelpreis::get("Artikel", $Posten->A("oldArtikelID"), $Posten->A("menge"));
				$js .= "
				\$j('#rabattID$PostenID').val('".Util::CLNumberParserZ($Posten->A("menge"))."');";
			}
			*/
			
			$js .= "
				if($('EK1dID$PostenID')) $('EK1dID$PostenID').value = '".Util::CLFormatCurrency($Posten->A("menge") * $Posten->A("EK1"))."';
				if($('EK2dID$PostenID')) $('EK2dID$PostenID').value = '".Util::CLFormatCurrency($Posten->A("menge") * $Posten->A("EK2"))."';
				if($('EK1sID$PostenID')) $('EK1sID$PostenID').value = '".Util::CLFormatCurrency($Posten->A("EK1")*1)."';
				if($('VKID$PostenID')) $('VKID$PostenID').value = '".Util::CLFormatCurrency($Posten->A("menge") * $Posten->A("preis") * ($Posten->A("rabatt") != null ? (100 - $Posten->A("rabatt")) / 100 : 1))."';
				if($('VKsID$PostenID')) $('VKsID$PostenID').value = '".Util::CLFormatCurrency($Posten->A("preis") * ($Posten->A("rabatt") != null ? (100 - $Posten->A("rabatt")) / 100 : 1))."';
				if($('rabattID$PostenID')) $('rabattID$PostenID').value = '".Util::CLNumberParserZ($Posten->A("rabatt") * 1)."';
			</script>";
		}
		
		if($echo) echo $t.$js;
		return $t;
	}

	public function getContextMenuHTML($identifier){
		$bps = $this->getMyBPSData();

		$ex = explode(":", $identifier);
		if(count($ex) > 1)
			$identifier = $ex[0];

		switch($identifier){
			/*case "1":
				echo "
				<table>
					<tr>
						<td><input type=\"button\" value=\"Angebot\" style=\"background-image:url(./images/navi/angebot.png);\" class=\"bigButton backgroundColor2\" onclick=\"phynxContextMenu.update('mPosten','A','Angebote:');\" /></td>
					</tr>
					<tr>
						<td><input type=\"button\" value=\"Lieferschein\" style=\"background-image:url(./images/navi/lieferschein.png);\" class=\"bigButton backgroundColor2\" onclick=\"phynxContextMenu.update('mPosten','L','Lieferscheine:');\" /></td>
					</tr>
					<tr>
						<td><input type=\"button\" value=\"Rechnung\" style=\"background-image:url(./images/navi/rechnung.png);\" class=\"bigButton backgroundColor2\" onclick=\"phynxContextMenu.update('mPosten','R','Rechnungen:');\" /></td>
					</tr>
					<tr>
						<td><input type=\"button\" value=\"Gutschrift\" style=\"background-image:url(./images/navi/gutschrift.png);\" class=\"bigButton backgroundColor2\" onclick=\"phynxContextMenu.update('mPosten','G','Gutschriften:');\" /></td>
					</tr>
				</table>";
			break;
			
			case "A":
			case "L":
			case "R":
			case "G":
				echo "
				<div id=\"copyContextExtension\" class=\"borderColor1 backgroundColor0\" style=\"float:right;width:200px;margin-right:-207px;padding:5px;border-style:solid;border-width:1px;\"></div>
				<table>
					<colgroup>
						<col style=\"width:20px;\" />
						<col class=\"backgroundColor2\" />
						<col class=\"backgroundColor3\" />
					</colgroup>";
				$G = new mGRLBMGUI();
				$G->addJoinV3("Auftrag","AuftragID","=","AuftragID");
				$G->setAssocV3("is$identifier","=","1");
				if($bps != -1) $G->addAssocV3("GRLBMID","!=",$bps["loadGRLBMID"]);
				$G->addOrderV3("nummer","DESC");
				$G->setLimitV3("10");
				$G->lCV3();
				while(($t = $G->getNextEntry())){

					$Adresse = null;
					if($t->getA()->AdresseID != ""){
						$Adresse = new Adresse($t->getA()->AdresseID);
						$Adresse->loadMe();
					}
					
					echo "
					<tr onclick=\"Auftrag.copyPostenFrom('".$t->getID()."','".$bps["loadGRLBMID"]."','$identifier','subframe');\" class=\"".(($bps != -1 AND $bps["AuftragID"] == $t->getA()->AuftragID) ? "backgroundColor1" : "")."\" onmouseout=\"this.className='".(($bps != -1 AND $bps["AuftragID"] == $t->getA()->AuftragID) ? "backgroundColor1" : "")."';\" style=\"cursor:pointer;\" onmouseover=\"this.className = 'backgroundColor0'; $('copyContextExtension').update('".($Adresse != null ? $Adresse->getA()->firma.($Adresse->getA()->nachname != "" ? "<br />".$Adresse->getA()->nachname." ".$Adresse->getA()->vorname : "") : "keine Daten verfügbar")."');\">
						<td><img src=\"./images/i2/copy.png\" class=\"mouseoverFade\" /></td>
						<td>".$identifier.$t->getA()->nummer."</td>
						<td>".$t->getA()->datum."</td>
					</tr>";
				}
				
				$B = new Button("Posten kopieren","./images/i2/save.gif");
				$B->type("icon");
				$B->style("float:right;");
				$B->onclick("Auftrag.copyPostenByTypeAndNumber($('copyFromNumber').value, '$identifier', '".$bps["loadGRLBMID"]."', 'subframe');");
				
				echo "<tr>
					<td colspan=\"3\">Geben Sie die Nummer ein:</td>
				</tr><tr>
					<td colspan=\"3\">$B<input type=\"text\" style=\"width:80%;\" id=\"copyFromNumber\"></td>
				</tr>
				</table>";
			break;*/
			
			case "100":
				$ud = new mUserdata();
	
				$aT = $ud->getUDValue("GRLBMAnsicht");
				
				if($aT == null)
					$selectedKey = "2";
				else $selectedKey = $aT;
				
				$kAL = array();
				$kAL["1"] = "wenig";
				$kAL["2"] = "normal";
				$kAL["3"] = "viel";
				$kAL["4"] = "sehr viel";
				
				$gui = new HTMLGUI();
				echo $gui->getContextMenu($kAL, "mPosten", "100", $selectedKey, "contentManager.loadFrame(\'subframe\', \'GRLBM\', \'$ex[1]\');");
			break;
		}
	}
	
	public function saveContextMenu($identifier, $key){
		$ud = new mUserdata();
		
		if($identifier == "100") $ud->setUserdata("GRLBMAnsicht",$key);
	}
	
	public function findArtikel($query){
		$mA = new mArtikelGUI();
		$AC = $mA->getACData("", $query, false);
		
		$A = $AC->n();
		if($A == null)
			return;
		
		if($AC->numLoaded() > 1)
			return;
		
		echo $A->getID();
	}
	
}
?>