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

class Bericht_KundenpreislisteGUI extends Bericht_default implements iBerichtDescriptor {
	
 	function __construct() {
 		parent::__construct();
 		
 		if(!$_SESSION["S"]->checkForPlugin("mArtikel"))
			return;
 		
		$Artikel = anyC::get("Artikel");
		
		if(isset($this->userdata["useKPShown"]) AND $this->userdata["useKPShown"] != "")
			$Artikel->addAssocV3("t1.KategorieID", "IN", "(".str_replace(",,", ",", trim($this->userdata["useKPShown"], ",")).")");
		
 		$Artikel->addJoinV3("Kategorie","KategorieID","=","KategorieID");
 		$Artikel->setFieldsV3(array("bildDateiName", "artikelnummer", "t2.name AS katName","t1.name","preis", "mwst", "aufschlagGesamt", "aufschlagListenpreis", "LohngruppeID", "Lohnminuten"));
 		$Artikel->addAssocV3("hideInReport","=","0");
		
		if(isset($this->userdata["useKPOrder"]) AND $this->userdata["useKPOrder"] != "")
			$Artikel->addOrderV3("FIND_IN_SET(t1.KategorieID, '".  str_replace(";", ",", $this->userdata["useKPOrder"])."')");
	
		if(isset($this->userdata["useKPArtOrder"]) AND $this->userdata["useKPArtOrder"] != "")
			$Artikel->addOrderV3($this->userdata["useKPArtOrder"]);
		else
			$Artikel->addOrderV3("name");

 		$this->collection = $Artikel;
 	}
 	
 	public function getLabel(){
 		if($_SESSION["S"]->checkForPlugin("mArtikel"))
			return "Kundenpreisliste";

		return null;
 	}
	
 	public function loadMe(){
 		parent::loadMe();

 		$this->A->useKPOrder = "";
 		$this->A->useKPShown = "";
 		$this->A->useKPPics = "0";
 		$this->A->useKPArtOrder = "";
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
		
		
		if(isset($this->userdata["useKPOrder"]) AND $this->userdata["useKPOrder"] != ""){
			$KTemp = array();
			$ex = explode(";", $this->userdata["useKPOrder"]);
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
			if(isset($this->userdata["useKPShown"]) AND strpos($this->userdata["useKPShown"], ",$KID,") !== false)
				$val = 1;
			
			$I = new HTMLInput("useEntry", "checkbox", $val);
			$I->style("vertical-align:middle;");
			$I->data("kid", $KID);
			$I->onchange("if(this.checked) \$j('#bericht [name=useKPShown]').val(\$j('#bericht [name=useKPShown]').val()+',$KID,').trigger('change'); else \$j('#bericht [name=useKPShown]').val(\$j('#bericht [name=useKPShown]').val().replace(',$KID,', '')).trigger('change');");
			
			$L->addItem($B.$I.$K->A("name"));
			$L->setItemID("K_$KID");
			$L->addItemStyle("margin-top:0px;");
		}
		
		
		$T = new HTMLTable(1, "Artikel-Kategorien:");
		$T->addRow($L);
		
		$F = new HTMLForm("bericht", array("useKPOrder", "useKPShown", "useKPPics", "useKPArtOrder"));
		$F->getTable()->setColWidth(1, 120);
		$F->setSaveBericht($this);
		
		
		$F->inputLineStyle("useKPOrder", "display:none;");
		$F->inputLineStyle("useKPShown", "display:none;");
		
		$F->setType("useKPPics", "checkbox");
		$F->setType("useKPArtOrder", "select", null, array("" => "Artikelname", "artikelnummer" => "Artikelnummer"));
		
		foreach($this->userdata AS $k => $v)
			$F->setValue($k, $v);
		
		$F->setLabel("useKPArtOrder", "Sortierung");
		$F->setLabel("useKPPics", "Bilder anzeigen?");
		$F->useRecentlyChanged();
		
		
		$js = OnEvent::script("
			\$j('#sortMe').sortable({
				handle: \$j('.handle'),
				axis: 'y',
				update: function(event, ui){
					\$j('#bericht [name=useKPOrder]').val(\$j('#sortMe').sortable('serialize').replace(/&/g,';').replace(/K\[\]\=/g,'')).trigger('change');
				}
			});
			\$j('#sortMe').disableSelection();");
		
 		return $phtml.$T.$F.$js;
 	}

	private static $usePics = false;
 	public function getPDF($save = false){
 		$userLabels = mUserdata::getRelabels("Artikel");

		$usePics = self::$usePics = (isset($this->userdata["useKPPics"]) AND $this->userdata["useKPPics"]);
		
		foreach($userLabels AS $key => $value)
 			$this->setLabel($key, $value);
 		
 		$nameWidth = 150;
 		
 		$this->fieldsToShow = array();

		if($usePics){
			$this->fieldsToShow[] = "bildDateiName";
			$nameWidth -= 20;
		}
		
		if(isset($this->userdata["useKPArtOrder"]) AND $this->userdata["useKPArtOrder"] != ""){
			$this->fieldsToShow[] = "artikelnummer";
			$this->fieldsToShow[] = "name";
		} else {
			$this->fieldsToShow[] = "name";
			$this->fieldsToShow[] = "artikelnummer";
		}
		
 		$this->fieldsToShow[] = "preis";

 		$this->groupBy = "katName";

 		$this->setHeader("Preisliste vom ".date("d.m.Y"));

		$this->setLabel("bildDateiName", "");
		$this->setLabel("artikelnummer", "Art.Nr.");

 		$this->setAlignment("preis", "R");
		$this->setDefaultFont("Arial", "", 8);
		$this->setDefaultCellHeight(3);
 		$this->setColWidth("name", $nameWidth);
 		$this->setColWidth("preis", 0);
 		
 		$this->setFieldParser("bildDateiName", "Bericht_mArtikelGUI::parserBild");
 		$this->setFieldParser("preis", "Bericht_KundenpreislisteGUI::parserPreis");
		$this->setLineParser("after", "Bericht_KundenpreislisteGUI::parserLine");
		
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
		$E->resetParsers();
		
 		return Util::conv_euro(Util::CLFormatCurrency($E->getGesamtBruttoVK(true) * 1, true));
 	}
	
} 
?>