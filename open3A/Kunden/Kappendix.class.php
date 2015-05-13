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
class Kappendix extends PersistentObject {
	public function __construct($id){
		parent::__construct($id);

		$this->customize();
	}
	
	public function checkIBAN($IBAN){
		$I = new IBAN($IBAN);
		return $I->Verify();
	}

	public function newAttributes(){
		$A = parent::newAttributes();
		$A->KappendixSameKontoinhaber = 1;
		$A->KappendixErloeskonto = "0";
		
		if($this->customizer != null)
			$this->customizer->customizeNewAttributes($this->getClearClass(get_class($this)), $A);

		return $A;
	}

	private function updateErloeskonto(){
		$EK = mUserdata::getGlobalSettingValue("DVKappendixErloeskonto", "8400");
		if(mUserdata::getGlobalSettingValue("DVKappendixKundenKonto", "0"))
			$EK += $this->A("kundennummer");
		
		$this->changeA("KappendixErloeskonto", $EK);
	}
	
	public function saveMe($checkUserData = true, $output = false){
		// <editor-fold defaultstate="collapsed" desc="Aspect:jP">
		try {
			$MArgs = func_get_args();
			return Aspect::joinPoint("around", $this, __METHOD__, $MArgs);
		} catch (AOPNoAdviceException $e) {}
		Aspect::joinPoint("before", $this, __METHOD__, $MArgs);
		// </editor-fold>

		if($this->A("KappendixErloeskonto") == "0")
			$this->updateErloeskonto();
				
		// <editor-fold defaultstate="collapsed" desc="Aspect:jP">
		return Aspect::joinPoint("after", $this, __METHOD__, parent::saveMe($checkUserData, $output));
		// </editor-fold>
	}
	
	public function newMe($checkUserData = true, $output = false) {
		if($this->A("KappendixErloeskonto") == "0")
			$this->updateErloeskonto();
		
		return parent::newMe($checkUserData, $output);
	}

	// <editor-fold defaultstate="collapsed" desc="getAdresseIDToKundennummer">
	public static function getAdresseIDToKundennummer($kundennummer){
		$ac = new anyC();
		$ac->setCollectionOf("Kappendix");
		$ac->addAssocV3("kundennummer", "=", $kundennummer);
		$ac->addJoinV3("Adresse", "AdresseID", "=", "AdresseID");

		$E = $ac->getNextEntry();
		if($E == null) return null;

		return $E->A("AdresseID");
	}
	// </editor-fold>
	
	// <editor-fold defaultstate="collapsed" desc="getKappendixToAdresse">
	public static function getKappendixToAdresse($AdresseID){
		$ac = anyC::get("Kappendix");
		$ac->addAssocV3("AdresseID", "=", $AdresseID);

		return $ac->getNextEntry();
	}
	// </editor-fold>

	// <editor-fold defaultstate="collapsed" desc="getKappendixToKundennummer">
	public static function getKappendixToKundennummer($kundennummer){
		$ac = new anyC();
		$ac->setCollectionOf("Kappendix");
		$ac->addAssocV3("kundennummer", "=", $kundennummer);

		return $ac->getNextEntry();
	}
	// </editor-fold>

	// <editor-fold defaultstate="collapsed" desc="getKappendixIDToAdresse">
	public static function getKappendixIDToAdresse($AdresseID, $returnKundennummer = false){
		$K = self::getKappendixToAdresse($AdresseID);
		
		if($K != null) {
			if($returnKundennummer) return $K->A("kundennummer");
			return $K->getID();
		}
		else return null;
	}
	// </editor-fold>
}
?>
