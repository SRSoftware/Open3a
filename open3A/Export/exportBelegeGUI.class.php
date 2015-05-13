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
class exportBelegeGUI extends exportDefault implements iExport {
	protected $usedDate = "datum";
	protected $belege = array("R", "G");
	protected $numberDisplayed = 20;
	protected static $UserID = 0;
	
	protected $headers = array(
			"Kundennummer",
			"Anrede",
			"Firma",
			"Vorname",
			"Nachname",
			"Belegnummer",
			"Belegdatum",
			"Lieferdatum",
			"Mehrwertsteuersatz",
			"Bruttobetrag",
			"Zahlungsart",
			"Bemerkung",
			"Zahlungsdatum",
			"EK",
			"Land",
			"Referenz"
		);
	
	public function getLabel(){
		return "Belege nach Belegdatum";
	}
	
	public function getApps(){
		return array("open3A");
	}

	public static $alreadyExported = array();
	public static $className;
	
	protected function getExportPreviewCollection(){
		$AC = anyC::get("GRLBM");
		foreach($this->belege AS $k => $b){
			if($b == "R" OR $b == "G")
				$AC->addAssocV3("is$b", "=", "1", $k == 0 ? "AND" : "OR");
			else
				$AC->addAssocV3("isWhat", "=", $b, $k == 0 ? "AND" : "OR");
		}
		
		if($this->usedDate == "datum")
			$AC->addOrderV3("datum", "DESC");
		else {
			$AC->addAssocV3("isPayed", "=", "1", "AND", "2");
			$AC->addOrderV3("GRLBMpayedDate", "DESC");
		}

		return $AC;
	}
	
	public function getHTML($id, $page = 0){
		self::$className = get_class($this);
		$AC = $this->getExportPreviewCollection();

		$AC->loadMultiPageMode($id, $page, mUserdata::getUDValueS("entriesPerPage".get_class($this), $this->numberDisplayed));

		$gui = new HTMLGUIX($AC, get_class($this));
		$gui->attributes(array("nummer", $this->usedDate, "wert", "GRLBMID"));

		$gui->colWidth("nummer", 90);
		$gui->colWidth($this->usedDate, 90);
		$gui->colWidth("GRLBMID", 20);

		$gui->colStyle("wert", "text-align:right;");

		$gui->displayMode("BrowserLeft");

		$gui->name("Belege");
		$gui->caption("Oder wählen Sie die Belege:");
		
		$gui->parser("GRLBMID", get_class($this)."::IDParser");
		$gui->parser($this->usedDate, "exportBelegeGUI::dateParser");
		$gui->parser("wert", "exportBelegeGUI::wertParser");
		$gui->parser("nummer", "exportBelegeGUI::nummerParser");

		$gui->options(false, false, false, false);

		$ST = new HTMLSideTable("right");

		$B = $ST->addButton("jetzt\nexportieren", "export");
		$B->windowRme(str_replace("GUI", "", get_class($this)), "-1", "getExportData", array("joinFormFields('exportForm')"));

		$B = $ST->addButton("Auswahl\ninvertieren", "bestaetigung");
		$B->onclick("for(i = 0;i < $('exportForm').elements.length;i++) { $('exportForm').elements[i].checked = !$('exportForm').elements[i].checked; }");

		$TT = new HTMLTable(2, "Bitte geben Sie einen Zeitraum ein:");
		$TT->setColWidth(1, 120);
		
		$IS = new HTMLInput("ZeitraumStart", "date");
		$IS->hasFocusEvent(true);

		$IE = new HTMLInput("ZeitraumEnde", "date");
		$IE->hasFocusEvent(true);

		$TT->addLV("Von:", $IS);
		$TT->addLV("Bis:", $IE);

		return $ST."<form id=\"exportForm\">".$TT."<div style=\"margin-top:30px;\">".$gui->getBrowserHTML($id)."</div></form>";
	}

	public static function nummerParser($w, $E){
		return $E->A("prefix").$w;
	}

	public static function wertParser($w, $E){
		if(!isset(self::$alreadyExported[$E->getID()]))
			self::$alreadyExported[$E->getID()] = self::$UserID == -1 ? mUserdata::getGlobalSettingValue(self::$className.$E->getID(), "") : mUserdata::getUDValueS(self::$className.$E->getID(), "");
		
		$wert = self::$alreadyExported[$E->getID()];
		
		return $wert != "" ? Util::CLDateParser($wert) : "";
	}

