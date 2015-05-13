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
class ArtikelGUI extends Artikel implements iGUIHTML2 {
	private static $hasLieferant = false;
	function getHTML($id){
		$gui = new HTMLGUI();
		$gui->translate($this->loadTranslation());
		
		$this->loadMeOrEmpty();

		$CT = $this->getCalcTable();

		if($this->A->isBrutto == "1")
			$this->A->preis = Util::CLNumberParserZ($this->A->bruttopreis,"load");
			
		$updateJS = "";
		if($id == -1){
			$this->A = $this->newAttributes();
			try {
				$id = $this->newMe(true, false);
			} catch(DuplicateEntryException $e){
				Red::errorDuplicate($e->getDuplicateFieldValue());
			}
			$this->forceReload();
			$updateJS = "<script type=\"text/javascript\">contentManager.setLeftFrame('Artikel', $id); contentManager.reloadFrame('contentRight');</script>";
		}

		$gui->setShowAttributes(array(
			"name",
			"gebinde",
			"artikelnummer",
			"KategorieID",
			"KategorieID2",
			"beschreibung",
			"preis",
			"aufschlagListenpreis",
			"aufschlagGesamt",
			"mwst",
			"mwStKategorieID",
			"EK1",
			"EK2",
			"LohngruppeID",
			"Lohnminuten",
			"bild",
			"bemerkung",
			"hideInReport",
			"isBrutto",
			"bruttopreis",
			"bildDateiName"));
		
		if(Session::isPluginLoaded("mDifferenzbesteuerung"))
			$gui->insertAttribute ("after", "isBrutto", "differenzbesteuert");
		
		$gui->setType("isBrutto","hidden");
		if(Session::isPluginLoaded("mBrutto") AND !Session::isPluginLoaded("mLohngruppe") AND !Session::isPluginLoaded("mMwSt")){
			$gui->setType("isBrutto","checkbox");
			$gui->setLabel("isBrutto","Bruttopreis?");
			$gui->setFieldDescription("isBrutto","Ist der angegebene Preis ein Bruttopreis?");
			$gui->setLabel("preis","Preis");
			$gui->activateFeature("addSaveDefaultButton", $this, "isBrutto");
		}

		if(!Session::isPluginLoaded("ImportDatanorm"))
			$gui->hideAttribute("rabattgruppe");

		
		$gui->setParser("artikelnummer", "ArtikelGUI::parserArtikelnummer");
		
		$gui->setObject($this);
		$gui->setName($this->languageClass->getSingular());
		
		$gui->setLabel("KategorieID2", "Kategorie 2");
		if($this->A("KategorieID2") == "0")
			$gui->setLineStyle("KategorieID2", "display:none;");
		
		$gui->setParser("preis","ArtikelGUI::preisInputParser", array($this->A("preisModus")));
		
		if(!Session::isPluginLoaded("mMwSt")){
			$kat = new Kategorien();
			$kat->addAssocV3("type","=","mwst");

			$keys = array();
			$values = $kat->getArrayWithValues();
			for($i=0;$i<count($values);$i++){
				$keys[] = Util::parseFloat("de_DE",str_replace("%","",$values[$i]));
				$values[$i] = Util::CLNumberParserZ(Util::parseFloat("de_DE",str_replace("%","",$values[$i])),"load")."%";
			}

			$gui->setType("mwst","select");
			$gui->setOptions("mwst",$keys,$values);

			$gui->hideAttribute("mwStKategorieID");
		} else {
			$kat = new Kategorien();
			$kat->addAssocV3("type","=","mwstsatz");
			$saetze = $kat->getArrayWithKeysAndValues("bitte auswählen");

			$keys = array_keys($saetze);
			$values = array_values($saetze);

			$gui->setType("mwStKategorieID","select");
			$gui->setOptions("mwStKategorieID",$keys,$values);
			
			$gui->setLabel("mwStKategorieID", "MwSt-Satz");
			
			$gui->hideAttribute("mwst");
			$gui->setFieldDescription("mwStKategorieID", "Diese Einträge verwalten Sie im <a style=\"color:grey;\" href=\"#\" onclick=\"contentManager.loadPlugin('contentRight', 'mMwSt'); return false;\" >MwSt-Plugin</a>.");
		}
		
		$gui->setType("differenzbesteuert","checkbox");
		$gui->setType("beschreibung","textarea");
		$gui->setType("KategorieID","select");
		$gui->setType("KategorieID2","select");
		
		if(!Session::isPluginLoaded("Provisionen"))
			$gui->setType("EK2", "hidden");
		
		$gui->hideAttribute("bruttopreis");
		$gui->hideAttribute("bild");
		$gui->hideAttribute("lieferantID");
		$gui->hideAttribute("ArtikelID");
		$gui->hideAttribute("bildDateiName");
	
		#$gui->insertSpaceAbove("hideInReport");
		$gui->insertSpaceAbove("EK1", "Einkauf");
		$gui->insertSpaceAbove("bemerkung", "Sonstiges");
		$gui->insertSpaceAbove("preis", "Verkauf");
		#$gui->insertSpaceAbove("aufschlagListenpreis");
		
		$gui->setFieldDescription("KategorieID", "Diese Einträge verwalten Sie im <a style=\"color:grey;\" href=\"#\" onclick=\"contentManager.loadPlugin('contentRight', 'Kategorien');return false;\" >Kategorien-Plugin</a>.");
		
		if(Session::isPluginLoaded("Berichte"))
			$gui->setType("hideInReport","checkbox");
		else
			$gui->setType("hideInReport","hidden");

		$gui->setType("LohngruppeID","hidden");
		$gui->setType("Lohnminuten","hidden");
		$gui->setType("aufschlagListenpreis","hidden");
		$gui->setType("aufschlagGesamt","hidden");
		if(Session::isPluginLoaded("mLohngruppe")){
			$gui->setLabel("LohngruppeID", "Lohngruppe");
			$gui->setType("LohngruppeID","select");
			$gui->selectWithCollection("LohngruppeID", anyC::get("Lohngruppe"), "LohngruppeName", "bitte auswählen");
			$gui->setType("Lohnminuten","text");
			$gui->insertSpaceAbove("LohngruppeID", "Lohn");
		}

		if(Session::isPluginLoaded("mMultiLanguage")){
			$gui->activateFeature("addAnotherLanguageButton", $this, "name");
			$gui->activateFeature("addAnotherLanguageButton", $this, "gebinde");
			$gui->activateFeature("addAnotherLanguageButton", $this, "beschreibung");
		}
		
		$kat = new Kategorien();
		$kat->addAssocV3("type","=","2");
		$kat->addOrderV3("name", "ASC");
		$keys = $kat->getArrayWithKeys();
		$keys[] = "0";
		
		$values = $kat->getArrayWithValues();
		$values[] = $this->texts["bitte auswählen"];
		
		$gui->setOptions("KategorieID", $keys, $values);
		$gui->setOptions("KategorieID2", $keys, $values);

		
		$B = new Button("Weitere Kategorie", "./images/i2/add.png", "icon");
		$B->onclick("contentManager.toggleFormFields('show', ['KategorieID2']);");
		$gui->activateFeature("addCustomButton", $this, "KategorieID", $B);
		
		$gui->setType("sachkonto", "hidden"); //Legacy since 24.02.2013
		
		$ST = new HTMLSideTable("right");
		
		$ST->addRow($this->getPic());
		if($this->A("bildDateiName") == "")
			$ST->addCellStyle (1, "display:none;");
		$ST->addCellID(1, "ArtikelBild");
		
		
		#$ST->setTableStyle("float:right;margin: 0 -170px 0 0;width: 160px;");
		if(Session::isPluginLoaded("mDArtikel"))
			$ST->addRow(DArtikel::getButton($this->getID()));
		
		if(Session::isPluginLoaded("mArtikelRG"))
			$ST->addRow(ArtikelRG::getButton($this->getID()));
		
		if(Session::isPluginLoaded("mPreisgruppe"))
			$ST->addRow(Preisgruppe::getButton("Artikel", $this->getID()));
			
		if(Session::isPluginLoaded("mEtikette"))
			$ST->addRow(Etikette::getButton("Artikel", $this->getID()));
		
		if(Session::isPluginLoaded("mLager"))
			$ST->addRow(Lagerbestand::getButton($this->getID()));
		
		if(Session::isPluginLoaded("mFile"))
			$ST->addRow(mFileGUI::getManagerButton("Artikel", $this->getID(), false, "bildDateiName", OnEvent::rme($this, "getPic", "1", "function(t){ if(t.responseText != '') \$j('#ArtikelBild').html(t.responseText).css('display', ''); else \$j('#ArtikelBild').html(t.responseText).css('display', 'none'); }")));
		
		if(Session::isPluginLoaded("mStueckliste")/* AND !Applications::isAppLoaded("openWaWi")*/)
			$ST->addRow(Stueckliste::getButton($this->getID()));
		
		if(Session::isPluginLoaded("mStaffelpreis"))
			$ST->addRow(Staffelpreis::getButton($this->getID()));
		
		if(Session::isPluginLoaded("mVariante"))
			$ST->addRow(Variante::getButton($this->getID()));
		
		if(Session::isPluginLoaded("mLieferant")){
			$ST->addRow(Lieferant::getButton($this->getID()));

			if(Lieferant::hasArtikelLieferant($this->getID())){
				self::$hasLieferant = true;
				
				$gui->setType("EK1", "hidden");
				$gui->setType("EK2", "hidden");
				#$gui->setType("preis", "hidden");
				
				$gui->setLabel("aufschlagGesamt", "Aufschlag");
				$gui->setType("aufschlagGesamt","text");
				$gui->setFieldDescription("aufschlagGesamt", "auf Gesamtpreis in Prozent");
				
				$gui->setLabel("aufschlagListenpreis", "Aufschlag");
				$gui->setType("aufschlagListenpreis","text");
				$gui->setFieldDescription("aufschlagListenpreis", "auf Listenpreis in Prozent");
				
				#contentManager.toggleFormFieldsTest(\$j(this).val() == '0', ['aufschlagGesamt', 'aufschlagListenpreis'], []);
				if($this->A("preisModus") == "1"){
					$gui->setLineStyle("aufschlagGesamt", "display:none;");
					$gui->setLineStyle("aufschlagListenpreis", "display:none;");
				}
			}
		}
		
		if(mUserdata::getPluginSpecificData("Provisionen", "pluginSpecificHideEK"))
			$gui->setType("EK2", "hidden");
		
		
		if(Session::isPluginLoaded("mVermietet"))
			$ST->addRow(Vermietet::getButton("Artikel", $this->getID()));
		
		$ST->addRow(array("&nbsp;"));
		
		$ST->addRow($CT);
		$ST->addCellID(1, "CalcTable");
		
		$gui->customize($this->customizer);
		$gui->setJSEvent("onSave", "function(){ contentManager.updateLine('AjaxForm', ".$this->getID()."); contentManager.rmePCR('Artikel', ".$this->getID().", 'getCalcTable', ['1'], function(transport){ $('CalcTable').update(transport.responseText); });}");
		$gui->setStandardSaveButton($this);

		return $ST.$updateJS.$gui->getEditHTML()/*.($_SESSION["S"]->checkForPlugin("mFile") ? "<div style=\"margin-top:20px;\">".$D->getHTML(-1, 0)."</div>" : "")*/;
	}

