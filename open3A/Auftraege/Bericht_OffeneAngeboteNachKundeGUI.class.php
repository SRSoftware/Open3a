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

class Bericht_OffeneAngeboteNachKundeGUI extends Bericht_default implements iBerichtDescriptor {
	function __construct() {
 		parent::__construct();

		if(Applications::activeApplication() != "open3A") return;
 		if(!Session::isPluginLoaded("Auftraege")) return;
 		
 		
 		$ac = anyC::get("Auftrag", "status", "open");
 		$ac->addAssocV3("isA","=","1");
 		#$ac->addAssocV3("isG","=","1","OR","2");
		$ac->addOrderV3("datum","ASC");
		$ac->addOrderV3("nummer","ASC");
 		$ac->addJoinV3("GRLBM","AuftragID","=","AuftragID");
		$ac->addJoinV3("Posten","t2.GRLBMID","=","GRLBMID");
		#$ac->addJoinV3("Auftrag","AuftragID","=","AuftragID");
		$ac->addJoinV3("Adresse","t1.AdresseID","=","AdresseID");
		$ac->addGroupV3("t2.GRLBMID");
		
		$ac->setFieldsV3(array(
			"nummer",
			"datum",
			"bruttobetrag AS Summe",
			"steuern AS USt",
			"nettobetrag AS Netto",
			"firma",
			#"isA",
			#"isAbschlussrechnung",
			"t1.AuftragID",
			#"GRLBMpayedDate",
			#"GRLBMpayedVia",
			#"isPayed",
			"t2.GRLBMID",
			"IF(isA='1', 'A','') AS belegTyp",
			"CONCAT(IF(firma='',CONCAT(vorname,' ', nachname),firma),'\n',strasse,' ',nr,'\n',plz,' ',ort) AS Name",
			"IF(kundennummer > 0, kundennummer, '') AS kundennummer"));
		
		$ac->setParser("datum","Util::CLDateParser");
		
 		$this->collection = $ac;
 	}
 	
 	public function getLabel(){
		return "Offene Angebote nach Kunde";
 	}
 	
 	public function getPDF($save = false){
 		
 		$this->setAlignment("Summe","R");
 		$this->setAlignment("nummer","R");
 		$this->setAlignment("USt","R");
 		$this->setAlignment("Netto","R");
 		$this->setAlignment("kundennummer","R");

		$this->fieldsToShow = array("nummer","belegTyp","datum","Netto","USt","Summe","Name", "kundennummer");
 		$this->setLabel("nummer", "Beleg Nr.");
 		$this->setLabel("Name","Kunde");
 		$this->setLabel("Summe","Brutto");
 		$this->setLabel("belegTyp","");
 		$this->setLabel("kundennummer","KdNr");
		
 		$this->setColWidth("Name","50");
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
		
 		$this->setPageBreakMargin(260);
 		
 		$this->setHeader("Offene Angebote nach Kunde vom ".Util::CLDateParser(time()));

 		return parent::getPDF($save);
 	}
	
	public static function parserLine(FPDF $fpdf, $E){
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
	}
 } 
 ?>