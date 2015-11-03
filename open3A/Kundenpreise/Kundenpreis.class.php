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
class Kundenpreis extends PersistentObject {
	public $skipVariantTest = false;
	
	function __construct($ID, $parsers = true){
		parent::__construct($ID);
		
		if($parsers)
			$this->setParser("kundenPreis","Util::CLNumberParserZ");
	}
	
	protected function saveMultiEditField($field,$value){
		if($this->A == null) $this->loadMe();
		$this->A->$field = $value;
		$this->saveMe();
	}
	
	public function makeKundenpreis($ArtikelID, $VarianteArtikelID = 0){
		$kundennummer = Kappendix::getKappendixIDToAdresse($this->getID(), true);
		
		#$bps = $this->getMyBPSData();
		#echo $VarianteArtikelID;
		if(!$this->skipVariantTest AND Session::isPluginLoaded("mVariante") AND Variante::has($ArtikelID) AND !defined("PHYNX_VIA_INTERFACE"))
			Red::redirect(OnEvent::popup("Variante auswählen", "mVariante", "-1", "variantSelectionPopup", array("'$ArtikelID'", "'addToKundenpreis'", "'".$this->getID()."'")));
		
		
		$Artikel = new Artikel($ArtikelID, false);
		#$Artikel->setParser("preis","Util::nothingParser");
		#$Artikel->setParser("EK1","Util::nothingParser");
		#$Artikel->setParser("EK2","Util::nothingParser");
		
		$Ks = anyC::get("Kundenpreis");
		$Ks->addAssocV3("ArtikelID","=",$ArtikelID);
		$Ks->addAssocV3("kundennummer", "=", $kundennummer);
		$Ks->addAssocV3("KundenpreisVarianteArtikelID", "=", $VarianteArtikelID);
		$Ks = $Ks->getNextEntry();
		if($Ks != null)
			return -1;
		

		$K = new Kundenpreis(-1, false);
		$KA = $K->newAttributes();
		$KA->ArtikelID = $ArtikelID;
		$KA->kundennummer = $kundennummer;
		$KA->kundenPreis = $Artikel->A("preis");
		$KA->KundenpreisVarianteArtikelID = $VarianteArtikelID;
		
		if($VarianteArtikelID != 0){
			$V = new VarianteArtikel($VarianteArtikelID);
		}
		
		$K->setA($KA);
		return $K->newMe();
	}
}
?>