	public static function parserArtikelnummer($w){
		$I = new HTMLInput("artikelnummer", "text", $w);
		$I->autocomplete("mArtikel", "function(){ return false; }");
		
		return $I;
	}


	public function getCalcTable($echo = "0"){
		$CT = new HTMLTable(2);
		$CT->setColClass(1, "");
		$CT->setColClass(2, "");
		$CT->addColStyle(2, "text-align:right;");

		$CT->addRow(array("Einkauf"));
		$CT->addRowColspan(1, 2);
		$CT->addCellClass(1, "backgroundColor3");

		$ArtikelEK = $this->getArtikelEK1();
		$CT->addRow(array("<b>Artikel:</b>", Util::CLNumberParserZ($ArtikelEK)));
		
		$Lohn = 0;
		if($this->A("LohngruppeID") != "0" AND Session::isPluginLoaded("mLohngruppe")){
			$Lohn = $this->getLohnEK();
			if($Lohn != 0){
				$CT->addRow(array("<b>Lohn:</b>", Util::CLNumberParserZ($Lohn)));
			}
		}

		if(Session::isPluginLoaded("mStueckliste")){
			$EKStueckliste = $this->getGesamtEK1Stueckliste();
			$CT->addRow(array("<b>Stückliste:</b>", Util::CLNumberParserZ($EKStueckliste)));
		}
		
		$nettoEK = $this->getGesamtEK1();
		$CT->addRow(array("<b>Netto:</b>", Util::CLNumberParserZ($nettoEK)));
		$CT->addCellClass(1, "borderColor1");
		$CT->addCellClass(2, "borderColor1");
		$CT->addCellStyle(1, "border-top-style:double;");
		$CT->addCellStyle(2, "border-top-style:double;");
		
			
			
		$CT->addRow(array("&nbsp;"));

		$CT->addRow(array("Verkauf"));
		$CT->addRowColspan(1, 2);
		$CT->addCellClass(1, "backgroundColor3");

		$listenpreis = $this->getArtikelLP();
		if($listenpreis != 0)
			$CT->addRow(array("<b>Listenpreis:</b>", Util::CLNumberParserZ($listenpreis)));
		
		$aufschlagListenpreis = $this->getAufschlagListenpreis();
		if($aufschlagListenpreis != 0 AND $this->A("preisModus") == "0")
			$CT->addRow(array("<b>Aufschlag:</b><br /><small>".$this->A("aufschlagListenpreis")."%</small>", Util::CLNumberParserZ($aufschlagListenpreis)));
		
		
		
		if($Lohn != 0)
			$CT->addRow(array("<b>Lohn:</b>", Util::CLNumberParserZ($Lohn)));
		
		$aufschlagGesamt = $this->getAufschlagGesamt();
		if($aufschlagGesamt != 0 AND $this->A("preisModus") == "0")
			$CT->addRow(array("<b>Aufschlag:</b><br /><small>".$this->A("aufschlagGesamt")."%</small>", Util::CLNumberParserZ($aufschlagGesamt)));
		
		
		if(Session::isPluginLoaded("mStueckliste"))
			$CT->addRow(array("<b>Stückliste:</b>", Util::CLNumberParserZ($this->getGesamtNettoVKStueckliste())));
		
		$nettoVK = $this->getGesamtNettoVK();
		$CT->addRow(array("<b>Netto:</b>", Util::CLNumberParserZ($nettoVK)));

		#if($aufschlagGesamt != 0 OR $aufschlagListenpreis != 0){
			$CT->addCellClass(1, "borderColor1");
			$CT->addCellClass(2, "borderColor1");
			$CT->addCellStyle(1, "border-top-style:double;");
			$CT->addCellStyle(2, "border-top-style:double;");
		#}
		
		$CT->addRow(array("<b>Gewinn:</b>", Util::CLNumberParserZ($nettoVK - $nettoEK)));

		if(!Session::isPluginLoaded("mMwSt"))
			$CT->addRow(array("<b>Brutto:</b>", Util::CLNumberParserZ($this->getGesamtBruttoVK())));

		if($echo == "1")
			echo $CT;

		return $CT;
	}
	
