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
class exportPrueferGUI extends exportDefault implements iExport {
	protected $usedDate = "datum";
	protected $belege = array("R", "G");
	
	protected $headersBelege = array(
		"Belegtyp",
		"Belegnummer",
		"Belegdatum",
		"Lieferdatum",
		"Leistungsdatum",
		"Belegsumme netto",
		"Beleg USt Betrag",
		"Rabatt Prozent",
		"Rabatt Betrag",
		"Zahlstatus",
		"Zahldatum",
		"Zahlbetrag",
		"Währung",
		"Kundennummer",
		"Kundenname",
		"Straße",
		"Ort",
		"Land",
		"USt-Id-Nr",
		"Steuerfrei"
	);
	
	protected $headersPositionen = array(
		"Belegtyp",
		"Belegnummer",
		"Bezeichnung",
		"Einheit",
		"Menge",
		"Rabatt Prozent",
		"Belegrabatt",
		"Preis netto",
		"Ust Betrag",
		"Ust Satz Prozent"
	);
	
	public function getLabel(){
		return "Betriebsprüfung";
	}
	
	public function getApps(){
		return array("open3A", "openFiBu");
	}

	public static $alreadyExported = array();
	public static $className;
	
	
	public function getHTML($id, $page = 0){
		self::$className = get_class($this);

		$ST = new HTMLSideTable("right");

		$B = $ST->addButton("Belege", "export");
		$B->windowRme(str_replace("GUI", "", get_class($this)), "-1", "getExportDataBelege", array("joinFormFields('exportForm')"));
		
		$B = $ST->addButton("Positionen", "export");
		$B->windowRme(str_replace("GUI", "", get_class($this)), "-1", "getExportDataPositionen", array("joinFormFields('exportForm')"));

		#$B = $ST->addButton("Auswahl\ninvertieren", "bestaetigung");
		#$B->onclick("for(i = 0;i < $('exportForm').elements.length;i++) { $('exportForm').elements[i].checked = !$('exportForm').elements[i].checked; }");

		$TT = new HTMLTable(2, "Bitte geben Sie einen Zeitraum ein:");
		$TT->setColWidth(1, 120);
		
		$IS = new HTMLInput("ZeitraumStart", "date");#, "01.01.2015");
		$IS->hasFocusEvent(true);

		$IE = new HTMLInput("ZeitraumEnde", "date");#, "31.12.2015");
		$IE->hasFocusEvent(true);

		$TT->addLV("Von:", $IS);
		$TT->addLV("Bis:", $IE);

		return $ST."<form id=\"exportForm\">".$TT."</form>";
	}


	public function factory(){
		return null;
	}
	
