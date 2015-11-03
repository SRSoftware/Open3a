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
class Kunden extends anyC {
	function __construct() {
		$this->setCollectionOf("Adresse");
		$this->addAssocV3("type","=","kundenAdresse");
	}
	
	function checkIfMyTableExists(){
		$this->collectionOf = "Kappendix";
		return parent::checkIfMyTableExists();
	}
	
	/*function checkIfMyTableExists(){
		$this->collectionOf = "Kappendix";
		parent::checkIfMyTableExists();
	}*/
	protected function createKundeFromAdresse($id){
		$A = new Adresse($id);
		$AA = $A->getA();
		$AA->type = "kundenAdresse";
		
		$A2 = new Adresse(-1);
		$A2->setA($AA);
		$AID = $A2->newMe();
		
		$mKApp = new mKappendixGUI();
		$KID = $mKApp->getNextKundenNummer();
		
		$KApp = new Kappendix(-1);
		$KAppA = $KApp->newAttributes();
		$KAppA->AdresseID = $AID;
		$KAppA->kundennummer = $KID;
		
		$KApp->setA($KAppA);
		$KApp->newMe();
	}
	
	public function createKundeToAdresse($id, $noKdNr = false, $returnNewObject = false){
		$mKApp = new mKappendixGUI();
		$KID = $mKApp->getNextKundenNummer();
		
		$KApp = new Kappendix(-1);
		$KAppA = $KApp->newAttributes();
		$KAppA->AdresseID = $id;
		
		if(!$noKdNr)
			$KAppA->kundennummer = $KID;
		
		$grlbms = Auftrag::getBelegArten();#array("R","G","A","L");
		
		for($i=0;$i<count($grlbms);$i++){
			
			$v = $grlbms[$i]."TextbausteinOben";
			
			if(!isset($KAppA->$v))
				continue;

			$KAppA->$v = 0;
			
			
			$v = $grlbms[$i]."TextbausteinUnten";
			$KAppA->$v = 0;
			
			
			$v = $grlbms[$i]."Zahlungsbedingungen";
			$KAppA->$v = 0;
		}
		
		$KApp->setA($KAppA);
		if($returnNewObject) return $KApp;
		return $KApp->newMe();
	}
}
?>