	public static function preisInputParser($w, $l, $p){
		$B = new Button("Brutto- in Nettopreis umrechnen", "./images/i2/calc.png", "icon");
		#$B->rmePCR("Artikel", "-1", "calcBruttoPreis", array("\$j('input[name=preis]').val()", "\$j('select[name=mwst]').val()"), "function(transport){ $('preis').value = transport.responseText; }");
		$B->style("float:right;");
		$B->contextMenu("Calculator", "input[name=preis]", "Rechner", "right", "down");
		if(Session::isPluginLoaded("mMwSt"))
			$B = "";
		
		$I = new HTMLInput("preis", "text", $w);
		if(self::$hasLieferant AND $p == "0"){
			$I->isDisabled(true);
		}
		
		$IM = "";
		if(self::$hasLieferant){
			$I->style("width:40%;");
			
			$IM = new HTMLInput("preisModus", "select", $p, array("0" => "Nach Lieferant", "1" => "Festpreis"));
			$IM->style("width:45%;margin-left:5%;");
			$IM->onchange("contentManager.toggleFormFieldsTest(\$j(this).val() == '0', ['aufschlagGesamt', 'aufschlagListenpreis'], []); if(\$j(this).val() == '1') { \$j('input[name=preis]').prop('disabled', ''); } else { \$j('input[name=preis]').prop('disabled', 'disabled'); }");
		}
		
		return "$B$I$IM";
	}

