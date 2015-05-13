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
class Auftraege extends anyC implements iPluginSpecificRestrictions {
	function __construct() {
		$this->setCollectionOf("Auftrag");
		#$this->collectionOf = "Auftrag";
		#$this->addJoinV3("GRLBM","isR","=","1");
		#$this->addJoinV3("Adresse","AdresseID","=","AdresseID");
		#$this->addJoinCondition("GRLBM","isR","1");
	}
	
	function getPluginSpecificRestrictions(){
		$a = array("pluginSpecificCanSetPayed" => "kann Rechnungen als bezahlt/storniert markieren","pluginSpecificCanOnlyEditOwn" => "kann nur eigene Aufträge bearbeiten", "pluginSpecificCanOnlySeeKalk" => "kann nur Kalkulation benutzen");
		$Us = new Users();
		$Us->addAssocV3("isAdmin","=","0");
		while(($t = $Us->getNextEntry()))
			$a["pluginSpecificCanSeeAuftraegeFrom".$t->getID()] = "sieht Aufträge von ".$t->getA()->username;
		return $a;
	}

	public function createEmpty($addBeleg = null, $AdresseID = null){
		$A = new Auftrag(-1);
		$id = $A->newWithDefaultValues($AdresseID);
		
		if($addBeleg){
			$A = new Auftrag($id);
			$A->createGRLBM($addBeleg);
		}
		
		return $id;
	}

	public static function getStatus(){
		$status = array("open" => "offen", "confirmed" => "bestätigt", "delivered" => "geliefert", "billed" => "berechnet", "declined" => "abgelehnt");
		
		return Aspect::joinPoint("after", __CLASS__, __METHOD__, $status);
	}

	public static function getStatusIcons(){
		$status = array("open" => "book_alt2", "confirmed" => "check", "delivered" => "box", "billed" => "book_alt", "declined" => "x");
		
		return Aspect::joinPoint("after", __CLASS__, __METHOD__, $status);
	}
	
	public static function doSomethingElse(){
		if(FileStorage::getFilesDir() != Util::getRootPath()."specifics/")
			addClassPath(FileStorage::getFilesDir());
	
		/*if(!isset($_SESSION["additionalTypes"])) $_SESSION["additionalTypes"] = array();
		if(!isset($_SESSION["additionalTypesWs"])) $_SESSION["additionalTypesWs"] = array();

		$_SESSION["additionalTypes"]["Kalk"] = "Kalkulation";
		$_SESSION["additionalTypesWs"]["Kalk"] = "Kalkulations";*/
	}
	
	public static function getHistorieData($ownerClass, $ownerClassID, HistorieTable $Tab){
		$K = Kappendix::getKappendixToAdresse($ownerClassID, true);
		if($K == null)
			return $Tab;
		
		$AC = anyC::get("GRLBM");
		$AC->addJoinV3("Auftrag", "AuftragID", "=", "AuftragID");
		$AC->addAssocV3("kundennummer", "=", $K->A("kundennummer"));
		$AC->addAssocV3("isM", "=", "0");
		$AC->setLimitV3("10");
		
		while($D = $AC->getNextEntry()){
			$B = new Button("Original", "pdf", "icon");
			$B->windowRme("Auftrag", $D->A("AuftragID"), "getGRLBMPDF", array("'false'", "''", $D->getID()), "_Brief;templateType:PDF");

			$Tab->addHistorie(Stammdaten::getLongType($D->getMyPrefix())." ".$D->A("prefix").$D->A("nummer"), Stammdaten::getIconType($D->getMyPrefix()), $D->A("datum"), "Bruttobetrag: ".Util::CLFormatCurrency($D->A("bruttobetrag") * 1, true), $B, "", $D->A("GRLBMCreatedByUserID"));
			
			if($D->A("isPayed") == "1" AND $D->A("GRLBMpayedDate") > 0)
				$Tab->addHistorie("Zahlungseingang", "./lightCRM/Historie/Zahlungseingang.png", $D->A("GRLBMpayedDate"), Stammdaten::getLongType($D->getMyPrefix())." ".$D->A("prefix").$D->A("nummer"), $B, "nach ".floor(($D->A("GRLBMpayedDate") - $D->A("datum")) / (3600 * 24))." Tagen");

			$ACM = anyC::get("GRLBM", "AuftragID", $D->getID());
			$ACM->addAssocV3("isM", "=", "1");
			while($M = $ACM->getNextEntry()){
				$BM = new Button("", "pdf", "icon");
				$BM->windowRme("Auftrag", $D->A("AuftragID"), "getGRLBMPDF", array("'false'", "''", $M->getID()), "_Brief;templateType:PDF");

				$Tab->addHistorie($M->A("nummer").". Mahnung", Stammdaten::getIconType("M"), $M->A("datum"), "für ".Stammdaten::getLongType($D->getMyPrefix())." ".$D->A("prefix").$D->A("nummer"), $BM);

			}
		}
		return true;
	}
	
	public static function getBerichteDir(){
		return dirname(__FILE__);
	}
}
?>