	public static function dateParser($w, $E){
		return $w != "" ? Util::CLDateParser($w) : "";
	}

	public static function IDParser($w, $E){
		if(!isset(self::$alreadyExported[$E->getID()]))
			self::$alreadyExported[$E->getID()] = self::$UserID == -1 ? mUserdata::getGlobalSettingValue(self::$className.$E->getID(), "") : mUserdata::getUDValueS(self::$className.$E->getID(), "");
		
		$wert = self::$alreadyExported[$E->getID()];
		
		$I = new HTMLInput("exportBeleg$w", "checkbox", $wert != "" ? "0" : "1");
		return $I;
	}

	public function factory(){
		return new CSVExport(count($this->headers));
	}
	
	public function getExportData($data){
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

		$Ex = $this->factory();

		if($this->headers != null)
			$Ex->addHeaderRow($this->headers);

		#$Kappendixes = array();
		
		while($C = $AC->getNextEntry()){

			mUserdata::setUserdataS(get_class($this).$C->getID(), time(), "belegExport", self::$UserID);

			$Adresse = new Adresse(-1);
			$Adresse->setA($C->getA());

			$Anrede = Util::formatAnrede("de_DE", $Adresse, true);
			if($C->A("anrede") == "3") $Anrede = "Firma";

			$Kundennummer = $C->A("kundennummer");
			if($Kundennummer == "-2") $Kundennummer = "";
			
			$sums = $C->getSumOfPosten(true, false);

			#print_r($sums);

			if($C->A("isPayed") == "0")
				$C->changeA("GRLBMpayedDate", "0");
		
			$i = 0;
			foreach($sums[5] AS $mwst => $sum) {
				/*$data = array(
					$Kundennummer,
					$Anrede,
					$C->A("firma"),
					$C->A("vorname"),
					$C->A("nachname"),
					$C->A("prefix").$C->A("nummer"),
					Util::CLDateParser($C->A("datum")),
					Util::CLDateParser($C->A("lieferDatum")),
					Util::CLFormatNumber($mwst * 1, "default", true, true, false),
					Util::CLFormatNumber($sum * 1, "default", true, true, false),
					GRLBM::getPaymentVia($C->A("GRLBMpayedVia")),
					str_replace("\n", " ", $C->A("GRLBMpayedBemerkung")),
					Util::CLDateParserE($C->A("GRLBMpayedDate")),
					Util::CLFormatNumber($i == 0 ? $sums[4] * 1 : 0, "default", true, true, false),
					$C->A("land")
				);*/
				
				$data = $this->formatData($i, $C, $Kundennummer, $Anrede, $mwst, $sums, $sum);
				
				$i++;
				$Ex->addRow($data);
			}
		}
		
		if(isset($parsedData["saveToFile"]))
			file_put_contents($parsedData["saveToFile"], $Ex->getExport());
		else
			$Ex->getExport("Belege_".Util::CLDateParser(time()).".csv");
	}
	
	public function formatData($i, $C, $Kundennummer, $Anrede, $mwst, $sums, $sum){
		return array(
			$Kundennummer,
			$Anrede,
			$C->A("firma"),
			$C->A("vorname"),
			$C->A("nachname"),
			$C->A("prefix").$C->A("nummer"),
			Util::CLDateParser($C->A("datum")),
			Util::CLDateParser($C->A("lieferDatum")),
			Util::CLFormatNumber($mwst * 1, "default", true, true, false),
			Util::CLFormatNumber($sum * 1, "default", true, true, false),
			GRLBM::getPaymentVia($C->A("GRLBMpayedVia")),
			str_replace("\n", " ", $C->A("GRLBMpayedBemerkung")),
			Util::CLDateParserE($C->A("GRLBMpayedDate")),
			Util::CLFormatNumber($i == 0 ? $sums[4] * 1 : 0, "default", true, true, false),
			$C->A("land"),
			$C->A("GRLBMReferenznummer")
		);
	}

	public function getExportCollection($data = null){
		$AC = new anyC();
		$AC->setCollectionOf("GRLBM");
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
			#$AC->addAssocV3("isR", "=", "1", "AND", "1");
			#$AC->addAssocV3("isG", "=", "1", "OR", "1");

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