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
class PostenGUI extends Posten implements iGUIHTML2 {
	
	function __construct($ID){
		parent::__construct($ID);
		
		$this->setParser("mwst","Util::CLNumberParserZ");
		$this->setParser("differenzbesteuertMwSt","Util::CLNumberParserZ");
	}
	
	function getHTML($id){
		#if($id != -1) $this->recalcNetto = false;
		$userLabels = mUserdata::getRelabels("Artikel");
		$userHiddenFields = mUserdata::getHides("Artikel");

		$GRLBMID = BPS::getProperty("mPostenGUI","loadGRLBMID");
		$this->loadMeOrEmpty();

		if($this->A->isBrutto == "1")
			$this->A->preis = Util::CLNumberParserZ($this->A->bruttopreis,"load");
	
		$message = "";
		$gui = new HTMLGUIX($this);
		if($id == -1) {
			$G = new GRLBM($GRLBMID);
			$this->A = $this->newAttributes($G);
		} else {
			$G = new GRLBM($this->A->GRLBMID);
			if($G->A("isPayed") == "1" AND ($G->A("isR") == "1" OR $G->A("isA") == "1")){
				$message = "<p class=\"highlight\">Dieser Posten kann nicht mehr bearbeitet werden, der Beleg wurde gesperrt!</p>";
				$gui->optionsEdit (false, false);
			}
		}

		#$gui->setObject($this);
		$gui->name("Posten");
		$gui->formID("1xPostenForm");
		$gui->displayMode("popupN");
		
		$gui->attributes(array(
			"name",
			"gebinde",
			"GRLBMID",
			"preis",
			"menge",
			"menge2",
			"mwst",
			"artikelnummer",
			#"rabatt",
			"EK1",
			"EK2",
			"beschreibung",
			"isBrutto",
			"createArtikel",
			"erloeskonto"));
		
		$gui->label("gebinde","Einheit");
		$gui->label("artikelnummer","Artikelnummer");
		$gui->label("mwst","MwSt");
		$gui->label("name","Name");
		$gui->label("menge","Menge");
		$gui->label("preis","Preis");
		$gui->label("beschreibung","Beschreibung");
		$gui->label("createArtikel", "Artikel?");
		$gui->label("erloeskonto", "Erlöskonto");
		
		$gui->type("beschreibung","textarea");
		#$gui->type("bemerkung","hidden");
		$gui->type("GRLBMID","hidden");
		$gui->type("menge2","hidden");
		#$gui->type("oldArtikelID","hidden");
		$gui->type("createArtikel", "checkbox");

		$B = new Button("Großes Textfeld", "./images/i2/fullscreen.png", "icon");
		$B->onclick("TextEditor.showTextarea(\$j('[name=beschreibung]'), '1xPostenForm');");
		$gui->addFieldButton("beschreibung", $B);
		
		$Stammdaten = mStammdaten::getActiveStammdaten();
		try {
			$CurrentVorlage = $Stammdaten->A("ownTemplate");
			$Vorlage = new $CurrentVorlage($Stammdaten);
		} catch (ClassNotFoundException $e){
			$Vorlage = new Vorlage_de_DE_leer($Stammdaten);
		}
		
		if($this->A("PostenUsedSerials") != "[]" AND $this->A("PostenUsedSerials") != ""){
			$gui->type("menge", "readonly");
			$gui->descriptionField("menge", "Die Menge kann nicht bearbeitet werden, da für diesen Posten Seriennummern eingetragen wurden.");
		}
		#$gui->parser("preis","PostenGUI::preisInputParser");
		
		$BC = new Button("Brutto- in Nettopreis umrechnen", "./images/i2/calc.png", "icon");
		#$BC->onclick("rme('Artikel','-1','calcBruttoPreis', Array(\$j('#1xPostenForm input[name=preis]').val(), \$j('#1xPostenForm input[name=mwst]').val()),'\$j(\'#1xPostenForm input[name=preis]\').val(transport.responseText);');");
		$BC->contextMenu("Calculator", "#1xPostenForm input[name=preis]", "Rechner");
		
		$gui->addFieldButton("preis", $BC);
		
		$gui->space("menge");
		$gui->space("beschreibung");
		

		$gui->descriptionField("createArtikel","Soll dieser Posten als Artikel angelegt werden?");
		

		if($this->A("oldArtikelID") != "0" AND $this->A("oldArtikelID") != "")
			$gui->type("createArtikel", "hidden");
		else
			$gui->space("createArtikel");
		

		if(Session::isPluginLoaded("mBrutto")){
			$gui->type("isBrutto","checkbox");
			$gui->label("isBrutto","Bruttopreis?");
			$gui->descriptionField("isBrutto","Ist der angegebene Preis ein Bruttopreis?");
			$gui->label("preis","Preis");
			$gui->activateFeature("addSaveDefaultButton", $this, "isBrutto");
		} else 
			$gui->type("isBrutto", "hidden");

		if(Session::isPluginLoaded("mDifferenzbesteuerung")){
			$gui->insertAttribute ("after", "isBrutto", "differenzbesteuert");
			$gui->insertAttribute ("after", "differenzbesteuert", "differenzbesteuertMwSt");
			$gui->type("differenzbesteuert","checkbox");
			#$gui->type("mwst","hidden");
			$gui->label("differenzbesteuert", "Differenzbesteuert");
			$gui->label("differenzbesteuertMwSt", "Differenzbest. MwSt");
			$gui->toggleFields("differenzbesteuert", "1", array("differenzbesteuertMwSt"), array("mwst"));
			$gui->addFieldEvent("differenzbesteuert", "onchange", "if(this.checked) { \$j('[name=differenzbesteuertMwSt]').val(\$j('[name=mwst]').val()); \$j('[name=mwst]').val('".Util::CLNumberParserZ(0)."'); }");
		}
		
		if(Session::isPluginLoaded("mexportDatev") AND mUserdata::getGlobalSettingValue("DVKappendixKundenKonto", "0"))
			$gui->space("sachkonto");
		else
			$gui->type("sachkonto", "hidden");
		
		
		if(!Session::isPluginLoaded("Provisionen"))
			$gui->type("EK2", "hidden");
		
		#$gui->removeAttribute("bruttopreis");
		#$gui->removeAttribute("PostenIsAlternative");
		#$gui->removeAttribute("PostenSortOrder");
		#$gui->removeAttribute("rabatt");
		#$gui->removeAttribute("PostenAddLine");
		#$gui->removeAttribute("PostenNewPage");
		#$gui->removeAttribute("menge2");
		
		foreach($userHiddenFields as $key => $value)
			$gui->type($key, "hidden");
		
		foreach($userLabels as $key => $value)
			$gui->label($key,$value);
			
		if(mUserdata::getPluginSpecificData("Provisionen", "pluginSpecificHideEK"))
			$gui->type("EK2", "hidden");
		
		
		if(mUserdata::getPluginSpecificData("Provisionen", "pluginSpecificHideEK1"))
			$gui->type("EK1", "hidden");
		
		
		#$kat = new Kategorien();
		#$kat->addAssocV3("type","=","mwst");
		#$mwst = array("0" => "bitte auswählen");
		#$values = $kat->getArrayWithValues();
		#for($i=0;$i<count($values);$i++){
			#$keys[] = Util::parseFloat("de_DE", str_replace("%","",$values[$i]));
			#$values[$i] = Util::CLNumberParserZ(Util::parseFloat("de_DE",str_replace("%","",$values[$i])),"load")."%";
		#	$mwst[Util::parseFloat("de_DE",str_replace("%","",$values[$i])).""] = Util::CLNumberParserZ(Util::parseFloat("de_DE",str_replace("%","",$values[$i])),"load")."%";
		#}
		
		#$keys[] = "0";
		#$values[] = "bitte auswählen";
		
		#$gui->setOptions("mwst",$keys,$values);
		#$gui->type("mwst", "select", $mwst);
		
		$gui->addToEvent("onSave", "var scrolled = \$j('#PostenSortableContainer').scrollTop(); contentManager.loadFrame('subframe','GRLBM','".$this->A("GRLBMID")."', '', '', function(){ \$j('#PostenSortableContainer').scrollTop(scrolled); });");
		
		/*$gui->setJSEvent("onSave","function() { 
					Popup.close('Posten', 'edit');
					contentManager.loadFrame('subframe','GRLBM','".$this->A("GRLBMID")."');"."
				}");*/

		
		$gui->customize($this->customizer);

		/*if(in_array($G->getMyPrefix(), $Vorlage->sumHideOn)){
			$gui->type("preis", "hidden");
			$gui->type("mwst", "hidden");
			$gui->type("EK1", "hidden");
			$gui->type("EK2", "hidden");
			$gui->type("isBrutto", "hidden");
			$gui->type("rabatt", "hidden");
		} else*/
			$gui->space("EK1");
		
		#$gui->setStandardSaveButton($this);
		
		return $message.$gui->getEditHTML();
	}
	
