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
class exportArtikelGUI extends exportDefault implements iExport, iGUIHTML2 {

	#private $Kategorien = array();
	
	public function getLabel(){
		return "Artikel";
	}
	
	public function getApps(){
		return array("open3A");
	}

	public function getExportCollection(){
		/*$ac = new anyC();
		$ac->setCollectionOf("Kategorie");
		$ac->addAssocV3("type", "=", "1");

		while($a = $ac->getNextEntry())
			$this->Kategorien[$a->getID()] = $a->A("name");
*/

		$ac = new anyC();
		$ac->setCollectionOf("Artikel");
		#$ac->addJoinV3("Kappendix", "AdresseID", "=", "AdresseID");
		#$ac->addAssocV3("AuftragID", "=", "-1");
		$ac->setFieldsV3(array(
			"name AS Artikelname",
			"artikelnummer AS Artikelnummer",
			"gebinde AS Einheit",
			"preis AS Preis",
			"EK1 AS EK1",
			"EK2 AS EK2",
			"mwst AS MwSt",
			"beschreibung AS Beschreibung",
			"bemerkung AS Bemerkung"));
		
		return $ac;
	}

	protected function entryParser(PersistentObject $entry){
		#$entry->changeA("Anrede", Util::formatAnrede("de_DE", $entry, true));

		#if(isset($this->Kategorien[$entry->A("Kategorie")]))
		#	$entry->changeA("Kategorie", $this->Kategorien[$entry->A("Kategorie")]);
		#else $entry->changeA("Kategorie", "");
		
		$A = $entry->getA();
		$entry->changeA("Preis", Util::CLNumberParserZ($entry->A("Preis")));
		$entry->changeA("EK1", Util::CLNumberParserZ($entry->A("EK1")));
		$entry->changeA("EK2", Util::CLNumberParserZ($entry->A("EK2")));
		$entry->changeA("MwSt", Util::CLNumberParserZ($entry->A("MwSt")));
		
		#unset($A->AdresseID);
		unset($A->ArtikelID);
	}
}
?>
