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
		$fields = array(
			"artikelnummer AS Artikelnummer",
			"name AS Artikelname",
			"gebinde AS Einheit",
			"preis",
			"EK1 AS EK1",
			"EK2 AS EK2",
			"mwst AS MwSt",
			"beschreibung AS Beschreibung",
			"bemerkung AS Bemerkung");
		
		if(Session::isPluginLoaded("mLieferant")){
			$fields[] = "preisModus";
			$fields[] = "aufschlagListenpreis";
			$fields[] = "aufschlagGesamt";
			$fields[] = "LieferantFirma AS Lieferant";
			$fields[] = "LieferantID";
			$fields[] = "LieferantPreisArtikelnummer AS Lieferantenartikelnummer";
			$fields[] = "LieferantPreisArtikelname AS Lieferantenartikelname";
			$fields[] = "LieferantPreisListenPreis AS Listenpreis";
			$fields[] = "LieferantPreisRabattgruppe AS Rabattgruppe";
			
			$ac->addJoinV3("LieferantPreis", "ArtikelID", "=", "LieferantPreisArtikelID");
			$ac->addJoinV3("LieferantPreis", "LieferantPreisVarianteArtikelID", "=", "0");
			$ac->addJoinV3("Lieferant", "t2.LieferantPreisLieferantID", "=", "LieferantID");
			$ac->addJoinV3("Rabattgruppe", "t2.LieferantPreisRabattgruppe", "=", "RabattgruppeNummer");
			#$ac->addJoinV3("Rabattgruppe", "RabattgruppeLieferantID", "=", "t2.LieferantPreisLieferantID");
			
		}
		
		
		$ac->setFieldsV3($fields);
		
		$KID = mUserdata::getUDValueS("exportArtikelKategorieID", "0");
		if($KID != 0)
			$ac->addAssocV3 ("KategorieID", "=", $KID);
		
		$this->hidden = array(
			"preisModus",
			"aufschlagListenpreis",
			"aufschlagGesamt",
			"LieferantID"
		);
		
		return $ac;
	}

	public function getHTML($id) {
		$ac = anyC::get("Kategorie", "type", "2");
		$ac->addOrderV3("name");
		$options = array();
		$options["0"] = "Alle";
		while($a = $ac->n())
			$options[$a->getID()] = $a->A("name");
		
		
		$F = new HTMLForm("lexEx", array("KategorieID"), "Export-Einstellungen");
		$F->setLabel("KategorieID", "Kategorie");
		$F->setType("KategorieID", "select", mUserdata::getUDValueS("exportArtikelKategorieID", "0"), $options);
		$F->getTable()->addColStyle(1, "width:120px;");
		$F->addJSEvent("KategorieID", "onChange", "contentManager.rmePCR('exportArtikel','', 'saveKID', this.value)");

		return parent::getHTML($id).$F;
	}
	
	public static function saveKID($KID){
		mUserdata::setUserdataS("exportArtikelKategorieID", $KID);

		Red::messageSaved();
	}
	
	protected function entryParser(PersistentObject $entry){
		#$entry->changeA("Anrede", Util::formatAnrede("de_DE", $entry, true));

		#if(isset($this->Kategorien[$entry->A("Kategorie")]))
		#	$entry->changeA("Kategorie", $this->Kategorien[$entry->A("Kategorie")]);
		#else $entry->changeA("Kategorie", "");
		$entry->resetParsers();
		$A = $entry->getA();
		$entry->changeA("preis", Util::CLNumberParserZ($entry->getGesamtNettoVK(false, Session::isPluginLoaded("mLieferant") ? $entry->A("LieferantID") : null)));
		$entry->changeA("EK1", Util::CLNumberParserZ($entry->getGesamtEK1(Session::isPluginLoaded("mLieferant") ? $entry->A("LieferantID") : null)));
		$entry->changeA("EK2", Util::CLNumberParserZ($entry->A("EK2")));
		$entry->changeA("MwSt", Util::CLNumberParserZ($entry->A("MwSt")));
		
		if(Session::isPluginLoaded("mLieferant"))
			$entry->changeA("Listenpreis", Util::CLNumberParserZ($entry->A("Listenpreis")));
		
		#unset($A->AdresseID);
		unset($A->ArtikelID);
	}
}
?>
