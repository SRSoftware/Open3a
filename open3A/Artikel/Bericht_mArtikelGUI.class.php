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

class Bericht_mArtikelGUI extends Bericht_default implements iBerichtDescriptor {
 	function __construct() {
 		parent::__construct();
 		
 		if(!$_SESSION["S"]->checkForPlugin("mArtikel"))
			return;
 		
		$Artikel = anyC::get("Artikel");
		
		if(isset($this->userdata["useBAShown"]) AND $this->userdata["useBAShown"] != "")
			$Artikel->addAssocV3("t1.KategorieID", "IN", "(".str_replace(",,", ",", trim($this->userdata["useBAShown"], ",")).")");
		
 		$Artikel->addJoinV3("Kategorie","KategorieID","=","KategorieID");
 		$Artikel->setFieldsV3(array("bildDateiName", "artikelnummer", "t2.name AS katName","t1.name","preis","EK1","EK2", "artikelnummer"));
 		$Artikel->addAssocV3("hideInReport","=","0");
		
		if(isset($this->userdata["useBAOrder"]) AND $this->userdata["useBAOrder"] != "")
			$Artikel->addOrderV3("FIND_IN_SET(t1.KategorieID, '".  str_replace(";", ",", $this->userdata["useBAOrder"])."')");
		
		if(isset($this->userdata["useBAArtOrder"]) AND $this->userdata["useBAArtOrder"] != "")
			$Artikel->addOrderV3($this->userdata["useBAArtOrder"]);
		else
			$Artikel->addOrderV3("name");

 		$this->collection = $Artikel;
 	}
 	
 	public function getLabel(){
 		if($_SESSION["S"]->checkForPlugin("mArtikel")) return "Artikelliste";
 		else return null;
 	}

	
 	public function loadMe(){
 		parent::loadMe();

 		$this->A->useBAOrder = "";
 		$this->A->useBAShown = "";
 		$this->A->useBAPics = "0";
 		$this->A->useBAArtOrder = "";
 	}
 	