	/*public static function preisInputParser($w, $l, $p){
		return "<img src=\"./images/i2/calc.png\" onclick=\"rme('Artikel','-1','calcBruttoPreis',Array(\$j('#1xPostenForm input[name=preis]').val(), \$j('#1xPostenForm select[name=mwst]').val()),'\$j(\'#1xPostenForm input[name=preis]\').val(transport.responseText);');\" style=\"float:right;\" class=\"mouseoverFade\" title=\"Brutto- in Nettopreis umrechnen\" />
			<input type=\"text\" value=\"$w\" id=\"preis\" style=\"width: 85%;\" name=\"preis\" onblur=\"blurMe(this);\" onfocus=\"focusMe(this);\"/>";
	}*/
	
	public function saveMultiEditField($field, $value){
		parent::saveMultiEditField($field, $value);
		#print_r($this->getA());
		Red::messageD("Änderung gespeichert", array("bestand" => $this->messageBestand));
	}
	
	public function popupOptions($ArtikelID, $GRLBMID){
		$GRLBM = new GRLBM($GRLBMID);
		$Artikel = new Artikel($ArtikelID);
		
		$B = new Button("Beenden", "stop");
		$B->style("float:right;margin:5px;");
		$B->onclick(OnEvent::closePopup("Posten"));
		
		$html = "<p class=\"prettyTitle\">$B".$Artikel->A("name")."</p><div style=\"clear:both;\"></div>";
		
		$i = 0;
		if(Session::isPluginLoaded("mLieferant")){
			$c = LieferantGUI::popupSelection($GRLBM, $ArtikelID);
			if($c != "")
				$html .= "<div class=\"sub\" style=\"width:250px;display:inline-block;vertical-align:top;\">$c</div>";
		}
		
		if(Session::isPluginLoaded("mVariante")){
			$c = VarianteArtikelGUI::popupSelection($GRLBM, $ArtikelID);
			if($c != "")
				$html .= "<div class=\"sub\" style=\"width:250px;display:inline-block;vertical-align:top;\">$c</div>";
		}
		
		if(Session::isPluginLoaded("mSeriennummer")){
			$c = SeriennummerGUI::popupSelection($GRLBM, $ArtikelID);
			if($c != "")
				$html .= "<div class=\"sub\" style=\"width:250px;display:inline-block;vertical-align:top;\">$c</div>";
		}
		
		$aspect = Aspect::joinPoint("selections", $this, __METHOD__, array($ArtikelID, $GRLBMID), array());
		if(is_array($aspect))
			$html .= implode("", $aspect);
		else
			$html .= $aspect;
		
		$IAID = new HTMLInput("ArtikelID", "hidden", $ArtikelID);
		$IGID = new HTMLInput("GRLBMID", "hidden", $GRLBMID);
		
		$BOK = new Button("Posten erstellen\nund beenden", "bestaetigung");
		$BOK->style("float:right;margin:10px;");
		$BOK->rmePCR("Posten", "-1", "popupOptionsDo", array("joinFormFields('newPostenForm')")/*array($ArtikelID, $GRLBMID, "\$j('[name=lieferantSelection]').length ? \$j('[name=lieferantSelection]:checked').val() : 0", "\$j('[name=variantSelection]').length ? \$j('[name=variantSelection]:checked').val() : 0", "\$j('[name=seriennummerSelection]').length ? \$j('[name=seriennummerSelection]').val() : ''")*/, "function(t){ Auftrag.checkBestand(t); contentManager.loadFrame('subframe', 'GRLBM', $GRLBMID); ".OnEvent::closePopup("Posten")." }");
		
		$BOK2 = new Button("Posten erstellen\nund nochmal", "navigation");
		$BOK2->style("float:right;margin:10px;");
		$BOK2->rmePCR("Posten", "-1", "popupOptionsDo", array("joinFormFields('newPostenForm')"), "function(t){ Auftrag.checkBestand(t); Auftrag.reloadBeleg($GRLBMID);/*contentManager.loadFrame('subframe', 'GRLBM', $GRLBMID);*/  }");
		$BOK2->className("backgroundColor4");
		
		echo "<form id=\"newPostenForm\">".$html.$IAID.$IGID."</form>".$BOK.$BOK2.OnEvent::script("\$j('#editDetailsPosten').css('width', \$j('#editDetailsPosten .sub').length * 250);");
	}
	