	public function getExportDataBelege($data){
		parse_str($data, $parsedData);
		unset($parsedData["targetPage"]);

		if($parsedData["ZeitraumStart"] == "")
			unset($parsedData["ZeitraumStart"]);
		else
			$parsedData["ZeitraumStart"] = Util::CLDateParser($parsedData["ZeitraumStart"], "store");

		if($parsedData["ZeitraumEnde"] == "")
			unset($parsedData["ZeitraumEnde"]);
		else
			$parsedData["ZeitraumEnde"] = Util::CLDateParser($parsedData["ZeitraumEnde"], "store");

		$AC = $this->getExportCollection($parsedData);

		if(isset($parsedData["ZeitraumEnde"]) AND !isset($parsedData["ZeitraumStart"]))
			 die(Util::getBasicHTMLError("Bitte geben Sie ein Startdatum ein.", "Export-Fehler"));

		if($AC == null) die(Util::getBasicHTMLError("Bitte wählen Sie Elemente zum Exportieren aus.", "Export-Fehler"));

		$Ex = new CSVExport(count($this->headersBelege));
		$Ex->enclosedBy("");
		$Ex->separator("\t");
		$Ex->addHeaderRow($this->headersBelege);

		#$Kappendixes = array();
		
		while($C = $AC->getNextEntry()){
			$Kundennummer = $C->A("kundennummer");
			if($Kundennummer == "-2") $Kundennummer = "";
			
			$sums = $C->getSumOfPosten(true, false);

			#print_r($sums);

			if($C->A("isPayed") == "0")
				$C->changeA("GRLBMpayedDate", "0");
		
			#foreach($sums[5] AS $mwst => $sum) {
			$type = "Rechnung";
			if($C->A("isG"))
				$type = "Gutschrift";

			$data = array(
				$type,
				$C->A("prefix").$C->A("nummer"),
				Util::CLDateParser($C->A("datum")),
				Util::CLDateParser($C->A("lieferDatum")),
				" ",
				Util::CLNumberParserZ($C->A("nettobetrag")),
				Util::CLNumberParserZ($C->A("steuern")),
				Util::CLNumberParserZ($C->A("rabatt")),
				$C->A("rabatt") > 0 ? Util::CLNumberParserZ(($C->A("nettobetrag") / (100 - $C->A("rabatt"))) * 100 - $C->A("nettobetrag")) : Util::CLNumberParserZ(0),
				$C->A("isPayed") ? "bezahlt" : "unbezahlt",
				Util::CLDateParserE($C->A("GRLBMpayedDate")),
				Util::CLNumberParserZ($C->A("isPayed") ? $C->A("bruttobetrag") * ((100 - $C->A("payedWithSkonto")) / 100) : 0),
				"EUR",
				$Kundennummer,
				$C->A("firma") != "" ? $C->A("firma") : $C->A("vorname")." ".$C->A("nachname"),
				$C->A("strasse")." ".$C->A("nr"),
				$C->A("plz")." ".$C->A("ort"),
				$C->A("land"),
				$C->A("UStIdNr"),
				(count($sums[5]) == 1 AND isset($sums[5]["0.00"])) ? "ja" : "nein"
			);

			$Ex->addRow($data);
			#}
		}
		
		if(isset($parsedData["saveToFile"]))
			file_put_contents($parsedData["saveToFile"], $Ex->getExport());
		else
			$Ex->getExport("Belege_".Util::CLDateParser(time()).".csv");
	}
	
	public function getExportDataPositionen($data){
		parse_str($data, $parsedData);
		unset($parsedData["targetPage"]);

		if($parsedData["ZeitraumStart"] == "")
			unset($parsedData["ZeitraumStart"]);
		else
			$parsedData["ZeitraumStart"] = Util::CLDateParser($parsedData["ZeitraumStart"], "store");

		if($parsedData["ZeitraumEnde"] == "")
			unset($parsedData["ZeitraumEnde"]);
		else
			$parsedData["ZeitraumEnde"] = Util::CLDateParser($parsedData["ZeitraumEnde"], "store");

		$AC = $this->getExportCollectionPositionen($parsedData);

		if(isset($parsedData["ZeitraumEnde"]) AND !isset($parsedData["ZeitraumStart"]))
			 die(Util::getBasicHTMLError("Bitte geben Sie ein Startdatum ein.", "Export-Fehler"));

		if($AC == null) die(Util::getBasicHTMLError("Bitte wählen Sie Elemente zum Exportieren aus.", "Export-Fehler"));

		$Ex = new CSVExport(count($this->headersPositionen));
		$Ex->enclosedBy("");
		$Ex->separator("\t");
		$Ex->addHeaderRow($this->headersPositionen);

		#$Kappendixes = array();
		
		while($C = $AC->getNextEntry()){
			$Kundennummer = $C->A("kundennummer");
			if($Kundennummer == "-2") $Kundennummer = "";
			
			#$sums = $C->getSumOfPosten(true, false);

			if($C->A("isPayed") == "0")
				$C->changeA("GRLBMpayedDate", "0");
		
			
			$type = "Rechnung";
			if($C->A("isG"))
				$type = "Gutschrift";
			
			$data = array(
				$type,
				$C->A("prefix").$C->A("nummer"),
				$C->A("name"),
				$C->A("gebinde"),
				Util::CLNumberParserZ($C->A("menge")),
				Util::CLNumberParserZ($C->A("rabattPosition")),
				Util::CLNumberParserZ($C->A("rabattBeleg")),
				Util::CLNumberParserZ($C->A("preis")),
				Util::CLNumberParserZ($C->A("preis") * ($C->A("mwst") / 100)),
				Util::CLNumberParserZ($C->A("mwst"))
			);

			$Ex->addRow($data);
		}
		
		if(isset($parsedData["saveToFile"]))
			file_put_contents($parsedData["saveToFile"], $Ex->getExport());
		else
			$Ex->getExport("Positionen_".Util::CLDateParser(time()).".csv");
	}