 	public function getHTML($id){
 		$this->loadMe();
 		
 		$phtml = parent::getHTML($id);
 		
		$Kategorien = array();
		
		$Kategorien[0] = new Kategorie(0);
		$Kategorien[0]->loadMeOrEmpty();
		$Kategorien[0]->changeA("name", "Ohne Kategorie");
		
		
		$AC = anyC::get("Kategorie");
		$AC->addAssocV3("type", "=", "2");
		while($K = $AC->n())
			$Kategorien[$K->getID()] = $K;
		
		
		if(isset($this->userdata["useBAOrder"]) AND $this->userdata["useBAOrder"] != ""){
			$KTemp = array();
			$ex = explode(";", $this->userdata["useBAOrder"]);
			foreach($ex AS $KID){
				if(!isset($Kategorien[$KID]))
					continue;
				
				$KTemp[$KID] = $Kategorien[$KID];
			}
			
			foreach($Kategorien AS $KID => $K){
				if(isset($KTemp[$KID]))
					continue;
				
				$KTemp[$KID] = $K;
			}
			
			$Kategorien = $KTemp;
		}
		
		
		$L = new HTMLList();
		$L->noDots();
		$L->setListID("sortMe");
		$L->maxHeight(300);
		foreach($Kategorien AS $KID => $K){
			
			$B = new Button("Eintrag verschieben", "./images/i2/topdown.png", "icon");
			$B->style("cursor:move;float:left;margin-right:5px;");
			$B->className("handle");
			
			$val = 0;
			if(isset($this->userdata["useBAShown"]) AND strpos($this->userdata["useBAShown"], ",$KID,") !== false)
				$val = 1;
			
			$I = new HTMLInput("useEntry", "checkbox", $val);
			$I->style("vertical-align:middle;");
			$I->data("kid", $KID);
			$I->onchange("if(this.checked) \$j('#bericht [name=useBAShown]').val(\$j('#bericht [name=useBAShown]').val()+',$KID,').trigger('change'); else \$j('#bericht [name=useBAShown]').val(\$j('#bericht [name=useBAShown]').val().replace(',$KID,', '')).trigger('change');");
			
			$L->addItem($B.$I.$K->A("name"));
			$L->setItemID("K_$KID");
			$L->addItemStyle("margin-top:0px;");
		}
		
		
		$T = new HTMLTable(1, "Artikel-Kategorien:");
		$T->addRow($L);
		
		$F = new HTMLForm("bericht", array("useBAOrder", "useBAShown", "useBAPics", "useBAArtOrder"));
		$F->getTable()->setColWidth(1, 120);
		$F->setSaveBericht($this);
		
		$F->setLabel("useBAArtOrder", "Sortierung");
		
		$F->inputLineStyle("useBAOrder", "display:none;");
		$F->inputLineStyle("useBAShown", "display:none;");
		
		$F->setType("useBAArtOrder", "select", null, array("" => "Artikelname", "artikelnummer" => "Artikelnummer"));
		$F->setType("useBAPics", "checkbox");
		
		foreach($this->userdata AS $k => $v)
			$F->setValue($k, $v);
		
		$F->setLabel("useBAPics", "Bilder anzeigen?");
		$F->useRecentlyChanged();
		
		
		$js = OnEvent::script("
			\$j('#sortMe').sortable({
				handle: \$j('.handle'),
				axis: 'y',
				update: function(event, ui){
					\$j('#bericht [name=useBAOrder]').val(\$j('#sortMe').sortable('serialize').replace(/&/g,';').replace(/K\[\]\=/g,'')).trigger('change');
				}
			});
			\$j('#sortMe').disableSelection();");
		
 		return $phtml.$T.$F.$js;
 	}

	private static $usePics = false;
 	public function getPDF($save = false){

 		$userLabels = mUserdata::getRelabels("Artikel");
		$userHiddenFields = mUserdata::getHides("Artikel");
		$usePics = self::$usePics = (isset($this->userdata["useBAPics"]) AND $this->userdata["useBAPics"]);
		foreach($userLabels AS $key => $value)
 			$this->setLabel($key, $value);
 		
 		$nameWidth = 100;
 		
 		$this->fieldsToShow = array();
		
		if($usePics){
			$this->fieldsToShow[] = "bildDateiName";
			$nameWidth -= 20;
		}
		
		
		if(isset($this->userdata["useBAArtOrder"]) AND $this->userdata["useBAArtOrder"] != ""){
			$this->fieldsToShow[] = "artikelnummer";
			$this->fieldsToShow[] = "name";
		} else {
			$this->fieldsToShow[] = "name";
			$this->fieldsToShow[] = "artikelnummer";
		}
		
 		if(!isset($userHiddenFields["EK1"]))
			$this->fieldsToShow[] = "EK1";
 		else
			$nameWidth += 20;
 		
		
 		if(!isset($userHiddenFields["EK2"]))
			$this->fieldsToShow[] = "EK2";
 		else
			$nameWidth += 20;
 		
 		$this->fieldsToShow[] = "preis";

 		$this->groupBy = "katName";

 		$this->setHeader("Artikelliste vom ".date("d.m.Y"));
		$this->setDefaultFont("Arial", "", 8);
		$this->setDefaultCellHeight(3);

 		$this->setAlignment("EK1", "R");
 		$this->setAlignment("EK2", "R");
 		$this->setAlignment("preis", "R");
 		$this->setAlignment("artikelnummer", "R");

 		$this->setColWidth("name", $nameWidth);
 		$this->setColWidth("preis", 0);
 		
 		$this->setFieldParser("preis", "Bericht_mArtikelGUI::parserPreis");
 		$this->setFieldParser("EK1", "Bericht_mArtikelGUI::parserEK1");
 		$this->setFieldParser("EK2", "Bericht_mArtikelGUI::parserEK2");
 		$this->setFieldParser("bildDateiName", "Bericht_mArtikelGUI::parserBild");
		$this->setLineParser("after", "Bericht_mArtikelGUI::parserLine");
		
		$this->setLabel("artikelnummer", "Art.Nr.");
		$this->setLabel("bildDateiName", "");
		
 		return parent::getPDF($save);
 	}
	
	public static function parserLine($pdf, $E){
		if($E->A("bildDateiName") == "")
			return;
		
		if(!file_exists($E->A("bildDateiName")))
			return;
		
		if(!self::$usePics)
			return;
		
		list($width, $height) = getimagesize($E->A("bildDateiName"));
		$ratio = $width / $height;
		$imHeight = 20 / $ratio;
		
		$pdf->Ln($imHeight);
	}
	
	public static function parserBild($w){
		if($w == "")
			return;
				
		if(!file_exists($w))
			return;
		
		self::$pdf->Image($w, self::$pdf->GetX(), self::$pdf->GetY(), 20);
	}
 	
 	public static function parserPreis($w, $p, $A, $E){
 		return Util::conv_euro(Util::CLFormatCurrency($E->getGesamtNettoVK(false) * 1, true));#number_format(str_replace(",",".",$w),2,",",".").chr(128);
 	}
 	
 	public static function parserEK1($w, $p, $A, $E){
		$E->resetParsers();
 		return Util::conv_euro(Util::CLFormatCurrency($E->getGesamtEK1() * 1, true));#number_format(str_replace(",",".",$w),2,",",".").chr(128);
 	}
 	
 	public static function parserEK2($w, $p, $A, $E){
 		return Util::conv_euro(Util::CLFormatCurrency($w * 1, true));#number_format(str_replace(",",".",$w),2,",",".").chr(128);
 	}
 } 
 ?>