	public function popupOptionsDo($data){
		parse_str($data, $pdata);
		
		$ArtikelID = $pdata["ArtikelID"];
		$GRLBMID = $pdata["GRLBMID"];
		$LieferantID = null;
		$VarianteArtikelID = 0;
		$Seriennummern = "";
		
		if(isset($pdata["lieferantSelection"]))
			$LieferantID = $pdata["lieferantSelection"];
		
		if(isset($pdata["variantSelection"]))
			$VarianteArtikelID = $pdata["variantSelection"];
		
		if(isset($pdata["seriennummerSelection"]))
			$Seriennummern = $pdata["seriennummerSelection"];
		
		$menge = 1;
		if(Session::isPluginLoaded("mSeriennummer") AND Seriennummer::has($ArtikelID)){
			if(trim($Seriennummern) == "")
				Red::alertD ("Bitte tragen Sie mindestens eine Seriennummer ein");
			
			$S = new mSeriennummer();
			$data = $S->checkNew("Artikel", $ArtikelID, explode("\n", trim($Seriennummern)), "sell");
			
			if($data[2])
				Red::alertD($data[2]." Seriennummer".($data[2] == 1 ? "" : "n")." befinde".($data[2] == 1 ? "t" : "n")." sich nicht im Lager.");
			
			$menge = $data[1];
		}
		
		Aspect::joinPoint("before", $this, __METHOD__, array($ArtikelID, $GRLBMID, $pdata));
		
		$P = new Posten(-1);
		$P->skipVariantTest = true;
		$P->skipLieferantTest = true;
		$P->skipSeriennummernTest = true;
		$PostenID = $P->newFromArtikel($ArtikelID, $GRLBMID, $menge, null, null, null, $VarianteArtikelID, $LieferantID);
				
		#if($VarianteArtikelID != 0){
		#	$V = new VarianteArtikel($VarianteArtikelID);
		#	$V->fixPosten($ArtikelID, $PostenID);
		#}
		
		if(Session::isPluginLoaded("mSeriennummer") AND trim($Seriennummern) != "")
			$S->doSell(explode("\n", trim($Seriennummern)), "Artikel", $ArtikelID, $GRLBMID, $PostenID);
		
		Aspect::joinPoint("after", $this, __METHOD__, array($ArtikelID, $GRLBMID, $PostenID, $pdata));
		
		Red::messageD("Posten erstellt", array("PostenID" => $PostenID, "bestand" => $P->messageBestand));
	}
}
?>