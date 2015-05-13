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

class Bericht_OffeneAngeboteNachArtikelGUI extends Bericht_default implements iBerichtDescriptor {
 	public function loadMe(){
 		parent::loadMe();

 		$this->A->useBOAStart = "";
 		$this->A->useBOAEnde = "";
 	}
	
	function __construct() {
 		parent::__construct();

		if(Applications::activeApplication() != "open3A") return;
 		if(!Session::isPluginLoaded("Auftraege")) return;
 		
 		
 		$ac = anyC::get("Auftrag", "status", "open");
 		$ac->addAssocV3("isA","=","1");
 		#$ac->addAssocV3("isG","=","1","OR","2");
		$ac->addOrderV3("artikelnummer");
 		$ac->addJoinV3("GRLBM","AuftragID","=","AuftragID");
		$ac->addJoinV3("Posten","t2.GRLBMID","=","GRLBMID");
		#$ac->addJoinV3("Auftrag","AuftragID","=","AuftragID");
		#$ac->addJoinV3("Adresse","t1.AdresseID","=","AdresseID");
		$ac->addAssocV3("oldArtikelID", "!=", "0");
		$ac->addGroupV3("oldArtikelID");
		
		if($this->userdata != null AND isset($this->userdata["useBOAStart"]) AND $this->userdata["useBOAStart"] != "")
			$ac->addAssocV3("t2.datum", ">=", Util::CLDateParser($this->userdata["useBOAStart"], "store"));
		
		if($this->userdata != null AND isset($this->userdata["useBOAEnde"]) AND $this->userdata["useBOAEnde"] != "")
			$ac->addAssocV3("t2.datum", "<=", Util::CLDateParser($this->userdata["useBOAEnde"], "store"));
		
		
		$ac->setFieldsV3(array(
			"name",
			"SUM(menge) AS menge",
			"artikelnummer",
			"SUM(menge * preis) AS gesamtpreis",
			"oldArtikelID"));
		
		$ac->setParser("datum","Util::CLDateParser");
		
 		$this->collection = $ac;
 	}
 	
 	public function getLabel(){
		return "Offene Angebote nach Artikel";
 	}
	public function getHTML($id){
 		$phtml = parent::getHTML($id);
 		
		$F = new HTMLForm("BP", array("useBOAStart", "useBOAEnde"), "Monat");
		$F->getTable()->setColWidth(1, 120);
		
		$F->setType("useBOAStart", "date", ($this->userdata != null AND isset($this->userdata["useBOAStart"])) ? $this->userdata["useBOAStart"] : "");
		$F->setType("useBOAEnde", "date", ($this->userdata != null AND isset($this->userdata["useBOAEnde"])) ? $this->userdata["useBOAEnde"] : "");
		
		$F->setLabel("useBOAStart", "Start");
		$F->setLabel("useBOAEnde", "Ende");
		
		$F->setSaveBericht($this);
		$F->useRecentlyChanged();
		
 		return $phtml.$F;
 	}
 	