	public function getExportCollection($data = null){
		$AC = anyC::get("GRLBM");
		$AC->addJoinV3("Auftrag", "AuftragID", "=", "AuftragID");
		$AC->addJoinV3("Adresse", "t2.AdresseID", "=", "AdresseID");
		$c = 0;
		if($this->usedDate == "GRLBMpayedDate")
			$AC->addAssocV3("isPayed", "=", "1");
		
		if(!isset($data["ZeitraumStart"])){
			foreach($data AS $k => $v){
				if($v == "0") continue;

				$k = str_replace("exportBeleg", "", $k);
				$AC->addAssocV3("GRLBMID", "=", $k, $c == 0 ? "AND" : "OR", "2");

				$c++;
			}
		} else {
			
			foreach($this->belege AS $k => $b){
				if($b == "R" OR $b == "G")
					$AC->addAssocV3("is$b", "=", "1", $k == 0 ? "AND" : "OR", "1");
				else
					$AC->addAssocV3("isWhat", "=", $b, $k == 0 ? "AND" : "OR", "1");
			}

			$AC->addAssocV3($this->usedDate, ">", $data["ZeitraumStart"], "AND", "2");

			if(isset($data["ZeitraumEnde"]))
				$AC->addAssocV3($this->usedDate, "<=", $data["ZeitraumEnde"], "AND", "2");

			$c++;
		}

		if($c == 0) return null;
		return $AC;
	}
	
	public function getExportCollectionPositionen($data = null){
		$AC = anyC::get("GRLBM");
		$AC->addJoinV3("Posten", "GRLBMID", "=", "GRLBMID");
		$AC->addJoinV3("Auftrag", "AuftragID", "=", "AuftragID");
		$AC->addJoinV3("Adresse", "t3.AdresseID", "=", "AdresseID");
		$AC->setFieldsV3(array(
			"prefix",
			"nummer",
			"name",
			"gebinde",
			"bruttopreis",
			"menge",
			"t2.rabatt AS rabattPosition",
			"t1.rabatt AS rabattBeleg",
			"preis",
			"mwst"
		));
		$c = 0;
		if($this->usedDate == "GRLBMpayedDate")
			$AC->addAssocV3("isPayed", "=", "1");
		
		if(!isset($data["ZeitraumStart"])){
			foreach($data AS $k => $v){
				if($v == "0") continue;

				$k = str_replace("exportBeleg", "", $k);
				$AC->addAssocV3("GRLBMID", "=", $k, $c == 0 ? "AND" : "OR", "2");

				$c++;
			}
		} else {
			
			foreach($this->belege AS $k => $b){
				if($b == "R" OR $b == "G")
					$AC->addAssocV3("is$b", "=", "1", $k == 0 ? "AND" : "OR", "1");
				else
					$AC->addAssocV3("isWhat", "=", $b, $k == 0 ? "AND" : "OR", "1");
			}

			$AC->addAssocV3($this->usedDate, ">", $data["ZeitraumStart"], "AND", "2");

			if(isset($data["ZeitraumEnde"]))
				$AC->addAssocV3($this->usedDate, "<=", $data["ZeitraumEnde"], "AND", "2");

			$c++;
		}

		if($c == 0) return null;
		return $AC;
	}

	protected function entryParser(PersistentObject $entry){
		
	}

}
?>