	public function calcBruttoPreis($preis, $mwst){
		$preis = Util::CLNumberParserZ($preis, "store");
		$mwst = Util::CLNumberParserZ($mwst, "store");

		$netto = $preis / (100 + $mwst) * 100;

		echo Util::CLFormatNumber($netto, 3);
	}
	
	public function addFile($id){
		parent::addFile($id);
		Red::messageSaved();
	}

	public function getJSON(){
		echo parent::getJSON();
	}
	
	/*public function saveDefaultSachkonto($sachkonto){
		$F = new Factory("Userdata");
		$F->sA("name", "DVArtikelSachkonto");
		$F->sA("UserID", "-1");
		$U = $F->exists(true);
		if($U){
			$U->changeA ("wert", $sachkonto);
			$U->saveMe();
		} else {
			$F->sA("wert", $sachkonto);
			$F->store();
		}
		
		Red::messageSaved();
	}*/
	
	public function saveMultiEditField($fieldName, $value){
		if($fieldName == "bildDateiName" AND $value != "" AND Util::ext($value) != "jpg" AND Util::ext($value) != "png")
			Red::alertD("Es können nur jpg oder png-Dateien als Standard-Bild verwendet werden");
		
		$this->changeA($fieldName, $value);
		$this->saveMe(true, true);
	}
	
	public function getPic($echo = false){
		if($this->A("bildDateiName") == "")
			return "";
		
		$data = file_get_contents($this->A("bildDateiName"));
		
		$Image = "<img style=\"max-width:150px;\" src=\"data:image/".Util::ext($this->A("bildDateiName")).";base64,".base64_encode($data)."\">";
		
		if($echo)
			echo $Image;
		
		return $Image;
	}
}
?>