 	public function getPDF($save = false){
		
		$widthName = 900;
		$fields = array("artikelnummer", "name");
		
		if(Session::isPluginLoaded("mLager")){
			$fields[] = "lager";
			$widthName = 50;
		}
		$fields[] = "menge";
		
		if(Session::isPluginLoaded("mLager"))
			$fields[] = "fehlt";
		
		$fields[] = "gesamtpreis";
		
 		$this->fieldsToShow = $fields;
 		$this->setHeader("Offene Angebote nach Artikel vom ".Util::CLDateParser(time()));
		
 		$this->setLabel("artikelnummer", "Artikel-Nr");
		
 		$this->setAlignment("menge","R");
 		$this->setAlignment("gesamtpreis","R");
 		$this->setAlignment("lager","R");
 		$this->setAlignment("fehlt","R");
		
 		$this->setColWidth("name",$widthName);
 		$this->setColWidth("gesamtpreis",40);
 		$this->setColWidth("artikelnummer",40);
		
 		$this->setFieldParser("menge","Bericht_OffeneAngeboteNachArtikelGUI::parserMenge");
 		$this->setFieldParser("gesamtpreis","Bericht_OffeneAngeboteNachArtikelGUI::parserGesamt");
		if(Session::isPluginLoaded("mLager")){
			$this->setFieldParser("lager","Bericht_OffeneAngeboteNachArtikelGUI::parserLager");
			$this->setFieldParser("fehlt","Bericht_OffeneAngeboteNachArtikelGUI::parserFehlt");
		}
		$this->setSumParser("gesamtpreis", "Util::CLFormatCurrency");
		
		$this->calcSum("Gesamtsumme", array("gesamtpreis"));
		/*
 		$this->setAlignment("nummer","R");
 		$this->setAlignment("USt","R");
 		$this->setAlignment("Netto","R");
 		$this->setAlignment("kundennummer","R");

		
 		$this->setLabel("nummer", "Beleg Nr.");
 		$this->setLabel("Name","Kunde");
 		$this->setLabel("Summe","Brutto");
 		$this->setLabel("belegTyp","");
 		$this->setLabel("kundennummer","KdNr");
		
 		$this->setColWidth("belegTyp","5");
 		$this->setColWidth("kundennummer","10");
 	
		$this->setLineParser("after", "Bericht_OffeneAngeboteNachKundeGUI::parserLine");
		
		$this->setFieldParser("Summe","Util::PDFCurrencyParser");
		$this->setFieldParser("USt","Util::PDFCurrencyParser");
		$this->setFieldParser("Netto","Util::PDFCurrencyParser");
		
		$this->calcSum("Summe",array("Netto","USt","Summe"));
		
		$this->setSumParser("Netto", "Util::PDFCurrencyParser");
		$this->setSumParser("USt", "Util::PDFCurrencyParser");
		$this->setSumParser("Summe", "Util::PDFCurrencyParser");

 		$this->setType("Name","MultiCell");
		
 		$this->setPageBreakMargin(260);*/
 		

 		return parent::getPDF($save);
 	}
	private static $lager = array();
	public static function parserLager($w, $l, $p, $E){
		$L = Lagerbestand::ofArtikel($E->A("oldArtikelID"));
		if($L == null){
			self::$lager[$E->A("oldArtikelID")] = 0;
			return Util::CLNumberParser(0);
		} else {
			self::$lager[$E->A("oldArtikelID")] = $L->A("menge")*1;
			return Util::CLNumberParserZ($L->A("menge")*1);
		}
	}
	
	public static function parserFehlt($w, $l, $p, $E){
		if(self::$lager[$E->A("oldArtikelID")] - $E->A("menge") >= 0)
			return "";
		
		return Util::CLNumberParserZ(self::$lager[$E->A("oldArtikelID")] - $E->A("menge"));
	}
	
	public static function parserMenge($w){
		return Util::CLFormatNumber($w * 1);
	}
	
	public static function parserGesamt($w){
		return Util::CLFormatCurrency($w * 1);
	}
	
	/*public static function parserLine(FPDF $fpdf, $E){
		$fpdf->SetDrawColor(190, 190, 190);
		$fpdf->Line(10,$fpdf->GetY(),200, $fpdf->GetY());
		$fpdf->SetDrawColor(0, 0, 0);
		
		$fpdf->SetFontSize(8);
		$fpdf->SetTextColor(100);
		$AC = anyC::get("Posten", "GRLBMID", $E->A("GRLBMID"));
		while($P = $AC->getNextEntry()){
			if($fpdf->GetY() > 280)
				$fpdf->AddPage ();
			
			$fpdf->Cell8(20, 4.5, "");
			$fpdf->Cell8(25, 4.5, substr($P->A("name"), 0, 23));
			$fpdf->Cell(20, 4.5, Util::PDFCurrencyParser($P->A("preis") * $P->A("menge")), "", 0, "R");
			$fpdf->Cell(20, 4.5, Util::PDFCurrencyParser(($P->A("bruttopreis") - $P->A("preis")) * $P->A("menge")), "", 0, "R");
			$fpdf->Cell(20, 4.5, Util::PDFCurrencyParser($P->A("bruttopreis") * $P->A("menge")), "", 1, "R");
		}
		if($AC->numLoaded() == 0){
			$fpdf->Cell8(20, 4.5, "");
			$fpdf->Cell8(25, 4.5, "Keine Positionen", "", 1);
		}
		
		$fpdf->SetTextColor(0);
		$fpdf->SetFontSize(9);
		$fpdf->ln(5);
	}*/
 } 
 ?>